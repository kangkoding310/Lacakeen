<?php

namespace App\Actions\Calendar;

use App\Models\TaskCalendarEvent;
use App\Models\User;
use App\Services\GoogleCalendarClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisconnectGoogleCalendarAction
{
    public function __construct(
        private readonly GoogleCalendarClient $client,
        private readonly RefreshGoogleCalendarTokenAction $refreshToken,
    ) {}

    public function handle(User $user): void
    {
        $account = $user->googleCalendarAccount;
        if (! $account) {
            return;
        }

        $mappings = TaskCalendarEvent::where('user_id', $user->id)->get();

        if ($mappings->isNotEmpty()) {
            try {
                $accessToken = $this->refreshToken->handle($account);

                foreach ($mappings as $mapping) {
                    try {
                        $this->client->deleteEvent($accessToken, $account->calendar_id, $mapping->google_event_id);
                    } catch (Throwable $e) {
                        Log::warning('Failed to delete a Google Calendar event on disconnect.', [
                            'user_id' => $user->id,
                            'google_event_id' => $mapping->google_event_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Could not refresh Google Calendar token to clean up events on disconnect.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        TaskCalendarEvent::where('user_id', $user->id)->delete();
        $this->client->revokeToken($account->refresh_token ?? $account->access_token);
        $account->delete();
    }
}
