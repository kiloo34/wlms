import React, { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Combobox } from '@/components/ui/combobox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Issue } from '@/types/issue';
import { CreateIssuePayload, UpdateIssuePayload, useIssueWorklogs } from '../hooks/useIssues';
import { WorklogHistory } from './WorklogHistory';
import { IssueCommentSection } from './IssueCommentSection';

export interface SelectOption {
    id: string;
    name: string;
}

interface IssueFormDialogProps {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (payload: CreateIssuePayload | UpdateIssuePayload) => void;
    issue?: Issue | null;
    isLoading?: boolean;
    issueTypes: SelectOption[];
    priorities: SelectOption[];
    statuses: SelectOption[];
    assignees: SelectOption[];
}

export const IssueFormDialog: React.FC<IssueFormDialogProps> = ({
    isOpen,
    onClose,
    onSubmit,
    issue,
    isLoading,
    issueTypes,
    priorities,
    statuses,
    assignees,
}) => {
    const isEdit = !!issue;

    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [issueTypeId, setIssueTypeId] = useState('');
    const [priorityId, setPriorityId] = useState('');
    const [statusId, setStatusId] = useState('');
    const [assigneeId, setAssigneeId] = useState('');
    const [originalEstimateHours, setOriginalEstimateHours] = useState<string>('');
    
    const { data: worklogs = [], isLoading: isLoadingWorklogs } = useIssueWorklogs(issue?.id || null);

    useEffect(() => {
        if (isOpen) {
            if (issue) {
                setTitle(issue.title);
                setDescription(issue.description || '');
                setIssueTypeId(String(issue.issue_type_id));
                setPriorityId(String(issue.priority_id));
                setStatusId(String(issue.status_id));
                setAssigneeId(issue.assignee_id ? String(issue.assignee_id) : 'unassigned');
                setOriginalEstimateHours(issue.original_estimate_seconds ? String(issue.original_estimate_seconds / 3600) : '');
            } else {
                // Recover draft from Local Storage for new issues
                setTitle(localStorage.getItem('wlms_draft_issue_title') || '');
                setDescription(localStorage.getItem('wlms_draft_issue_description') || '');
                setIssueTypeId(issueTypes[0] ? String(issueTypes[0].id) : '');
                setPriorityId(priorities[0] ? String(priorities[0].id) : '');
                setStatusId(statuses[0] ? String(statuses[0].id) : '');
                setAssigneeId('unassigned');
                setOriginalEstimateHours('');
            }
        }
    }, [isOpen, issue, issueTypes, priorities, statuses]);

    // Save draft to local storage when typing (only for create)
    useEffect(() => {
        if (!isEdit && isOpen) {
            localStorage.setItem('wlms_draft_issue_title', title);
            localStorage.setItem('wlms_draft_issue_description', description);
        }
    }, [title, description, isEdit, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const payload: CreateIssuePayload | UpdateIssuePayload = {
            title,
            description: description || null,
            issue_type_id: issueTypeId,
            priority_id: priorityId,
            assignee_id: assigneeId === 'unassigned' ? null : assigneeId,
            sprint_id: issue?.sprint_id || null,
            original_estimate_seconds: originalEstimateHours ? Number(originalEstimateHours) * 3600 : null,
            remaining_estimate_seconds: issue?.remaining_estimate_seconds !== undefined ? issue.remaining_estimate_seconds : (originalEstimateHours ? Number(originalEstimateHours) * 3600 : null),
        };

        if (isEdit) {
            (payload as UpdateIssuePayload).status_id = statusId;
        } else if (statusId) {
            payload.status_id = statusId;
        }

        onSubmit(payload);

        // Clear draft on successful submit
        if (!isEdit) {
            localStorage.removeItem('wlms_draft_issue_title');
            localStorage.removeItem('wlms_draft_issue_description');
        }
    };
    
    const formContent = (
        <form onSubmit={handleSubmit} className="space-y-4 py-4">
            <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder="Issue title"
                    required
                />
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder="Description..."
                    rows={4}
                />
                {!isEdit && (
                    <p className="text-xs text-muted-foreground text-right italic">
                        *Draft otomatis tersimpan (Auto-saved)
                    </p>
                )}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="issue_type">Type</Label>
                    <Combobox 
                        id="issue_type"
                        options={issueTypes.map((type) => ({ value: String(type.id), label: type.name }))}
                        value={issueTypeId}
                        onChange={setIssueTypeId}
                        placeholder="Search type..."
                        emptyText="No type found."
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="priority">Priority</Label>
                    <Combobox 
                        id="priority"
                        options={priorities.map((p) => ({ value: String(p.id), label: p.name }))}
                        value={priorityId}
                        onChange={setPriorityId}
                        placeholder="Search priority..."
                        emptyText="No priority found."
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="status">Status {isEdit ? '*' : '(Optional)'}</Label>
                    <Combobox 
                        id="status"
                        options={statuses.map((s) => ({ value: String(s.id), label: s.name }))}
                        value={statusId}
                        onChange={setStatusId}
                        placeholder="Search status..."
                        emptyText="No status found."
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="assignee">Assignee</Label>
                    <Combobox 
                        id="assignee"
                        options={[
                            { value: 'unassigned', label: 'Unassigned' },
                            ...assignees.map((a) => ({ value: String(a.id), label: a.name }))
                        ]}
                        value={assigneeId}
                        onChange={setAssigneeId}
                        placeholder="Search assignee..."
                        emptyText="No user found."
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="original_estimate">Original Estimate (Hours)</Label>
                    <Input
                        id="original_estimate"
                        type="number"
                        min="0"
                        step="0.1"
                        value={originalEstimateHours}
                        onChange={(e) => setOriginalEstimateHours(e.target.value)}
                        placeholder="e.g. 8"
                    />
                </div>
            </div>

            <div className="flex justify-end space-x-2 pt-4">
                <Button type="button" variant="outline" onClick={onClose} disabled={isLoading}>
                    Cancel
                </Button>
                <Button type="submit" disabled={isLoading}>
                    {isLoading ? 'Saving...' : 'Save Issue'}
                </Button>
            </div>
        </form>
    );

    return (
        <Dialog open={isOpen} onOpenChange={(open) => {
            if (!open) onClose();
        }}>
            <DialogContent 
                className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto"
                onInteractOutside={(e) => {
                    e.preventDefault();
                }}
            >
                <DialogHeader>
                    <DialogTitle>{isEdit ? 'Edit Issue' : 'Create New Issue'}</DialogTitle>
                </DialogHeader>

                {isEdit ? (
                    <Tabs defaultValue="details" className="w-full mt-4">
                        <TabsList className="w-full">
                            <TabsTrigger value="details" className="flex-1">Details</TabsTrigger>
                            <TabsTrigger value="worklogs" className="flex-1">Worklogs</TabsTrigger>
                            <TabsTrigger value="comments" className="flex-1">Comments</TabsTrigger>
                        </TabsList>
                        <TabsContent value="details">
                            {formContent}
                        </TabsContent>
                        <TabsContent value="worklogs">
                            <WorklogHistory worklogs={worklogs} isLoading={isLoadingWorklogs} />
                        </TabsContent>
                        <TabsContent value="comments">
                            <IssueCommentSection issueId={String(issue.id)} />
                        </TabsContent>
                    </Tabs>
                ) : (
                    formContent
                )}
            </DialogContent>
        </Dialog>
    );
};
