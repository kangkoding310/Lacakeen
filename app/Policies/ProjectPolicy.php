<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->members()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        return $project->members()->where('users.id', $user->id)
            ->wherePivotIn('role_in_project', ['owner', 'editor'])->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->members()->where('users.id', $user->id)
            ->wherePivot('role_in_project', 'owner')->exists();
    }
}
