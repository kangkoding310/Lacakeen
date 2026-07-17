import { router } from '@inertiajs/vue3';
import type { RequestPayload, VisitOptions } from '@inertiajs/core';

export const projectService = {
    store(payload: RequestPayload, options?: VisitOptions) {
        router.post(route('projects.store'), payload, options);
    },

    update(projectId: string, payload: RequestPayload, options?: VisitOptions) {
        router.patch(route('projects.update', projectId), payload, options);
    },

    destroy(projectId: string, options?: VisitOptions) {
        router.delete(route('projects.destroy', projectId), options);
    },

    addMember(projectId: string, payload: RequestPayload, options?: VisitOptions) {
        router.post(route('projects.members.store', projectId), payload, options);
    },

    updateMember(
        projectId: string,
        userId: number,
        payload: RequestPayload,
        options?: VisitOptions
    ) {
        router.patch(route('projects.members.update', [projectId, userId]), payload, options);
    },

    removeMember(projectId: string, userId: number, options?: VisitOptions) {
        router.delete(route('projects.members.destroy', [projectId, userId]), options);
    },
};
