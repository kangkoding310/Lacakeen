import type { AuthUser, ProjectSummary, WorkspaceSummary } from './models';
import type { NotificationItem } from './notification';

export interface SharedPageProps {
    auth: {
        user: AuthUser | null;
    };
    notifications: {
        unread: number;
        latest: NotificationItem[];
    };
    taskComposer: {
        projects: ProjectSummary[];
    };
    projectNavigation: {
        recent: ProjectSummary[];
        workspace: WorkspaceSummary | null;
        canCreate: boolean;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedPageProps;
    }
}
