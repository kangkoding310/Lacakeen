import { router } from '@inertiajs/vue3';
import type { VisitOptions } from '@inertiajs/core';

export const workflowService = {
    toggle(workflowId: string, isActive: boolean, options?: VisitOptions) {
        router.patch(route('workflow.update', workflowId), { is_active: isActive }, options);
    },
};
