import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import type { Status, CreateWorkflowDto } from '../../types';

interface WorkflowFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (payload: CreateWorkflowDto) => void;
    statuses: Status[];
    isLoading?: boolean;
}

export function WorkflowFormDialog({ open, onOpenChange, onSubmit, statuses, isLoading }: WorkflowFormDialogProps) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [isActive, setIsActive] = useState(true);
    
    useEffect(() => {
        if (open) {
            setName('');
            setDescription('');
            setIsActive(true);
            
        }
    }, [open]);

    
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit({ 
            name, 
            description,
            is_active: isActive,
             
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Create Workflow</DialogTitle>
                    <DialogDescription>
                        Create a new workflow and assign statuses to it.
                    </DialogDescription>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    <div className="space-y-2">
                        <Label htmlFor="workflow_name">Name</Label>
                        <Input 
                            id="workflow_name" 
                            value={name} 
                            onChange={(e) => setName(e.target.value)} 
                            placeholder="e.g. Software Development"
                            required
                        />
                    </div>
                    
                    <div className="space-y-2">
                        <Label htmlFor="workflow_desc">Description</Label>
                        <Textarea 
                            id="workflow_desc" 
                            value={description} 
                            onChange={(e) => setDescription(e.target.value)} 
                            placeholder="Optional description"
                        />
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox 
                            id="is_active" 
                            checked={isActive} 
                            onCheckedChange={(checked) => setIsActive(checked === true)}
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>

                                        
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading || !name.trim()}>
                            {isLoading ? 'Saving...' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
