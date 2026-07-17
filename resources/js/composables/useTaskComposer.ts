import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { ProjectSummary } from '@/types/models';

export function useTaskComposer() {
    const page = usePage();

    const projects = computed<ProjectSummary[]>(() => page.props.taskComposer?.projects ?? []);

    const findProject = (projectId: string | null | undefined): ProjectSummary | undefined =>
        projects.value.find((project) => project.id === projectId);

    return { projects, findProject };
}
