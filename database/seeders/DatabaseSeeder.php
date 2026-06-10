<?php

namespace Database\Seeders;

use App\Enums\SnapshotType;
use App\Models\CanonicalDatabase;
use App\Models\Project;
use App\Models\User;
use App\Services\CanonicalTemplateImporter;
use App\Services\SnapshotImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@notionproviders.com'],
            [
                'name' => 'Atlas Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if (CanonicalDatabase::query()->count() === 0) {
            $this->seedCanonicalDatabases();
        }

        Project::query()
            ->where('slug', 'formosa-ev')
            ->update([
                'slug' => 'notion',
                'name' => 'Notion',
                'client_name' => 'Notion',
            ]);

        $project = Project::query()->firstOrCreate(
            ['slug' => 'notion'],
            [
                'user_id' => $admin->id,
                'name' => 'Notion',
                'client_name' => 'Notion',
                'notes' => 'Demo Notion workspace for Atlas Console.',
            ],
        );

        foreach (SnapshotType::cases() as $type) {
            $project->snapshots()->firstOrCreate(['type' => $type]);
        }

        $beforeSnapshot = $project->snapshots()->where('type', SnapshotType::Before)->first();
        $demoPath = resource_path('data/demo-notion-before.json');
        $needsDemoImport = $beforeSnapshot?->isEmpty()
            || data_get($beforeSnapshot?->tree_json, 'label') === 'Formosa EV';

        if ($needsDemoImport && is_readable($demoPath)) {
            app(SnapshotImporter::class)->import($project, SnapshotType::Before, $demoPath);
        }
    }

    private function seedCanonicalDatabases(): void
    {
        $jsonPath = resource_path('data/canonical-databases.json');

        if (! is_readable($jsonPath)) {
            return;
        }

        app(CanonicalTemplateImporter::class)->importFromJson($jsonPath, replace: true);
    }
}
