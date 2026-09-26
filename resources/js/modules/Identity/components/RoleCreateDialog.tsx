import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus } from 'lucide-react';
import { useCreateRole } from '../hooks/use-roles';
import { toast } from 'sonner';

type RoleScope = 'GLOBAL' | 'WORKSPACE' | 'PROJECT';

export function RoleCreateDialog() {
    const [isOpen, setIsOpen] = useState(false);
    const [name, setName] = useState('');
    const [scope, setScope] = useState<RoleScope>('GLOBAL');
    
    const createRole = useCreateRole();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await createRole.mutateAsync({ name, scope });
            toast.success('Role created successfully');
            setIsOpen(false);
            setName('');
            setScope('GLOBAL');
        } catch (error) {
            toast.error('Failed to create role');
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button className="bg-blue-600 hover:bg-blue-700 text-white">
                    <Plus className="w-4 h-4 mr-2" /> Create Role
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create New Role</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Role Name</Label>
                        <Input 
                            id="name" 
                            value={name} 
                            onChange={(e) => setName(e.target.value)} 
                            placeholder="e.g. Developer"
                            required 
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="scope">Scope</Label>
                        <select 
                            id="scope" 
                            value={scope} 
                            onChange={(e) => setScope(e.target.value as RoleScope)}
                            className="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="GLOBAL">Global</option>
                            <option value="WORKSPACE">Workspace</option>
                            <option value="PROJECT">Project</option>
                        </select>
                    </div>
                    <Button type="submit" disabled={createRole.isPending} className="w-full">
                        {createRole.isPending ? 'Creating...' : 'Save Role'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

