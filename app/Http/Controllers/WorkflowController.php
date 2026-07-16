<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Workflow/Index', [
            'workflows' => Workflow::whereHas('project', fn ($projects) => $projects->visibleTo($request->user()))
                ->with(['project:id,name,color', 'logs' => fn ($query) => $query->limit(3)])
                ->withCount('logs')->latest()->get(),
            'projects' => Project::visibleTo($request->user())->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'], 'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'], 'trigger_type' => ['required', 'string', 'max:80'],
            'trigger_value' => ['nullable', 'string', 'max:255'], 'action_type' => ['required', 'string', 'max:80'],
            'action_target' => ['nullable', 'string', 'max:255'],
        ]);
        $this->authorize('update', Project::findOrFail($validated['project_id']));
        Workflow::create([
            'project_id' => $validated['project_id'], 'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'trigger' => ['type' => $validated['trigger_type'], 'value' => $validated['trigger_value'] ?? null],
            'actions' => [['type' => $validated['action_type'], 'target' => $validated['action_target'] ?? null]],
        ]);

        return back()->with('success', 'Workflow created.');
    }

    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow->project);
        $workflow->update($request->validate(['is_active' => ['required', 'boolean']]));

        return back()->with('success', 'Workflow updated.');
    }
}
