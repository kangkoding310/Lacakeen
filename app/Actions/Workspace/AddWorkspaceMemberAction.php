<?php

namespace App\Actions\Workspace;

use App\Models\Workspace;
use Illuminate\Support\Str;

class AddWorkspaceMemberAction
{
    public function handle(Workspace $workspace, array $data): void
    {
        $workspace->members()->syncWithoutDetaching([
            $data['user_id'] => ['id' => (string) Str::uuid(), 'role' => $data['role']],
        ]);
    }
}
