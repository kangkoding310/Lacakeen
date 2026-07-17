import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';
import type { TaskFilters } from '@/types/task';

export const taskService = {
    list(filters: Partial<TaskFilters>, options?: VisitOptions) {
        router.get(route('tasks.index'), filters, {
            preserveState: true,
            replace: true,
            ...options,
        });
    },

    update(taskId: string, payload: RequestPayload, options?: VisitOptions) {
        router.patch(route('tasks.update', taskId), payload, options);
    },

    destroy(taskId: string, options?: VisitOptions) {
        router.delete(route('tasks.destroy', taskId), options);
    },
};
