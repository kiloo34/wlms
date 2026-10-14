import React, { useState } from 'react';
import { useDroppable } from '@dnd-kit/core';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { Issue } from '@/types/issue';
import { Button } from '@/components/ui/button';
import { useTranslate } from '@/hooks/useTranslate';
import { useIconSize } from '@/hooks/use-appearance';
import { BacklogIssueRow, LookupData } from './BacklogIssueRow';
import { InlineCreateIssue } from './DroppableSprint';

export interface DroppableBacklogProps {
    issues: Issue[];
    lookups?: LookupData;
    projectId: string;
    updateIssue: any;
    assignIssue: any;
    isOwner: boolean;
    onIssueClick?: (issue: Issue) => void;
    onCreateSprint: () => void;
}

export const DroppableBacklog: React.FC<DroppableBacklogProps> = ({
    issues,
    lookups,
    projectId,
    updateIssue,
    assignIssue,
    isOwner,
    onIssueClick,
    onCreateSprint,
}) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { setNodeRef, isOver } = useDroppable({
        id: 'backlog',
        data: { type: 'Backlog' },
    });
    const [isExpanded, setIsExpanded] = useState(true);

    const headerPaddingClass =
        iconSize === 'sm'
            ? 'py-1.5 px-2 text-xs'
            : iconSize === 'lg'
            ? 'py-3 px-3 text-base'
            : 'py-2 px-2 text-sm';

    const backlogTitleClass =
        iconSize === 'sm' ? 'font-semibold text-xs' : iconSize === 'lg' ? 'font-bold text-base' : 'font-semibold text-sm';

    const pillBoxClass =
        iconSize === 'sm' ? 'w-4.5 h-4.5 text-[9px]' : iconSize === 'lg' ? 'w-6 h-6 text-xs font-semibold' : 'w-5 h-5 text-[10px]';

    const backlogBtnClass =
        iconSize === 'sm' ? 'h-6.5 text-[11px] px-2.5 font-medium' : iconSize === 'lg' ? 'h-8 text-sm px-3.5 font-medium' : 'h-7 text-xs px-3 font-medium';

    return (
        <div className={`mt-8 rounded-md transition-colors border ${isOver ? 'border-primary ring-1 ring-primary bg-primary/5' : 'border-border/60 bg-card'} shadow-sm overflow-hidden`}>
            <div className={`flex items-center justify-between ${headerPaddingClass} bg-muted/20 border-b border-border/50`}>
                <div className="flex items-center gap-2">
                    <button onClick={() => setIsExpanded(!isExpanded)} className="p-1 hover:bg-muted rounded-sm text-muted-foreground">
                        {isExpanded ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
                    </button>
                    <h3 className={backlogTitleClass}>{t('Backlog')}</h3>
                    <span className="text-xs text-muted-foreground">({issues.length} work items)</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-1 mr-2 opacity-70">
                        <div className={`${pillBoxClass} rounded-sm bg-secondary flex items-center justify-center font-medium text-secondary-foreground`}>0</div>
                        <div className={`${pillBoxClass} rounded-sm bg-info flex items-center justify-center font-medium text-info-foreground`}>0</div>
                        <div className={`${pillBoxClass} rounded-sm bg-success flex items-center justify-center font-medium text-success-foreground`}>0</div>
                    </div>
                    {isOwner && (
                        <Button size="sm" variant="secondary" onClick={onCreateSprint} className={`${backlogBtnClass} bg-muted hover:bg-muted/80 text-foreground`}>
                            {t('Create sprint')}
                        </Button>
                    )}
                </div>
            </div>

            {isExpanded && (
                <div ref={setNodeRef} className="bg-background flex flex-col">
                    {issues.length > 0 ? (
                        issues.map((issue) => (
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
                            {t('Your backlog is empty.')}
                        </div>
                    )}
                    <InlineCreateIssue projectId={projectId} sprintId={null} lookups={lookups} />
                </div>
            )}
        </div>
    );
};

