import { useState, useEffect } from 'react';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface EditWorkspaceDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialName: string;
    onSubmit: (name: string) => void;
    isPending?: boolean;
}

export function EditWorkspaceDialog({ 
    open, 
    onOpenChange, 
    initialName, 
    onSubmit, 
    isPending = false 
}: EditWorkspaceDialogProps) {
    const [name, setName] = useState(initialName);

    // Reset name when dialog opens/closes or initialName changes
    useEffect(() => {
        if (open) {
            setName(initialName);
        }
    }, [open, initialName]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!name.trim() || name === initialName) {
            if (name === initialName) {
                onOpenChange(false);
            }
            return;
        }
        onSubmit(name);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit Workspace</DialogTitle>
                        <DialogDescription>
                            Change the name of your workspace.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="grid gap-4 py-6">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Workspace Name</Label>
                            <Input
                                id="edit-name"
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
                            onClick={() => onOpenChange(false)}
                            disabled={isPending}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isPending || !name.trim() || name === initialName}>
                            {isPending ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
