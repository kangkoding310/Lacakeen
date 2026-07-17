<?php

namespace App\Http\Controllers;

use App\Actions\Task\AddTaskCommentAction;
use App\Http\Requests\Task\StoreTaskCommentRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskCommentController extends Controller
{
    public function store(StoreTaskCommentRequest $request, Task $task, AddTaskCommentAction $action): RedirectResponse
    {
        $action->handle($task, $request->validated(), $request->user());

        return back()->with('success', 'Comment added.');
    }
}
