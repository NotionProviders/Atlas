<?php

namespace Database\Seeders;

use App\Enums\SnapshotType;
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

        $this->seedCanonicalDatabases();

        $project = Project::query()->firstOrCreate(
            ['slug' => 'formosa-ev'],
            [
                'user_id' => $admin->id,
                'name' => 'Formosa EV',
                'client_name' => 'Formosa EV',
                'notes' => 'Demo client workspace for Atlas Console development.',
            ],
        );

        foreach (SnapshotType::cases() as $type) {
            $project->snapshots()->firstOrCreate(['type' => $type]);
        }

        $demoPath = resource_path('data/demo-formosa-before.json');

        if (is_readable($demoPath)) {
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
