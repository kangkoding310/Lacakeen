<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = Task::visibleTo($request->user())
            ->with('project:id,name,color')
            ->when($request->project, fn ($query, $id) => $query->where('project_id', $id))
            ->when($request->assignee, fn ($query, $id) => $query->whereHas('assignees', fn ($users) => $users->where('users.id', $id)))
            ->whereNotNull('due_date')->get()
            ->map(fn ($task) => [
                'id' => $task->id, 'title' => "{$task->code} · {$task->title}",
                'start' => $task->due_date->toDateString(), 'allDay' => true,
                'backgroundColor' => $task->project->color, 'extendedProps' => ['type' => 'task', 'projectId' => $task->project_id],
            ]);
        $manual = CalendarEvent::where('user_id', $request->user()->id)
            ->when($request->project, fn ($query, $id) => $query->where('project_id', $id))->get()->map(fn ($event) => [
                'id' => $event->id, 'title' => $event->title, 'start' => $event->start_at->toIso8601String(),
                'end' => $event->end_at?->toIso8601String(), 'allDay' => $event->all_day,
                'backgroundColor' => '#7C3AED', 'extendedProps' => ['type' => 'event', 'description' => $event->description],
            ]);

        return Inertia::render('Calendar/Index', [
            'events' => $tasks->concat($manual),
            'projects' => Project::visibleTo($request->user())->select('id', 'name', 'color')->get(),
            'members' => User::whereHas('projects', fn ($projects) => $projects->visibleTo($request->user()))->select('id', 'name', 'avatar')->get(),
            'filters' => $request->only(['project', 'assignee']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'], 'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'], 'all_day' => ['boolean'],
        ]);
        if ($validated['project_id'] ?? null) {
            $this->authorize('view', Project::findOrFail($validated['project_id']));
        }
        CalendarEvent::create([...$validated, 'user_id' => $request->user()->id]);

        return back()->with('success', 'Event created.');
    }
}
