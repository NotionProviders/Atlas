<?php

namespace App\Services\Migration;

use App\Models\MigrationRun;
use Illuminate\Support\Facades\File;

/**
 * Builds the "Notion import punch list" for a finished run — a PHP port of the
 * Python tool's `report` command. Scans the exported markdown for the things
 * that bite you after a Notion import (empty pages, links that will 404,
 * unconverted HTML, over-long titles) and rolls in per-item failures.
 *
 * Returns a structured array suitable for JSON storage on the run and for
 * rendering in the console.
 */
class MigrationReport
{
    public function __construct(private readonly MigrationStorage $storage) {}

    /** Category => [severity, human description]. */
    private const LABELS = [
        'empty_page' => ['warn', 'Pages with no body — import as blank Notion pages'],
        'title_too_long' => ['warn', 'Titles over 100 chars — truncated/ugly in Notion'],
        'source_link' => ['danger', 'Links to Loop/SharePoint — will 404 after import'],
        'raw_html' => ['danger', 'Unconverted HTML tags — appear as literal text'],
        'failed_page' => ['danger', 'Pages that failed to scrape — content missing'],
        'thin_content' => ['muted', 'Pages with very little content — may be intentional'],
    ];

    /**
     * @return array{
     *   scanned: int,
     *   total_issues: int,
     *   categories: array<string, array{severity: string, description: string, items: array<int, string>}>,
     *   reattach_count: int
     * }
     */
    public function build(MigrationRun $run): array
    {
        $exportDir = $this->storage->exportDir($run);
        $files = $this->storage->markdownFiles($run);

        $buckets = array_fill_keys(array_keys(self::LABELS), []);

        foreach ($files as $path) {
            $rel = ltrim(str_replace($exportDir, '', $path), '/\\');
            $text = (string) File::get($path);
            $stem = pathinfo($path, PATHINFO_FILENAME);

            $contentLines = array_values(array_filter(
                preg_split('/\r?\n/', $text),
                fn ($l) => trim($l) !== '' && ! str_starts_with($l, '#')
            ));

            if ($contentLines === []) {
                $buckets['empty_page'][] = $rel;
            }

            if (mb_strlen($stem) > 100) {
                $buckets['title_too_long'][] = "{$rel}  (".mb_strlen($stem).' chars)';
            }

            // Loop/SharePoint links, ignoring our own re-attach annotation lines.
            $nonAnnotation = implode("\n", array_filter(
                preg_split('/\r?\n/', $text),
                fn ($l) => ! str_starts_with($l, '> ⚠') && ! preg_match('/^> _https?:\/\//', $l)
            ));
            if (preg_match('/https?:\/\/(loop\.cloud\.microsoft|[^\/]*\.sharepoint\.com)/', $nonAnnotation)) {
                $buckets['source_link'][] = $rel;
            }

            if (preg_match('/<[a-z][a-z0-9]*[\s>]/i', $text)) {
                $buckets['raw_html'][] = $rel;
            }

            $count = count($contentLines);
            if ($count >= 1 && $count <= 2) {
                $snippet = mb_substr(trim($contentLines[0]), 0, 60);
                $buckets['thin_content'][] = "{$rel}  → \"{$snippet}\"";
            }
        }

        foreach ($run->items()->where('status', 'failed')->get() as $item) {
            $buckets['failed_page'][] = "{$item->title}  (".mb_substr((string) $item->error, 0, 60).')';
        }

        $categories = [];
        foreach (self::LABELS as $key => [$severity, $description]) {
            $categories[$key] = [
                'severity' => $severity,
                'description' => $description,
                'items' => array_values($buckets[$key]),
            ];
        }

        return [
            'scanned' => count($files),
            'total_issues' => array_sum(array_map(fn ($b) => count($b), $buckets)),
            'categories' => $categories,
            'reattach_count' => count($this->storage->readArtifacts($run)),
        ];
    }

    /**
     * Write REATTACH.md into the export tree from the accumulated artifact log,
     * mirroring the Python `_write_reattach`.
     */
    public function writeReattachManifest(MigrationRun $run): int
    {
        $artifacts = $this->storage->readArtifacts($run);
        if ($artifacts === []) {
            return 0;
        }

        $lines = [
            '# Attachments & Links to Re-attach in Notion',
            '',
            'These items were linked in the source tool but cannot be migrated automatically.',
            'After importing to Notion, manually attach or link each one to its page.',
            '',
            '| # | Type | Label | Page | Original URL |',
            '|---|---|---|---|---|',
        ];
        foreach ($artifacts as $i => $a) {
            $url = $a['url'] ?? '';
            $urlShort = mb_strlen($url) > 80 ? mb_substr($url, 0, 80).'…' : $url;
            $lines[] = sprintf('| %d | %s | %s | %s | %s |',
                $i + 1, $a['kind'] ?? '', $a['label'] ?? '', $a['page'] ?? '', $urlShort);
        }
        $lines[] = '';

        File::ensureDirectoryExists(dirname($this->storage->reattachPath($run)));
        File::put($this->storage->reattachPath($run), implode("\n", $lines));

        return count($artifacts);
    }
}
