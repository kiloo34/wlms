import { useEffect, useState } from 'react';
import { Project, CreateProjectPayload, UpdateProjectPayload } from '../types';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Combobox } from '@/components/ui/combobox';
import { useWorkflows } from '../../Workflows/hooks/useWorkflows';

interface ProjectFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    project?: Project | null;
    workspaceId: string;
    priorities?: { id: string; name: string; category: string }[];
    onSubmit: (payload: CreateProjectPayload | UpdateProjectPayload) => void;
    isPending?: boolean;
}

export function ProjectFormDialog({
    open,
    onOpenChange,
    project,
    workspaceId,
    priorities = [],
    onSubmit,
    isPending = false,
}: ProjectFormDialogProps) {
    const isEditing = !!project;

    const [key, setKey] = useState('');
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [workflowId, setWorkflowId] = useState<string>('none');
    const [priorityId, setPriorityId] = useState<string>('none');

    const { data: workflows = [], isLoading: isLoadingWorkflows } = useWorkflows();

    useEffect(() => {
        if (open) {
            setKey(project?.key || '');
            setName(project?.name || '');
            setDescription(project?.description || '');
            setWorkflowId(project?.workflow_id || 'none');
            setPriorityId(project?.priority_id || 'none');
        }
    }, [open, project]);

    const workflowOptions = [{ value: 'none', label: 'Default Workflow' }, ...workflows.map(wf => ({ value: wf.id, label: wf.name }))];
    const priorityOptions = [{ value: 'none', label: 'No Priority' }, ...priorities.map(p => ({ value: p.id, label: p.name }))];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const payloadWorkflowId = workflowId === 'none' ? undefined : workflowId;
        const payloadPriorityId = priorityId === 'none' ? undefined : priorityId;
        
        if (isEditing) {
            onSubmit({ name, description, workflow_id: payloadWorkflowId, priority_id: payloadPriorityId });
        } else {
            onSubmit({ workspace_id: workspaceId, key, name, description, workflow_id: payloadWorkflowId, priority_id: payloadPriorityId });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>{isEditing ? 'Edit Project' : 'Create Project'}</DialogTitle>
                        <DialogDescription>
                            {isEditing
                                ? 'Update your project details below.'
                                : 'Fill out the form below to create a new project.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {!isEditing && (
                            <div className="grid gap-2">
                                <Label htmlFor="key">Key</Label>
                                <Input
                                    id="key"
                                    value={key}
                                    onChange={(e) => setKey(e.target.value.toUpperCase())}
                                    placeholder="e.g. PROJ"
                                    maxLength={10}
                                    required
                                    disabled={isPending}
                                />
                                <p className="text-[0.8rem] text-muted-foreground">
                                    A unique short identifier for the project.
                                </p>
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="Project Name"
                                required
                                disabled={isPending}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">Description (Optional)</Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Describe the purpose of this project..."
                                disabled={isPending}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="workflow">Workflow</Label>
                            <Combobox 
                                id="workflow"
                                options={workflowOptions}
                                value={workflowId}
                                onChange={setWorkflowId}
                                disabled={isPending || isLoadingWorkflows}
                                placeholder="Select a workflow..."
                            />
                            <p className="text-[0.8rem] text-muted-foreground">
                                Select a custom workflow to manage tasks statuses in this project.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="priority">Project Level Priority</Label>
                            <Combobox 
                                id="priority"
                                options={priorityOptions}
                                value={priorityId}
                                onChange={setPriorityId}
                                disabled={isPending}
                                placeholder="Select a priority..."
                            />
                            <p className="text-[0.8rem] text-muted-foreground">
                                Optional urgency level indicating the project's importance.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isPending}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isPending || (!isEditing && !key.trim()) || !name.trim()}>
                            {isPending ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
