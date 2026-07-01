<?php

namespace App\Services\Migration;

use App\Models\MigrationRun;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Zips a run's exported markdown tree into a single download. The zip contains
 * one top-level folder named after the workspace so it drops straight into
 * Notion's "Markdown & CSV" importer.
 */
class ExportArchive
{
    public function __construct(private readonly MigrationStorage $storage) {}

    public function build(MigrationRun $run): string
    {
        $exportDir = $this->storage->exportDir($run);
        if (! File::isDirectory($exportDir)) {
            throw new RuntimeException('Nothing to archive — export directory is missing.');
        }

        $zipPath = $this->storage->zipPath($run);
        File::delete($zipPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip archive.');
        }

        $rootFolder = MarkdownCleaner::safeFilename($run->name) ?: $run->slug;

        foreach (File::allFiles($exportDir) as $file) {
            $relative = ltrim(str_replace($exportDir, '', $file->getPathname()), '/\\');
            $relative = str_replace('\\', '/', $relative);
            $zip->addFile($file->getPathname(), $rootFolder.'/'.$relative);
        }

        $zip->close();

        return $zipPath;
    }

    public function exists(MigrationRun $run): bool
    {
        return File::exists($this->storage->zipPath($run));
    }
}
