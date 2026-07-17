import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';
import type { CalendarFilters } from '@/types/calendarEvent';

export const calendarService = {
    list(filters: Partial<CalendarFilters>, options?: VisitOptions) {
        router.get(route('calendar'), filters, { preserveState: true, ...options });
    },

    storeEvent(payload: RequestPayload, options?: VisitOptions) {
        router.post(route('calendar.events.store'), payload, options);
    },
};
