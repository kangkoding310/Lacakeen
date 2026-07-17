<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;

class RemoveProjectMemberAction
{
    public function handle(Project $project, User $user): void
    {
        abort_if($project->created_by === $user->id, 422, 'The project creator cannot be removed.');

        $project->members()->detach($user->id);
    }
}
