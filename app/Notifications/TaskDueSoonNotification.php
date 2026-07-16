<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

    public function via(object $notifiable): array
    {
        $preferences = $notifiable->notification_preferences ?? [];

        return ($preferences['email_due_reminder'] ?? true) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Due soon: {$this->task->code}")
            ->line("{$this->task->title} is due {$this->task->due_date->format('M d, Y')}.")
            ->action('Review task', route('tasks.show', $this->task));
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => "{$this->task->code} is due soon", 'url' => route('tasks.show', $this->task, false), 'task_id' => $this->task->id];
    }
}
