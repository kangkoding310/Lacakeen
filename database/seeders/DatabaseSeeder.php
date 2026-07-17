<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\Integration;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workflow;
use App\Models\Workspace;
use App\Notifications\DemoNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            'manage users', 'manage projects', 'manage tasks', 'view reports', 'manage workflows',
        ])->mapWithKeys(fn ($name) => [$name => Permission::firstOrCreate(['name' => $name])]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'project_manager']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        Role::firstOrCreate(['name' => 'viewer']);
        $adminRole->syncPermissions($permissions);
        $managerRole->syncPermissions($permissions->except('manage users'));
        $memberRole->syncPermissions(['manage tasks']);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@lacakeen.test',
            'job_title' => 'Admin',
            'avatar' => null,
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole($adminRole);

        // $members = User::factory(7)->create()->each(fn (User $user) => $user->assignRole(
        //     fake()->randomElement([$managerRole, $memberRole, $memberRole])
        // ));
        // $users = collect([$admin])->merge($members);

        $workspace = Workspace::create(['name' => 'Product and Solutions', 'owner_id' => $admin->id]);

        // $projectData = [
        //     ['name' => 'Website Redesign', 'prefix' => 'WEB', 'color' => '#2563EB', 'description' => 'New marketing website and design system.'],
        //     ['name' => 'Mobile Application', 'prefix' => 'APP', 'color' => '#7C3AED', 'description' => 'Customer mobile experience.'],
        //     ['name' => 'Growth Campaign', 'prefix' => 'MKT', 'color' => '#EA580C', 'description' => 'Quarterly growth initiatives.'],
        // ];
        $statusTemplates = [
            ['name' => 'Not Started', 'color' => '#64748B', 'order' => 0, 'is_default' => true],
            ['name' => 'Pending', 'color' => '#F97316', 'order' => 1],
            ['name' => 'Completed', 'color' => '#22C55E', 'order' => 2],
            ['name' => 'Under Review', 'color' => '#A855F7', 'order' => 3],
        ];
        // $titles = [
        //     'Partone Consultancy Website', 'Design wireframes – Homepage', 'Modify content for homepage',
        //     'MTC Design Approval', 'Nexa component revision', 'V0.1 Design System introduction',
        //     'Build responsive navigation', 'Review analytics tracking', 'Create onboarding checklist',
        //     'QA payment flow', 'Prepare release notes', 'Improve empty states',
        // ];

        // foreach ($projectData as $projectIndex => $data) {
        //     $project = Project::create([...$data, 'workspace_id' => $workspace->id, 'created_by' => $david->id, 'next_task_number' => 9]);
        //     foreach ($users as $index => $user) {
        //         $project->members()->attach($user, ['id' => fake()->uuid(), 'role_in_project' => $index === 0 ? 'owner' : 'editor']);
        //     }
        //     $statuses = collect($statusTemplates)->map(fn ($status) => TaskStatus::create([...$status, 'project_id' => $project->id]));
        //     $labels = collect([
        //         ['name' => 'Frontend', 'color' => '#2563EB'],
        //         ['name' => 'Design', 'color' => '#A855F7'],
        //         ['name' => 'Bug', 'color' => '#EF4444'],
        //     ])->map(fn ($label) => TaskLabel::create([...$label, 'project_id' => $project->id]));

        //     foreach (range(1, 8) as $number) {
        //         $status = $statuses[($number + $projectIndex) % $statuses->count()];
        //         $task = Task::create([
        //             'project_id' => $project->id,
        //             'status_id' => $status->id,
        //             'title' => $titles[($number - 1 + $projectIndex * 4) % count($titles)],
        //             'code' => $project->prefix.'-'.$number,
        //             'description' => 'Collaborate with the team, confirm acceptance criteria, and ship a polished result.',
        //             'priority' => ['urgent', 'high', 'medium', 'low'][($number + $projectIndex) % 4],
        //             'start_date' => now()->subDays(random_int(2, 20))->toDateString(),
        //             'due_date' => now()->addDays(random_int(-3, 28))->toDateString(),
        //             'created_by' => $david->id,
        //             'assignee_id' => $users[($number + $projectIndex) % $users->count()]->id,
        //             'order' => $number,
        //         ]);
        //         $assignees = $users->shuffle()->take(random_int(1, 4));
        //         $task->assignees()->attach($assignees->pluck('id')->mapWithKeys(fn ($id) => [$id => ['id' => fake()->uuid()]]));
        //         $task->labels()->attach($labels->random()->id);
        //         foreach (range(1, random_int(1, 3)) as $comment) {
        //             TaskComment::create(['task_id' => $task->id, 'user_id' => $users->random()->id, 'comment' => fake()->sentence()]);
        //         }
        //         TaskActivityLog::create(['task_id' => $task->id, 'user_id' => $david->id, 'action' => 'created', 'meta' => ['source' => 'seed']]);
        //     }

        //     Workflow::create([
        //         'project_id' => $project->id,
        //         'name' => 'Notify PM when task completes',
        //         'description' => 'Keep project managers in the loop automatically.',
        //         'trigger' => ['type' => 'status_changed', 'value' => 'Completed'],
        //         'actions' => [['type' => 'notify', 'target' => 'project_manager']],
        //     ]);
        // }

        // $conversation = Conversation::create(['type' => 'group', 'name' => 'Website Redesign Team', 'created_by' => $david->id]);
        // $conversation->participants()->attach($users->pluck('id'));
        // foreach (['Morning team! What are we shipping today?', 'The homepage review is ready.', 'Great — I will take a look after stand-up.'] as $index => $body) {
        //     Message::create(['conversation_id' => $conversation->id, 'sender_id' => $users[$index]->id, 'body' => $body]);
        // }

        // $firstProject = Project::first();
        // CalendarEvent::create([
        //     'title' => 'Weekly design review', 'description' => 'Review progress and unblock the team.',
        //     'project_id' => $firstProject->id, 'user_id' => $david->id,
        //     'start_at' => now()->addDays(2)->setTime(10, 0), 'end_at' => now()->addDays(2)->setTime(11, 0),
        // ]);

        // foreach ([
        //     ['Google Calendar', 'google-calendar'], ['GitHub', 'github'], ['Zoom', 'zoom'],
        // ] as [$name, $type]) {
        //     Integration::create(['name' => $name, 'type' => $type]);
        // }

        // $david->notify(new DemoNotification('You were assigned to WEB-6', '/tasks'));
        // $david->notify(new DemoNotification('MTC Design Approval is due soon', '/calendar'));
    }
}
