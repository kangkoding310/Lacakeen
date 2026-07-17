<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Str;

class AddProjectMemberAction
{
    public function handle(Project $project, array $data): void
    {
        $project->members()->syncWithoutDetaching([
            $data['user_id'] => ['id' => (string) Str::uuid(), 'role_in_project' => $data['role_in_project']],
        ]);
    }
}
