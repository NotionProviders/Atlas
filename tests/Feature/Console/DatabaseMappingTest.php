<?php

namespace Tests\Feature\Console;

use App\Enums\NodeKind;
use App\Enums\SnapshotType;
use App\Models\AtlasNode;
use App\Models\CanonicalDatabase;
use App\Models\Project;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_canonical_creates_custom_database_and_mapping(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $snapshot = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $snapshot->id,
            'kind' => NodeKind::Database,
            'label' => 'Client CRM',
        ]);

        $this->actingAs($user)
            ->post('/console/projects/test/mappings/quick-canonical', [
                'name' => 'Custom Leads',
                'atlas_node_id' => $node->id,
            ])
            ->assertRedirect();

        $canonical = CanonicalDatabase::query()->where('name', 'Custom Leads')->first();
        $this->assertNotNull($canonical);
        $this->assertTrue($canonical->is_custom);

        $this->assertDatabaseHas('database_mappings', [
            'project_id' => $project->id,
            'atlas_node_id' => $node->id,
            'canonical_database_id' => $canonical->id,
        ]);

        $this->assertDatabaseHas('canonical_placements', [
            'project_id' => $project->id,
            'canonical_database_id' => $canonical->id,
            'teamspace_node_id' => null,
        ]);
    }

    public function test_mappings_page_shows_canonical_table_with_pending_teamspace(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $snapshot = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $snapshot->id,
            'kind' => NodeKind::Database,
            'label' => 'People',
        ]);
        $canonical = CanonicalDatabase::query()->create([
            'name' => 'Contacts',
            'slug' => 'contacts',
            'is_custom' => true,
        ]);

        $this->actingAs($user)->post('/console/projects/test/mappings', [
            'atlas_node_id' => $node->id,
            'canonical_database_id' => $canonical->id,
        ]);

        $this->actingAs($user)
            ->get('/console/projects/test/mappings')
            ->assertOk()
            ->assertSee('Canonical table')
            ->assertSee('Not in template')
            ->assertSee('Pending teamspace');
    }

    public function test_first_mapping_redirects_to_canonical_table(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $snapshot = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $snapshot->id,
            'kind' => NodeKind::Database,
            'label' => 'People',
        ]);
        $canonical = CanonicalDatabase::query()->create([
            'name' => 'Contacts',
            'slug' => 'contacts',
        ]);

        $this->actingAs($user)->post('/console/projects/test/mappings', [
            'atlas_node_id' => $node->id,
            'canonical_database_id' => $canonical->id,
        ])
            ->assertRedirect('/console/projects/test/snapshots/canon?view=table');

        $this->actingAs($user)
            ->get('/console/projects/test/snapshots/canon?view=table')
            ->assertOk()
            ->assertSee('Canonical table')
            ->assertSee('Contacts');
    }

    public function test_project_page_shows_canonical_table_when_mappings_exist(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $before = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Canon]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $before->id,
            'kind' => NodeKind::Database,
            'label' => 'People',
        ]);
        $canonical = CanonicalDatabase::query()->create(['name' => 'Contacts', 'slug' => 'contacts']);

        $this->actingAs($user)->post('/console/projects/test/mappings', [
            'atlas_node_id' => $node->id,
            'canonical_database_id' => $canonical->id,
        ]);

        $this->actingAs($user)
            ->get('/console/projects/test')
            ->assertOk()
            ->assertSee('1 mapped database')
            ->assertSee('/console/projects/test/snapshots/canon?view=table');
    }

    public function test_database_mapping_page_exists_before_mapping(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $snapshot = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $snapshot->id,
            'kind' => NodeKind::Database,
            'label' => 'Unmapped DB',
        ]);

        $this->actingAs($user)
            ->get('/console/projects/test/mappings/database/'.$node->id)
            ->assertOk()
            ->assertSee('Unmapped DB')
            ->assertSee('Select or create a canonical target');
    }

    public function test_database_panel_loads_for_unmapped_node(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Test', 'slug' => 'test']);
        $snapshot = Snapshot::query()->create(['project_id' => $project->id, 'type' => SnapshotType::Before]);
        $node = AtlasNode::query()->create([
            'snapshot_id' => $snapshot->id,
            'kind' => NodeKind::Database,
            'label' => 'Unmapped DB',
        ]);

        $this->actingAs($user)
            ->get('/console/projects/test/mappings/c/database/'.$node->id.'/panel')
            ->assertOk()
            ->assertSee('Unmapped DB')
            ->assertSee('Select or create a canonical target above');
    }
}
