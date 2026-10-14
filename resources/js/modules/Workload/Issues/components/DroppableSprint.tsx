import React, { useState, KeyboardEvent } from 'react';
import { format } from 'date-fns';
import { useDroppable } from '@dnd-kit/core';
import { ChevronDown, ChevronRight, MoreHorizontal, Plus } from 'lucide-react';
import { Issue } from '@/types/issue';
import { Sprint } from '@/types/sprint';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslate } from '@/hooks/useTranslate';
import { useIconSize } from '@/hooks/use-appearance';
import { useIssues } from '../hooks/useIssues';
import { useSprintWorkload } from '../hooks/useSprints';
import { SprintWorkloadWidget } from './SprintWorkloadWidget';
import { BacklogIssueRow, LookupData } from './BacklogIssueRow';

export const InlineCreateIssue = ({
    projectId,
    sprintId,
    lookups,
}: {
    projectId: string;
    sprintId: string | null;
    lookups?: LookupData;
}) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { createIssue } = useIssues(projectId);
    const [title, setTitle] = useState('');

    const inputRowClass =
        iconSize === 'sm'
            ? 'px-2.5 py-1.5 text-xs'
            : iconSize === 'lg'
            ? 'px-4 py-3 text-base'
            : 'px-3 py-2 text-sm';

    const handleKeyDown = async (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && title.trim()) {
            try {
                await createIssue({
                    title: title.trim(),
                    issue_type_id: lookups?.issueTypes?.[0]?.id,
                    priority_id: lookups?.priorities?.[0]?.id,
                    sprint_id: sprintId,
                } as any);
                setTitle('');
            } catch (err) {
                console.error('Failed to create issue', err);
            }
        }
    };

    return (
        <div className={`flex items-center gap-3 ${inputRowClass} bg-card border-b border-border/50 focus-within:ring-1 focus-within:ring-primary focus-within:border-primary rounded-b-md transition-all`}>
            <Plus className="w-4 h-4 text-muted-foreground" />
            <input
                type="text"
                placeholder={t('What needs to be done?')}
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                onKeyDown={handleKeyDown}
                className="flex-1 bg-transparent border-none focus:outline-none placeholder:text-muted-foreground"
            />
        </div>
    );
};

export interface DroppableSprintProps {
    sprint: Sprint;
    issues: Issue[];
    lookups?: LookupData;
    onStart: (id: string) => void;
    onComplete: (id: string) => void;
    projectId: string;
    updateIssue: any;
    assignIssue: any;
    isOwner: boolean;
    onIssueClick?: (issue: Issue) => void;
    onEditSprint: (sprint: Sprint, mode: 'edit' | 'start') => void;
}

export const DroppableSprint: React.FC<DroppableSprintProps> = ({
    sprint,
    issues,
    lookups,
    onStart,
    onComplete,
    projectId,
    updateIssue,
    assignIssue,
    isOwner,
    onIssueClick,
    onEditSprint,
}) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { setNodeRef, isOver } = useDroppable({
        id: sprint.id,
        data: { type: 'Sprint', sprint },
    });
    const [isExpanded, setIsExpanded] = useState(true);
    const { data: workload, isLoading: isLoadingWorkload } = useSprintWorkload(sprint.id);

    const headerPaddingClass =
        iconSize === 'sm'
            ? 'py-1.5 px-2 text-xs'
            : iconSize === 'lg'
            ? 'py-3 px-3 text-base'
            : 'py-2 px-2 text-sm';

    const sprintTitleClass =
        iconSize === 'sm' ? 'font-semibold text-xs' : iconSize === 'lg' ? 'font-bold text-base' : 'font-semibold text-sm';

    const pillBoxClass =
        iconSize === 'sm' ? 'w-4.5 h-4.5 text-[9px]' : iconSize === 'lg' ? 'w-6 h-6 text-xs font-semibold' : 'w-5 h-5 text-[10px]';

    const sprintBtnClass =
        iconSize === 'sm' ? 'h-6.5 text-[11px] px-2.5 font-medium' : iconSize === 'lg' ? 'h-8 text-sm px-3.5 font-medium' : 'h-7 text-xs px-3 font-medium';

    return (
        <div className={`mb-6 rounded-md transition-colors border ${isOver ? 'border-primary ring-1 ring-primary bg-primary/5' : 'border-border/60 bg-card'} shadow-sm overflow-hidden`}>
            <div className={`flex items-center justify-between ${headerPaddingClass} bg-muted/20 border-b border-border/50`}>
                <div className="flex items-center gap-2">
                    <button onClick={() => setIsExpanded(!isExpanded)} className="p-1 hover:bg-muted rounded-sm text-muted-foreground">
                        {isExpanded ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                    </button>
                    <h3 className={sprintTitleClass}>{sprint.name}</h3>
                    <span onClick={() => isOwner && onEditSprint(sprint, 'edit')} className={`text-xs text-muted-foreground mx-2 ${isOwner ? 'cursor-pointer hover:underline hover:text-foreground' : ''}`}>{sprint.start_date ? `${format(new Date(sprint.start_date), 'd MMM')} - ${format(new Date(sprint.end_date || new Date()), 'd MMM')}` : 'Add dates'}</span>
                    <span className="text-xs text-muted-foreground">({issues.length} work items)</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-1 mr-2 opacity-70">
                        <div className={`${pillBoxClass} rounded-sm bg-secondary flex items-center justify-center font-medium text-secondary-foreground`} title={t('To Do')}>0</div>
                        <div className={`${pillBoxClass} rounded-sm bg-info flex items-center justify-center font-medium text-info-foreground`} title={t('In Progress')}>0</div>
                        <div className={`${pillBoxClass} rounded-sm bg-success flex items-center justify-center font-medium text-success-foreground`} title={t('Done')}>0</div>
                    </div>
                    {sprint.state?.toUpperCase() === 'PENDING' && isOwner && (
                        <Button size="sm" variant="secondary" className={`${sprintBtnClass} bg-muted hover:bg-muted/80 text-foreground`} onClick={() => onEditSprint(sprint, 'start')}>{t('Start sprint')}</Button>
                    )}
                    {sprint.state?.toUpperCase() === 'ACTIVE' && isOwner && (
                        <Button size="sm" variant="default" className={sprintBtnClass} onClick={() => onComplete(sprint.id)}>{t('Complete sprint')}</Button>
                    )}
                    {isOwner && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button size="icon" variant="ghost" className={`${iconSize === 'sm' ? 'h-6.5 w-6.5' : iconSize === 'lg' ? 'h-8 w-8' : 'h-7 w-7'} text-muted-foreground`}><MoreHorizontal className="w-4 h-4" /></Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={() => onEditSprint(sprint, 'edit')}>{t('Edit sprint')}</DropdownMenuItem>
                                <DropdownMenuItem className="text-destructive">{t('Delete sprint')}</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>

            {isExpanded && (
                <div ref={setNodeRef} className="bg-background flex flex-col">
                    {(workload && workload.length > 0) && (
                        <div className="px-4 pb-2 pt-1 border-b border-border/50 bg-card/30">
                            <SprintWorkloadWidget workload={workload} isLoading={isLoadingWorkload} />
                        </div>
                    )}
                    {issues.length > 0 ? (
                        issues.map(issue => (
                            <BacklogIssueRow
                                key={issue.id}
                                issue={issue}
                                lookups={lookups}
                                updateIssue={updateIssue}
                                assignIssue={assignIssue}
                                isOwner={isOwner}
                                onIssueClick={onIssueClick}
                            />
                        ))
                    ) : (
                        <div className="h-16 border-dashed border-2 border-transparent flex items-center justify-center text-xs text-muted-foreground m-1 rounded bg-muted/10">
                            {t('Plan a sprint by dragging work items into it.')}
                        </div>
                    )}
                    <InlineCreateIssue projectId={projectId} sprintId={sprint.id} lookups={lookups} />
                </div>
            )}
        </div>
    );
};

