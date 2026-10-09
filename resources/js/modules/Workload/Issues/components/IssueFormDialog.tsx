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
import { useTranslate } from "@/hooks/useTranslate";
import { toast } from 'sonner';
import { useProjectLookups } from '@/hooks/api/issues';

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
    projectId?: string;
    projects?: SelectOption[];
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
    projectId,
    projects = [],
    issueTypes,
    priorities,
    statuses,
    assignees,
}) => {
    const { t } = useTranslate();
    const isEdit = !!issue;

    const [selectedProjectId, setSelectedProjectId] = useState(projectId || '');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [issueTypeId, setIssueTypeId] = useState('');
    const [priorityId, setPriorityId] = useState('');
    const [statusId, setStatusId] = useState('');
    const [assigneeId, setAssigneeId] = useState('');
    const [originalEstimateHours, setOriginalEstimateHours] = useState<string>('');
    
    const { data: worklogs = [], isLoading: isLoadingWorklogs } = useIssueWorklogs(issue?.id || null);

    // Dynamic Lookups for Cross-Project Create Task
    const { data: dynamicLookups, isLoading: isLoadingLookups } = useProjectLookups(!isEdit && !projectId && selectedProjectId ? selectedProjectId : null);
    const activeIssueTypes = dynamicLookups?.issueTypes || issueTypes;
    const activePriorities = dynamicLookups?.priorities || priorities;
    const activeStatuses = dynamicLookups?.statuses || statuses;
    const activeAssignees = dynamicLookups?.users || assignees;

    useEffect(() => {
        if (!isEdit && selectedProjectId && dynamicLookups) {
            if (activeIssueTypes.length > 0 && !activeIssueTypes.find((t: any) => String(t.id) === issueTypeId)) {
                setIssueTypeId(String(activeIssueTypes[0].id));
            }
            if (activePriorities.length > 0 && !activePriorities.find((p: any) => String(p.id) === priorityId)) {
                setPriorityId(String(activePriorities[0].id));
            }
            if (activeStatuses.length > 0 && !activeStatuses.find((s: any) => String(s.id) === statusId)) {
                setStatusId(String(activeStatuses[0].id));
            }
            if (assigneeId !== 'unassigned' && activeAssignees.length > 0 && !activeAssignees.find((u: any) => String(u.id) === assigneeId)) {
                setAssigneeId('unassigned');
            }
        }
    }, [selectedProjectId, dynamicLookups, isEdit]);

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

    useEffect(() => {
        if (projectId) {
            setSelectedProjectId(projectId);
        }
    }, [projectId]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!isEdit && !projectId && !selectedProjectId) {
            toast.error(t('Please select a project'));
            return;
        }

        const payload: CreateIssuePayload | UpdateIssuePayload = {
            project_id: !isEdit && !projectId ? selectedProjectId : undefined,
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
            {!isEdit && (
                <div className="space-y-2">
                    <Label htmlFor="project">Project *</Label>
                    <Combobox 
                        id="project"
                        options={(projects || []).map((p) => ({ value: String(p.id), label: p.name }))}
                        value={selectedProjectId}
                        onChange={setSelectedProjectId}
                        placeholder={t('Select project...')}
                        emptyText="No project found."
                        disabled={!!projectId}
                    />
                </div>
            )}
            
            <div className="space-y-2">
                <Label htmlFor="title">{t('Title')}</Label>
                <Input
                    id="title"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder={t('Issue title')}
                    required
                />
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">{t('Description')}</Label>
                <Textarea
                    id="description"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder={t('Description...')}
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
                    <Label htmlFor="issue_type">{t('Type')}</Label>
                    <Combobox 
                        id="issue_type"
                        options={(activeIssueTypes || []).map((type: any) => ({ value: String(type.id), label: type.name }))}
                        value={issueTypeId}
                        onChange={setIssueTypeId}
                        placeholder={t('Search type...')}
                        emptyText="No type found."
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="priority">{t('Priority')}</Label>
                    <Combobox 
                        id="priority"
                        options={(activePriorities || []).map((p: any) => ({ value: String(p.id), label: p.name }))}
                        value={priorityId}
                        onChange={setPriorityId}
                        placeholder={t('Search priority...')}
                        emptyText="No priority found."
                    />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="status">{t('Status')} {isEdit ? '*' : '(Optional)'}</Label>
                    <Combobox 
                        id="status"
                        options={(activeStatuses || []).map((s: any) => ({ value: String(s.id), label: s.name }))}
                        value={statusId}
                        onChange={setStatusId}
                        placeholder={t('Search status...')}
                        emptyText="No status found."
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="assignee">{t('Assignee')}</Label>
                    <Combobox 
                        id="assignee"
                        options={[
                            { value: 'unassigned', label: 'Unassigned' },
                            ...(activeAssignees || []).map((a: any) => ({ value: String(a.id), label: a.name }))
                        ]}
                        value={assigneeId}
                        onChange={setAssigneeId}
                        placeholder={t('Search assignee...')}
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
                    {t('Cancel')}
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
                            <TabsTrigger value="details" className="flex-1">{t('Details')}</TabsTrigger>
                            <TabsTrigger value="worklogs" className="flex-1">{t('Worklogs')}</TabsTrigger>
                            <TabsTrigger value="comments" className="flex-1">{t('Comments')}</TabsTrigger>
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
