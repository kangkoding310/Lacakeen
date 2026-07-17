<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request, ?Conversation $conversation = null): Response
    {
        $conversations = $request->user()->conversations()
            ->with(['participants:id,name,avatar', 'messages' => fn ($query) => $query->with('sender:id,name,avatar')->limit(1)])
            ->withMax('messages', 'created_at')->orderByDesc('messages_max_created_at')->get();
        $active = $conversation ?: $conversations->first();
        if ($active) {
            $this->authorize('view', $active);
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'activeConversation' => $active?->load(['participants:id,name,avatar', 'messages' => fn ($query) => $query->with('sender:id,name,avatar')->reorder()->oldest()]),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);
        $validated = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        Message::create([...$validated, 'conversation_id' => $conversation->id, 'sender_id' => $request->user()->id]);

        return back();
    }
}
