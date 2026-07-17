<?php

namespace App\Actions\Member;

use App\Models\User;
use App\Notifications\MemberInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InviteMemberAction
{
    public function handle(User $inviter, array $data): void
    {
        $token = Str::random(64);
        DB::table('member_invitations')->insert([
            'id' => Str::uuid(), ...$data, 'token' => hash('sha256', $token),
            'invited_by' => $inviter->id, 'expires_at' => now()->addDays(7),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Notification::route('mail', $data['email'])->notify(new MemberInvitationNotification($token));
    }
}
