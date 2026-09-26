export interface Status {
    category?: string;
    id: string;
    name: string;
    is_default: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Workflow {
    id: string;
    name: string;
    description?: string;
    is_active: boolean;
    statuses: Status[];
    created_at?: string;
    updated_at?: string;
    transitions?: Transition[];
}

export interface Transition {
    id: string;
    workflow_id: string;
    name: string;
    from_status_id: string | null;
    to_status_id: string;
    created_at?: string;
    updated_at?: string;
}

export interface CreateTransitionDto {
    name: string;
    from_status_id: string | null;
    to_status_id: string;
}

export interface CreateStatusDto {
    slug: string;
    category: string;
    color?: string;
    name: string;
    is_default?: boolean;
}

export interface UpdateStatusDto {
    name: string;
    is_default?: boolean;
}

export interface CreateWorkflowDto {
    name: string;
    description?: string;
    is_active?: boolean;
    status_ids?: string[];
}
