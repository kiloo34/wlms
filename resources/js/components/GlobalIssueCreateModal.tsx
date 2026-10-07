import React, { useState } from 'react';
import { useGlobalIssueModal } from '@/contexts/GlobalIssueModalContext';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useMyWorkspaces, useWorkspaceProjects, useProjectLookups, useCreateIssue } from '@/hooks/api/issues';
import { useTranslate } from '@/hooks/useTranslate';


export function GlobalIssueCreateModal() {
    const { t } = useTranslate();

    const { isOpen, closeModal } = useGlobalIssueModal();
    const [workspaceId, setWorkspaceId] = useState<string>('');
    const [projectId, setProjectId] = useState<string>('');
    
    // Form fields
    const [title, setTitle] = useState('');
    const [typeId, setTypeId] = useState<string>('');
    const [priorityId, setPriorityId] = useState<string>('');
    const [assigneeId, setAssigneeId] = useState<string>('');

    const { data: workspaces, isLoading: isLoadingWorkspaces } = useMyWorkspaces();
    const { data: projects, isLoading: isLoadingProjects } = useWorkspaceProjects(workspaceId);
    const { data: lookups, isLoading: isLoadingLookups } = useProjectLookups(projectId);

    const createIssueMutation = useCreateIssue();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!projectId || !title) return;

        createIssueMutation.mutate({
            project_id: projectId,
            title,
            type_id: typeId || null,
            priority_id: priorityId || null,
            assignee_id: assigneeId || null,
        }, {
            onSuccess: () => {
                resetForm();
                closeModal();
            }
        });
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
                            onValueChange={(val) => {
                                setWorkspaceId(val);
                                setProjectId('');
                                setTypeId('');
                                setPriorityId('');
                                setAssigneeId('');
                            }}
                        >
                            <SelectTrigger id="workspace">
                                <SelectValue placeholder={isLoadingWorkspaces ? "Loading workspaces..." : "Select Workspace"} />
                            </SelectTrigger>
                            <SelectContent>
                                {workspaces?.map((ws: any) => (
                                    <SelectItem key={ws.id} value={ws.id.toString()}>
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
                                onValueChange={(val) => {
                                    setProjectId(val);
                                    setTypeId('');
                                    setPriorityId('');
                                    setAssigneeId('');
                                }}
                            >
                                <SelectTrigger id="project">
                                    <SelectValue placeholder={isLoadingProjects ? "Loading projects..." : "Select Project"} />
                                </SelectTrigger>
                                <SelectContent>
                                    {projects?.map((proj: any) => (
                                        <SelectItem key={proj.id} value={proj.id.toString()}>
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
                                <Label htmlFor="title">{t('Issue Title')}</Label>
                                <Input 
                                    id="title" 
                                    value={title} 
                                    onChange={(e) => setTitle(e.target.value)} 
                                    placeholder={t('Enter issue title')}
                                    required 
                                />
                            </div>

                            {!isLoadingLookups && lookups && (
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="type">{t('Type')}</Label>
                                        <Select value={typeId} onValueChange={setTypeId}>
                                            <SelectTrigger id="type">
                                                <SelectValue placeholder={t('Select Type')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {lookups.types?.map((type: any) => (
                                                    <SelectItem key={type.id} value={type.id.toString()}>
                                                        {type.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="priority">{t('Priority')}</Label>
                                        <Select value={priorityId} onValueChange={setPriorityId}>
                                            <SelectTrigger id="priority">
                                                <SelectValue placeholder={t('Select Priority')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {lookups.priorities?.map((priority: any) => (
                                                    <SelectItem key={priority.id} value={priority.id.toString()}>
                                                        {priority.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2 col-span-2">
                                        <Label htmlFor="assignee">{t('Assignee')}</Label>
                                        <Select value={assigneeId} onValueChange={setAssigneeId}>
                                            <SelectTrigger id="assignee">
                                                <SelectValue placeholder={t('Select Assignee')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {lookups.members?.map((member: any) => (
                                                    <SelectItem key={member.id} value={member.id.toString()}>
                                                        {member.name || member.user?.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end pt-4">
                                <Button 
                                    type="submit" 
                                    disabled={!title || createIssueMutation.isPending}
                                >
                                    {createIssueMutation.isPending ? "Creating..." : "Create Issue"}
                                </Button>
                            </div>
                        </>
                    )}
                </form>
            </DialogContent>
        </Dialog>
    );
}
