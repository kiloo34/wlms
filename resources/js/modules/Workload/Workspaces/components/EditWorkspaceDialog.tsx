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
import { useTranslate } from '@/hooks/useTranslate';

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
    const { t } = useTranslate();
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
                        <DialogTitle>{t('Edit Workspace')}</DialogTitle>
                        <DialogDescription>
                            {t('Change the name of your workspace.')}
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="grid gap-4 py-6">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">{t('Workspace Name')}</Label>
                            <Input
                                id="edit-name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder={t('e.g. Project Alpha')}
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
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={isPending || !name.trim() || name === initialName}>
                            {isPending ? t('Saving...') : t('Save Changes')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
