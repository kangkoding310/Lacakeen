<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task, private readonly User $author) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->author->name} mentioned you in {$this->task->code}",
            'url' => route('tasks.show', $this->task, false),
            'task_id' => $this->task->id,
            'category' => 'mention',
        ];
    }
}
