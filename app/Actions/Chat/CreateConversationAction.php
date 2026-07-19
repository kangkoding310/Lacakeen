<?php

namespace App\Actions\Chat;

use App\Events\ConversationCreated;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateConversationAction
{
    public function handle(User $creator, array $data): Conversation
    {
        $participantIds = collect($data['user_ids'])->push($creator->id)->unique()->values();
        abort_if($participantIds->count() < 2, 422, 'Select at least one other person to start a conversation.');

        if ($participantIds->count() === 2) {
            $otherUserId = $participantIds->first(fn ($id) => $id !== $creator->id);
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', fn ($query) => $query->where('users.id', $creator->id))
                ->whereHas('participants', fn ($query) => $query->where('users.id', $otherUserId))
                ->first();

            if ($existing) {
                $existing->participants()->updateExistingPivot($creator->id, ['deleted_at' => null]);

                return $existing;
            }
        }

        $conversation = DB::transaction(function () use ($participantIds, $data, $creator) {
            $conversation = Conversation::create([
                'type' => $participantIds->count() > 2 ? 'group' : 'direct',
                'name' => $participantIds->count() > 2 ? ($data['name'] ?? null) : null,
                'created_by' => $creator->id,
            ]);
            $conversation->participants()->attach($participantIds->all());

            return $conversation;
        });

        $conversation->participants()->where('users.id', '!=', $creator->id)->get()
            ->each(fn ($recipient) => broadcast(new ConversationCreated($conversation, $recipient)));

        return $conversation;
    }
}
