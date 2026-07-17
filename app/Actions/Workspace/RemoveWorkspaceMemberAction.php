<?php

namespace App\Actions\Workspace;

use App\Models\User;
use App\Models\Workspace;

class RemoveWorkspaceMemberAction
{
    public function handle(Workspace $workspace, User $user): void
    {
        abort_if($workspace->owner_id === $user->id, 422, 'The workspace owner cannot be removed.');

        $workspace->members()->detach($user->id);
    }
}
