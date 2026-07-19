<?php

namespace App\Http\Controllers;

use App\Actions\Chat\CreateConversationAction;
use App\Actions\Chat\DeleteConversationAction;
use App\Http\Requests\Chat\StoreConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function store(StoreConversationRequest $request, CreateConversationAction $action): RedirectResponse
    {
        $conversation = $action->handle($request->user(), $request->validated());

        return redirect()->route('chat', $conversation);
    }

    public function destroy(Request $request, Conversation $conversation, DeleteConversationAction $action): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $action->handle($conversation, $request->user());

        return redirect()->route('chat');
    }
}
