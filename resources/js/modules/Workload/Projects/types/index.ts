export interface Project {
    id: string;
    workspace_id: string;
    workflow_id?: string | null;
    key: string;
    name: string;
    description: string | null;
    priority_id?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface CreateProjectPayload {
    workspace_id: string;
    workflow_id?: string;
    priority_id?: string;
    key: string;
    name: string;
    description?: string;
}

export interface UpdateProjectPayload {
    workflow_id?: string;
    priority_id?: string;
    name?: string;
    description?: string;
}

export interface ProjectResource {
    data: Project;
}

export interface ProjectCollectionResource {
    data: Project[];
}
