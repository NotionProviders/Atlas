<?php

namespace App\Console\Commands;

use App\Enums\SnapshotType;
use App\Models\Project;
use App\Services\SnapshotImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportSnapshotCommand extends Command
{
    protected $signature = 'atlas:import-snapshot {project : Project slug} {type : before|ideal|after} {file : Path to JSON file}';

    protected $description = 'Import workspace snapshot data from a JSON file';

    public function handle(SnapshotImporter $importer): int
    {
        $project = Project::query()->where('slug', $this->argument('project'))->first();

        if (! $project) {
            $this->error('Project not found: '.$this->argument('project'));

            return self::FAILURE;
        }

        try {
            $type = SnapshotType::from($this->argument('type'));
        } catch (\ValueError) {
            $this->error('Invalid type. Use before, ideal, or after.');

            return self::FAILURE;
        }

        try {
            $snapshot = $importer->import($project, $type, $this->argument('file'));
        } catch (InvalidArgumentException|\JsonException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Imported %s snapshot for "%s" (%d nodes).',
            $type->value,
            $project->name,
            $snapshot->nodes()->count(),
        ));

        return self::SUCCESS;
    }
}
