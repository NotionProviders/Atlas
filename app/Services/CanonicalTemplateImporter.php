<?php

namespace App\Services;

use App\Models\CanonicalDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CanonicalTemplateImporter
{
    public function __construct(
        private readonly NotionExportPropertyTypeInferrer $propertyTypeInferrer = new NotionExportPropertyTypeInferrer(),
    ) {}
    /** @var list<string> */
    private array $lookupPrefixes = [
        'All Address Type',
        'All Agreement Types',
        'All Automation Categories',
        'All Automation Type',
        'All Business Types',
        'All Contact Tags',
        'All Document Purposes',
        'All Estimate Types',
        'All File Types',
        'All Industries',
        'All Interaction Outcomes',
        'All Interaction Types',
        'All Invoice Types',
        'All Languages',
        'All Lead Sources',
        'All Lead Tags',
        'All Leads Lists',
        'All Note Types',
        'All Occupations',
        'All Outside Service Categories',
        'All Outside Service Types',
        'All Platforms',
        'All Provider Tags',
        'All Provider Types',
        'All Ratings',
        'All Roles',
        'All Service Categories',
        'All Service Types',
        'All Skill Categories',
        'All Titles',
        'All Topics',
        'Admin Docs Types',
    ];

    /**
     * @return array{imported: int, skipped: int, properties: int}
     */
    public function importFromDirectory(string $directory, bool $replace = false): array
    {
        if (! is_dir($directory)) {
            throw new InvalidArgumentException("Directory not found: {$directory}");
        }

        $files = $this->discoverDatabaseCsvFiles($directory);

        if ($files === []) {
            throw new RuntimeException('No template database CSV files found in export.');
        }

        return DB::transaction(function () use ($files, $replace) {
            if ($replace) {
                CanonicalDatabase::query()->each(function (CanonicalDatabase $db): void {
                    $db->properties()->delete();
                    $db->delete();
                });
            }

            $imported = 0;
            $skipped = 0;
            $propertyCount = 0;
            $sortOrder = 0;

            foreach ($files as $file) {
                $parsed = $this->parseCsvFile($file);

                if ($parsed === null) {
                    $skipped++;

                    continue;
                }

                $canonical = CanonicalDatabase::query()->updateOrCreate(
                    ['notion_export_id' => $parsed['notion_export_id']],
                    [
                        'name' => $parsed['name'],
                        'slug' => $this->uniqueSlug($parsed['name'], $parsed['notion_export_id']),
                        'description' => null,
                        'template_tag' => null,
                        'is_lookup' => $parsed['is_lookup'],
                        'sort_order' => $sortOrder++,
                    ],
                );

                $canonical->properties()->delete();

                foreach ($parsed['properties'] as $index => $property) {
                    $canonical->properties()->create([
                        ...$property,
                        'sort_order' => $index,
                    ]);
                    $propertyCount++;
                }

                $imported++;
            }

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'properties' => $propertyCount,
            ];
        });
    }

    /**
     * @return array{imported: int, skipped: int, properties: int}
     */
    public function importFromJson(string $jsonPath, bool $replace = false): array
    {
        if (! is_readable($jsonPath)) {
            throw new InvalidArgumentException("Cannot read JSON: {$jsonPath}");
        }

        $payload = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        return DB::transaction(function () use ($payload, $replace) {
            if ($replace) {
                CanonicalDatabase::query()->each(function (CanonicalDatabase $db): void {
                    $db->properties()->delete();
                    $db->delete();
                });
            }

            $imported = 0;
            $propertyCount = 0;

            foreach ($payload['databases'] ?? [] as $index => $row) {
                $canonical = CanonicalDatabase::query()->updateOrCreate(
                    ['notion_export_id' => $row['notion_export_id'] ?? null],
                    [
                        'name' => $row['name'],
                        'slug' => $this->uniqueSlug($row['name'], $row['notion_export_id'] ?? (string) $index),
                        'description' => $row['description'] ?? null,
                        'template_tag' => null,
                        'is_lookup' => (bool) ($row['is_lookup'] ?? false),
                        'sort_order' => $row['sort_order'] ?? $index,
                    ],
                );

                $canonical->properties()->delete();

                foreach ($row['properties'] ?? [] as $pIndex => $property) {
                    $canonical->properties()->create([
                        'name' => $property['name'],
                        'property_type' => $property['property_type'] ?? 'unknown',
                        'is_title' => (bool) ($property['is_title'] ?? false),
                        'options' => $property['options'] ?? null,
                        'sort_order' => $pIndex,
                    ]);
                    $propertyCount++;
                }

                $imported++;
            }

            return ['imported' => $imported, 'skipped' => 0, 'properties' => $propertyCount];
        });
    }

    /** @return array<string, mixed>|null */
    public function parseCsvFile(string $path): ?array
    {
        $basename = pathinfo($path, PATHINFO_FILENAME);
        $basename = preg_replace('/_all$/i', '', $basename) ?? $basename;

        if (! preg_match('/^(.+?) ([a-f0-9]{32})$/i', $basename, $matches)) {
            return null;
        }

        $fullName = trim($matches[1]);
        $notionId = strtolower($matches[2]);

        if (! preg_match('/\[(CC|FT)\]$/i', $fullName)) {
            return null;
        }

        if (str_starts_with($fullName, 'View of ') || str_contains($fullName, 'All CC Databases')) {
            return null;
        }

        if (! str_starts_with($fullName, 'All ')) {
            return null;
        }

        $displayName = preg_replace('/\s+\[(CC|FT)\]$/i', '', $fullName) ?? $fullName;
        $displayName = preg_replace('/^All\s+/i', '', $displayName) ?? $displayName;

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }

        $header = fgetcsv($handle);
        $sampleRows = [];
        while (($row = fgetcsv($handle)) !== false && count($sampleRows) < 50) {
            $sampleRows[] = $row;
        }
        fclose($handle);

        if ($header === false || $header === []) {
            return null;
        }

        $properties = [];
        foreach ($header as $index => $column) {
            $column = trim((string) $column);
            $column = preg_replace('/^\xEF\xBB\xBF/', '', $column) ?? $column;
            if ($column === '') {
                continue;
            }

            // Count rollups duplicate the linked relation property in this template.
            if (preg_match('/\sCounts?$/i', $column)) {
                continue;
            }

            $isTitle = strcasecmp($column, 'Name') === 0;
            $samples = array_map(
                fn (array $row): ?string => $row[$index] ?? null,
                $sampleRows,
            );

            $properties[] = [
                'name' => $column,
                'property_type' => $this->propertyTypeInferrer->infer($column, $samples, $isTitle),
                'is_title' => $isTitle,
                'options' => null,
            ];
        }

        return [
            'name' => $displayName,
            'notion_export_id' => $notionId,
            'is_lookup' => $this->isLookupDatabase($fullName, $displayName),
            'properties' => $properties,
        ];
    }

    /**
     * Export parsed databases to JSON for committed seed data.
     *
     * @return array{databases: list<array<string, mixed>>}
     */
    public function buildJsonFromDirectory(string $directory): array
    {
        $files = $this->discoverDatabaseCsvFiles($directory);
        $databases = [];
        $sortOrder = 0;

        foreach ($files as $file) {
            $parsed = $this->parseCsvFile($file);
            if ($parsed === null) {
                continue;
            }

            $parsed['sort_order'] = $sortOrder++;
            $databases[] = $parsed;
        }

        usort($databases, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        foreach ($databases as $i => &$db) {
            $db['sort_order'] = $i;
        }

        return ['databases' => $databases];
    }

    /** @return list<string> */
    private function discoverDatabaseCsvFiles(string $directory): array
    {
        $allFiles = glob($directory.DIRECTORY_SEPARATOR.'*_all.csv') ?: [];
        $allFiles = array_values(array_filter($allFiles, function (string $path): bool {
            $name = basename($path);

            if (str_starts_with($name, 'Untitled')) {
                return false;
            }

            if (str_starts_with($name, 'View of ')) {
                return false;
            }

            return (bool) preg_match('/^All .+\[(CC|FT)\] [a-f0-9]{32}_all\.csv$/i', $name);
        }));

        if ($allFiles !== []) {
            return $allFiles;
        }

        // Fallback for exports that only ship view CSVs.
        $files = glob($directory.DIRECTORY_SEPARATOR.'*.csv') ?: [];

        return array_values(array_filter($files, function (string $path): bool {
            $name = basename($path);

            if (str_contains($name, '_all.csv')) {
                return false;
            }

            if (str_starts_with($name, 'Untitled')) {
                return false;
            }

            if (str_starts_with($name, 'View of ')) {
                return false;
            }

            return (bool) preg_match('/^All .+\[(CC|FT)\] [a-f0-9]{32}\.csv$/i', $name);
        }));
    }

    private function isLookupDatabase(string $fullName, string $displayName): bool
    {
        foreach ($this->lookupPrefixes as $prefix) {
            if (str_starts_with($fullName, $prefix) || str_starts_with($displayName, str_replace('All ', '', $prefix))) {
                return true;
            }
        }

        return (bool) preg_match('/\b(Types?|Tags?|Categories|Sources|Lists?|Outcomes|Platforms|Ratings|Roles|Titles|Languages|Industries|Occupations|Topics|Zones|Regions)\b/i', $fullName);
    }

    private function uniqueSlug(string $name, string $suffix): string
    {
        $slug = Str::slug($name);
        $candidate = $slug;
        $existing = CanonicalDatabase::query()->where('slug', $candidate)->where('notion_export_id', '!=', $suffix)->exists();

        if ($existing) {
            $candidate = $slug.'-'.substr($suffix, 0, 8);
        }

        $base = $candidate;
        $i = 1;
        while (CanonicalDatabase::query()->where('slug', $candidate)->where('notion_export_id', '!=', $suffix)->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }
}
