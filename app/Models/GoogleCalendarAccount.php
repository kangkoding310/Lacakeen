<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GoogleCalendarAccount extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return ! $this->token_expires_at || $this->token_expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
