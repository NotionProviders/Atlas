<?php

namespace App\Services\Notion;

use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Builds an Atlas map from a Notion export (Markdown & CSV, or HTML).
 *
 * Notion exports preserve the workspace hierarchy on disk:
 *   - every page is a `.md` / `.html` file, e.g.
 *       "Command Center 386fdd23a330822680e601eb42fe4b12.md"
 *   - a page that has children gets a sibling folder with the *same* name,
 *       holding its subpages (recursively).
 *   - every database is a `.csv` (plus a "<name>_all.csv" full export) with a
 *       sibling folder of its row pages.
 *
 * That on-disk tree is exactly the structure Atlas renders, so this builder
 * walks it pages-first-then-databases (mirroring the live crawler) and emits
 * the same node shape — no Notion API access required. This is the intake path
 * that works even for teamspaces the REST API can't reach.
 */
class ExportMapBuilder
{
    private const COLORS = [
        'workspace' => '#ffd66b',
        'teamspace' => '#c094ff',
        'database' => '#5eb0ff',
        'page' => '#4aebb0',
        'row' => '#ffb05a',
    ];

    /** Trailing 32-char hex id Notion appends to every exported file/folder. */
    private const ID_SUFFIX = '/[ _-]([0-9a-f]{32})$/i';

    private int $nodeCount = 0;

    private int $maxNodes = 50000;

    private int $maxDepth = 30;

    /**
     * Build a map from an uploaded export file (zip, or a single md/html/csv).
     *
     * @return array{palette: array, tree: array, legend: array, meta: array}
     */
    public function buildFromUpload(string $absolutePath, string $workspaceName): array
    {
        $this->nodeCount = 0;

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        $children = $extension === 'zip'
            ? $this->fromZip($absolutePath)
            : $this->fromSingleFile($absolutePath);

        return $this->wrap($workspaceName, $children);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fromZip(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open the uploaded zip. Re-export from Notion and try again.');
        }

        $tmp = sys_get_temp_dir().'/atlas-export-'.bin2hex(random_bytes(6));
        if (! mkdir($tmp, 0755, true) && ! is_dir($tmp)) {
            throw new RuntimeException('Could not create a temp directory to extract the export.');
        }

        try {
            $zip->extractTo($tmp);
            $zip->close();

            return $this->scanDirectory($this->effectiveRoot($tmp), 1);
        } finally {
            $this->deleteTree($tmp);
        }
    }

    /**
     * Notion wraps everything in an "Export-<uuid>" folder. If the extracted
     * root holds a single directory, descend into it so the top level is the
     * real content rather than the wrapper.
     */
    private function effectiveRoot(string $dir): string
    {
        $entries = array_values(array_filter(
            scandir($dir) ?: [],
            fn ($e) => $e !== '.' && $e !== '..'
        ));

        if (count($entries) === 1 && is_dir($dir.'/'.$entries[0])) {
            return $this->effectiveRoot($dir.'/'.$entries[0]);
        }

        return $dir;
    }

    /**
     * Walk a directory into page/database nodes, pages first then databases.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scanDirectory(string $dir, int $depth): array
    {
        if ($depth > $this->maxDepth || $this->nodeCount >= $this->maxNodes) {
            return [];
        }

        /** @var array<int, SplFileInfo> $files */
        $files = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $files[] = new SplFileInfo($dir.'/'.$entry);
        }

        // Index folders by name so a document can find its children folder.
        $folders = [];
        foreach ($files as $file) {
            if ($file->isDir()) {
                $folders[$file->getFilename()] = $file->getPathname();
            }
        }

        $pages = [];
        $databases = [];
        $claimedFolders = [];

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            $base = $this->basename($file);

            if ($ext === 'csv') {
                // Skip the "<name>_all.csv" duplicate when the view csv exists,
                // and vice-versa — keep one csv per database.
                $isAll = str_ends_with($base, '_all');
                $core = $isAll ? substr($base, 0, -4) : $base;

                if (isset($databases[$core])) {
                    continue;
                }

                $childDir = $folders[$core] ?? null;
                if ($childDir !== null) {
                    $claimedFolders[$core] = true;
                }

                $databases[$core] = $this->makeDatabase($core, $childDir, $depth);
            } elseif (in_array($ext, ['md', 'html', 'htm'], true)) {
                if (isset($pages[$base])) {
                    continue;
                }

                $childDir = $folders[$base] ?? null;
                if ($childDir !== null) {
                    $claimedFolders[$base] = true;
                }

                $pages[$base] = $this->makePage($base, $childDir, $depth);
            }
        }

        // Folders with no matching document/csv (rare) become standalone pages.
        foreach ($folders as $folderName => $folderPath) {
            if (isset($claimedFolders[$folderName])) {
                continue;
            }
            if (isset($pages[$folderName]) || isset($databases[$folderName])) {
                continue;
            }
            $pages[$folderName] = $this->makePage($folderName, $folderPath, $depth);
        }

        return array_merge(
            array_values($pages),
            array_values($databases),
        );
    }

    private function makePage(string $base, ?string $childDir, int $depth): array
    {
        $this->nodeCount++;
        $children = $childDir !== null ? $this->scanDirectory($childDir, $depth + 1) : [];

        return $this->node($this->label($base), 'page', $children, [
            'color' => self::COLORS['page'],
            'note' => $children === [] ? 'Page' : 'Page · '.count($children).' inside',
        ]);
    }

    private function makeDatabase(string $base, ?string $childDir, int $depth): array
    {
        $this->nodeCount++;

        $rows = $childDir !== null ? $this->scanDirectory($childDir, $depth + 1) : [];
        foreach ($rows as &$row) {
            // A database entry is itself a page; tint it as a row.
            $row['c'] = self::COLORS['row'];
            $row['t'] = 'row';
        }
        unset($row);

        return $this->node($this->label($base), 'database', $rows, [
            'color' => self::COLORS['database'],
            'kind' => 'sub',
            'note' => 'Database · '.count($rows).' pages',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fromSingleFile(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $base = $this->basename(new SplFileInfo($path));

        if ($ext === 'csv') {
            return [$this->makeDatabase($base, null, 1)];
        }

        return [$this->makePage($base, null, 1)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     */
    private function node(string $label, string $type, array $children, array $opts): array
    {
        $kind = $opts['kind'] ?? ($children !== [] ? 'sub' : 'leaf');

        $node = [
            'label' => $label === '' ? 'Untitled' : $label,
            'kind' => $kind,
            'c' => $opts['color'],
            't' => $type,
        ];

        if (! empty($opts['note'])) {
            $node['note'] = $opts['note'];
        }
        if ($children !== []) {
            $node['children'] = $children;
        }

        return $node;
    }

    private function wrap(string $workspaceName, array $children): array
    {
        return [
            'palette' => [
                'core' => '#ffd66b', 'blue' => '#5eb0ff', 'amber' => '#f5a623',
                'violet' => '#c094ff', 'green' => '#4aebb0', 'pink' => '#ff85a0',
                'cyan' => '#45e0f5', 'red' => '#ff7080', 'orange' => '#ffb05a',
            ],
            'tree' => [
                'label' => $workspaceName,
                'kind' => 'core',
                'c' => self::COLORS['workspace'],
                'id' => 'workspace',
                't' => 'workspace',
                'note' => 'Mapped from a Notion export — every page and database as it sits on disk.',
                'children' => $children,
            ],
            'legend' => [
                ['teamspace', 'Teamspaces'],
                ['database', 'Databases'],
                ['page', 'Pages'],
                ['row', 'Database pages'],
            ],
            'meta' => [
                'name' => $workspaceName,
                'source' => 'export-upload',
                'generatedAt' => now()->toIso8601String(),
                'nodeCount' => $this->nodeCount,
            ],
        ];
    }

    /** File name without its extension. */
    private function basename(SplFileInfo $file): string
    {
        $name = $file->getFilename();
        $ext = $file->getExtension();

        return $ext === '' ? $name : substr($name, 0, -(strlen($ext) + 1));
    }

    /** Human label: decode, then strip Notion's trailing 32-hex id. */
    private function label(string $base): string
    {
        $label = rawurldecode($base);
        $label = preg_replace(self::ID_SUFFIX, '', $label) ?? $label;

        return trim($label);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->deleteTree($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
