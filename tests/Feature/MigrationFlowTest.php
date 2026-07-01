<?php

namespace Tests\Feature;

use App\Models\ExtensionToken;
use App\Models\MigrationRun;
use App\Services\Migration\MigrationStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolate exported files from any real runs, and clean them up after.
        config(['migration.storage_root' => 'migrations-test']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/migrations-test'));
        parent::tearDown();
    }

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/extension/me')->assertStatus(401);
        $this->postJson('/api/migrations', ['source' => 'loop', 'name' => 'x'])->assertStatus(401);
    }

    public function test_pairing_code_can_be_redeemed_once(): void
    {
        [, $code] = ExtensionToken::mintPairingCode(600, 'test');

        $this->postJson('/api/extension/pair', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['token', 'app', 'sources']);

        // Codes are single-use.
        $this->postJson('/api/extension/pair', ['code' => $code])->assertStatus(422);
        $this->postJson('/api/extension/pair', ['code' => 'NOTREAL9'])->assertStatus(422);
    }

    public function test_full_ingest_flow_produces_a_completed_export(): void
    {
        [, $code] = ExtensionToken::mintPairingCode(600, 'test');
        $token = $this->postJson('/api/extension/pair', ['code' => $code])->json('token');
        $auth = ['Authorization' => "Bearer {$token}"];

        $slug = $this->postJson('/api/migrations', [
            'source' => 'loop', 'name' => 'Flow WS', 'total' => 2,
        ], $auth)->assertCreated()->json('run.slug');

        $this->postJson("/api/migrations/{$slug}/nodes", [
            'nodes' => [
                ['title' => 'Home', 'path' => [], 'level' => 1, 'hasChildren' => true, 'markdown' => "Welcome.\n\n- a\n\n- b"],
                ['title' => 'Sub', 'path' => ['Home'], 'level' => 2, 'markdown' => 'Body with [x](https://z.sharepoint.com/:w:/r/d.docx).'],
            ],
        ], $auth)->assertOk();

        $this->postJson("/api/migrations/{$slug}/complete", ['status' => 'completed'], $auth)
            ->assertOk()
            ->assertJsonPath('run.status', 'completed')
            ->assertJsonPath('run.succeeded', 2);

        $run = MigrationRun::where('slug', $slug)->first();
        $this->assertSame(100, $run->progressPercent());
        $this->assertNotNull($run->report);
        $this->assertGreaterThanOrEqual(1, $run->report['reattach_count']);

        // The markdown tree was actually written (parent page = folder + same-named file).
        $files = app(MigrationStorage::class)->markdownFiles($run);
        $this->assertNotEmpty($files);
    }

    public function test_unknown_source_is_rejected(): void
    {
        [, $code] = ExtensionToken::mintPairingCode(600, 'test');
        $token = $this->postJson('/api/extension/pair', ['code' => $code])->json('token');

        $this->postJson('/api/migrations', ['source' => 'nope', 'name' => 'x'],
            ['Authorization' => "Bearer {$token}"])->assertStatus(422);
    }

    public function test_console_migrations_page_renders_when_authed(): void
    {
        $this->withSession(['console_authed' => true])
            ->get('/console/migrations')
            ->assertOk()
            ->assertSee('Browser extension')
            ->assertSee('What we can migrate');
    }
}
