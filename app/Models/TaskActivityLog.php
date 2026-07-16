<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TaskActivityLog extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
