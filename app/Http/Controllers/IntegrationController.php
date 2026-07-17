<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function index(Request $request): Response
    {
        $account = $request->user()->googleCalendarAccount;
        $integrations = Integration::orderBy('name')->get()->map(function ($integration) use ($account) {
            if ($integration->type === 'google-calendar') {
                $integration->status = $account ? 'connected' : 'not_connected';
                $integration->connected_by = $account?->user_id;
                $integration->google_account_email = $account?->google_account_email;
            }

            return $integration;
        });

        return Inertia::render('Integrations/Index', ['integrations' => $integrations]);
    }

    public function update(Request $request, Integration $integration): RedirectResponse
    {
        abort_if($integration->type === 'google-calendar', 422, 'Use the Google Calendar connect flow to manage this integration.');
        $validated = $request->validate(['connected' => ['required', 'boolean']]);
        $integration->update([
            'status' => $validated['connected'] ? 'connected' : 'not_connected',
            'connected_by' => $validated['connected'] ? $request->user()->id : null,
        ]);

        return back()->with('success', "{$integration->name} connection updated.");
    }
}
