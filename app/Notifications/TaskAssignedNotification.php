<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task assigned: {$this->task->code}")
            ->line("You have been assigned to {$this->task->title}.")
            ->action('Open task', route('tasks.show', $this->task));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "You were assigned to {$this->task->code}",
            'url' => route('tasks.show', $this->task, false),
            'task_id' => $this->task->id,
        ];
    }
}
