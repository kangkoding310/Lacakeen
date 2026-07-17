<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;

class UpdateProjectMemberRoleAction
{
    public function handle(Project $project, User $user, array $data): void
    {
        abort_if($project->created_by === $user->id, 422, 'The project creator must remain an owner.');
        abort_unless($project->members()->where('users.id', $user->id)->exists(), 404);

        $project->members()->updateExistingPivot($user->id, $data);
    }
}
