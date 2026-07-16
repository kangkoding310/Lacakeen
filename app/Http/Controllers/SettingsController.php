<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request, string $tab = 'profile'): Response
    {
        abort_unless(in_array($tab, ['profile', 'workspace', 'notifications', 'billing', 'security']), 404);

        return Inertia::render('Settings/Index', [
            'tab' => $tab,
            'workspace' => $request->user()->ownedWorkspaces()->first() ?? $request->user()->projects()->first()?->workspace,
        ]);
    }

    public function preferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_task_assigned' => ['boolean'], 'email_due_reminder' => ['boolean'], 'push_comments' => ['boolean'],
        ]);
        $request->user()->update(['notification_preferences' => $validated]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function workspace(Request $request): RedirectResponse
    {
        $workspace = $request->user()->ownedWorkspaces()->firstOrFail();
        $workspace->update($request->validate(['name' => ['required', 'string', 'max:255']]));

        return back()->with('success', 'Workspace updated.');
    }
}
