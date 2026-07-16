<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskStatus;
use App\Models\User;
use App\Notifications\DemoNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'project', 'status', 'priority', 'assignee']);
        $tasks = Task::visibleTo($request->user())
            ->with(['project:id,name,color', 'status:id,name,color', 'assignees:id,name,email,avatar'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")
            ))
            ->when($filters['project'] ?? null, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $id) => $query->where('status_id', $id))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['assignee'] ?? null, fn ($query, $id) => $query->whereHas('assignees', fn ($users) => $users->where('users.id', $id)))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $projects = Project::visibleTo($request->user())->with('statuses:id,project_id,name,color')->orderBy('name')->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'members' => $request->user()->hasRole('admin')
                ? User::select('id', 'name', 'email', 'avatar')->orderBy('name')->get()
                : $request->user()->projects()->first()?->members()->select('users.id', 'name', 'email', 'avatar')->get() ?? collect(),
            'filters' => $filters,
        ]);
    }

    public function show(Task $task): Response
    {
        $this->authorize('view', $task);

        return Inertia::render('Tasks/Show', [
            'task' => $task->load([
                'project:id,name,color', 'status:id,name,color', 'assignees:id,name,email,avatar',
                'labels:id,name,color', 'comments.user:id,name,avatar', 'attachments',
                'activityLogs.user:id,name,avatar', 'subtasks.status:id,name,color', 'subtasks.assignees:id,name,avatar',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'status_id' => ['nullable', 'uuid', 'exists:task_statuses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'parent_task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'assignee_ids' => ['array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
            'label_ids' => ['array'],
            'label_ids.*' => ['uuid', 'exists:task_labels,id'],
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('update', $project);
        abort_if(collect($validated['assignee_ids'] ?? [])->diff($project->members()->pluck('users.id'))->isNotEmpty(), 422, 'Assignees must be project members.');
        abort_if(collect($validated['label_ids'] ?? [])->diff($project->labels()->pluck('id'))->isNotEmpty(), 422, 'Labels must belong to this project.');
        abort_if(($validated['parent_task_id'] ?? null) && ! $project->tasks()->whereKey($validated['parent_task_id'])->exists(), 422, 'Parent task must belong to this project.');
        abort_if(($validated['parent_task_id'] ?? null) && Task::whereKey($validated['parent_task_id'])->whereNotNull('parent_task_id')->exists(), 422, 'Nested subtasks are not allowed.');

        $task = DB::transaction(function () use ($validated, $project, $request) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $statusId = $validated['status_id'] ?? $project->statuses()->where('is_default', true)->value('id') ?? $project->statuses()->value('id');
            abort_unless($statusId && TaskStatus::where('id', $statusId)->where('project_id', $project->id)->exists(), 422, 'Invalid status for this project.');

            $task = Task::create([
                ...collect($validated)->except(['assignee_ids', 'label_ids', 'status_id'])->all(),
                'status_id' => $statusId,
                'code' => $project->prefix.'-'.$project->next_task_number,
                'created_by' => $request->user()->id,
                'assignee_id' => $validated['assignee_ids'][0] ?? null,
                'order' => Task::where('status_id', $statusId)->max('order') + 1,
            ]);
            $project->increment('next_task_number');
            $task->assignees()->sync(collect($validated['assignee_ids'] ?? [])->mapWithKeys(fn ($id) => [$id => ['id' => (string) Str::uuid()]])->all());
            $task->labels()->sync($validated['label_ids'] ?? []);
            TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'action' => 'created']);

            return $task;
        });

        $task->assignees()->where('users.id', '!=', $request->user()->id)->get()
            ->each(fn ($user) => $user->notify(new TaskAssignedNotification($task)));

        return back()->with('success', "{$task->code} created successfully.");
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => ['sometimes', 'uuid', 'exists:task_statuses,id'],
            'priority' => ['sometimes', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['uuid', 'exists:task_labels,id'],
        ]);
        if (isset($validated['status_id'])) {
            abort_unless(TaskStatus::where('id', $validated['status_id'])->where('project_id', $task->project_id)->exists(), 422);
        }
        abort_if(collect($validated['assignee_ids'] ?? [])->diff($task->project->members()->pluck('users.id'))->isNotEmpty(), 422, 'Assignees must be project members.');
        abort_if(collect($validated['label_ids'] ?? [])->diff($task->project->labels()->pluck('id'))->isNotEmpty(), 422, 'Labels must belong to this project.');

        $task->update(collect($validated)->except(['assignee_ids', 'label_ids'])->all());
        if (array_key_exists('assignee_ids', $validated)) {
            $task->assignees()->sync(collect($validated['assignee_ids'])->mapWithKeys(fn ($id) => [$id => ['id' => (string) Str::uuid()]])->all());
            $task->update(['assignee_id' => $validated['assignee_ids'][0] ?? null]);
        }
        if (array_key_exists('label_ids', $validated)) {
            $task->labels()->sync($validated['label_ids']);
        }
        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'action' => 'updated', 'meta' => ['fields' => array_keys($validated)]]);

        return back()->with('success', "{$task->code} updated.");
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    public function move(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $validated = $request->validate([
            'status_id' => ['required', 'uuid', 'exists:task_statuses,id'],
            'order' => ['required', 'integer', 'min:0'],
            'ordered_ids' => ['sometimes', 'array'],
            'ordered_ids.*' => ['uuid', 'exists:tasks,id'],
        ]);
        abort_unless(TaskStatus::where('id', $validated['status_id'])->where('project_id', $task->project_id)->exists(), 422);

        DB::transaction(function () use ($validated, $task, $request) {
            $from = $task->status_id;
            $task->update(['status_id' => $validated['status_id'], 'order' => $validated['order']]);
            foreach ($validated['ordered_ids'] ?? [] as $order => $id) {
                Task::where('id', $id)->where('project_id', $task->project_id)->update(['status_id' => $validated['status_id'], 'order' => $order]);
            }
            TaskActivityLog::create([
                'task_id' => $task->id, 'user_id' => $request->user()->id, 'action' => 'moved',
                'meta' => ['from' => $from, 'to' => $validated['status_id']],
            ]);

            $statusName = TaskStatus::find($validated['status_id'])->name;
            $task->project->workflows()->where('is_active', true)->get()
                ->filter(fn ($workflow) => ($workflow->trigger['type'] ?? null) === 'status_changed'
                    && (! ($workflow->trigger['value'] ?? null) || $workflow->trigger['value'] === $statusName))
                ->each(function ($workflow) use ($task, $statusName) {
                    DB::table('workflow_logs')->insert([
                        'id' => Str::uuid(), 'workflow_id' => $workflow->id, 'task_id' => $task->id,
                        'status' => 'completed', 'meta' => json_encode(['new_status' => $statusName]),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if (($workflow->actions[0]['type'] ?? null) === 'notify') {
                        $task->project->members()->role('project_manager')->get()->each(
                            fn ($user) => $user->notify(new DemoNotification("Workflow ran for {$task->code}", route('tasks.show', $task, false)))
                        );
                    }
                });
        });

        return back()->with('success', 'Task moved.');
    }
}
