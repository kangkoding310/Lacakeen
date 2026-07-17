import type { Task } from './task';

export type ProjectRole = 'owner' | 'editor' | 'viewer';

export interface ProjectMember {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    pivot: { role_in_project: ProjectRole };
}

export interface ProjectListItem {
    id: string;
    name: string;
    prefix: string;
    description: string | null;
    color: string;
    status: 'active' | 'archived';
    created_by: number;
    tasks_count: number;
    members: ProjectMember[];
}

export interface ProjectDetail extends ProjectListItem {
    workspace_id?: string;
}

export interface ProjectStatusColumn {
    id: string;
    project_id: string;
    name: string;
    color: string;
    order: number;
    is_default: boolean;
    tasks_count?: number;
    tasks: Task[];
}

export interface ProjectEvent {
    id: string;
    title: string;
    start: string;
    allDay: boolean;
    backgroundColor: string;
    extendedProps: { type: string };
}

export interface ProjectFormPayload {
    name: string;
    prefix: string;
    description: string;
    color: string;
    workspace_id?: string;
}
