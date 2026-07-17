<?php

namespace App\Jobs;

use App\Actions\Calendar\RefreshGoogleCalendarTokenAction;
use App\Models\GoogleCalendarAccount;
use App\Services\GoogleCalendarClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteTaskCalendarEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, array{user_id: int, google_event_id: string}>  $mappings
     */
    public function __construct(public readonly array $mappings) {}

    public function handle(GoogleCalendarClient $client, RefreshGoogleCalendarTokenAction $refreshToken): void
    {
        foreach ($this->mappings as $mapping) {
            $account = GoogleCalendarAccount::where('user_id', $mapping['user_id'])->first();
            if (! $account) {
                continue;
            }

            try {
                $accessToken = $refreshToken->handle($account);
                $client->deleteEvent($accessToken, $account->calendar_id, $mapping['google_event_id']);
            } catch (Throwable $e) {
                Log::warning('Failed to delete a Google Calendar event for a removed task.', [
                    'user_id' => $mapping['user_id'],
                    'google_event_id' => $mapping['google_event_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
