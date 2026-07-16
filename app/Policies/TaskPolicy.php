<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $task->project->members()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Task $task): bool
    {
        return $task->project->members()->where('users.id', $user->id)
            ->wherePivotIn('role_in_project', ['owner', 'editor'])->exists();
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
