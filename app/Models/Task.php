<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function scopeVisibleTo($query, User $user)
    {
        return $query->whereHas('project', fn ($projects) => $projects->visibleTo($user));
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryAssignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function labels()
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_pivot');
    }

    public function activityLogs()
    {
        return $this->hasMany(TaskActivityLog::class)->latest();
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function calendarEvents()
    {
        return $this->hasMany(TaskCalendarEvent::class);
    }
}
