<?php

namespace App\Http\Controllers;

use App\Actions\Workflow\CreateWorkflowAction;
use App\Http\Requests\Workflow\StoreWorkflowRequest;
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

    public function store(StoreWorkflowRequest $request, CreateWorkflowAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return back()->with('success', 'Workflow created.');
    }

    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow->project);
        $workflow->update($request->validate(['is_active' => ['required', 'boolean']]));

        return back()->with('success', 'Workflow updated.');
    }
}
