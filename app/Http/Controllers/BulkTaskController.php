<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteTaskCalendarEventsJob;
use App\Jobs\SyncTaskCalendarEventsJob;
use App\Models\Task;
use App\Models\TaskCalendarEvent;
use App\Models\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BulkTaskController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'], 'task_ids.*' => ['uuid', 'exists:tasks,id'],
            'action' => ['required', Rule::in(['status', 'assign', 'delete'])],
            'status_id' => ['nullable', 'uuid', 'exists:task_statuses,id'], 'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $tasks = Task::whereIn('id', $validated['task_ids'])->get();
        $tasks->each(fn ($task) => $this->authorize($validated['action'] === 'delete' ? 'delete' : 'update', $task));

        $deletedMappings = [];

        DB::transaction(function () use ($validated, $tasks, &$deletedMappings) {
            foreach ($tasks as $task) {
                if ($validated['action'] === 'delete') {
                    $deletedMappings = array_merge($deletedMappings, TaskCalendarEvent::where('task_id', $task->id)
                        ->get(['user_id', 'google_event_id'])->toArray());
                    $task->delete();
                } elseif ($validated['action'] === 'status') {
                    abort_unless(TaskStatus::whereKey($validated['status_id'])->where('project_id', $task->project_id)->exists(), 422, 'Status must belong to each selected task project.');
                    $task->update(['status_id' => $validated['status_id']]);
                } else {
                    abort_unless($task->project->members()->where('users.id', $validated['assignee_id'])->exists(), 422, 'Assignee must be a project member.');
                    $task->update(['assignee_id' => $validated['assignee_id']]);
                    $task->assignees()->syncWithoutDetaching([$validated['assignee_id'] => ['id' => (string) Str::uuid()]]);
                }
            }
        });

        if ($deletedMappings) {
            DeleteTaskCalendarEventsJob::dispatch($deletedMappings);
        }
        if ($validated['action'] === 'assign') {
            $tasks->each(fn ($task) => SyncTaskCalendarEventsJob::dispatch($task));
        }

        return back()->with('success', count($validated['task_ids']).' tasks updated.');
    }
}
