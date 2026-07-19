<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request, ?Conversation $conversation = null): Response
    {
        $conversations = $request->user()->conversations()
            ->wherePivotNull('deleted_at')
            ->with(['participants:id,name,avatar', 'messages' => fn ($query) => $query->with('sender:id,name,avatar')->limit(1)])
            ->withMax('messages', 'created_at')->orderByDesc('messages_max_created_at')->get();

        $active = $conversation;
        if ($active && ! $conversations->contains('id', $active->id)) {
            // Not in the visible list: either hidden-for-self (still a participant, so just show
            // no active conversation) or the user isn't a participant at all (let authorize()
            // below reject it with a 403 instead of silently clearing it).
            $isParticipant = $active->participants()->where('users.id', $request->user()->id)->exists();
            if ($isParticipant) {
                $active = null;
            }
        }
        if ($active) {
            $this->authorize('view', $active);
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'activeConversation' => $active?->load(['participants:id,name,avatar', 'messages' => fn ($query) => $query->with('sender:id,name,avatar')->reorder()->oldest()]),
            'members' => User::where('status', 'active')->where('id', '!=', $request->user()->id)
                ->select('id', 'name', 'email', 'avatar')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $message = Message::create([...$validated, 'conversation_id' => $conversation->id, 'sender_id' => $request->user()->id]);

        broadcast(new MessageSent($message))->toOthers();

        return back();
    }
}
