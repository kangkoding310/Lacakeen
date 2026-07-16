<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tasks = Task::visibleTo($request->user())
            ->with(['status:id,name', 'assignees:id,name,avatar'])
            ->when($request->project, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->from, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->to, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->get();

        return Inertia::render('Reporting/Index', [
            'projects' => Project::visibleTo($request->user())->select('id', 'name')->get(),
            'filters' => $request->only(['project', 'from', 'to']),
            'analytics' => [
                'byStatus' => $tasks->groupBy('status.name')->map->count(),
                'byPriority' => $tasks->groupBy('priority')->map->count(),
                'completion' => $tasks->groupBy(fn ($task) => $task->updated_at->format('Y-m-d'))
                    ->map(fn ($items) => $items->where('status.name', 'Completed')->count()),
                'members' => $tasks->flatMap->assignees->groupBy('id')->map(fn ($users) => [
                    'name' => $users->first()->name,
                    'avatar' => $users->first()->avatar,
                    'tasks' => $tasks->filter(fn ($task) => $task->assignees->contains('id', $users->first()->id))->count(),
                    'completed' => $tasks->filter(fn ($task) => $task->status->name === 'Completed' && $task->assignees->contains('id', $users->first()->id))->count(),
                ])->values(),
            ],
        ]);
    }
}
