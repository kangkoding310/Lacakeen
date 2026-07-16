<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_open_every_primary_application_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'david@lacakeen.test')->firstOrFail();

        foreach ([
            'dashboard', 'tasks.index', 'calendar', 'chat', 'reporting', 'workflow',
            'notifications', 'members', 'inbox', 'integrations', 'help-center',
            'settings', 'projects.index',
        ] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }
}
