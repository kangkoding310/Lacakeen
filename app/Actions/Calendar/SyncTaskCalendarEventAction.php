<?php

namespace App\Actions\Calendar;

use App\Models\Task;
use App\Models\TaskCalendarEvent;
use App\Models\User;
use App\Services\GoogleCalendarClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTaskCalendarEventAction
{
    public function __construct(
        private readonly GoogleCalendarClient $client,
        private readonly RefreshGoogleCalendarTokenAction $refreshToken,
    ) {}

    public function handle(Task $task, User $user): void
    {
        $account = $user->googleCalendarAccount;
        if (! $account) {
            return;
        }

        $mapping = TaskCalendarEvent::where('task_id', $task->id)->where('user_id', $user->id)->first();
        $isAssignee = $task->assignees()->where('users.id', $user->id)->exists();
        $shouldHaveEvent = $isAssignee && $task->due_date !== null;

        try {
            $accessToken = $this->refreshToken->handle($account);

            if (! $shouldHaveEvent) {
                if ($mapping) {
                    $this->client->deleteEvent($accessToken, $account->calendar_id, $mapping->google_event_id);
                    $mapping->delete();
                }

                return;
            }

            $payload = $this->buildEventPayload($task);

            if ($mapping) {
                $this->client->updateEvent($accessToken, $account->calendar_id, $mapping->google_event_id, $payload);
            } else {
                $event = $this->client->insertEvent($accessToken, $account->calendar_id, $payload);
                TaskCalendarEvent::create([
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'google_event_id' => $event['id'],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to sync task to Google Calendar.', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildEventPayload(Task $task): array
    {
        return [
            'summary' => "{$task->code} · {$task->title}",
            'description' => $task->description ?? '',
            'start' => ['date' => $task->due_date->toDateString()],
            'end' => ['date' => $task->due_date->copy()->addDay()->toDateString()],
            'source' => [
                'title' => 'Lacakeen',
                'url' => route('tasks.show', $task),
            ],
        ];
    }
}
