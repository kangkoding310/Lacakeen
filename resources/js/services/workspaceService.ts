import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';

export const workspaceService = {
    store(payload: RequestPayload, options?: VisitOptions) {
        router.post(route('workspaces.store'), payload, options);
    },

    update(workspaceId: string, payload: RequestPayload, options?: VisitOptions) {
        router.patch(route('workspaces.update', workspaceId), payload, options);
    },

    destroy(workspaceId: string, options?: VisitOptions) {
        router.delete(route('workspaces.destroy', workspaceId), options);
    },

    addMember(workspaceId: string, payload: RequestPayload, options?: VisitOptions) {
        router.post(route('workspaces.members.store', workspaceId), payload, options);
    },

    updateMember(
        workspaceId: string,
        userId: number,
        payload: RequestPayload,
        options?: VisitOptions
    ) {
        router.patch(route('workspaces.members.update', [workspaceId, userId]), payload, options);
    },

    removeMember(workspaceId: string, userId: number, options?: VisitOptions) {
        router.delete(route('workspaces.members.destroy', [workspaceId, userId]), options);
    },
};
