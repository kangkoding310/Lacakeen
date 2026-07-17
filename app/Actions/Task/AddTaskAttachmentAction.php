<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class AddTaskAttachmentAction
{
    public function handle(Task $task, UploadedFile $file, User $uploader): void
    {
        $task->attachments()->create([
            'file_path' => $file->store('task-attachments', 'public'),
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $uploader->id,
        ]);

        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $uploader->id, 'action' => 'attachment_added']);
    }
}
