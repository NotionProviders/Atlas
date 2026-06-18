<?php

namespace App\Services\Intake;

/**
 * Lightweight, memory-safe inspection of an uploaded export file.
 *
 * Streams the file rather than loading it, so a 25M-row audit log won't blow
 * up. Reports a row count and — when a page-identifier column is present and
 * the file is small enough to be safe — a distinct page-ID count, which is the
 * headline metric for seed sources like AdminContentSearch.
 */
class CsvInspector
{
    /** Above this size, skip distinct-ID counting and just report rows. */
    private const DISTINCT_BYTE_LIMIT = 80 * 1024 * 1024;

    /**
     * @return array{rows:int, columns:array<int,string>, metric:string}
     */
    public function inspect(string $path): array
    {
        if (str_ends_with(strtolower($path), '.zip')) {
            return $this->inspectZip($path);
        }

        $handle = @fopen($path, 'r');
        if ($handle === false) {
            return ['rows' => 0, 'columns' => [], 'metric' => 'unreadable file'];
        }

        $header = fgetcsv($handle) ?: [];
        $columns = array_map(fn ($c) => trim((string) $c), $header);

        $idIndex = $this->pageIdColumn($columns);
        $countDistinct = $idIndex !== null && filesize($path) <= self::DISTINCT_BYTE_LIMIT;

        $rows = 0;
        $ids = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }
            $rows++;
            if ($countDistinct && isset($row[$idIndex])) {
                $val = trim((string) $row[$idIndex]);
                if ($val !== '') {
                    $ids[$val] = true;
                }
            }
        }
        fclose($handle);

        $metric = $countDistinct
            ? number_format(count($ids)).' distinct page IDs · '.number_format($rows).' rows'
            : number_format($rows).' rows';

        return ['rows' => $rows, 'columns' => $columns, 'metric' => $metric];
    }

    /**
     * @return array{rows:int, columns:array<int,string>, metric:string}
     */
    private function inspectZip(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            return ['rows' => 0, 'columns' => [], 'metric' => 'unreadable archive'];
        }
        $files = $zip->numFiles;
        $zip->close();

        return ['rows' => $files, 'columns' => [], 'metric' => number_format($files).' files in archive'];
    }

    /**
     * Find a column that looks like a page identifier.
     */
    private function pageIdColumn(array $columns): ?int
    {
        $candidates = ['page id', 'pageid', 'id', 'page url', 'page_url', 'url', 'content id'];
        foreach ($columns as $i => $name) {
            if (in_array(strtolower(trim($name)), $candidates, true)) {
                return $i;
            }
        }

        return null;
    }
}
