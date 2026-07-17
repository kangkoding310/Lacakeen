<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoveTaskAction
{
    public function __construct(private readonly TriggerStatusChangeWorkflowsAction $triggerStatusChangeWorkflows) {}

    public function handle(Task $task, array $data, User $user): void
    {
        abort_unless(TaskStatus::where('id', $data['status_id'])->where('project_id', $task->project_id)->exists(), 422);

        DB::transaction(function () use ($data, $task, $user) {
            $from = $task->status_id;
            $task->update(['status_id' => $data['status_id'], 'order' => $data['order']]);

            foreach ($data['ordered_ids'] ?? [] as $order => $id) {
                Task::where('id', $id)->where('project_id', $task->project_id)->update(['status_id' => $data['status_id'], 'order' => $order]);
            }

            TaskActivityLog::create([
                'task_id' => $task->id, 'user_id' => $user->id, 'action' => 'moved',
                'meta' => ['from' => $from, 'to' => $data['status_id']],
            ]);

            $this->triggerStatusChangeWorkflows->handle($task, TaskStatus::find($data['status_id'])->name);
        });
    }
}
