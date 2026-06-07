<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_projects_requires_authentication(): void
    {
        $response = $this->get('/console/projects');

        $response->assertRedirect('/console/login');
    }

    public function test_authenticated_user_can_view_projects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/console/projects');

        $response->assertOk();
    }
}
