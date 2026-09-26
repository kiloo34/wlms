export interface Workspace {
    id: string;
    name: string;
    status: 'ACTIVE' | 'ARCHIVED' | string;
    ownerGroupId: string;
}

export interface CreateWorkspacePayload {
    name: string;
    description?: string;
}

// Wrapper DTO dari Laravel Resource biasanya ada didalam "data"
export interface WorkspaceResource {
    data: Workspace;
}

export interface WorkspaceCollectionResource {
    data: Workspace[];
}

export interface UpdateWorkspacePayload {
    name: string;
}

