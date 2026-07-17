<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'prefix' => ['required', 'alpha_num:ascii', 'max:12'],
            'description' => ['nullable', 'string'], 'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'workspace_id' => ['required', 'uuid', 'exists:workspaces,id'],
        ]);
        abort_unless($request->user()->ownedWorkspaces()->whereKey($validated['workspace_id'])->exists() || $request->user()->hasRole('admin'), 403);
        $validated['prefix'] = strtoupper($validated['prefix']);
        $request->validate(['prefix' => [Rule::unique('projects')->where('workspace_id', $validated['workspace_id'])]]);

        $project = DB::transaction(function () use ($validated, $request) {
            $project = Project::create([...$validated, 'created_by' => $request->user()->id]);
            $project->members()->attach($request->user(), ['id' => (string) Str::uuid(), 'role_in_project' => 'owner']);
            foreach ([
                ['Not Started', '#64748B', true], ['Pending', '#F97316', false],
                ['Completed', '#22C55E', false], ['Under Review', '#A855F7', false],
            ] as $order => [$name, $color, $default]) {
                $project->statuses()->create(['name' => $name, 'color' => $color, 'order' => $order, 'is_default' => $default]);
            }

            return $project;
        });

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()->whereNull('parent_task_id')->with([
            'project:id,name,color', 'status:id,name,color', 'assignees:id,name,avatar', 'comments.user:id,name,avatar',
            'attachments', 'activityLogs.user:id,name,avatar', 'labels:id,name,color',
            'subtasks.status:id,name,color', 'subtasks.assignees:id,name,avatar',
        ])->orderBy('order')->get();

        return Inertia::render('Projects/Show', [
            'project' => $project->load(['members:id,name,email,avatar', 'statuses' => fn ($query) => $query->withCount('tasks')])->loadCount('tasks'),
            'statuses' => $project->statuses->map(fn ($status) => [
                ...$status->toArray(), 'tasks' => $tasks->where('status_id', $status->id)->values(),
            ]),
            'tasks' => $tasks,
            'events' => $tasks->whereNotNull('due_date')->map(fn ($task) => [
                'id' => $task->id, 'title' => "{$task->code} · {$task->title}",
                'start' => $task->due_date->toDateString(), 'allDay' => true,
                'backgroundColor' => $project->color, 'extendedProps' => ['type' => 'task'],
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

    public function addMember(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_in_project' => ['required', Rule::in(['editor', 'viewer'])],
        ]);
        $project->members()->syncWithoutDetaching([
            $validated['user_id'] => ['id' => (string) Str::uuid(), 'role_in_project' => $validated['role_in_project']],
        ]);

        return back()->with('success', 'Project member added.');
    }

    public function updateMember(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($project->created_by === $user->id, 422, 'The project creator must remain an owner.');
        abort_unless($project->members()->where('users.id', $user->id)->exists(), 404);
        $validated = $request->validate(['role_in_project' => ['required', Rule::in(['owner', 'editor', 'viewer'])]]);
        $project->members()->updateExistingPivot($user->id, $validated);

        return back()->with('success', 'Project role updated.');
    }

    public function removeMember(Project $project, User $user): RedirectResponse
    {
        $this->authorize('update', $project);
        abort_if($project->created_by === $user->id, 422, 'The project creator cannot be removed.');
        $project->members()->detach($user->id);

        return back()->with('success', 'Project member removed.');
    }
}
