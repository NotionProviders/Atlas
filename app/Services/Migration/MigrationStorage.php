<?php

namespace App\Services\Migration;

use App\Models\MigrationRun;
use Illuminate\Support\Facades\File;

/**
 * Owns everything on disk for a run: the exported markdown tree, the running
 * artifact log, and the downloadable zip. Everything lives under
 * storage/app/{storage_root}/{slug}/ so it's outside the repo and easy to purge.
 */
class MigrationStorage
{
    public function baseDir(MigrationRun $run): string
    {
        $root = (string) config('migration.storage_root', 'migrations');

        return storage_path("app/{$root}/{$run->slug}");
    }

    public function exportDir(MigrationRun $run): string
    {
        return $this->baseDir($run).'/export';
    }

    public function artifactLog(MigrationRun $run): string
    {
        return $this->baseDir($run).'/artifacts.jsonl';
    }

    public function zipPath(MigrationRun $run): string
    {
        return $this->baseDir($run).'/export.zip';
    }

    public function reattachPath(MigrationRun $run): string
    {
        return $this->exportDir($run).'/REATTACH.md';
    }

    /**
     * Write a markdown file at a path relative to the run's export dir. Returns
     * the byte length written.
     */
    public function writeMarkdown(MigrationRun $run, string $relativePath, string $contents): int
    {
        $full = $this->exportDir($run).'/'.ltrim($relativePath, '/');
        File::ensureDirectoryExists(dirname($full));
        File::put($full, $contents);

        return strlen($contents);
    }

    /**
     * Append artifacts (Loop/SharePoint links needing re-attachment) to the log.
     *
     * @param  array<int, array{page: string, label: string, kind: string, url: string}>  $artifacts
     */
    public function appendArtifacts(MigrationRun $run, array $artifacts): void
    {
        if ($artifacts === []) {
            return;
        }

        File::ensureDirectoryExists($this->baseDir($run));
        $lines = '';
        foreach ($artifacts as $a) {
            $lines .= json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
        }
        File::append($this->artifactLog($run), $lines);
    }

    /**
     * @return array<int, array{page: string, label: string, kind: string, url: string}>
     */
    public function readArtifacts(MigrationRun $run): array
    {
        $path = $this->artifactLog($run);
        if (! File::exists($path)) {
            return [];
        }

        $out = [];
        foreach (preg_split('/\r?\n/', (string) File::get($path)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $out[] = $decoded;
            }
        }

        return $out;
    }

    public function delete(MigrationRun $run): void
    {
        File::deleteDirectory($this->baseDir($run));
    }

    /**
     * All exported markdown files (absolute paths), REATTACH.md excluded.
     *
     * @return array<int, string>
     */
    public function markdownFiles(MigrationRun $run): array
    {
        $dir = $this->exportDir($run);
        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::allFiles($dir))
            ->filter(fn ($f) => strtolower($f->getExtension()) === 'md' && $f->getFilename() !== 'REATTACH.md')
            ->map(fn ($f) => $f->getPathname())
            ->values()
            ->all();
    }
}
