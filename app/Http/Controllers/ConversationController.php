<?php

namespace App\Http\Controllers;

use App\Actions\Chat\CreateConversationAction;
use App\Http\Requests\Chat\StoreConversationRequest;
use Illuminate\Http\RedirectResponse;

class ConversationController extends Controller
{
    public function store(StoreConversationRequest $request, CreateConversationAction $action): RedirectResponse
    {
        $conversation = $action->handle($request->user(), $request->validated());

        return redirect()->route('chat', $conversation);
    }
}
