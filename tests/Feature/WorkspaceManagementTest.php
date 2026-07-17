<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_workspace_redirects_to_the_owned_workspace(): void
    {
        [$owner, $workspace] = $this->workspaceFixture();

        $this->actingAs($owner)->get(route('workspace.show'))
            ->assertRedirect(route('workspaces.show', $workspace));
    }

    public function test_owner_can_view_rename_and_manage_members(): void
    {
        [$owner, $workspace] = $this->workspaceFixture();
        $newMember = User::factory()->create();

        $this->actingAs($owner)->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Workspaces/Show')
                ->where('workspace.id', $workspace->id));

        $this->actingAs($owner)->patch(route('workspaces.update', $workspace), ['name' => 'Renamed Workspace'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Renamed Workspace']);

        $this->actingAs($owner)->post(route('workspaces.members.store', $workspace), [
            'user_id' => $newMember->id, 'role' => 'member',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id, 'user_id' => $newMember->id, 'role' => 'member',
        ]);

        $this->actingAs($owner)->patch(route('workspaces.members.update', [$workspace, $newMember]), ['role' => 'admin'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id, 'user_id' => $newMember->id, 'role' => 'admin',
        ]);

        $this->actingAs($owner)->delete(route('workspaces.members.destroy', [$workspace, $newMember]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $workspace->id, 'user_id' => $newMember->id]);
    }

    public function test_workspace_admin_member_can_manage_but_not_delete(): void
    {
        [$owner, $workspace] = $this->workspaceFixture();
        $admin = User::factory()->create();
        $workspace->members()->attach($admin, ['id' => (string) Str::uuid(), 'role' => 'admin']);

        $this->actingAs($admin)->patch(route('workspaces.update', $workspace), ['name' => 'Admin Renamed'])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->delete(route('workspaces.destroy', $workspace))->assertForbidden();
        $this->actingAs($admin)->delete(route('workspaces.members.destroy', [$workspace, $owner]))->assertUnprocessable();
    }

    public function test_plain_member_cannot_update_or_delete_workspace(): void
    {
        [, $workspace] = $this->workspaceFixture();
        $member = User::factory()->create();
        $workspace->members()->attach($member, ['id' => (string) Str::uuid(), 'role' => 'member']);

        $this->actingAs($member)->get(route('workspaces.show', $workspace))->assertOk();
        $this->actingAs($member)->patch(route('workspaces.update', $workspace), ['name' => 'Should Fail'])->assertForbidden();
        $this->actingAs($member)->delete(route('workspaces.destroy', $workspace))->assertForbidden();
    }

    public function test_owner_deleting_workspace_cascades_projects(): void
    {
        [$owner, $workspace] = $this->workspaceFixture();
        $project = Project::create([
            'name' => 'Project', 'prefix' => 'PRJ', 'workspace_id' => $workspace->id,
            'created_by' => $owner->id, 'color' => '#2563EB',
        ]);

        $this->actingAs($owner)->delete(route('workspaces.destroy', $workspace))->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_only_admin_can_list_and_create_workspaces(): void
    {
        [$owner, $workspace] = $this->workspaceFixture();
        $newOwner = User::factory()->create();
        $admin = $this->adminUser();

        $this->actingAs($owner)->get(route('workspaces.index'))->assertForbidden();
        $this->actingAs($owner)->post(route('workspaces.store'), ['name' => 'New Division', 'owner_id' => $newOwner->id])
            ->assertForbidden();

        $this->actingAs($admin)->get(route('workspaces.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Workspaces/Index')->has('workspaces', 1));

        $this->actingAs($admin)->post(route('workspaces.store'), ['name' => 'New Division', 'owner_id' => $newOwner->id])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workspaces', ['name' => 'New Division', 'owner_id' => $newOwner->id]);

        // Admin can also manage a division it does not own.
        $this->actingAs($admin)->patch(route('workspaces.update', $workspace), ['name' => 'Managed By Admin'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Managed By Admin']);
    }

    private function workspaceFixture(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Workspace', 'owner_id' => $owner->id]);

        return [$owner, $workspace];
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
