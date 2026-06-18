<?php

namespace Tests\Feature;

use Tests\TestCase;

class AtlasMapTest extends TestCase
{
    public function test_root_renders_the_default_workspace_map(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('window.ATLAS_CONFIG', false);
    }

    public function test_config_script_serves_the_formosa_map(): void
    {
        $response = $this->get('/atlas-config.js?w=formosa-ev-hq');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('Formosa EV HQ', false);
    }

    public function test_unknown_workspace_falls_back_to_default(): void
    {
        $this->get('/?w=does-not-exist')->assertStatus(200);
    }

    public function test_legacy_concept_map_still_loads(): void
    {
        $this->get('/atlas-config.js?w=concept')
            ->assertStatus(200)
            ->assertSee('Teamspaces', false);
    }

    public function test_workspaces_console_lists_maps(): void
    {
        $this->get('/workspaces')
            ->assertStatus(200)
            ->assertSee('Workspaces')
            ->assertSee('Formosa EV HQ');
    }

    public function test_workspace_crawl_requires_a_token(): void
    {
        $this->post('/workspaces', [
            'name' => 'No Token Co',
            'token' => '',
            'mode' => 'discover',
        ])->assertSessionHasErrors('token');
    }
}
