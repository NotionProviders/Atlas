<?php

namespace Tests\Feature;

use App\Services\Intake\IntakeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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
        $this->get('/atlas-config.js?w=formosa-ev-hq')
            ->assertStatus(200)
            ->assertDontSee('Formosa EV HQ', false);
    }

    public function test_console_requires_login(): void
    {
        $this->get('/console')->assertRedirect(route('console.login'));
        $this->get('/console/map/formosa-ev-hq')->assertRedirect(route('console.login'));
        $this->get('/console/guide')->assertRedirect(route('console.login'));
    }

    public function test_intake_pages_require_login(): void
    {
        $this->get('/console/workspaces/anything/intake')->assertRedirect(route('console.login'));
        $this->post('/console/workspaces')->assertRedirect(route('console.login'));
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

    public function test_authed_console_shows_dashboard_and_guide(): void
    {
        $this->withSession(['console_authed' => true])->get('/console')
            ->assertStatus(200)->assertSee('Workspaces');

        $this->withSession(['console_authed' => true])->get('/console/guide')
            ->assertStatus(200)->assertSee('AdminContentSearch');
    }

    public function test_create_workspace_then_intake_page_lists_sources(): void
    {
        $slug = $this->createWorkspace('Pen Test Co');

        try {
            $this->withSession(['console_authed' => true])
                ->get("/console/workspaces/{$slug}/intake")
                ->assertStatus(200)
                ->assertSee('AdminContentSearch — Active')
                ->assertSee('Audit Log')
                ->assertSee('Live API scan');
        } finally {
            app(IntakeRepository::class)->deleteWorkspace($slug);
        }
    }

    public function test_uploading_a_csv_is_recorded_in_the_manifest(): void
    {
        $slug = $this->createWorkspace('Upload Co');

        try {
            $csv = "Page ID,Title\naaa,Alpha\nbbb,Beta\naaa,Alpha dupe\n";
            $file = UploadedFile::fake()->createWithContent('acs.csv', $csv);

            $this->withSession(['console_authed' => true])
                ->post("/console/workspaces/{$slug}/intake/upload", [
                    'source' => 'admin_content_search_active',
                    'file' => $file,
                ])->assertRedirect();

            $manifest = app(IntakeRepository::class)->getManifest($slug);
            $this->assertArrayHasKey('admin_content_search_active', $manifest['sources']);
            $this->assertSame(3, $manifest['sources']['admin_content_search_active']['rows']);
        } finally {
            app(IntakeRepository::class)->deleteWorkspace($slug);
        }
    }

    public function test_api_scan_requires_a_token(): void
    {
        $slug = $this->createWorkspace('Scan Co');

        try {
            $this->withSession(['console_authed' => true])
                ->post("/console/workspaces/{$slug}/intake/scan", ['mode' => 'discover'])
                ->assertSessionHasErrors('token');
        } finally {
            app(IntakeRepository::class)->deleteWorkspace($slug);
        }
    }

    private function createWorkspace(string $name): string
    {
        $this->withSession(['console_authed' => true])
            ->post('/console/workspaces', ['name' => $name])
            ->assertRedirect();

        return Str::slug($name);
    }
}
