<?php

namespace App\Http\Controllers;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\MoveTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Http\Requests\Task\MoveTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(StoreTaskRequest $request, CreateTaskAction $action): RedirectResponse
    {
        $task = $action->handle($request->user(), $request->validated());

        return back()->with('success', "{$task->code} created successfully.");
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        $task = $action->handle($task, $request->validated(), $request->user());

        return back()->with('success', "{$task->code} updated.");
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }

    public function move(MoveTaskRequest $request, Task $task, MoveTaskAction $action): RedirectResponse
    {
        $action->handle($task, $request->validated(), $request->user());

        return back()->with('success', 'Task moved.');
    }
}
