<?php

namespace Tests\Feature;

use App\Events\ConversationCreated;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

    public function test_sending_a_message_broadcasts_to_the_conversation_channel(): void
    {
        [$user, $conversation] = $this->conversationFixture();
        Event::fake([MessageSent::class]);

        $this->actingAs($user)->post(route('chat.messages.store', $conversation), ['body' => 'Broadcast me'])
            ->assertSessionHasNoErrors();

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            return $event->message->conversation_id === $conversation->id
                && $event->message->body === 'Broadcast me'
                && $event->broadcastOn()[0]->name === "private-conversation.{$conversation->id}";
        });
    }

    public function test_starting_a_direct_conversation_reuses_an_existing_one(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('chat.conversations.store'), ['user_ids' => [$other->id]])
            ->assertSessionHasNoErrors();
        $first = Conversation::where('type', 'direct')->firstOrFail();
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $first->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $first->id, 'user_id' => $other->id]);

        $this->actingAs($user)->post(route('chat.conversations.store'), ['user_ids' => [$other->id]])
            ->assertRedirect(route('chat', $first));
        $this->assertSame(1, Conversation::where('type', 'direct')->count());
    }

    public function test_starting_a_group_conversation_creates_it_with_all_participants(): void
    {
        $user = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        Event::fake([ConversationCreated::class]);

        $this->actingAs($user)->post(route('chat.conversations.store'), [
            'user_ids' => [$memberA->id, $memberB->id],
            'name' => 'Launch squad',
        ])->assertSessionHasNoErrors();

        $conversation = Conversation::where('type', 'group')->firstOrFail();
        $this->assertSame('Launch squad', $conversation->name);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $conversation->id, 'user_id' => $memberA->id]);
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $conversation->id, 'user_id' => $memberB->id]);
        Event::assertDispatchedTimes(ConversationCreated::class, 2);
    }

    public function test_broadcasting_auth_allows_participants_and_rejects_others(): void
    {
        // The test suite forces BROADCAST_CONNECTION=null (see phpunit.xml) so no real socket
        // traffic happens in tests. Broadcast::channel() registers callbacks on whichever driver
        // instance was active when routes/channels.php first loaded (the null driver, at boot),
        // so switching the config here alone leaves the "reverb" driver with no channels
        // registered; re-requiring the routes file re-runs those registrations against it.
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');

        [$user, $conversation] = $this->conversationFixture();
        $outsider = User::factory()->create();

        $this->actingAs($user)->post('/broadcasting/auth', [
            'channel_name' => "private-conversation.{$conversation->id}",
            'socket_id' => '1234.5678',
        ])->assertOk();

        $this->actingAs($outsider)->post('/broadcasting/auth', [
            'channel_name' => "private-conversation.{$conversation->id}",
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    private function conversationFixture(): array
    {
        $user = User::factory()->create();
        $conversation = Conversation::create(['id' => (string) Str::uuid(), 'created_by' => $user->id]);
        $conversation->participants()->attach($user, ['last_read_at' => now()]);

        return [$user, $conversation];
    }
}
