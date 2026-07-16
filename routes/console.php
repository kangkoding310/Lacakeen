<?php

use App\Models\Task;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Task::with('assignees')
        ->whereDate('due_date', now()->addDay()->toDateString())
        ->whereHas('status', fn ($query) => $query->where('name', '!=', 'Completed'))
        ->chunkById(100, fn ($tasks) => $tasks->each(
            fn ($task) => $task->assignees->each(fn ($user) => $user->notify(new TaskDueSoonNotification($task)))
        ));
})->dailyAt('08:00')->name('task-due-reminders')->withoutOverlapping();
