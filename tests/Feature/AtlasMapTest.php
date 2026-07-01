<?php

namespace Tests\Feature;

use Tests\TestCase;

class AtlasMapTest extends TestCase
{
    public function test_public_root_renders_the_conceptual_atlas(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('window.ATLAS_CONFIG', false);
    }

    public function test_public_config_serves_the_concept_map(): void
    {
        $this->get('/atlas-config.js')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee('Teamspaces', false);
    }

    public function test_private_map_is_not_served_publicly(): void
    {
        // Asking for the private Formosa map on the public endpoint must fall
        // back to the concept map — it must not leak.
        $this->get('/atlas-config.js?w=formosa-ev-hq')
            ->assertStatus(200)
            ->assertDontSee('Formosa EV HQ', false);
    }

    public function test_console_requires_login(): void
    {
        $this->get('/console')->assertRedirect(route('console.login'));
        $this->get('/console/map/formosa-ev-hq')->assertRedirect(route('console.login'));
    }

    public function test_crawl_endpoint_requires_login(): void
    {
        $this->post('/console/workspaces', ['name' => 'X', 'mode' => 'discover'])
            ->assertRedirect(route('console.login'));
    }

    public function test_login_with_correct_password_grants_access(): void
    {
        config(['console.password' => 'secret']);

        $this->post('/console/login', ['password' => 'secret'])
            ->assertRedirect(route('console.index'))
            ->assertSessionHas('console_authed', true);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        config(['console.password' => 'secret']);

        $this->post('/console/login', ['password' => 'nope'])
            ->assertSessionHasErrors('password');
    }

    public function test_authed_console_shows_dashboard(): void
    {
        $this->withSession(['console_authed' => true])
            ->get('/console')
            ->assertStatus(200)
            ->assertSee('Intake Hub')
            ->assertSee('Mapped workspaces');
    }

    public function test_authed_console_can_view_private_map(): void
    {
        $this->withSession(['console_authed' => true])
            ->get('/console/map/formosa-ev-hq')
            ->assertStatus(200)
            ->assertSee('Formosa EV HQ', false);
    }

    public function test_workspace_crawl_requires_a_token_when_authed(): void
    {
        config(['notion.token' => null]);

        $this->withSession(['console_authed' => true])
            ->post('/console/workspaces', [
                'name' => 'No Token Co',
                'token' => '',
                'mode' => 'discover',
            ])->assertSessionHasErrors('token');
    }
}
