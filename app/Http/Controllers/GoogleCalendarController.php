<?php

namespace App\Http\Controllers;

use App\Actions\Calendar\ConnectGoogleCalendarAction;
use App\Actions\Calendar\DisconnectGoogleCalendarAction;
use App\Services\GoogleCalendarClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    private const STATE_SESSION_KEY = 'google_calendar_oauth_state';

    public function connect(Request $request, GoogleCalendarClient $client): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put(self::STATE_SESSION_KEY, $state);

        return redirect()->away($client->getAuthUrl($state));
    }

    public function callback(Request $request, ConnectGoogleCalendarAction $action): RedirectResponse
    {
        $state = $request->session()->pull(self::STATE_SESSION_KEY);
        abort_if(! $state || $state !== $request->query('state'), 422, 'Invalid Google Calendar authorization state.');

        if ($request->query('error') || ! $request->query('code')) {
            return redirect()->route('integrations')->with('error', 'Google Calendar connection was not completed.');
        }

        $action->handle($request->user(), $request->query('code'));

        return redirect()->route('integrations')->with('success', 'Google Calendar connected.');
    }

    public function disconnect(Request $request, DisconnectGoogleCalendarAction $action): RedirectResponse
    {
        $action->handle($request->user());

        return back()->with('success', 'Google Calendar disconnected.');
    }
}
