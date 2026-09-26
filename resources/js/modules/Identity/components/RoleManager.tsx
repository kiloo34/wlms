import { useRoles, useCreateRole, useDeleteRole } from '../hooks/use-roles';
import { Shield, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { RoleList } from './RoleList';
import { RoleCreateDialog } from './RoleCreateDialog';

// Ini adalah Smart Component
// Tugasnya murni menghandle Server State (React Query) dan interaksi API.
// Tidak ada hardcode UI rumit di sini, UI diserahkan ke Dumb Component.
export function RoleManager() {
    const { data: roles, isLoading } = useRoles();
    const createRole = useCreateRole();
    const deleteRole = useDeleteRole();

    if (isLoading) {
        return (
            <div className="flex justify-center p-8">
                <Loader2 className="animate-spin text-muted-foreground w-8 h-8" />
            </div>
        );
    }

    const handleCreate = async (payload: { name: string; scope: 'GLOBAL' | 'WORKSPACE' | 'PROJECT' }) => {
        try {
            await createRole.mutateAsync(payload);
            toast.success('Role created successfully');
        } catch (error) {
            toast.error('Failed to create role');
            throw error; // Propagate error so dialog knows it failed
        }
    };

    const handleDelete = async (id: string) => {
        try {
            await deleteRole.mutateAsync(id);
            toast.success('Role deleted successfully');
        } catch (error) {
            toast.error('Failed to delete role');
        }
    };

    return (
        <div className="space-y-4">
            <RoleList 
                roles={roles || []} 
                onDelete={handleDelete} 
            />
        </div>
    );
}
