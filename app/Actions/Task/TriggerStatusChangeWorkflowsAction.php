<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Notifications\DemoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TriggerStatusChangeWorkflowsAction
{
    public function handle(Task $task, string $newStatusName): void
    {
        $task->project->workflows()->where('is_active', true)->get()
            ->filter(fn ($workflow) => ($workflow->trigger['type'] ?? null) === 'status_changed'
                && (! ($workflow->trigger['value'] ?? null) || $workflow->trigger['value'] === $newStatusName))
            ->each(function ($workflow) use ($task, $newStatusName) {
                DB::table('workflow_logs')->insert([
                    'id' => Str::uuid(), 'workflow_id' => $workflow->id, 'task_id' => $task->id,
                    'status' => 'completed', 'meta' => json_encode(['new_status' => $newStatusName]),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                if (($workflow->actions[0]['type'] ?? null) === 'notify') {
                    $task->project->members()->role('project_manager')->get()->each(
                        fn ($user) => $user->notify(new DemoNotification("Workflow ran for {$task->code}", route('tasks.show', $task, false)))
                    );
                }
            });
    }
}
