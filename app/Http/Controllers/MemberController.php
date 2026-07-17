<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\MemberInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function invite(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage users'), 403);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);
        $token = Str::random(64);
        DB::table('member_invitations')->insert([
            'id' => Str::uuid(), ...$validated, 'token' => hash('sha256', $token),
            'invited_by' => $request->user()->id, 'expires_at' => now()->addDays(7),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Notification::route('mail', $validated['email'])->notify(new MemberInvitationNotification($token));

        return back()->with('success', 'Invitation sent.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('manage users'), 403);
        abort_if($request->user()->is($user) && $request->status === 'inactive', 422, 'You cannot deactivate yourself.');
        $validated = $request->validate([
            'role' => ['sometimes', Rule::exists('roles', 'name')],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
        if ($validated['role'] ?? null) {
            $user->syncRoles([$validated['role']]);
        }
        if ($validated['status'] ?? null) {
            $user->update(['status' => $validated['status']]);
        }

        return back()->with('success', 'Member updated.');
    }
}
