<?php

namespace Tests\Feature;

use App\Models\GoogleCalendarAccount;
use App\Models\Integration;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCalendarEvent;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoogleCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connecting_creates_account_and_syncs_existing_assigned_tasks(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $task = Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Ship release',
            'code' => 'WEB-1', 'priority' => 'medium', 'created_by' => $user->id,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        $task->assignees()->attach($user->id, ['id' => (string) Str::uuid()]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response(['email' => 'user@gmail.com']),
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response(['id' => 'gcal-event-1']),
        ]);

        $this->withSession(['google_calendar_oauth_state' => 'state-abc'])
            ->actingAs($user)
            ->get(route('integrations.google-calendar.callback', ['code' => 'auth-code', 'state' => 'state-abc']))
            ->assertRedirect(route('integrations'));

        $this->assertDatabaseHas('google_calendar_accounts', ['user_id' => $user->id, 'google_account_email' => 'user@gmail.com']);
        $this->assertDatabaseHas('task_calendar_events', [
            'task_id' => $task->id, 'user_id' => $user->id, 'google_event_id' => 'gcal-event-1',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.googleapis.com/calendar/v3/calendars/primary/events'
            && $request->method() === 'POST');
    }

    public function test_creating_a_task_with_a_connected_assignee_syncs_a_calendar_event(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $assignee = User::factory()->create();
        $project->members()->attach($assignee, ['id' => (string) Str::uuid(), 'role_in_project' => 'editor']);
        GoogleCalendarAccount::create([
            'user_id' => $assignee->id, 'google_account_email' => 'assignee@gmail.com',
            'access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'token_expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response(['id' => 'gcal-event-2']),
        ]);

        $this->actingAs($user)->post(route('tasks.store'), [
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Design review',
            'priority' => 'high', 'due_date' => now()->addDays(5)->toDateString(), 'assignee_ids' => [$assignee->id],
        ])->assertSessionHasNoErrors();

        $task = Task::where('code', 'WEB-1')->firstOrFail();
        $this->assertDatabaseHas('task_calendar_events', [
            'task_id' => $task->id, 'user_id' => $assignee->id, 'google_event_id' => 'gcal-event-2',
        ]);
    }

    public function test_removing_an_assignee_deletes_their_calendar_event(): void
    {
        [$user, $project, $status] = $this->projectFixture();
        $assignee = User::factory()->create();
        $project->members()->attach($assignee, ['id' => (string) Str::uuid(), 'role_in_project' => 'editor']);
        GoogleCalendarAccount::create([
            'user_id' => $assignee->id, 'access_token' => 'access-1', 'refresh_token' => 'refresh-1',
            'token_expires_at' => now()->addHour(),
        ]);
        $task = Task::create([
            'project_id' => $project->id, 'status_id' => $status->id, 'title' => 'Prep demo',
            'code' => 'WEB-1', 'priority' => 'medium', 'created_by' => $user->id,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        $task->assignees()->attach($assignee->id, ['id' => (string) Str::uuid()]);
        TaskCalendarEvent::create(['task_id' => $task->id, 'user_id' => $assignee->id, 'google_event_id' => 'gcal-event-3']);

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/*' => Http::response([], 204),
        ]);

        $this->actingAs($user)->patch(route('tasks.update', $task), ['assignee_ids' => []])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('task_calendar_events', ['task_id' => $task->id, 'user_id' => $assignee->id]);
    }

    public function test_disconnecting_deletes_synced_events_and_the_account(): void
    {
        [$user] = $this->projectFixture();
        GoogleCalendarAccount::create([
            'user_id' => $user->id, 'access_token' => 'access-1', 'refresh_token' => 'refresh-1',
            'token_expires_at' => now()->addHour(),
        ]);
        $task = Task::create([
            'project_id' => Project::first()->id, 'status_id' => TaskStatus::first()->id, 'title' => 'Cleanup',
            'code' => 'WEB-99', 'priority' => 'low', 'created_by' => $user->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);
        TaskCalendarEvent::create(['task_id' => $task->id, 'user_id' => $user->id, 'google_event_id' => 'gcal-event-4']);

        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/*' => Http::response([], 204),
            'https://oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $this->actingAs($user)->delete(route('integrations.google-calendar.disconnect'))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_calendar_events', ['task_id' => $task->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('google_calendar_accounts', ['user_id' => $user->id]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'oauth2.googleapis.com/revoke'));
    }

    public function test_generic_integration_update_rejects_google_calendar_type(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create(['name' => 'Google Calendar', 'type' => 'google-calendar']);

        $this->actingAs($user)->patch(route('integrations.update', $integration), ['connected' => true])
            ->assertStatus(422);
    }

    private function projectFixture(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Workspace', 'owner_id' => $owner->id]);
        $project = Project::create([
            'name' => 'Project', 'prefix' => 'WEB', 'workspace_id' => $workspace->id,
            'created_by' => $owner->id, 'color' => '#2563EB',
        ]);
        $project->members()->attach($owner, ['id' => (string) Str::uuid(), 'role_in_project' => 'owner']);
        $status = TaskStatus::create(['project_id' => $project->id, 'name' => 'To Do', 'color' => '#2563EB', 'order' => 0]);

        return [$owner, $project, $status];
    }
}
