import type { ProjectLabel } from './models';

export type TaskPriority = 'urgent' | 'high' | 'medium' | 'low';

export interface TaskProjectRef {
    id: string;
    name: string;
    color: string;
}

export interface TaskStatusRef {
    id: string;
    name: string;
    color: string;
}

export interface TaskAssignee {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
}

export interface TaskComment {
    id: string;
    comment: string;
    created_at: string;
    user: TaskAssignee;
}

export interface TaskAttachment {
    id: string;
    file_name: string;
    file_path: string;
}

export interface TaskActivityLogEntry {
    id: string;
    action: string;
    meta: Record<string, unknown> | null;
    created_at: string;
    user: TaskAssignee | null;
}

export interface TaskSubtask {
    id: string;
    code: string;
    title: string;
    priority: TaskPriority;
    status_id: string;
    status: TaskStatusRef | null;
    assignees: TaskAssignee[];
}

export interface TaskListItem {
    id: string;
    code: string;
    title: string;
    priority: TaskPriority;
    due_date: string | null;
    updated_at: string;
    project: TaskProjectRef;
    status: TaskStatusRef;
    assignees: TaskAssignee[];
    comments_count?: number;
    comments?: TaskComment[];
    order?: number;
}

export interface Task extends TaskListItem {
    project_id: string;
    status_id: string;
    parent_task_id: string | null;
    description: string | null;
    start_date: string | null;
    labels: ProjectLabel[];
    comments: TaskComment[];
    attachments: TaskAttachment[];
    activityLogs: TaskActivityLogEntry[];
    subtasks: TaskSubtask[];
}

export interface TaskFilters {
    search: string;
    project: string;
    status: string;
    priority: string;
    assignee: string;
}
