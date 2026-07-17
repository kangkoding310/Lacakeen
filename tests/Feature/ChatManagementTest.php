<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_view_and_send_messages_in_their_conversation(): void
    {
        [$user, $conversation] = $this->conversationFixture();

        $this->actingAs($user)->get(route('chat', $conversation))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Chat/Index')
                ->where('activeConversation.id', $conversation->id));

        $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['body' => 'Hello team'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'sender_id' => $user->id, 'body' => 'Hello team']);
    }

    public function test_non_participant_cannot_view_or_send_messages(): void
    {
        [, $conversation] = $this->conversationFixture();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('chat', $conversation))->assertForbidden();
        $this->actingAs($outsider)->post(route('chat.messages.store', $conversation), ['body' => 'Intruding'])->assertForbidden();
    }

    private function conversationFixture(): array
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['id' => (string) Str::uuid(), 'created_by' => $user->id]);
        $conversation->participants()->attach($user, ['last_read_at' => now()]);

        return [$user, $conversation];
    }
}
