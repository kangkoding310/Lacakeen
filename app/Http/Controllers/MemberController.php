<?php

namespace App\Http\Controllers;

use App\Actions\Member\InviteMemberAction;
use App\Actions\Member\UpdateMemberAction;
use App\Http\Requests\Member\InviteMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status']);
        $users = User::query()->with([
            'roles:id,name',
            'assignedTasks' => fn ($query) => $query->with(['status:id,name,color', 'project:id,name,color'])->latest('tasks.updated_at')->limit(5),
        ])->withCount([
            'assignedTasks as active_tasks_count' => fn ($query) => $query->whereHas('status', fn ($status) => $status->where('name', '!=', 'Completed')),
            'assignedTasks as completed_tasks_count' => fn ($query) => $query->whereHas('status', fn ($status) => $status->where('name', 'Completed')),
        ])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->role($role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Members/Index', ['members' => $users, 'filters' => $filters, 'roles' => Role::pluck('name')]);
    }

    public function invite(InviteMemberRequest $request, InviteMemberAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return back()->with('success', 'Invitation sent.');
    }

    public function update(UpdateMemberRequest $request, User $user, UpdateMemberAction $action): RedirectResponse
    {
        $action->handle($request->user(), $user, $request->validated());

        return back()->with('success', 'Member updated.');
    }
}
