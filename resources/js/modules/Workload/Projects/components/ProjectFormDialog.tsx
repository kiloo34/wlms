import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
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
import { useTranslate } from '@/hooks/useTranslate';
import { personLabel, usePeople } from '@/hooks/usePeople';
import { ScheduleFields } from '../../shared/ScheduleFields';

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
    const { t } = useTranslate();
    const isEditing = !!project;
    const { auth } = usePage().props as unknown as {
        auth?: { user?: { id?: number | string } };
    };
    const currentUserId = auth?.user?.id;

    const [key, setKey] = useState('');
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [workflowId, setWorkflowId] = useState<string>('none');
    const [priorityId, setPriorityId] = useState<string>('none');
    const [leadId, setLeadId] = useState<string>('none');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');

    const { data: workflows = [], isLoading: isLoadingWorkflows } =
        useWorkflows();
    const { data: people = [], isLoading: isLoadingPeople } = usePeople(open);

    useEffect(() => {
        if (open) {
            setKey(project?.key || '');
            setName(project?.name || '');
            setDescription(project?.description || '');
            setWorkflowId(project?.workflow_id || 'none');
            setPriorityId(project?.priority_id || 'none');
            // Project baru: owner default = pembuat project (sama seperti backend).
            const initialLead = project ? project.lead_id : currentUserId;
            setLeadId(
                initialLead !== undefined && initialLead !== null
                    ? String(initialLead)
                    : 'none',
            );
            setStartDate(project?.start_date?.slice(0, 10) || '');
            setEndDate(project?.end_date?.slice(0, 10) || '');
        }
    }, [open, project, currentUserId]);

    const workflowOptions = [
        { value: 'none', label: 'Default Workflow' },
        ...workflows.map((wf) => ({ value: wf.id, label: wf.name })),
    ];
    const priorityOptions = [
        { value: 'none', label: 'No Priority' },
        ...priorities.map((p) => ({ value: p.id, label: p.name })),
    ];
    const ownerOptions = [
        { value: 'none', label: t('No owner') },
        ...people.map((person) => ({
            value: String(person.id),
            label: personLabel(person),
        })),
    ];
    // Owner lama yang sudah nonaktif tetap tampil agar tidak hilang diam-diam.
    if (
        leadId !== 'none' &&
        !ownerOptions.some((option) => option.value === leadId)
    ) {
        ownerOptions.push({
            value: leadId,
            label: project?.lead?.name ?? `#${leadId}`,
        });
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const payloadWorkflowId =
            workflowId === 'none' ? undefined : workflowId;
        const payloadPriorityId =
            priorityId === 'none' ? undefined : priorityId;
        const shared = {
            name,
            description,
            workflow_id: payloadWorkflowId,
            priority_id: payloadPriorityId,
            lead_id: leadId === 'none' ? null : leadId,
            start_date: startDate || null,
            end_date: endDate || null,
        };

        if (isEditing) {
            onSubmit(shared);
        } else {
            onSubmit({ ...shared, workspace_id: workspaceId, key });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[560px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEditing ? 'Edit Project' : 'Create Project'}
                        </DialogTitle>
                        <DialogDescription>
                            {isEditing
                                ? 'Update your project details below.'
                                : 'Fill out the form below to create a new project.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {!isEditing && (
                            <div className="grid gap-2">
                                <Label htmlFor="key">{t('Key')}</Label>
                                <Input
                                    id="key"
                                    value={key}
                                    onChange={(e) =>
                                        setKey(e.target.value.toUpperCase())
                                    }
                                    placeholder="e.g. PROJ"
                                    maxLength={10}
                                    required
                                    disabled={isPending}
                                />
                                <p className="text-muted-foreground text-[0.8rem]">
                                    {t(
                                        'A unique short identifier for the project.',
                                    )}
                                </p>
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t('Name')}</Label>
                            <Input
                                id="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder={t('Project Name')}
                                required
                                disabled={isPending}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="project_owner">
                                {t('Project Owner')}
                            </Label>
                            <Combobox
                                id="project_owner"
                                options={ownerOptions}
                                value={leadId}
                                onChange={setLeadId}
                                disabled={isPending || isLoadingPeople}
                                placeholder={t('Search person...')}
                                emptyText={t('No user found.')}
                            />
                            <p className="text-muted-foreground text-[0.8rem]">
                                {t(
                                    'The person responsible for this project, chosen from registered users.',
                                )}
                            </p>
                        </div>
                        <ScheduleFields
                            idPrefix="project"
                            startDate={startDate}
                            endDate={endDate}
                            onChange={(next) => {
                                setStartDate(next.startDate);
                                setEndDate(next.endDate);
                            }}
                            disabled={isPending}
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                {t('Description & notes')}
                            </Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder={t(
                                    'Describe the purpose of this project...',
                                )}
                                rows={4}
                                disabled={isPending}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="workflow">{t('Workflow')}</Label>
                            <Combobox
                                id="workflow"
                                options={workflowOptions}
                                value={workflowId}
                                onChange={setWorkflowId}
                                disabled={isPending || isLoadingWorkflows}
                                placeholder={t('Select a workflow...')}
                            />
                            <p className="text-muted-foreground text-[0.8rem]">
                                {t(
                                    'Select a custom workflow to manage tasks statuses in this project.',
                                )}
                            </p>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="priority">
                                {t('Project Level Priority')}
                            </Label>
                            <Combobox
                                id="priority"
                                options={priorityOptions}
                                value={priorityId}
                                onChange={setPriorityId}
                                disabled={isPending}
                                placeholder={t('Select a priority...')}
                            />
                            <p className="text-muted-foreground text-[0.8rem]">
                                Optional urgency level indicating the project's
                                importance.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={isPending}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                isPending ||
                                (!isEditing && !key.trim()) ||
                                !name.trim()
                            }
                        >
                            {isPending ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
