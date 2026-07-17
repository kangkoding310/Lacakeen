<?php

namespace App\Actions\Workflow;

use App\Models\Project;
use App\Models\Workflow;
use Illuminate\Support\Facades\Gate;

class CreateWorkflowAction
{
    public function handle(array $data): Workflow
    {
        $project = Project::findOrFail($data['project_id']);
        Gate::authorize('update', $project);

        return Workflow::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger' => ['type' => $data['trigger_type'], 'value' => $data['trigger_value'] ?? null],
            'actions' => [['type' => $data['action_type'], 'target' => $data['action_target'] ?? null]],
        ]);
    }
}
