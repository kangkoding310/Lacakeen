<?php

namespace App\Http\Controllers;

use App\Actions\Task\AddTaskAttachmentAction;
use App\Http\Requests\Task\StoreTaskAttachmentRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskAttachmentController extends Controller
{
    public function store(StoreTaskAttachmentRequest $request, Task $task, AddTaskAttachmentAction $action): RedirectResponse
    {
        $action->handle($task, $request->file('attachment'), $request->user());

        return back()->with('success', 'Attachment uploaded.');
    }
}
