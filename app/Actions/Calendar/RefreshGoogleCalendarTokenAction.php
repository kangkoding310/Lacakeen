<?php

namespace App\Actions\Calendar;

use App\Models\GoogleCalendarAccount;
use App\Services\GoogleCalendarClient;

class RefreshGoogleCalendarTokenAction
{
    public function __construct(private readonly GoogleCalendarClient $client) {}

    public function handle(GoogleCalendarAccount $account): string
    {
        if (! $account->isExpired()) {
            return $account->access_token;
        }

        abort_unless($account->refresh_token, 422, 'Google Calendar connection needs to be reconnected.');

        $token = $this->client->refreshAccessToken($account->refresh_token);
        $account->update([
            'access_token' => $token['access_token'],
            'token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
        ]);

        return $account->access_token;
    }
}
