<?php

namespace App\Http\Controllers;

use App\Actions\Project\AddProjectMemberAction;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\RemoveProjectMemberAction;
use App\Actions\Project\UpdateProjectMemberRoleAction;
use App\Http\Requests\Project\AddProjectMemberRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => Project::visibleTo($request->user())->withCount('tasks')->with('members:id,name,avatar')->latest()->get(),
            'workspace' => $request->user()->currentWorkspace(),
        ]);
    }

    public function store(StoreProjectRequest $request, CreateProjectAction $action): RedirectResponse
    {
        $project = $action->handle($request->user(), $request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()->whereNull('parent_task_id')->with([
            'project:id,name,color',
            'status:id,name,color',
            'assignees:id,name,avatar',
            'comments.user:id,name,avatar',
            'attachments',
            'activityLogs.user:id,name,avatar',
            'labels:id,name,color',
            'subtasks.status:id,name,color',
            'subtasks.assignees:id,name,avatar',
        ])->orderBy('order')->get();

        $resProject = $project
            ->load([
                'members' => fn($query) => $query
                    ->select('users.id', 'users.name', 'users.email', 'users.avatar')
                    ->orderByPivot('created_at', 'asc'),

                'statuses' => fn($query) => $query->withCount('tasks'),
            ])
            ->loadCount('tasks');

        return Inertia::render('Projects/Show', [
            'project' => $resProject,
            'statuses' => $project->statuses->map(fn($status) => [
                ...$status->toArray(),
                'tasks' => $tasks->where('status_id', $status->id)->values(),
            ]),
            'tasks' => $tasks,
            'events' => $tasks->whereNotNull('due_date')->map(fn($task) => [
                'id' => $task->id,
                'title' => "{$task->code} · {$task->title}",
                'start' => $task->due_date->toDateString(),
                'allDay' => true,
                'backgroundColor' => $project->color,
                'extendedProps' => ['type' => 'task'],
            ])->values(),
            'tab' => $request->string('tab')->value() ?: 'summary',
            'availableMembers' => User::where('status', 'active')->whereNotIn('id', $project->members()->pluck('users.id'))
                ->select('id', 'name', 'email', 'avatar')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'prefix' => ['sometimes', 'required', 'alpha_num:ascii', 'max:12', Rule::unique('projects')->where('workspace_id', $project->workspace_id)->ignore($project)],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ]);
        if (isset($validated['prefix'])) {
            $validated['prefix'] = strtoupper($validated['prefix']);
        }
        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function addMember(AddProjectMemberRequest $request, Project $project, AddProjectMemberAction $action): RedirectResponse
    {
        $action->handle($project, $request->validated());

        return back()->with('success', 'Project member added.');
    }

    public function updateMember(UpdateProjectMemberRequest $request, Project $project, User $user, UpdateProjectMemberRoleAction $action): RedirectResponse
    {
        $action->handle($project, $user, $request->validated());

        return back()->with('success', 'Project role updated.');
    }

    public function removeMember(Project $project, User $user, RemoveProjectMemberAction $action): RedirectResponse
    {
        $this->authorize('update', $project);
        $action->handle($project, $user);

        return back()->with('success', 'Project member removed.');
    }
}
