<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->members()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->members()->where('users.id', $user->id)->wherePivot('role', 'admin')->exists();
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }
}
