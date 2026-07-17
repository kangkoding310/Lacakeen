import type { Role } from './models';

export interface MemberAssignedTask {
    id: string;
    code: string;
    title: string;
    status: { id: string; name: string; color: string };
}

export interface Member {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    job_title: string | null;
    status: 'active' | 'inactive';
    roles: Role[];
    active_tasks_count: number;
    completed_tasks_count: number;
    created_at: string;
    assigned_tasks: MemberAssignedTask[];
}

export interface MemberFilters {
    search: string;
    role: string;
    status: string;
}
