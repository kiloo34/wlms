import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useGlobalIssueModal } from '@/contexts/GlobalIssueModalContext';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    useMyWorkspaces,
    useWorkspaceProjects,
    useProjectLookups,
    useCreateIssue,
} from '@/hooks/api/issues';
import { useTranslate } from '@/hooks/useTranslate';

interface LookupItem {
    id: string | number;
    name?: string;
    user?: { name?: string };
}

interface WorkspaceItem {
    id: string | number;
    name: string;
}

interface ProjectItem {
    id: string | number;
    name: string;
}

interface ProjectContext {
    id?: string;
    workspace_id?: string;
}

interface PagePropsWithProject {
    project?: ProjectContext;
    [key: string]: unknown;
}

export function GlobalIssueCreateModal() {
    const { t } = useTranslate();

    const { isOpen, closeModal } = useGlobalIssueModal();
    const [workspaceId, setWorkspaceId] = useState<string>('');
    const [projectId, setProjectId] = useState<string>('');

    const { props } = usePage<PagePropsWithProject>();
    const activeProject = props.project;

    useEffect(() => {
        if (isOpen && activeProject?.id && activeProject?.workspace_id) {
            setWorkspaceId(activeProject.workspace_id);
            setProjectId(activeProject.id);
        }
    }, [isOpen, activeProject]);

    // Form fields
    const [title, setTitle] = useState('');
    const [typeId, setTypeId] = useState<string>('');
    const [priorityId, setPriorityId] = useState<string>('');
    const [assigneeId, setAssigneeId] = useState<string>('');

    const { data: workspaces, isLoading: isLoadingWorkspaces } =
        useMyWorkspaces();
    const { data: projects, isLoading: isLoadingProjects } =
        useWorkspaceProjects(workspaceId);
    const { data: lookups, isLoading: isLoadingLookups } =
        useProjectLookups(projectId);

    const createIssueMutation = useCreateIssue();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!projectId || !title || !typeId || !priorityId) return;

        createIssueMutation.mutate(
            {
                project_id: projectId,
                title,
                issue_type_id: typeId,
                priority_id: priorityId,
                assignee_id: assigneeId || null,
            },
            {
                onSuccess: () => {
                    resetForm();
                    closeModal();
                },
            },
        );
    };

    const resetForm = () => {
        setWorkspaceId('');
        setProjectId('');
        setTitle('');
        setTypeId('');
        setPriorityId('');
        setAssigneeId('');
    };

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            closeModal();
            setTimeout(resetForm, 300); // reset after transition
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{t('Create New Issue')}</DialogTitle>
                    <DialogDescription>
                        {t('Create a new issue across any of your projects.')}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="workspace">{t('Workspace')}</Label>
                        <Select
                            value={workspaceId}
                            disabled={!!activeProject?.workspace_id}
                            onValueChange={(val) => {
                                setWorkspaceId(val);
                                setProjectId('');
                                setTypeId('');
                                setPriorityId('');
                                setAssigneeId('');
                            }}
                        >
                            <SelectTrigger id="workspace">
                                <SelectValue
                                    placeholder={
                                        isLoadingWorkspaces
                                            ? 'Loading workspaces...'
                                            : 'Select Workspace'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {workspaces?.map((ws: WorkspaceItem) => (
                                    <SelectItem
                                        key={ws.id}
                                        value={ws.id.toString()}
                                    >
                                        {ws.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {workspaceId && (
                        <div className="space-y-2">
                            <Label htmlFor="project">{t('Project')}</Label>
                            <Select
                                value={projectId}
                                disabled={!!activeProject?.id}
                                onValueChange={(val) => {
                                    setProjectId(val);
                                    setTypeId('');
                                    setPriorityId('');
                                    setAssigneeId('');
                                }}
                            >
                                <SelectTrigger id="project">
                                    <SelectValue
                                        placeholder={
                                            isLoadingProjects
                                                ? 'Loading projects...'
                                                : 'Select Project'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {projects?.map((proj: ProjectItem) => (
                                        <SelectItem
                                            key={proj.id}
                                            value={proj.id.toString()}
                                        >
                                            {proj.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {projectId && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="title">
                                    {t('Issue Title')}
                                </Label>
                                <Input
                                    id="title"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder={t('Enter issue title')}
                                    required
                                />
                            </div>

                            {!isLoadingLookups &&
                                lookups &&
                                (() => {
                                    const issueTypes: LookupItem[] =
                                        lookups.issueTypes ||
                                        lookups.types ||
                                        [];
                                    const priorities: LookupItem[] =
                                        lookups.priorities || [];
                                    const assignees: LookupItem[] =
                                        lookups.users || lookups.members || [];

                                    return (
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="type">
                                                    {t('Type')} *
                                                </Label>
                                                <Select
                                                    value={typeId}
                                                    onValueChange={setTypeId}
                                                >
                                                    <SelectTrigger id="type">
                                                        <SelectValue
                                                            placeholder={t(
                                                                'Select Type',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {issueTypes.map(
                                                            (
                                                                type: LookupItem,
                                                            ) => (
                                                                <SelectItem
                                                                    key={
                                                                        type.id
                                                                    }
                                                                    value={type.id.toString()}
                                                                >
                                                                    {type.name}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="priority">
                                                    {t('Priority')} *
                                                </Label>
                                                <Select
                                                    value={priorityId}
                                                    onValueChange={
                                                        setPriorityId
                                                    }
                                                >
                                                    <SelectTrigger id="priority">
                                                        <SelectValue
                                                            placeholder={t(
                                                                'Select Priority',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {priorities.map(
                                                            (
                                                                priority: LookupItem,
                                                            ) => (
                                                                <SelectItem
                                                                    key={
                                                                        priority.id
                                                                    }
                                                                    value={priority.id.toString()}
                                                                >
                                                                    {
                                                                        priority.name
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="col-span-2 space-y-2">
                                                <Label htmlFor="assignee">
                                                    {t('Assignee')}
                                                </Label>
                                                <Select
                                                    value={assigneeId}
                                                    onValueChange={
                                                        setAssigneeId
                                                    }
                                                >
                                                    <SelectTrigger id="assignee">
                                                        <SelectValue
                                                            placeholder={t(
                                                                'Select Assignee',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {assignees.map(
                                                            (
                                                                member: LookupItem,
                                                            ) => (
                                                                <SelectItem
                                                                    key={
                                                                        member.id
                                                                    }
                                                                    value={member.id.toString()}
                                                                >
                                                                    {member.name ||
                                                                        member
                                                                            .user
                                                                            ?.name}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    );
                                })()}

                            <div className="flex justify-end pt-4">
                                <Button
                                    type="submit"
                                    disabled={
                                        !title ||
                                        !projectId ||
                                        !typeId ||
                                        !priorityId ||
                                        createIssueMutation.isPending
                                    }
                                >
                                    {createIssueMutation.isPending
                                        ? t('Creating...')
                                        : t('Create Issue')}
                                </Button>
                            </div>
                        </>
                    )}
                </form>
            </DialogContent>
        </Dialog>
    );
}
