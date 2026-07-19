<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\User;
use App\Notifications\TaskMentionedNotification;

class AddTaskCommentAction
{
    public function handle(Task $task, array $data, User $author): void
    {
        $task->comments()->create([...$data, 'user_id' => $author->id]);

        TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $author->id, 'action' => 'commented']);

        $this->notifyMentionedUsers($task, $data['comment'] ?? '', $author);
    }

    private function notifyMentionedUsers(Task $task, string $commentHtml, User $author): void
    {
        preg_match_all('/data-id="(\d+)"/', $commentHtml, $matches);
        $mentionedIds = collect($matches[1])->unique()->reject(fn ($id) => (int) $id === $author->id);

        if ($mentionedIds->isEmpty()) {
            return;
        }

        User::whereIn('id', $mentionedIds)->get()
            ->each(fn (User $mentioned) => $mentioned->notify(new TaskMentionedNotification($task, $author)));
    }
}
