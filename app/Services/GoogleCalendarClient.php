<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarClient
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    private const CALENDAR_BASE_URL = 'https://www.googleapis.com/calendar/v3';

    private const SCOPES = 'openid email https://www.googleapis.com/auth/calendar.events';

    public function getAuthUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        return Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ])->throw()->json();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        return Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $refreshToken,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
        ])->throw()->json();
    }

    public function revokeToken(string $token): void
    {
        try {
            Http::asForm()->post(self::REVOKE_URL, ['token' => $token]);
        } catch (Throwable $e) {
            Log::warning('Failed to revoke Google Calendar token.', ['error' => $e->getMessage()]);
        }
    }

    public function getUserEmail(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)->get(self::USERINFO_URL);

        return $response->successful() ? $response->json('email') : null;
    }

    public function insertEvent(string $accessToken, string $calendarId, array $event): array
    {
        return Http::withToken($accessToken)
            ->post(self::CALENDAR_BASE_URL.'/calendars/'.rawurlencode($calendarId).'/events', $event)
            ->throw()->json();
    }

    public function updateEvent(string $accessToken, string $calendarId, string $eventId, array $event): array
    {
        return Http::withToken($accessToken)
            ->patch(self::CALENDAR_BASE_URL.'/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($eventId), $event)
            ->throw()->json();
    }

    public function deleteEvent(string $accessToken, string $calendarId, string $eventId): void
    {
        $response = Http::withToken($accessToken)
            ->delete(self::CALENDAR_BASE_URL.'/calendars/'.rawurlencode($calendarId).'/events/'.rawurlencode($eventId));

        // Google returns 410 Gone for an already-deleted event and 404 if it never
        // existed; both mean the desired end state (no event) already holds.
        if (! $response->successful() && ! in_array($response->status(), [404, 410], true)) {
            $response->throw();
        }
    }
}
