import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AuthUser } from '@/types/models';
import type { ProjectMember } from '@/types/project';
import type { WorkspaceMember } from '@/types/workspace';

export function usePermissions() {
    const page = usePage();
    // Pages using this composable sit behind the `auth` middleware, so the user is always present.
    const currentUser = computed(() => page.props.auth.user as AuthUser);
    const isAdmin = computed(() => currentUser.value.roles.some((role) => role.name === 'admin'));

    const projectRole = (members: ProjectMember[] = []) =>
        members.find((member) => member.id === currentUser.value.id)?.pivot?.role_in_project;
    const canManageProject = (members: ProjectMember[] = []) =>
        isAdmin.value || ['owner', 'editor'].includes(projectRole(members) ?? '');
    const canDeleteProject = (members: ProjectMember[] = []) =>
        isAdmin.value || projectRole(members) === 'owner';
    const canCreateProjectIn = (workspace?: { owner_id: number } | null) =>
        isAdmin.value || workspace?.owner_id === currentUser.value.id;

    const workspaceRole = (members: WorkspaceMember[] = []) =>
        members.find((member) => member.id === currentUser.value.id)?.pivot?.role;
    const isWorkspaceOwner = (workspace?: { owner_id: number } | null) =>
        workspace?.owner_id === currentUser.value.id;
    const canManageWorkspace = (workspace: { owner_id: number; members: WorkspaceMember[] }) =>
        isAdmin.value ||
        isWorkspaceOwner(workspace) ||
        workspaceRole(workspace.members) === 'admin';
    const canDeleteWorkspace = (workspace?: { owner_id: number } | null) =>
        isAdmin.value || isWorkspaceOwner(workspace);

    return {
        currentUser,
        isAdmin,
        projectRole,
        canManageProject,
        canDeleteProject,
        canCreateProjectIn,
        workspaceRole,
        isWorkspaceOwner,
        canManageWorkspace,
        canDeleteWorkspace,
    };
}
