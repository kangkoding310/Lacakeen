<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateProjectAction
{
    private const DEFAULT_STATUSES = [
        ['Not Started', '#64748B', true],
        ['Pending', '#F97316', false],
        ['Completed', '#22C55E', false],
        ['Under Review', '#A855F7', false],
    ];

    public function handle(User $creator, array $data): Project
    {
        $workspace = Workspace::findOrFail($data['workspace_id']);
        Gate::authorize('createProject', $workspace);

        return DB::transaction(function () use ($data, $creator) {
            $project = Project::create([...$data, 'created_by' => $creator->id]);
            $project->members()->attach($creator, ['id' => (string) Str::uuid(), 'role_in_project' => 'owner']);

            foreach (self::DEFAULT_STATUSES as $order => [$name, $color, $default]) {
                $project->statuses()->create(['name' => $name, 'color' => $color, 'order' => $order, 'is_default' => $default]);
            }

            return $project;
        });
    }
}
