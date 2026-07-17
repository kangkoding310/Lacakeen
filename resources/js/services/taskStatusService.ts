import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';

export const taskStatusService = {
    store(projectId: string, payload: RequestPayload, options?: VisitOptions) {
        router.post(route('statuses.store', projectId), payload, options);
    },

    update(statusId: string, payload: RequestPayload, options?: VisitOptions) {
        router.patch(route('statuses.update', statusId), payload, options);
    },

    destroy(statusId: string, options?: VisitOptions) {
        router.delete(route('statuses.destroy', statusId), options);
    },
};
