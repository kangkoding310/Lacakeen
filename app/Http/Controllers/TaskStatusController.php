<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskStatusController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $validated = $request->validate(['name' => ['required', 'string', 'max:80'], 'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        $project->statuses()->create([...$validated, 'color' => $validated['color'] ?? '#64748B', 'order' => $project->statuses()->max('order') + 1]);

        return back()->with('success', 'Column added.');
    }

    public function update(Request $request, TaskStatus $status): RedirectResponse
    {
        $this->authorize('update', $status->project);
        $status->update($request->validate(['name' => ['required', 'string', 'max:80'], 'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/']]));

        return back()->with('success', 'Column updated.');
    }

    public function destroy(TaskStatus $status): RedirectResponse
    {
        $this->authorize('update', $status->project);
        abort_if($status->tasks()->exists(), 422, 'Move tasks before deleting this column.');
        $status->delete();

        return back()->with('success', 'Column deleted.');
    }
}
