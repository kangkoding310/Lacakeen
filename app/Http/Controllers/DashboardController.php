<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projects = Project::visibleTo($request->user())->where('status', 'active')->orderBy('name')->get();
        $project = $projects->firstWhere('id', $request->string('project')->toString()) ?? $projects->first();
        $tasks = Task::visibleTo($request->user());
        $taskCount = (clone $tasks)->count();
        $completedCount = (clone $tasks)->whereHas('status', fn ($query) => $query->where('name', 'Completed'))->count();

        $statuses = $project?->statuses()
            ->with(['tasks' => fn ($query) => $query
                ->with([
                    'project:id,name,color', 'status:id,name,color', 'assignees:id,name,email,avatar', 'labels:id,name,color',
                    'comments.user:id,name,avatar', 'attachments', 'activityLogs.user:id,name,avatar',
                ])
                ->withCount('comments')
                ->orderBy('order')])
            ->get() ?? collect();

        return Inertia::render('Dashboard', [
            'stats' => [
                'activeEmployees' => User::where('status', 'active')->count(),
                'activeProjects' => $projects->count(),
                'tasks' => $taskCount,
                'accomplished' => $taskCount ? round(($completedCount / $taskCount) * 100, 1) : 0,
            ],
            'projects' => $projects,
            'selectedProject' => $project,
            'statuses' => $statuses,
            'members' => $project?->members()->select('users.id', 'name', 'email', 'avatar')->get() ?? collect(),
        ]);
    }
}
