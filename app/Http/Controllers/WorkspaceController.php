<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\AddWorkspaceMemberAction;
use App\Actions\Workspace\RemoveWorkspaceMemberAction;
use App\Actions\Workspace\UpdateWorkspaceAction;
use App\Actions\Workspace\UpdateWorkspaceMemberRoleAction;
use App\Http\Requests\Workspace\AddWorkspaceMemberRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceMemberRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function redirectToMine(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace();
        abort_if(! $workspace, 404);

        return redirect()->route('workspaces.show', $workspace);
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Workspace::class);

        return Inertia::render('Workspaces/Index', [
            'workspaces' => Workspace::with('owner:id,name,email,avatar')
                ->withCount(['members', 'projects'])->orderBy('name')->get(),
            'availableOwners' => User::where('status', 'active')->select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Workspace::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $workspace = Workspace::create($validated);

        return redirect()->route('workspaces.show', $workspace)->with('success', 'Workspace created.');
    }

    public function show(Workspace $workspace): Response
    {
        $this->authorize('view', $workspace);

        return Inertia::render('Workspaces/Show', [
            'workspace' => $workspace->load(['owner:id,name,email,avatar', 'members:id,name,email,avatar'])
                ->loadCount('projects'),
            'availableMembers' => User::where('status', 'active')
                ->where('id', '!=', $workspace->owner_id)
                ->whereNotIn('id', $workspace->members()->pluck('users.id'))
                ->select('id', 'name', 'email', 'avatar')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspaceAction $action): RedirectResponse
    {
        $action->handle($workspace, $request->validated());

        return back()->with('success', 'Workspace updated.');
    }

    public function destroy(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('delete', $workspace);
        $workspace->delete();

        return redirect()
            ->route($request->user()->hasRole('admin') ? 'workspaces.index' : 'dashboard')
            ->with('success', 'Workspace deleted.');
    }

    public function addMember(AddWorkspaceMemberRequest $request, Workspace $workspace, AddWorkspaceMemberAction $action): RedirectResponse
    {
        $action->handle($workspace, $request->validated());

        return back()->with('success', 'Workspace member added.');
    }

    public function updateMember(UpdateWorkspaceMemberRequest $request, Workspace $workspace, User $user, UpdateWorkspaceMemberRoleAction $action): RedirectResponse
    {
        $action->handle($workspace, $user, $request->validated());

        return back()->with('success', 'Workspace role updated.');
    }

    public function removeMember(Workspace $workspace, User $user, RemoveWorkspaceMemberAction $action): RedirectResponse
    {
        $this->authorize('update', $workspace);
        $action->handle($workspace, $user);

        return back()->with('success', 'Workspace member removed.');
    }
}
