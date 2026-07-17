<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\User;

class AddTaskCommentAction
{
    public function handle(Task $task, array $data, User $author): void
    {
        $task->comments()->create([...$data, 'user_id' => $author->id]);

        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $author->id, 'action' => 'commented']);
    }
}
