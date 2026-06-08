<?php

namespace App\Console\Commands;

use App\Services\CanonicalTemplateImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportCanonicalTemplateCommand extends Command
{
    protected $signature = 'atlas:import-canonical-template
                            {path : Path to Notion HTML export directory or canonical-databases.json}
                            {--replace : Replace existing canonical databases}
                            {--export-json= : Write parsed databases to a JSON file}';

    protected $description = 'Import canonical databases and properties from a Notion template export';

    public function handle(CanonicalTemplateImporter $importer): int
    {
        $path = $this->argument('path');

        if ($this->option('export-json')) {
            if (! is_dir($path)) {
                $this->error('--export-json requires a directory path.');

                return self::FAILURE;
            }

            $payload = $importer->buildJsonFromDirectory($path);
            file_put_contents(
                $this->option('export-json'),
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->info('Wrote '.count($payload['databases']).' databases to '.$this->option('export-json'));
        }

        try {
            if (is_dir($path)) {
                $result = $importer->importFromDirectory($path, (bool) $this->option('replace'));
            } elseif (is_file($path) && str_ends_with(strtolower($path), '.json')) {
                $result = $importer->importFromJson($path, (bool) $this->option('replace'));
            } else {
                $this->error('Path must be an export directory or .json file.');

                return self::FAILURE;
            }
        } catch (InvalidArgumentException|\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Imported %d canonical databases (%d properties, %d skipped).',
            $result['imported'],
            $result['properties'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
