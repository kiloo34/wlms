import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Sprint } from '@/types/sprint';
import { useSprints, UpdateSprintPayload } from '../hooks/useSprints';
import { toast } from 'sonner';
import { addWeeks } from 'date-fns';



interface SprintFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sprint: Sprint | null;
    projectId: string;
    mode?: 'edit' | 'start';
}

export const SprintFormDialog: React.FC<SprintFormDialogProps> = ({ open, onOpenChange, sprint, projectId, mode = 'edit' }) => {
    const { updateSprint, startSprint, isUpdating, isStarting } = useSprints(projectId);
    
    const [name, setName] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [goal, setGoal] = useState('');
        const [duration, setDuration] = useState(mode === 'start' ? '2w' : 'custom');
    const [durationOpen, setDurationOpen] = useState(false);

    const durations = [
        { value: 'custom', label: 'Custom' },
        { value: '1w', label: '1 week' },
        { value: '2w', label: '2 weeks' },
        { value: '3w', label: '3 weeks' },
        { value: '4w', label: '4 weeks' },
    ];

    useEffect(() => {
        if (open && sprint) {
            setName(sprint.name);
            setGoal(sprint.goal || '');
            setStartDate(sprint.start_date ? sprint.start_date.substring(0, 16) : (mode === 'start' ? new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().substring(0, 16) : ''));
            setEndDate(sprint.end_date ? sprint.end_date.substring(0, 16) : '');
            setDuration(mode === 'start' ? '2w' : 'custom'); // Default based on mode
        }
    }, [open, sprint]);

    useEffect(() => {
        if (duration !== 'custom' && startDate) {
            const weeks = parseInt(duration.replace('w', ''));
            const newEndDate = addWeeks(new Date(startDate), weeks);
            // Format to YYYY-MM-DDThh:mm (respecting local timezone offset)
            const iso = new Date(newEndDate.getTime() - (newEndDate.getTimezoneOffset() * 60000)).toISOString().substring(0, 16);
            setEndDate(iso);
        }
    }, [duration, startDate]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!sprint) return;

        try {
            await updateSprint({
                id: sprint.id,
                payload: {
                    name,
                    goal: goal || undefined,
                    start_date: startDate ? new Date(startDate).toISOString() : undefined,
                    end_date: endDate ? new Date(endDate).toISOString() : undefined,
                }
            });
            
            if (mode === 'start') {
                await startSprint(sprint.id);
                toast.success("Sprint started successfully!");
            } else {
                toast.success("Sprint updated successfully");
            }
            onOpenChange(false);
        } catch (error: any) {
            toast.error(error.response?.data?.error || error.response?.data?.message || `Failed to ${mode} sprint`);
            console.error(error);
        }
    };

    if (!sprint) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{mode === 'start' ? 'Start sprint' : 'Edit sprint'}: {sprint.name}</DialogTitle>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    <p className="text-xs text-muted-foreground">Required fields are marked with an asterisk <span className="text-destructive">*</span></p>
                    
                    <div className="space-y-2">
                        <Label>Sprint name <span className="text-destructive">*</span></Label>
                        <Input required value={name} onChange={e => setName(e.target.value)} />
                    </div>

                    <div className="space-y-2">
                        <Label>Duration</Label>
                        <Popover open={durationOpen} onOpenChange={setDurationOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    aria-expanded={durationOpen}
                                    className="w-full justify-between font-normal"
                                >
                                    {durations.find((d) => d.value === duration)?.label || "Select duration..."}
                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-[450px] p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Search duration..." />
                                    <CommandList>
                                        <CommandEmpty>No duration found.</CommandEmpty>
                                        <CommandGroup>
                                            {durations.map((d) => (
                                                <CommandItem
                                                    key={d.value}
                                                    value={d.value}
                                                    onSelect={(currentValue) => {
                                                        setDuration(currentValue);
                                                        setDurationOpen(false);
                                                    }}
                                                >
                                                    <Check
                                                        className={cn(
                                                            "mr-2 h-4 w-4",
                                                            duration === d.value ? "opacity-100" : "opacity-0"
                                                        )}
                                                    />
                                                    {d.label}
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label>Start date</Label>
                            <Input type="datetime-local" value={startDate} onChange={e => setStartDate(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>End date</Label>
                            <Input 
                                type="datetime-local" 
                                value={endDate} 
                                onChange={e => setEndDate(e.target.value)} 
                                readOnly={duration !== 'custom'}
                                className={duration !== 'custom' ? 'bg-muted opacity-70 cursor-not-allowed' : ''}
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>Sprint goal</Label>
                        <Textarea 
                            rows={4} 
                            value={goal} 
                            onChange={e => setGoal(e.target.value)} 
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => onOpenChange(false)}>Cancel</Button>
                        <Button type="submit" disabled={isUpdating || (mode === 'start' && isStarting)}>
                            {mode === 'start' ? (isUpdating || isStarting ? 'Starting...' : 'Start') : (isUpdating ? 'Updating...' : 'Update')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
