<?php

namespace App\Actions\Calendar;

use App\Jobs\SyncUserAssignedTasksToGoogleCalendarJob;
use App\Models\GoogleCalendarAccount;
use App\Models\User;
use App\Services\GoogleCalendarClient;

class ConnectGoogleCalendarAction
{
    public function __construct(private readonly GoogleCalendarClient $client) {}

    public function handle(User $user, string $code): GoogleCalendarAccount
    {
        $token = $this->client->exchangeCodeForToken($code);
        $email = $this->client->getUserEmail($token['access_token']);
        $existing = GoogleCalendarAccount::where('user_id', $user->id)->first();

        $account = GoogleCalendarAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_account_email' => $email,
                'access_token' => $token['access_token'],
                // Google only returns a refresh_token on the consent Google actually showed;
                // keep the previous one if a reconnect didn't get a fresh one back.
                'refresh_token' => $token['refresh_token'] ?? $existing?->refresh_token,
                'token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
            ]
        );

        SyncUserAssignedTasksToGoogleCalendarJob::dispatch($user);

        return $account;
    }
}
