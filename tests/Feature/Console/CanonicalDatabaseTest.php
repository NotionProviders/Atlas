<?php

namespace Tests\Feature\Console;

use App\Models\CanonicalDatabase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_registry_requires_authentication(): void
    {
        $this->get('/console/canonical-databases')->assertRedirect('/console/login');
    }

    public function test_authenticated_user_can_view_canonical_show_and_panel(): void
    {
        $user = User::factory()->create();

        $canonical = CanonicalDatabase::query()->create([
            'name' => 'Addresses',
            'slug' => 'addresses',
            'description' => 'Client and vendor addresses.',
            'is_lookup' => false,
        ]);

        $this->actingAs($user)
            ->get('/console/canonical-databases')
            ->assertOk()
            ->assertSee('Addresses');

        $this->actingAs($user)
            ->get('/console/canonical-databases/addresses')
            ->assertOk()
            ->assertSee('Addresses')
            ->assertSee('Client and vendor addresses.');

        $this->actingAs($user)
            ->get('/console/canonical-databases/c/addresses/panel')
            ->assertOk()
            ->assertSee('Addresses');

        $this->actingAs($user)
            ->get('/console/canonical-databases/c/addresses')
            ->assertOk()
            ->assertSee('Addresses');
    }

    public function test_projects_index_does_not_leak_project_breadcrumb(): void
    {
        $user = User::factory()->create();

        CanonicalDatabase::query()->create([
            'name' => 'Only On Registry',
            'slug' => 'only-on-registry',
        ]);

        \App\Models\Project::query()->create([
            'name' => 'Formosa EV',
            'slug' => 'formosa-ev',
        ]);

        $response = $this->actingAs($user)->get('/console/projects');

        $response->assertOk();
        $response->assertSee('Formosa EV');
        $response->assertDontSee('Atlas Console / Formosa EV', false);
    }
}
