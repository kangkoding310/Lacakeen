export type WorkspaceRole = 'admin' | 'member';

export interface WorkspaceOwner {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
}

export interface WorkspaceMember {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    pivot: { role: WorkspaceRole };
}

export interface WorkspaceListItem {
    id: string;
    name: string;
    owner: WorkspaceOwner;
    members_count: number;
    projects_count: number;
}

export interface WorkspaceDetail {
    id: string;
    name: string;
    owner_id: number;
    owner: WorkspaceOwner;
    members: WorkspaceMember[];
    projects_count: number;
    created_at: string;
}
