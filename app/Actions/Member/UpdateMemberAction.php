<?php

namespace App\Actions\Member;

use App\Models\User;

class UpdateMemberAction
{
    public function handle(User $actor, User $user, array $data): void
    {
        abort_if($actor->is($user) && ($data['status'] ?? null) === 'inactive', 422, 'You cannot deactivate yourself.');

        if ($data['role'] ?? null) {
            $user->syncRoles([$data['role']]);
        }
        if ($data['status'] ?? null) {
            $user->update(['status' => $data['status']]);
        }
    }
}
