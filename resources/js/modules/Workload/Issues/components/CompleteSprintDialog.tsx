import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Sprint } from '@/types/sprint';
import { Issue } from '@/types/issue';
import { Trophy } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useTranslate } from "@/hooks/useTranslate";

interface CompleteSprintDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sprint: Sprint | null;
    issues: Issue[];
    availableSprints: Sprint[];
    onComplete: (sprintId: string, moveToSprintId: string | null) => Promise<void>;
    isCompleting: boolean;
}

export const CompleteSprintDialog: React.FC<CompleteSprintDialogProps> = ({
    open,
    onOpenChange,
    sprint,
    issues,
    availableSprints,
    onComplete,
    isCompleting
}) => {
    const { t } = useTranslate();
    const [moveTo, setMoveTo] = useState<string>('backlog');

    if (!sprint) return null;

    // Calculate completed vs open
    const sprintIssues = issues.filter(i => i.sprint_id === sprint.id);
    const completedCount = sprintIssues.filter(i => i.status?.category === 'DONE' || i.status?.name?.toLowerCase() === 'done').length;
    const openCount = sprintIssues.length - completedCount;

    // Filter available sprints for the dropdown (exclude current sprint and completed sprints)
    const targetSprints = availableSprints.filter(s => s.id !== sprint.id && s.state !== 'COMPLETED');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const targetId = moveTo === 'backlog' ? null : moveTo;
        await onComplete(sprint.id, targetId);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px] p-0 overflow-hidden">
                <div className="bg-gradient-to-r from-blue-500 to-cyan-400 h-32 flex items-center justify-center relative overflow-hidden">
                    {/* Abstract wave shapes can be done with SVG, but we'll keep it simple */}
                    <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-white to-transparent"></div>
                    <div className="bg-yellow-400 p-4 rounded-full shadow-lg z-10 border-4 border-white/20">
                        <Trophy className="w-10 h-10 text-white" />
                    </div>
                </div>
                
                <form onSubmit={handleSubmit} className="p-6 pt-4 space-y-6">
                    <DialogHeader>
                        <DialogTitle className="text-xl font-bold">{t('Complete')} {sprint.name}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 text-sm">
                        <p>
                            {t('This sprint contains')} <span className="font-bold">{completedCount} {t('completed work items')}</span> {t('and')} <span className="font-bold">{openCount} {t('open work items')}</span>.
                        </p>
                        
                        <ul className="list-disc pl-5 space-y-2 text-muted-foreground">
                            <li>{t('Completed work items includes everything in the last column on the board, Done.')}</li>
                            <li>{t('Open work items includes everything from any other column on the board. Move these to a new sprint or the backlog.')}</li>
                        </ul>
                        
                        {openCount > 0 && (
                            <div className="space-y-2 pt-2">
                                <label className="font-medium text-sm">{t('Move open work items to')}</label>
                                <Combobox 
                                    options={[
                                        ...targetSprints.map(ts => ({ value: ts.id, label: ts.name })),
                                        { value: 'backlog', label: 'Backlog' }
                                    ]}
                                    value={moveTo}
                                    onChange={(val) => setMoveTo(val || 'backlog')}
                                    placeholder={t('Select destination')}
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0 pt-2">
                        <Button type="button" variant="ghost" onClick={() => onOpenChange(false)}>{t('Cancel')}</Button>
                        <Button type="submit" disabled={isCompleting}>
                            {isCompleting ? 'Completing...' : 'Complete sprint'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
