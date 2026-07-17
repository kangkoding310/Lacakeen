<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_and_update_a_member(): void
    {
        $admin = $this->adminUser();
        $member = User::factory()->create();
        Role::firstOrCreate(['name' => 'member']);

        $this->actingAs($admin)->post(route('members.invite'), [
            'email' => 'newhire@lacakeen.test', 'role' => 'member',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('member_invitations', ['email' => 'newhire@lacakeen.test']);

        $this->actingAs($admin)->patch(route('members.update', $member), ['status' => 'inactive'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'status' => 'inactive']);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->patch(route('members.update', $admin), ['status' => 'inactive'])
            ->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }

    public function test_plain_member_cannot_invite_or_update_members(): void
    {
        $member = User::factory()->create();
        $target = User::factory()->create();
        Role::firstOrCreate(['name' => 'member']);

        $this->actingAs($member)->post(route('members.invite'), ['email' => 'x@lacakeen.test', 'role' => 'member'])
            ->assertForbidden();
        $this->actingAs($member)->patch(route('members.update', $target), ['status' => 'inactive'])
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
