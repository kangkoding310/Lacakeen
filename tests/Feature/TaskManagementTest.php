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

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_editor_can_create_a_task_with_incrementing_project_code(): void
    {
        [$user, $project, $status] = $this->projectFixture();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'project_id' => $project->id,
            'status_id' => $status->id,
            'title' => 'Ship the dashboard',
            'priority' => 'high',
            'assignee_ids' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'code' => 'WEB-1', 'title' => 'Ship the dashboard']);
        $this->assertSame(2, $project->fresh()->next_task_number);
    }

    public function test_project_editor_can_move_a_task_between_columns(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $target = TaskStatus::create(['project_id' => $project->id, 'name' => 'Completed', 'color' => '#22C55E', 'order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Move me',
            'code' => 'WEB-99', 'priority' => 'medium', 'created_by' => $user->id,
        ]);

        $this->actingAs($user)->patch(route('tasks.move', $task), [
            'status_id' => $target->id, 'order' => 0, 'ordered_ids' => [$task->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status_id' => $target->id, 'order' => 0]);
        $this->assertDatabaseHas('task_activity_logs', ['task_id' => $task->id, 'action' => 'moved']);
    }

    public function test_project_editor_can_add_a_status_column(): void
    {
        [$user, $project] = $this->projectFixture();

        $this->actingAs($user)->post(route('statuses.store', $project), [
            'name' => 'In Review', 'color' => '#A855F7',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('task_statuses', [
            'project_id' => $project->id, 'name' => 'In Review', 'color' => '#A855F7', 'order' => 1,
        ]);
    }

    public function test_project_editor_can_create_a_subtask_for_a_project_task(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $parent = Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Parent task',
            'code' => 'WEB-99', 'priority' => 'medium', 'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('tasks.store'), [
            'project_id' => $project->id, 'status_id' => $status->id, 'parent_task_id' => $parent->id,
            'title' => 'Child task', 'priority' => 'medium', 'assignee_ids' => [],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id, 'parent_task_id' => $parent->id, 'title' => 'Child task',
        ]);
        $this->assertCount(1, $parent->subtasks()->get());
    }

    public function test_nested_subtasks_are_rejected(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $parent = Task::create(['project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Parent', 'code' => 'WEB-98', 'priority' => 'medium', 'created_by' => $user->id]);
        $subtask = Task::create(['project_id' => $project->id, 'status_id' => $status->id, 'parent_task_id' => $parent->id, 'title' => 'Subtask', 'code' => 'WEB-99', 'priority' => 'medium', 'created_by' => $user->id]);

        $this->actingAs($user)->post(route('tasks.store'), [
            'project_id' => $project->id, 'status_id' => $status->id, 'parent_task_id' => $subtask->id,
            'title' => 'Nested task', 'priority' => 'medium', 'assignee_ids' => [],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('tasks', ['parent_task_id' => $subtask->id]);
    }

    public function test_subtask_priority_assignee_and_status_can_be_updated(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $target = TaskStatus::create(['project_id' => $project->id, 'name' => 'Completed', 'color' => '#22C55E', 'order' => 1]);
        $subtask = Task::create(['project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Subtask', 'code' => 'WEB-99', 'priority' => 'low', 'created_by' => $user->id]);

        $this->actingAs($user)->patch(route('tasks.update', $subtask), [
            'priority' => 'urgent', 'status_id' => $target->id, 'assignee_ids' => [$user->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $subtask->id, 'priority' => 'urgent', 'status_id' => $target->id, 'assignee_id' => $user->id]);
        $this->assertDatabaseHas('task_assignees', ['task_id' => $subtask->id, 'user_id' => $user->id]);
    }

    public function test_non_member_cannot_update_a_project_task(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $outsider = User::factory()->create();
        $task = Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Private task',
            'code' => 'WEB-1', 'priority' => 'medium', 'created_by' => $user->id,
        ]);

        $this->actingAs($outsider)->patch(route('tasks.update', $task), ['title' => 'Not allowed'])->assertForbidden();
        $this->assertSame('Private task', $task->fresh()->title);
    }

    private function projectFixture(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Test Workspace', 'owner_id' => $user->id]);
        $project = Project::create([
            'name' => 'Website', 'prefix' => 'WEB', 'workspace_id' => $workspace->id,
            'created_by' => $user->id, 'color' => '#2563EB',
        ]);
        $project->members()->attach($user, ['id' => (string) Str::uuid(), 'role_in_project' => 'editor']);
        $status = TaskStatus::create([
            'project_id' => $project->id, 'name' => 'Not Started', 'color' => '#64748B',
            'order' => 0, 'is_default' => true,
        ]);

        return [$user, $project, $status];
    }
}
