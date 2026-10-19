import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { UserPlus } from 'lucide-react';
import axios from '@/lib/axios';
import type { Role } from '../../hooks/use-roles';
import type { User } from '../../hooks/use-users';
import { useTranslate } from '@/hooks/useTranslate';

interface UserAssignRoleDialogProps {
    user: User;
    roles: Role[];
    isSubmitting: boolean;
    onSubmit: (payload: {
        user_id: string;
        role_id: string;
        context_type?: string | null;
        context_id?: string | null;
    }) => Promise<void>;
}

export function UserAssignRoleDialog({
    user,
    roles,
    isSubmitting,
    onSubmit,
}: UserAssignRoleDialogProps) {
    const { t } = useTranslate();
    const [isOpen, setIsOpen] = useState(false);
    const [selectedRole, setSelectedRole] = useState<string>('');
    const [selectedWorkspaceId, setSelectedWorkspaceId] = useState<string>('');
    const [selectedProjectId, setSelectedProjectId] = useState<string>('');
    const [workspaces, setWorkspaces] = useState<
        { id: string; name: string }[]
    >([]);
    const [projects, setProjects] = useState<{ id: string; name: string }[]>(
        [],
    );

    const selectedScope = roles
        .find((r) => r.id === selectedRole)
        ?.scope?.toUpperCase();

    useEffect(() => {
        setSelectedWorkspaceId('');
        setSelectedProjectId('');
        setProjects([]);
        if (selectedScope === 'WORKSPACE' || selectedScope === 'PROJECT') {
            axios.get('/api/workspaces').then((res) => {
                const list = Array.isArray(res.data)
                    ? res.data
                    : res.data?.data || [];
                setWorkspaces(list);
            });
        }
    }, [selectedRole, selectedScope]);

    useEffect(() => {
        setSelectedProjectId('');
        if (selectedScope === 'PROJECT' && selectedWorkspaceId) {
            axios
                .get(`/api/workspaces/${selectedWorkspaceId}/projects`)
                .then((res) => {
                    const list = Array.isArray(res.data)
                        ? res.data
                        : res.data?.data || [];
                    setProjects(list);
                });
        } else {
            setProjects([]);
        }
    }, [selectedScope, selectedWorkspaceId]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRole) return;
        if (selectedScope === 'WORKSPACE' && !selectedWorkspaceId) return;
        if (selectedScope === 'PROJECT' && !selectedProjectId) return;

        try {
            const payload: {
                user_id: string;
                role_id: string;
                context_type?: string | null;
                context_id?: string | null;
            } = {
                user_id: String(user.id),
                role_id: selectedRole,
            };
            if (selectedScope === 'WORKSPACE') {
                payload.context_type = 'WORKSPACE';
                payload.context_id = selectedWorkspaceId;
            } else if (selectedScope === 'PROJECT') {
                payload.context_type = 'PROJECT';
                payload.context_id = selectedProjectId;
            }
            await onSubmit(payload);
            setIsOpen(false);
            setSelectedRole('');
            setSelectedWorkspaceId('');
            setSelectedProjectId('');
        } catch (error) {
            // Error handling done by parent
        }
    };

    const isContextIncomplete =
        (selectedScope === 'WORKSPACE' && !selectedWorkspaceId) ||
        (selectedScope === 'PROJECT' && !selectedProjectId);

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <UserPlus className="mr-2 h-4 w-4" />
                    {t('Assign Role')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Assign Role to')} {user.name}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="role">{t('Select Role')}</Label>
                        <select
                            id="role"
                            value={selectedRole}
                            onChange={(e) => setSelectedRole(e.target.value)}
                            className="border-input bg-background flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm"
                            required
                        >
                            <option value="" disabled>
                                {t('Select a role...')}
                            </option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name} ({role.scope})
                                </option>
                            ))}
                        </select>
                    </div>

                    {(selectedScope === 'WORKSPACE' ||
                        selectedScope === 'PROJECT') && (
                        <div className="space-y-2">
                            <Label htmlFor="workspace">
                                {t('Select Workspace')}
                            </Label>
                            <select
                                id="workspace"
                                value={selectedWorkspaceId}
                                onChange={(e) =>
                                    setSelectedWorkspaceId(e.target.value)
                                }
                                className="border-input bg-background flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm"
                                required
                            >
                                <option value="" disabled>
                                    {t('Select a workspace...')}
                                </option>
                                {workspaces.map((ws) => (
                                    <option key={ws.id} value={ws.id}>
                                        {ws.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {selectedScope === 'PROJECT' && selectedWorkspaceId && (
                        <div className="space-y-2">
                            <Label htmlFor="project">
                                {t('Select Project')}
                            </Label>
                            <select
                                id="project"
                                value={selectedProjectId}
                                onChange={(e) =>
                                    setSelectedProjectId(e.target.value)
                                }
                                className="border-input bg-background flex h-10 w-full items-center justify-between rounded-md border px-3 py-2 text-sm"
                                required
                            >
                                <option value="" disabled>
                                    {t('Select a project...')}
                                </option>
                                {projects.map((proj) => (
                                    <option key={proj.id} value={proj.id}>
                                        {proj.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    <Button
                        type="submit"
                        disabled={
                            isSubmitting || !selectedRole || isContextIncomplete
                        }
                        className="w-full"
                    >
                        {isSubmitting ? 'Assigning...' : 'Assign Role'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
