import { useState } from 'react';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateWorkspace } from '../hooks/useWorkspaces';
import { toast } from 'sonner';

interface CreateWorkspaceModalProps {
    children: React.ReactNode;
}

export function CreateWorkspaceModal({ children }: CreateWorkspaceModalProps) {
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const { mutate: createWorkspace, isPending } = useCreateWorkspace();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!name.trim()) return;

        createWorkspace(
            { name },
            {
                onSuccess: () => {
                    toast.success('Workspace created successfully!');
                    setOpen(false);
                    setName('');
                },
                onError: (error: any) => {
                    toast.error(
                        error.response?.data?.message || 'Failed to create workspace'
                    );
                }
            }
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {children}
            </DialogTrigger>
            <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create Workspace</DialogTitle>
                        <DialogDescription>
                            Create a new workspace for your team to manage projects and tasks.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="grid gap-4 py-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Workspace Name</Label>
                            <Input
                                id="name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="e.g. Project Alpha"
                                disabled={isPending}
                                autoFocus
                            />
                        </div>
                    </div>
                    
                    <DialogFooter>
                        <Button 
                            type="button" 
                            variant="outline" 
                            onClick={() => setOpen(false)}
                            disabled={isPending}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isPending || !name.trim()}>
                            {isPending ? 'Creating...' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

