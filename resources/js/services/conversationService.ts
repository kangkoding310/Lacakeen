import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';

export const conversationService = {
    store(payload: RequestPayload, options?: VisitOptions) {
        router.post(route('chat.conversations.store'), payload, options);
    },
};
