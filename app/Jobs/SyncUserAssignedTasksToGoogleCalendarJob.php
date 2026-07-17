<?php

namespace App\Jobs;

use App\Actions\Calendar\SyncTaskCalendarEventAction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUserAssignedTasksToGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function handle(SyncTaskCalendarEventAction $action): void
    {
        $this->user->assignedTasks()->whereNotNull('due_date')->get()
            ->each(fn ($task) => $action->handle($task, $this->user));
    }
}
