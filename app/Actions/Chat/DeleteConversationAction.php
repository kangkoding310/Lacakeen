<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\User;

class DeleteConversationAction
{
    public function handle(Conversation $conversation, User $user): void
    {
        $conversation->participants()->updateExistingPivot($user->id, ['deleted_at' => now()]);
    }
}
