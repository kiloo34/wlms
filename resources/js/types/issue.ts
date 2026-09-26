export interface Issue {
  id: string;
  project_id: string;
  title: string;
  description: string | null;
  issue_type_id: string;
  priority_id: string;
  status_id: string;
  sprint_id: string | null;
  assignee_id: string | null;
  status?: { id: string; name: string; category: string; color?: string; };
  assignee?: { id: string; name: string; avatar?: string; };
  original_estimate_seconds?: number | null;
  remaining_estimate_seconds?: number | null;
  created_at: string;
  updated_at: string;
}

export interface BoardColumn {
  id: string;
  name: string;
  issues: Issue[];
}

export interface Transition {
  id: string;
  name: string;
  to_status_id: string;
}

export interface CreateIssuePayload {
  project_id: string;
  title: string;
  description?: string;
  issue_type_id: string;
  priority_id: string;
  sprint_id?: string | null;
}

export interface TransitionIssuePayload {
  to_status_id: string;
}

export interface AssignIssuePayload {
  assignee_id: string;
}

