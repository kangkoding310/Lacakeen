<?php

namespace App\Jobs;

use App\Actions\Calendar\SyncTaskCalendarEventAction;
use App\Models\Task;
use App\Models\TaskCalendarEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTaskCalendarEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Task $task) {}

    public function handle(SyncTaskCalendarEventAction $action): void
    {
        $userIds = $this->task->assignees()->pluck('users.id')
            ->merge(TaskCalendarEvent::where('task_id', $this->task->id)->pluck('user_id'))
            ->unique();

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $action->handle($this->task, $user);
            }
        }
    }
}
