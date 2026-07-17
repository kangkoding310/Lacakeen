<?php

namespace App\Actions\Task;

use App\Jobs\SyncTaskCalendarEventsJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskStatus;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateTaskAction
{
    public function handle(User $creator, array $data): Task
    {
        $project = Project::findOrFail($data['project_id']);
        Gate::authorize('update', $project);

        abort_if(collect($data['assignee_ids'] ?? [])->diff($project->members()->pluck('users.id'))->isNotEmpty(), 422, 'Assignees must be project members.');
        abort_if(collect($data['label_ids'] ?? [])->diff($project->labels()->pluck('id'))->isNotEmpty(), 422, 'Labels must belong to this project.');
        abort_if(($data['parent_task_id'] ?? null) && ! $project->tasks()->whereKey($data['parent_task_id'])->exists(), 422, 'Parent task must belong to this project.');
        abort_if(($data['parent_task_id'] ?? null) && Task::whereKey($data['parent_task_id'])->whereNotNull('parent_task_id')->exists(), 422, 'Nested subtasks are not allowed.');

        $task = DB::transaction(function () use ($data, $project, $creator) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $statusId = $data['status_id']
                ?? $project->statuses()->where('is_default', true)->value('id')
                ?? $project->statuses()->value('id');
            abort_unless($statusId && TaskStatus::where('id', $statusId)->where('project_id', $project->id)->exists(), 422, 'Invalid status for this project.');

            $task = Task::create([
                ...collect($data)->except(['assignee_ids', 'label_ids', 'status_id'])->all(),
                'status_id' => $statusId,
                'code' => $project->prefix.'-'.$project->next_task_number,
                'created_by' => $creator->id,
                'assignee_id' => $data['assignee_ids'][0] ?? null,
                'order' => Task::where('status_id', $statusId)->max('order') + 1,
            ]);
            $project->increment('next_task_number');
            $task->assignees()->sync(collect($data['assignee_ids'] ?? [])->mapWithKeys(fn ($id) => [$id => ['id' => (string) Str::uuid()]])->all());
            $task->labels()->sync($data['label_ids'] ?? []);
            TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $creator->id, 'action' => 'created']);

            return $task;
        });

        $task->assignees()->where('users.id', '!=', $creator->id)->get()
            ->each(fn ($assignee) => $assignee->notify(new TaskAssignedNotification($task)));

        SyncTaskCalendarEventsJob::dispatch($task);

        return $task;
    }
}
