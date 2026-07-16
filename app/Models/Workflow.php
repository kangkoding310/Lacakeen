<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['trigger' => 'array', 'actions' => 'array', 'is_active' => 'boolean'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function logs()
    {
        return $this->hasMany(WorkflowLog::class)->latest();
    }
}
