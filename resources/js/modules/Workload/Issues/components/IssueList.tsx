import React from 'react';
import { usePage } from '@inertiajs/react';
import { Issue } from '@/types/issue';
import { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { SelectOption } from './IssueFormDialog';

interface IssueListProps {
    issues: Issue[];
    isLoading: boolean;
    onCreateClick?: () => void;
    onEdit: (issue: Issue) => void;
    onDelete: (id: string) => void;
    onLogWork: (issue: Issue) => void;
    // Map dictionaries for rendering
    issueTypes: SelectOption[];
    priorities: SelectOption[];
    statuses: SelectOption[];
    assignees: SelectOption[];
}

export const IssueList: React.FC<IssueListProps> = ({
    issues,
    isLoading,
    onCreateClick,
    onEdit,
    onDelete,
    onLogWork,
    issueTypes,
    priorities,
    statuses,
    assignees,
}) => {
    const { auth } = usePage<SharedData>().props;
    const currentUserId: number | null = auth.user?.id ?? null;

    const getName = (options: SelectOption[], id: string | null) => {
        if (!id) return '-';
        return options.find(o => o.id === id)?.name || id;
    };

    if (isLoading) {
        return <div className="text-center p-4">Loading issues...</div>;
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Issues</h3>
                {onCreateClick && (
                    <Button onClick={onCreateClick}>Create Task</Button>
                )}
            </div>

            {(!issues || issues.length === 0) ? (
                <div className="text-center p-4 border rounded-md bg-muted">
                    No issues found.
                </div>
            ) : (
                <div className="overflow-x-auto rounded-md border">
                    <table className="min-w-full divide-y divide-border">
                <thead className="bg-muted">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Title</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Priority</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Assignee</th>
                        <th className="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody className="bg-card divide-y divide-border">
                    {issues.map((issue) => (
                        <tr key={issue.id}>
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                {issue.title}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {getName(issueTypes, issue.issue_type_id)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {getName(statuses, issue.status_id)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {getName(priorities, issue.priority_id)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                {getName(assignees, issue.assignee_id)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                {currentUserId && issue.assignee_id && String(currentUserId) === String(issue.assignee_id) && (
                                    <Button variant="outline" size="sm" onClick={() => onLogWork(issue)}>
                                        Log Work
                                    </Button>
                                )}
                                <Button variant="outline" size="sm" onClick={() => onEdit(issue)}>
                                    Edit
                                </Button>
                                <Button variant="destructive" size="sm" onClick={() => {
                                    if (window.confirm('Are you sure you want to delete this issue?')) {
                                        onDelete(issue.id);
                                    }
                                }}>
                                    Delete
                                </Button>
                            </td>

                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
        )}
        </div>
    );
};

