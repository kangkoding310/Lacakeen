export interface WorkflowTrigger {
    type: string;
    value: string | null;
}

export interface WorkflowActionDef {
    type: string;
    target: string | null;
}

export interface WorkflowLog {
    id: string;
    created_at: string;
}

export interface WorkflowProjectRef {
    id: string;
    name: string;
    color: string;
}

export interface WorkflowItem {
    id: string;
    name: string;
    description: string | null;
    is_active: boolean;
    project: WorkflowProjectRef;
    trigger: WorkflowTrigger;
    actions: WorkflowActionDef[];
    logs: WorkflowLog[];
    logs_count: number;
}
