<?php

namespace App\Actions\Workspace;

use App\Models\User;
use App\Models\Workspace;

class UpdateWorkspaceMemberRoleAction
{
    public function handle(Workspace $workspace, User $user, array $data): void
    {
        abort_if($workspace->owner_id === $user->id, 422, 'The workspace owner must remain an owner.');
        abort_unless($workspace->members()->where('users.id', $user->id)->exists(), 404);

        $workspace->members()->updateExistingPivot($user->id, $data);
    }
}
