<?php

namespace Tests\Unit;

use App\Services\CanonicalTemplateImporter;
use Tests\TestCase;

class CanonicalTemplateImporterTest extends TestCase
{
    public function test_parse_csv_file_reads_all_properties_from_all_export(): void
    {
        $directory = 'c:/Users/Mr Cakes/Downloads/ExportBlock-atlas-template';

        if (! is_dir($directory)) {
            $this->markTestSkipped('Notion export directory not available locally.');
        }

        $files = glob($directory.'/*_all.csv') ?: [];
        $companiesFile = null;

        foreach ($files as $file) {
            if (str_contains(basename($file), 'All Companies [CC]')) {
                $companiesFile = $file;
                break;
            }
        }

        $this->assertNotNull($companiesFile, 'Companies _all.csv not found in export.');

        $parsed = app(CanonicalTemplateImporter::class)->parseCsvFile($companiesFile);

        $this->assertNotNull($parsed);
        $this->assertSame('Companies', $parsed['name']);
        $this->assertGreaterThanOrEqual(20, count($parsed['properties']));

        $propertyNames = array_column($parsed['properties'], 'name');
        $this->assertContains('Name', $propertyNames);
        $this->assertContains('Archive', $propertyNames);
        $this->assertContains('Company Email', $propertyNames);
    }
}
