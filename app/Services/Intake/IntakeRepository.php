<?php

namespace App\Services\Intake;

use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Manages intake workspaces: each is a private folder holding an import
 * manifest (which sources have arrived) plus the uploaded export files. All
 * of it lives under storage/app (never web-served, never committed).
 */
class IntakeRepository
{
    private string $root;

    public function __construct(private readonly WorkspaceMapRepository $maps)
    {
        $this->root = storage_path('app/atlas/intake');
    }

    /** The catalog of every possible source, from config. */
    public function catalog(): array
    {
        return config('intake.sources', []);
    }

    /** Source keys that are file uploads (the checklist denominator). */
    public function uploadSourceKeys(): array
    {
        return array_values(array_map(
            fn ($s) => $s['key'],
            array_filter($this->catalog(), fn ($s) => $s['kind'] === 'upload')
        ));
    }

    /**
     * Every intake workspace, plus any private maps that don't yet have a
     * manifest (so a map made by an earlier crawl still shows up).
     *
     * @return array<int, array<string,mixed>>
     */
    public function listWorkspaces(): array
    {
        $slugs = [];
        if (File::isDirectory($this->root)) {
            foreach (File::directories($this->root) as $dir) {
                $slugs[basename($dir)] = true;
            }
        }
        foreach ($this->maps->list() as $map) {
            if ($map['slug'] !== 'concept' && ! $this->maps->isPublic($map['slug'])) {
                $slugs[$map['slug']] = true;
            }
        }

        $out = [];
        foreach (array_keys($slugs) as $slug) {
            $manifest = $this->getManifest($slug);
            $imported = count(array_filter(
                $manifest['sources'],
                fn ($k) => in_array($k, $this->uploadSourceKeys(), true),
                ARRAY_FILTER_USE_KEY
            ));
            $out[] = [
                'slug' => $slug,
                'name' => $manifest['name'],
                'createdAt' => $manifest['createdAt'] ?? null,
                'importedUploads' => $imported,
                'totalUploads' => count($this->uploadSourceKeys()),
                'hasApiScan' => isset($manifest['sources']['api_scan']),
                'hasMap' => $this->maps->exists($slug),
            ];
        }

        usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    public function createWorkspace(string $name): string
    {
        $slug = Str::slug($name) ?: 'workspace-'.Str::lower(Str::random(5));
        $dir = $this->dir($slug);
        File::ensureDirectoryExists($dir.'/files');

        $manifest = $this->getManifest($slug);
        $manifest['name'] = $name;
        $manifest['createdAt'] ??= now()->toIso8601String();
        $this->putManifest($slug, $manifest);

        return $slug;
    }

    public function exists(string $slug): bool
    {
        return File::exists($this->manifestPath($slug)) || $this->maps->exists($slug);
    }

    /**
     * Load a workspace manifest, synthesizing a default when none exists yet.
     */
    public function getManifest(string $slug): array
    {
        $path = $this->manifestPath($slug);
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            if (is_array($data)) {
                $data['sources'] ??= [];

                return $data;
            }
        }

        // Synthesize from a map's metadata if one exists.
        $map = $this->maps->exists($slug) ? $this->maps->load($slug) : null;
        $name = $map['meta']['name'] ?? Str::headline($slug);
        $sources = [];
        if ($map !== null && ($map['meta']['source'] ?? null) === 'notion-crawl') {
            $sources['api_scan'] = [
                'kind' => 'api',
                'metric' => number_format($map['meta']['nodeCount'] ?? 0).' nodes',
                'importedAt' => $map['meta']['generatedAt'] ?? null,
            ];
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'createdAt' => $map['meta']['generatedAt'] ?? null,
            'sources' => $sources,
        ];
    }

    public function recordUpload(string $slug, string $sourceKey, UploadedFile $file, array $stats): void
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'csv');
        $storedAs = $sourceKey.'.'.$ext;
        $file->move($this->dir($slug).'/files', $storedAs);

        $manifest = $this->getManifest($slug);
        $manifest['sources'][$sourceKey] = [
            'kind' => 'upload',
            'filename' => $file->getClientOriginalName(),
            'storedAs' => $storedAs,
            'size' => $stats['size'] ?? null,
            'rows' => $stats['rows'] ?? null,
            'metric' => $stats['metric'] ?? null,
            'importedAt' => now()->toIso8601String(),
        ];
        $this->putManifest($slug, $manifest);
    }

    public function recordApiScan(string $slug, string $metric): void
    {
        $manifest = $this->getManifest($slug);
        $manifest['sources']['api_scan'] = [
            'kind' => 'api',
            'metric' => $metric,
            'importedAt' => now()->toIso8601String(),
        ];
        $this->putManifest($slug, $manifest);
    }

    public function removeSource(string $slug, string $sourceKey): void
    {
        $manifest = $this->getManifest($slug);
        $entry = $manifest['sources'][$sourceKey] ?? null;
        if ($entry && ! empty($entry['storedAs'])) {
            File::delete($this->dir($slug).'/files/'.$entry['storedAs']);
        }
        unset($manifest['sources'][$sourceKey]);
        $this->putManifest($slug, $manifest);
    }

    public function deleteWorkspace(string $slug): void
    {
        if (File::isDirectory($this->dir($slug))) {
            File::deleteDirectory($this->dir($slug));
        }
        $this->maps->delete($slug);
    }

    public function importedAt(string $slug): ?string
    {
        return $this->getManifest($slug)['createdAt'] ?? null;
    }

    private function dir(string $slug): string
    {
        return $this->root.'/'.$slug;
    }

    private function manifestPath(string $slug): string
    {
        return $this->dir($slug).'/manifest.json';
    }

    private function putManifest(string $slug, array $manifest): void
    {
        File::ensureDirectoryExists($this->dir($slug).'/files');
        $manifest['updatedAt'] = Carbon::now()->toIso8601String();
        File::put(
            $this->manifestPath($slug),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
