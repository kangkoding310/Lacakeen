<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_archive_and_delete_a_project(): void
    {
        [$owner, $project] = $this->projectFixture();

        $this->actingAs($owner)->patch(route('projects.update', $project), [
            'name' => 'Updated Project', 'prefix' => 'UPD', 'description' => 'Updated',
            'color' => '#7C3AED', 'status' => 'archived',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Project', 'prefix' => 'UPD', 'status' => 'archived']);
        $this->actingAs($owner)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_editor_can_manage_project_members_but_cannot_remove_creator(): void
    {
        [$owner, $project] = $this->projectFixture();
        $editor = User::factory()->create();
        $newMember = User::factory()->create();
        $project->members()->attach($editor, ['id' => (string) Str::uuid(), 'role_in_project' => 'editor']);

        $this->actingAs($editor)->post(route('projects.members.store', $project), [
            'user_id' => $newMember->id, 'role_in_project' => 'viewer',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $newMember->id, 'role_in_project' => 'viewer']);

        $this->actingAs($editor)->patch(route('projects.members.update', [$project, $newMember]), ['role_in_project' => 'editor'])->assertSessionHasNoErrors();
        $this->actingAs($editor)->delete(route('projects.members.destroy', [$project, $newMember]))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('project_members', ['project_id' => $project->id, 'user_id' => $newMember->id]);

        $this->actingAs($editor)->delete(route('projects.members.destroy', [$project, $owner]))->assertUnprocessable();
        $this->actingAs($editor)->delete(route('projects.destroy', $project))->assertForbidden();
    }

    public function test_member_can_open_project_workspace_with_board_data(): void
    {
        [$owner, $project] = $this->projectFixture();
        $status = TaskStatus::create(['project_id' => $project->id, 'name' => 'To Do', 'color' => '#2563EB', 'order' => 0]);
        Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Workspace task',
            'code' => 'PRJ-1', 'priority' => 'medium', 'created_by' => $owner->id, 'order' => 0,
        ]);

        $this->actingAs($owner)->get(route('projects.show', ['project' => $project, 'tab' => 'kanban']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Projects/Show')
                ->where('tab', 'kanban')->has('statuses', 1)->has('statuses.0.tasks', 1)->has('tasks', 1));
    }

    private function projectFixture(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Workspace', 'owner_id' => $owner->id]);
        $project = Project::create([
            'name' => 'Project', 'prefix' => 'PRJ', 'workspace_id' => $workspace->id,
            'created_by' => $owner->id, 'color' => '#2563EB',
        ]);
        $project->members()->attach($owner, ['id' => (string) Str::uuid(), 'role_in_project' => 'owner']);

        return [$owner, $project];
    }
}
