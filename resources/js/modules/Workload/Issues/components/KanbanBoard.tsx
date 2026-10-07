import React, { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { Issue } from '@/types/issue';
import { SharedData } from '@/types';
import { SelectOption } from './IssueFormDialog';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DndContext, DragEndEvent, DragOverlay, DragStartEvent, MouseSensor, TouchSensor, useSensor, useSensors, closestCorners } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useTranslate } from "@/hooks/useTranslate";

interface KanbanBoardProps {
    issues: Issue[];
    statuses: SelectOption[];
    lookups?: {
        issueTypes: {id: string, name: string}[];
        priorities: {id: string, name: string}[];
        users: {id: string, name: string}[];
    };
    onTransition: (issueId: string, toStatusId: string) => void;
    onEdit: (issue: Issue) => void;
    onLogWork: (issue: Issue) => void;
}

const SortableIssueCard = ({ issue, onEdit, onLogWork, lookups }: { issue: Issue, onEdit: (issue: Issue) => void, onLogWork: (issue: Issue) => void, lookups?: { issueTypes: {id:string,name:string}[], priorities: {id:string,name:string}[], users: {id:string,name:string}[] } }) => {
    const { t } = useTranslate();
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
    const priorityName = lookups?.priorities?.find((p) => String(p.id) === String(issue.priority_id))?.name || 'Normal';
    const assigneeName = issue.assignee?.name || lookups?.users?.find((u) => String(u.id) === String(issue.assignee_id))?.name || 'Unassigned';

    const { auth } = usePage<SharedData>().props;
    const currentUserId: number | null = auth.user?.id ?? null;
    const canLogWork = !!(currentUserId && issue.assignee_id && String(currentUserId) === String(issue.assignee_id));

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            className="p-4 mb-3 bg-card text-card-foreground border rounded-lg shadow-sm cursor-grab active:cursor-grabbing hover:border-primary/50 group relative flex flex-col gap-3 transition-colors"
        >
            <div className="font-medium text-sm leading-tight pr-10">{issue.title}</div>
            
            <div className="flex items-center justify-between mt-auto">
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="text-[10px] px-1.5 py-0 h-5 bg-muted/50 font-normal">
                        {typeName}
                    </Badge>
                    <span className="text-[10px] text-muted-foreground font-medium uppercase tracking-wider">
                        {priorityName}
                    </span>
                </div>
                
                <Avatar className="h-6 w-6 border bg-muted">
                    {issue.assignee_id ? (
                        <AvatarFallback className="text-[10px] font-medium bg-[#DE350B] text-white">
                            {assigneeName === 'Unassigned' ? '?' : assigneeName.split(' ').map((n: string) => n[0]).join('').substring(0, 2).toUpperCase()}
                        </AvatarFallback>
                    ) : (
                        <AvatarFallback className="bg-muted text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-3.5 h-3.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </AvatarFallback>
                    )}
                </Avatar>
            </div>

            <div className="absolute top-2 right-2 flex opacity-100 [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 transition-opacity space-x-2 bg-card/80 backdrop-blur-sm p-1 rounded-md border shadow-sm">
                {canLogWork && (
                    <button 
                        className="text-xs text-primary hover:text-primary/80 font-medium px-1"
                        onClick={(e) => { e.stopPropagation(); onLogWork(issue); }}
                    >
                        {t('Log')}
                                            </button>
                )}
                <button 
                    className="text-xs text-secondary hover:text-secondary/80 font-medium px-1"
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
    const { setNodeRef } = useSortable({
        id: status.id,
        data: { type: 'Column', status },
    });

    return (
        <div className="flex flex-col bg-muted/40 rounded-xl p-3 w-[300px] flex-shrink-0 border border-transparent hover:border-border/50 transition-colors">
            <div className="flex items-center justify-between mb-4 px-1">
                <div className="font-semibold text-sm text-foreground">
                    {status.name}
                </div>
                <div className="bg-muted text-muted-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
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
                        <div className="h-24 border-2 border-dashed border-border rounded-lg flex items-center justify-center text-sm text-muted-foreground">
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
        return statuses.map(status => ({
            ...status,
            issues: issues.filter(issue => issue.status_id === status.id),
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
