export interface Role {
    id: string | number;
    name: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    roles: Role[];
}

export interface ProjectStatus {
    id: string;
    project_id: string;
    name: string;
    color: string;
    order?: number;
}

export interface ProjectMember {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
}

export interface ProjectLabel {
    id: string;
    project_id: string;
    name: string;
    color: string;
}

export interface ProjectSummary {
    id: string;
    name: string;
    color: string;
    prefix: string;
    statuses?: ProjectStatus[];
    members?: ProjectMember[];
    labels?: ProjectLabel[];
}

export interface WorkspaceSummary {
    id: string;
    name: string;
    owner_id: number;
}
