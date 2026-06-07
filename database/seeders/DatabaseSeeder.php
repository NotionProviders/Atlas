<?php

namespace Database\Seeders;

use App\Enums\SnapshotType;
use App\Models\CanonicalDatabase;
use App\Models\Project;
use App\Models\User;
use App\Services\SnapshotImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $definitions = [
            [
                'name' => 'Companies',
                'description' => 'Canonical account / company registry.',
                'properties' => [
                    ['name' => 'Status', 'property_type' => 'status'],
                    ['name' => 'Owner', 'property_type' => 'person'],
                    ['name' => 'Industry', 'property_type' => 'select'],
                ],
            ],
            [
                'name' => 'People Directory',
                'description' => 'Canonical people and team members.',
                'properties' => [
                    ['name' => 'Email', 'property_type' => 'email'],
                    ['name' => 'Department', 'property_type' => 'select'],
                    ['name' => 'Start Date', 'property_type' => 'date'],
                ],
            ],
            [
                'name' => 'Meetings',
                'description' => 'Canonical meeting notes and cadences.',
                'properties' => [
                    ['name' => 'Date', 'property_type' => 'date'],
                    ['name' => 'Attendees', 'property_type' => 'person'],
                ],
            ],
        ];

        foreach ($definitions as $index => $def) {
            $slug = Str::slug($def['name']);
            $canonical = CanonicalDatabase::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'sort_order' => $index,
                ],
            );

            if ($canonical->properties()->exists()) {
                continue;
            }

            foreach ($def['properties'] as $i => $property) {
                $canonical->properties()->create([
                    ...$property,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
