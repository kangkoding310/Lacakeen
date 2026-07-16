<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);
        $validated = $request->validate(['comment' => ['required', 'string', 'max:5000']]);
        $task->comments()->create([...$validated, 'user_id' => $request->user()->id]);
        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'action' => 'commented']);

        return back()->with('success', 'Comment added.');
    }
}
