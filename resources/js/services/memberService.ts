import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';
import type { MemberFilters } from '@/types/member';

export const memberService = {
    list(filters: Partial<MemberFilters>, options?: VisitOptions) {
        router.get(route('members'), filters, { preserveState: true, replace: true, ...options });
    },

    invite(payload: RequestPayload, options?: VisitOptions) {
        router.post(route('members.invite'), payload, options);
    },

    update(userId: number, payload: RequestPayload, options?: VisitOptions) {
        router.patch(route('members.update', userId), payload, options);
    },
};
