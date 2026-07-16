<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', ['invite' => $request->query('invite')]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invite' => ['nullable', 'string'],
        ]);

        $invitation = $request->invite ? DB::table('member_invitations')
            ->where('token', hash('sha256', $request->invite))
            ->whereNull('accepted_at')->where('expires_at', '>', now())->first() : null;
        if ($request->invite && (! $invitation || strtolower($invitation->email) !== strtolower($request->email))) {
            throw ValidationException::withMessages(['email' => 'This invitation is invalid, expired, or belongs to another email address.']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        if ($invitation) {
            $user->assignRole($invitation->role);
            DB::table('member_invitations')->where('id', $invitation->id)->update(['accepted_at' => now(), 'updated_at' => now()]);
        } elseif (Role::where('name', 'member')->exists()) {
            $user->assignRole('member');
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
