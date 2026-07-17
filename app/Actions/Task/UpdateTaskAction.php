<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Str;

class UpdateTaskAction
{
    public function handle(Task $task, array $data, User $user): Task
    {
        if (isset($data['status_id'])) {
            abort_unless(TaskStatus::where('id', $data['status_id'])->where('project_id', $task->project_id)->exists(), 422);
        }
        abort_if(collect($data['assignee_ids'] ?? [])->diff($task->project->members()->pluck('users.id'))->isNotEmpty(), 422, 'Assignees must be project members.');
        abort_if(collect($data['label_ids'] ?? [])->diff($task->project->labels()->pluck('id'))->isNotEmpty(), 422, 'Labels must belong to this project.');

        $task->update(collect($data)->except(['assignee_ids', 'label_ids'])->all());

        if (array_key_exists('assignee_ids', $data)) {
            $task->assignees()->sync(collect($data['assignee_ids'])->mapWithKeys(fn ($id) => [$id => ['id' => (string) Str::uuid()]])->all());
            $task->update(['assignee_id' => $data['assignee_ids'][0] ?? null]);
        }
        if (array_key_exists('label_ids', $data)) {
            $task->labels()->sync($data['label_ids']);
        }

        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $user->id, 'action' => 'updated', 'meta' => ['fields' => array_keys($data)]]);

        return $task;
    }
}
