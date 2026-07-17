<?php

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateCalendarEventAction
{
    public function handle(User $user, array $data): CalendarEvent
    {
        if ($data['project_id'] ?? null) {
            Gate::authorize('view', Project::findOrFail($data['project_id']));
        }

        return CalendarEvent::create([...$data, 'user_id' => $user->id]);
    }
}
