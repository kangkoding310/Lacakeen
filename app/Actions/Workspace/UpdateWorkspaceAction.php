<?php

namespace App\Actions\Workspace;

use App\Models\Workspace;

class UpdateWorkspaceAction
{
    public function handle(Workspace $workspace, array $data): Workspace
    {
        $workspace->update($data);

        return $workspace;
    }
}
