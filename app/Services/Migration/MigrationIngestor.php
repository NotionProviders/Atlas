<?php

namespace App\Services\Migration;

use App\Models\ExtensionToken;
use App\Models\MigrationItem;
use App\Models\MigrationRun;
use Illuminate\Support\Str;

/**
 * Orchestrates a run end to end, source-agnostically:
 *
 *   create()      the extension announces a workspace it's about to scrape
 *   ingest()      batches of already-extracted pages stream in and become files
 *   complete()    finalise counts, build the punch list + REATTACH.md + zip
 *
 * The extension does the source-specific DOM work and hands us a normalized
 * node shape; everything here is the same for Loop, Confluence, or anything
 * else added later.
 */
class MigrationIngestor
{
    public function __construct(
        private readonly MigrationStorage $storage,
        private readonly MigrationReport $report,
        private readonly ExportArchive $archive,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function create(string $source, string $name, ?string $sourceRef, array $options, ?ExtensionToken $token): MigrationRun
    {
        return MigrationRun::create([
            'slug' => $this->uniqueSlug($name),
            'source' => $source,
            'name' => $name,
            'source_ref' => $sourceRef,
            'status' => 'running',
            'options' => $options ?: null,
            'token_id' => $token?->id,
            'started_at' => now(),
        ]);
    }

    public function setTotal(MigrationRun $run, int $total): void
    {
        $run->forceFill(['total' => max($total, 0)])->save();
    }

    /**
     * Ingest a batch of normalized nodes. Each node is a page or attachment the
     * extension already extracted from the source DOM.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array{ok: int, failed: int, empty: int, attachments: int}
     */
    public function ingest(MigrationRun $run, array $nodes): array
    {
        $tally = ['ok' => 0, 'failed' => 0, 'empty' => 0, 'attachments' => 0];

        foreach ($nodes as $node) {
            $title = MarkdownCleaner::safeFilename((string) ($node['title'] ?? 'Untitled'));
            $segments = array_map(
                fn ($s) => MarkdownCleaner::safeFilename((string) $s),
                array_values((array) ($node['path'] ?? []))
            );
            $level = (int) ($node['level'] ?? count($segments));

            if (! empty($node['isAttachment'])) {
                $this->recordAttachment($run, $node, $title, $segments, $level);
                $tally['attachments']++;

                continue;
            }

            if (! empty($node['error'])) {
                $this->recordItem($run, $node, $title, $this->fileRelPath($segments, $title, false), $level, 'failed', (string) $node['error'], 0);
                $tally['failed']++;

                continue;
            }

            [$content, $bytes, $isEmpty] = $this->renderPage($run, $node, $title);
            $relPath = $this->fileRelPath($segments, $title, (bool) ($node['hasChildren'] ?? false));
            $written = $this->storage->writeMarkdown($run, $relPath, $content);

            $status = $isEmpty ? 'empty' : 'ok';
            $this->recordItem($run, $node, $title, $relPath, $level, $status, null, $written);
            $tally[$isEmpty ? 'empty' : 'ok']++;
        }

        // Persist rolled-up counts (empty pages still count as succeeded — they're written).
        $run->increment('succeeded', $tally['ok'] + $tally['empty']);
        $run->increment('failed', $tally['failed']);
        $run->increment('attachments', $tally['attachments']);

        return $tally;
    }

    public function complete(MigrationRun $run, string $status = 'completed'): MigrationRun
    {
        $this->report->writeReattachManifest($run);
        $report = $this->report->build($run);

        try {
            $this->archive->build($run);
        } catch (\Throwable) {
            // A missing zip shouldn't fail the whole run; the console handles absence.
        }

        $run->forceFill([
            'status' => $status,
            'report' => $report,
            'finished_at' => now(),
        ])->save();

        return $run->refresh();
    }

    /**
     * Assemble a page's markdown body (heading + cleaned body + comments) and
     * return [content, byteLength, isEmpty].
     *
     * @param  array<string, mixed>  $node
     * @return array{0: string, 1: int, 2: bool}
     */
    private function renderPage(MigrationRun $run, array $node, string $title): array
    {
        $rawBody = (string) ($node['markdown'] ?? '');

        [$body, $artifacts] = MarkdownCleaner::fixSourceLinks($rawBody, $title);
        $body = MarkdownCleaner::clean($body, $title);
        $this->storage->appendArtifacts($run, $artifacts);

        $fullMd = "# {$title}\n\n{$body}";
        $isEmpty = MarkdownCleaner::isEmptyPage($fullMd);

        $parts = ["# {$title}"];
        if ($body !== '' && ! $isEmpty) {
            $parts[] = $body;
        }

        $comments = (array) ($node['comments'] ?? []);
        if ($comments !== []) {
            $parts[] = '---';
            foreach ($comments as $c) {
                $author = trim((string) ($c['author'] ?? ''));
                $ts = trim((string) ($c['timestamp'] ?? ''));
                $text = trim((string) ($c['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $prefix = $author !== '' ? "**COMMENT [{$author} · {$ts}]:** " : '**COMMENT:** ';
                $parts[] = "> {$prefix}{$text}";
            }
        }

        $content = implode("\n\n", array_filter($parts, fn ($p) => trim($p) !== ''));

        return [$content, strlen($content), $isEmpty];
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function fileRelPath(array $segments, string $title, bool $hasChildren): string
    {
        $dir = implode('/', $segments);
        if ($hasChildren) {
            // Notion-style: a page with children is a folder + a same-named .md.
            $dir = ($dir !== '' ? $dir.'/' : '').$title;

            return $dir.'/'.$title.'.md';
        }

        return ($dir !== '' ? $dir.'/' : '').$title.'.md';
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<int, string>  $segments
     */
    private function recordAttachment(MigrationRun $run, array $node, string $title, array $segments, int $level): void
    {
        $url = (string) ($node['attachmentUrl'] ?? '');
        $filename = (string) ($node['attachmentFilename'] ?? $title);

        // Attachments aren't inlined into markdown (Notion can't ingest binaries
        // from a markdown import); log them for the re-attach manifest instead.
        if ($url !== '') {
            $this->storage->appendArtifacts($run, [[
                'page' => $title,
                'label' => $filename,
                'kind' => 'Attachment',
                'url' => $url,
            ]]);
        }

        $this->recordItem($run, $node, $filename, implode('/', $segments), $level, 'skipped', null, 0, 'attachment');
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function recordItem(MigrationRun $run, array $node, string $title, string $path, int $level, string $status, ?string $error, int $bytes, string $kind = 'page'): void
    {
        MigrationItem::create([
            'migration_run_id' => $run->id,
            'external_id' => isset($node['externalId']) ? (string) $node['externalId'] : null,
            'title' => Str::limit($title, 250, ''),
            'path' => Str::limit($path, 1000, ''),
            'level' => max($level, 0),
            'kind' => $kind,
            'status' => $status,
            'error' => $error,
            'bytes' => $bytes,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $n = 2;
        while (MigrationRun::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
