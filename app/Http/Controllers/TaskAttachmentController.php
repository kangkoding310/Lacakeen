<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $request->validate(['attachment' => ['required', 'file', 'max:10240']]);
        $file = $request->file('attachment');
        $task->attachments()->create([
            'file_path' => $file->store('task-attachments', 'public'),
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);
        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $request->user()->id, 'action' => 'attachment_added']);

        return back()->with('success', 'Attachment uploaded.');
    }
}
