import React, { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { Issue } from '@/types/issue';
import { SharedData } from '@/types';
import { SelectOption } from './IssueFormDialog';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { PriorityBadge } from '@/components/PriorityBadge';
import { DndContext, DragEndEvent, DragOverlay, DragStartEvent, MouseSensor, TouchSensor, useSensor, useSensors, closestCorners } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useTranslate } from "@/hooks/useTranslate";
import { useIconSize } from '@/hooks/use-appearance';

interface KanbanBoardProps {
    issues: Issue[];
    statuses: SelectOption[];
    lookups?: {
        issueTypes: {id: string, name: string}[];
        priorities: {id: string, name: string; category?: string}[];
        users: {id: string, name: string}[];
    };
    onTransition: (issueId: string, toStatusId: string) => void;
    onEdit: (issue: Issue) => void;
    onLogWork: (issue: Issue) => void;
}

const SortableIssueCard = ({ issue, onEdit, onLogWork, lookups }: { issue: Issue, onEdit: (issue: Issue) => void, onLogWork: (issue: Issue) => void, lookups?: { issueTypes: {id:string,name:string}[], priorities: {id:string,name:string; category?: string}[], users: {id:string,name:string}[] } }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: issue.id, data: { type: 'Issue', issue } });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    const typeName = lookups?.issueTypes?.find((t) => String(t.id) === String(issue.issue_type_id))?.name || 'Task';
    const priorityObj = lookups?.priorities?.find((p) => String(p.id) === String(issue.priority_id));
    const priorityName = priorityObj?.name || 'Normal';
    const assigneeName = issue.assignee?.name || lookups?.users?.find((u) => String(u.id) === String(issue.assignee_id))?.name || 'Unassigned';

    const { auth } = usePage<SharedData>().props;
    const currentUserId: number | null = auth.user?.id ?? null;
    const canLogWork = !!(currentUserId && issue.assignee_id && String(currentUserId) === String(issue.assignee_id));

    // Dynamic sizing styles based on global iconSize
    const cardPaddingClass =
        iconSize === 'sm'
            ? 'p-2.5 mb-2 gap-2 rounded-md'
            : iconSize === 'lg'
            ? 'p-5 mb-3.5 gap-3 rounded-xl'
            : 'p-3.5 mb-2.5 gap-2.5 rounded-lg';

    const titleClass =
        iconSize === 'sm'
            ? 'font-medium text-xs leading-snug pr-8 cursor-pointer hover:text-primary transition-colors'
            : iconSize === 'lg'
            ? 'font-semibold text-base leading-snug pr-12 cursor-pointer hover:text-primary transition-colors'
            : 'font-medium text-sm leading-snug pr-10 cursor-pointer hover:text-primary transition-colors';

    const badgeClass =
        iconSize === 'sm'
            ? 'text-[9px] px-1 py-0 h-4'
            : iconSize === 'lg'
            ? 'text-xs px-2 py-0.5 h-6'
            : 'text-[10px] px-1.5 py-0 h-5';

    const avatarSizeClass =
        iconSize === 'sm'
            ? 'h-5 w-5 text-[9px]'
            : iconSize === 'lg'
            ? 'h-8 w-8 text-xs'
            : 'h-6 w-6 text-[10px]';

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            onClick={() => onEdit(issue)}
            className={`bg-card text-card-foreground border shadow-xs cursor-pointer hover:border-primary/50 group relative flex flex-col transition-colors ${cardPaddingClass}`}
        >
            <div className={titleClass}>{issue.title}</div>
            
            <div className="flex items-center justify-between mt-auto pt-1 gap-2">
                <div className="flex items-center gap-1.5 flex-wrap">
                    <Badge variant="outline" className={`${badgeClass} bg-muted/50 font-normal`}>
                        {typeName}
                    </Badge>
                    <PriorityBadge 
                        name={priorityName} 
                        category={priorityObj?.category} 
                        size={iconSize === 'lg' ? 'md' : 'sm'} 
                    />
                </div>
                
                <Avatar className={`${avatarSizeClass} border bg-muted shrink-0`}>
                    {issue.assignee_id ? (
                        <AvatarFallback className={`${iconSize === 'sm' ? 'text-[9px]' : iconSize === 'lg' ? 'text-xs' : 'text-[10px]'} font-medium bg-[#DE350B] text-white`}>
                            {assigneeName === 'Unassigned' ? '?' : assigneeName.split(' ').map((n: string) => n[0]).join('').substring(0, 2).toUpperCase()}
                        </AvatarFallback>
                    ) : (
                        <AvatarFallback className="bg-muted text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={iconSize === 'sm' ? 'w-2.5 h-2.5' : iconSize === 'lg' ? 'w-4 h-4' : 'w-3.5 h-3.5'}><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </AvatarFallback>
                    )}
                </Avatar>
            </div>

            <div className="absolute top-2 right-2 flex opacity-100 [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 transition-opacity space-x-1.5 bg-card/90 backdrop-blur-sm p-1 rounded-md border shadow-xs z-10">
                {canLogWork && (
                    <button 
                        className="text-xs text-primary hover:text-primary/80 font-medium px-1.5 py-0.5 rounded hover:bg-primary/10 transition-colors"
                        onClick={(e) => { e.stopPropagation(); onLogWork(issue); }}
                    >
                        {t('Log')}
                    </button>
                )}
                <button 
                    className="text-xs text-muted-foreground hover:text-foreground font-medium px-1.5 py-0.5 rounded hover:bg-muted transition-colors"
                    onClick={(e) => { e.stopPropagation(); onEdit(issue); }}
                >
                    {t('Edit')}
                </button>
            </div>
        </div>
    );
};

const KanbanColumn = ({ status, issues, onEdit, onLogWork, lookups }: { status: SelectOption, issues: Issue[], onEdit: (issue: Issue) => void, onLogWork: (issue: Issue) => void, lookups?: { issueTypes: {id:string,name:string}[], priorities: {id:string,name:string}[], users: {id:string,name:string}[] } }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { setNodeRef } = useSortable({
        id: status.id,
        data: { type: 'Column', status },
    });

    const columnWidthClass =
        iconSize === 'sm'
            ? 'w-[250px] p-2.5 rounded-lg'
            : iconSize === 'lg'
            ? 'w-[360px] p-4 rounded-2xl'
            : 'w-[300px] p-3 rounded-xl';

    const headerTextClass =
        iconSize === 'sm'
            ? 'text-xs font-semibold'
            : iconSize === 'lg'
            ? 'text-base font-bold'
            : 'text-sm font-semibold';

    return (
        <div className={`flex flex-col bg-muted/40 flex-shrink-0 border border-transparent hover:border-border/50 transition-colors ${columnWidthClass}`}>
            <div className="flex items-center justify-between mb-3 px-1">
                <div className={`${headerTextClass} text-foreground`}>
                    {status.name}
                </div>
                <div className={`bg-muted text-muted-foreground rounded-full flex items-center justify-center font-medium ${iconSize === 'sm' ? 'h-4.5 w-4.5 text-[10px]' : iconSize === 'lg' ? 'h-6 w-6 text-xs' : 'h-5 w-5 text-xs'}`}>
                    {issues.length}
                </div>
            </div>
            <div ref={setNodeRef} className="flex-1 overflow-y-auto min-h-[200px]">
                <SortableContext items={issues.map(i => i.id)} strategy={verticalListSortingStrategy}>
                    {issues.length > 0 ? (
                        issues.map(issue => (
                            <SortableIssueCard key={issue.id} issue={issue} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />
                        ))
                    ) : (
                        <div className={`border-2 border-dashed border-border rounded-lg flex items-center justify-center text-muted-foreground ${iconSize === 'sm' ? 'h-16 text-xs' : iconSize === 'lg' ? 'h-28 text-sm' : 'h-24 text-sm'}`}>
                            {t('No issues here')}
                        </div>
                    )}
                </SortableContext>
            </div>
        </div>
    );
};

export const KanbanBoard: React.FC<KanbanBoardProps> = ({ issues, statuses, onTransition, onEdit, onLogWork, lookups }) => {
    const { t } = useTranslate();
    const [activeIssue, setActiveIssue] = React.useState<Issue | null>(null);

    const columns = useMemo(() => {
        const now = new Date();
        const MAX_DAYS = 30;

        return statuses.map(status => ({
            ...status,
            issues: issues.filter(issue => {
                if (issue.status_id !== status.id) return false;

                const isDone = issue.status?.category?.toUpperCase() === 'DONE' || status.name.toUpperCase() === 'DONE';
                if (isDone && issue.updated_at) {
                    const updatedAt = new Date(issue.updated_at);
                    const diffTime = Math.abs(now.getTime() - updatedAt.getTime());
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays > MAX_DAYS) {
                        return false;
                    }
                }
                return true;
            }),
        }));
    }, [issues, statuses]);

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                distance: 5,
            },
        }),
        useSensor(TouchSensor, {
            activationConstraint: {
                delay: 250,
                tolerance: 5,
            },
        })
    );

    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;
        if (active.data.current?.type === 'Issue') {
            setActiveIssue(active.data.current.issue as Issue);
        }
    };

    const handleDragEnd = (event: DragEndEvent) => {
        setActiveIssue(null);
        const { active, over } = event;
        if (!over) return;

        const activeId = active.id;
        const overId = over.id;

        const isActiveIssue = active.data.current?.type === 'Issue';
        const isOverColumn = over.data.current?.type === 'Column';
        const isOverIssue = over.data.current?.type === 'Issue';

        if (!isActiveIssue) return;

        const activeIssueData = active.data.current?.issue as Issue;
        let targetStatusId = activeIssueData.status_id;

        if (isOverColumn) {
            targetStatusId = overId as string;
        } else if (isOverIssue) {
            targetStatusId = (over.data.current?.issue as Issue).status_id;
        }

        if (targetStatusId && targetStatusId !== activeIssueData.status_id) {
            onTransition(activeId as string, targetStatusId);
        }
    };

    return (
        <div className="flex gap-4 overflow-x-auto pb-4 pt-2 px-1">
            <DndContext
                sensors={sensors}
                collisionDetection={closestCorners}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
            >
                {columns.map(col => (
                    <KanbanColumn key={col.id} status={col} issues={col.issues} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />
                ))}

                <DragOverlay>
                    {activeIssue ? (
                        <div className="opacity-80 rotate-2 scale-105">
                            <SortableIssueCard issue={activeIssue as Issue} onEdit={onEdit} onLogWork={onLogWork} lookups={lookups} />
                        </div>
                    ) : null}
                </DragOverlay>
            </DndContext>
        </div>
    );
};
