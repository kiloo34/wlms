import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { UserPlus } from 'lucide-react';
import type { Role } from '../../hooks/use-roles';
import type { User } from '../../hooks/use-users';
import { useTranslate } from "@/hooks/useTranslate";

interface UserAssignRoleDialogProps {
    user: User;
    roles: Role[];
    isSubmitting: boolean;
    onSubmit: (payload: { user_id: string; role_id: string }) => Promise<void>;
}

export function UserAssignRoleDialog({ user, roles, isSubmitting, onSubmit }: UserAssignRoleDialogProps) {
    const { t } = useTranslate();
    const [isOpen, setIsOpen] = useState(false);
    const [selectedRole, setSelectedRole] = useState<string>('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedRole) return;
        
        try {
            await onSubmit({ user_id: String(user.id), role_id: selectedRole });
            setIsOpen(false);
            setSelectedRole('');
        } catch (error) {
            // Error handling done by parent
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <UserPlus className="w-4 h-4 mr-2" />
                    {t('Assign Role')}
                                    </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Assign Role to')} {user.name}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="role">{t('Select Role')}</Label>
                        <select
                            id="role"
                            value={selectedRole}
                            onChange={(e) => setSelectedRole(e.target.value)}
                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        >
                            <option value="" disabled>{t('Select a role...')}</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name} ({role.scope})
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" disabled={isSubmitting || !selectedRole} className="w-full">
                        {isSubmitting ? 'Assigning...' : 'Assign Role'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

