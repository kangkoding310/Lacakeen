<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function scopeVisibleTo($query, User $user)
    {
        return $query->when(! $user->hasRole('admin'), fn ($query) => $query->whereHas(
            'members', fn ($members) => $members->where('users.id', $user->id)
        ));
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role_in_project')
            ->withTimestamps();
    }

    public function statuses()
    {
        return $this->hasMany(TaskStatus::class)->orderBy('order');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function labels()
    {
        return $this->hasMany(TaskLabel::class);
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }
}
