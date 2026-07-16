<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Integrations/Index', ['integrations' => Integration::orderBy('name')->get()]);
    }

    public function update(Request $request, Integration $integration): RedirectResponse
    {
        $validated = $request->validate(['connected' => ['required', 'boolean']]);
        $integration->update([
            'status' => $validated['connected'] ? 'connected' : 'not_connected',
            'connected_by' => $validated['connected'] ? $request->user()->id : null,
        ]);

        return back()->with('success', "{$integration->name} connection updated.");
    }
}
