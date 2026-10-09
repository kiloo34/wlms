import { EstimatePopover } from './EstimatePopover';
import { CompleteSprintDialog } from './CompleteSprintDialog';
import { SprintFormDialog } from './SprintFormDialog';
import React, { useMemo, useState, KeyboardEvent } from 'react';
import { useSprints } from '../hooks/useSprints';
import { usePage } from '@inertiajs/react';
import { Issue } from '@/types/issue';
import { Sprint } from '@/types/sprint';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DndContext, DragEndEvent, useDraggable, useDroppable, PointerSensor, MouseSensor, TouchSensor, useSensor, useSensors, closestCorners, DragStartEvent, DragOverlay } from '@dnd-kit/core';
import { useIssues } from '../hooks/useIssues';
import { ChevronDown, ChevronRight, MoreHorizontal, SlidersHorizontal, LineChart, Plus, Pencil, Bookmark, CheckSquare } from 'lucide-react';
import { format } from 'date-fns';
import { useSprintWorkload } from '../hooks/useSprints';
import { SprintWorkloadWidget } from './SprintWorkloadWidget';
import { toast } from 'sonner';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { useIconSize, type IconSize } from '@/hooks/use-appearance';

interface LookupData {
    statuses?: any[];
    issueTypes?: any[];
    priorities?: any[];
    users?: any[];
}

interface BacklogManagerProps {
    projectId: string;
    issues: Issue[];
    lookups?: LookupData;
    onIssueClick?: (issue: Issue) => void;
}

const getFullPayload = (issue: Issue, overrides: any) => ({
    title: overrides.title !== undefined ? overrides.title : issue.title,
    description: overrides.description !== undefined ? overrides.description : issue.description,
    issue_type_id: overrides.issue_type_id !== undefined ? overrides.issue_type_id : issue.issue_type_id,
    status_id: overrides.status_id !== undefined ? overrides.status_id : issue.status_id,
    priority_id: overrides.priority_id !== undefined ? overrides.priority_id : issue.priority_id,
    sprint_id: overrides.sprint_id !== undefined ? overrides.sprint_id : issue.sprint_id,
    assignee_id: overrides.assignee_id !== undefined ? overrides.assignee_id : (issue.assignee_id || null),
    original_estimate_seconds: overrides.original_estimate_seconds !== undefined ? overrides.original_estimate_seconds : issue.original_estimate_seconds,
    remaining_estimate_seconds: overrides.remaining_estimate_seconds !== undefined ? overrides.remaining_estimate_seconds : issue.remaining_estimate_seconds,
});

const getStatusVariant = (name: string) => {
    const n = name.toLowerCase();
    if (n.includes('done') || n.includes('resolve')) return 'success';
    if (n.includes('progress') || n.includes('review') || n.includes('uat')) return 'info';
    return 'secondary';
};

const getTypeIcon = (name: string, size: IconSize = 'md') => {
    const n = name.toLowerCase();
    const iconClass = size === 'sm' ? 'w-3 h-3' : size === 'lg' ? 'w-4 h-4' : 'w-3.5 h-3.5';
    if (n.includes('bug')) return <div className={`${iconClass} bg-destructive rounded-sm`} />;
    if (n.includes('task')) return <CheckSquare className={`${iconClass} text-info`} />;
    return <Bookmark className={`${iconClass} text-success`} />;
};

const StatusDropdown = ({ issue, lookups, updateIssue }: { issue: Issue, lookups?: LookupData, updateIssue: any }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const statusName = lookups?.statuses?.find((s: any) => String(s.id) === String(issue.status_id))?.name || 'To Do';
    
    const badgeClass =
        iconSize === 'sm'
            ? 'text-[9px] font-medium px-1.5 py-0.5 rounded-sm h-4.5 cursor-pointer flex items-center gap-1'
            : iconSize === 'lg'
            ? 'text-xs font-medium px-2.5 py-1 rounded-sm h-6 cursor-pointer flex items-center gap-1'
            : 'text-[10px] font-medium px-2 py-0.5 rounded-sm h-5 cursor-pointer flex items-center gap-1';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Badge variant={getStatusVariant(statusName) as any} className={badgeClass}>
                    {statusName} <ChevronDown className="w-3 h-3 opacity-70" />
                </Badge>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                {lookups?.statuses?.map((status: any) => (
                    <DropdownMenuItem 
                        key={status.id} 
                        onClick={(e) => {
                            e.stopPropagation();
                            updateIssue({ id: issue.id, payload: getFullPayload(issue, { status_id: status.id }) as any });
                        }}
                    >
                        {status.name}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <DropdownMenuItem className="text-muted-foreground">{t('View workflow')}</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem } from "@/components/ui/command";
import { Check } from "lucide-react";
import { cn } from "@/lib/utils";
import { useTranslate } from "@/hooks/useTranslate";

const AssigneeDropdown = ({ issue, lookups, assignIssue, disabled }: { issue: Issue, lookups?: LookupData, assignIssue: any, disabled: boolean }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const [open, setOpen] = useState(false);
    
    const assigneeName = issue.assignee?.name || lookups?.users?.find((u: any) => String(u.id) === String(issue.assignee_id))?.name || 'Unassigned';

    const avatarClass =
        iconSize === 'sm'
            ? 'h-5 w-5'
            : iconSize === 'lg'
            ? 'h-8 w-8'
            : 'h-6 w-6';

    const fallbackTextClass =
        iconSize === 'sm'
            ? 'text-[8px]'
            : iconSize === 'lg'
            ? 'text-[11px]'
            : 'text-[9px]';

    return (
        <Popover open={open && !disabled} onOpenChange={(val) => !disabled && setOpen(val)}>
            <PopoverTrigger asChild>
                <div className={`rounded-full transition-all ${disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:ring-2 hover:ring-primary'}`}>
                    <Avatar className={`${avatarClass} border border-border/50`}>
                        {issue.assignee_id ? (
                            <AvatarFallback className={`${fallbackTextClass} font-medium bg-[#DE350B] text-white`}>
                                {assigneeName === 'Unassigned' ? '?' : assigneeName.split(' ').map((n: string) => n[0]).join('').substring(0, 2).toUpperCase()}
                            </AvatarFallback>
                        ) : (
                            <AvatarFallback className="bg-muted text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={iconSize === 'sm' ? "w-3 h-3" : iconSize === 'lg' ? "w-4 h-4" : "w-3.5 h-3.5"}><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </AvatarFallback>
                        )}
                    </Avatar>
                </div>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-[200px] p-0" onPointerDownOutside={(e) => {
                // Prevent issue row drag from triggering
                e.stopPropagation();
            }}>
                <Command>
                    <CommandInput placeholder={t('Search user...')} />
                    <CommandList>
                        <CommandEmpty>{t('No users found.')}</CommandEmpty>
                        <CommandGroup heading="Assign to...">
                            <CommandItem
                                onSelect={() => {
                                    assignIssue({ id: issue.id, assigneeId: null });
                                    setOpen(false);
                                }}
                            >
                                <Check className={cn("mr-2 h-4 w-4", !issue.assignee_id ? "opacity-100" : "opacity-0")} />
                                {t('Unassigned')}
                                                            </CommandItem>
                            {lookups?.users?.map((user: any) => (
                                <CommandItem 
                                    key={user.id} 
                                    onSelect={() => {
                                        assignIssue({ id: issue.id, assigneeId: user.id });
                                        setOpen(false);
                                    }}
                                >
                                    <Check className={cn("mr-2 h-4 w-4", issue.assignee_id === user.id ? "opacity-100" : "opacity-0")} />
                                    {user.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

const DraggableIssue = ({ issue, lookups, updateIssue, assignIssue, isOwner, onIssueClick }: { issue: Issue, lookups?: LookupData, updateIssue: any, assignIssue: any, isOwner: boolean, onIssueClick?: (issue: Issue) => void }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { attributes, listeners, setNodeRef, transform, isDragging } = useDraggable({
        id: issue.id,
        data: { type: 'Issue', issue },
    });

    const style = transform ? {
        transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
        opacity: isDragging ? 0.5 : 1,
    } : undefined;

    const dateFormatted = issue.created_at ? format(new Date(issue.created_at), 'MMM d') : '-';
    const typeName = lookups?.issueTypes?.find((t: any) => String(t.id) === String(issue.issue_type_id))?.name || 'Task';

    const rowPaddingClass =
        iconSize === 'sm'
            ? 'py-1.5 px-2.5 text-xs'
            : iconSize === 'lg'
            ? 'py-3.5 px-4 text-base'
            : 'py-2 px-3 text-sm';

    const titleClass =
        iconSize === 'sm'
            ? 'text-xs font-medium text-foreground truncate flex items-center gap-1.5 cursor-pointer hover:text-primary'
            : iconSize === 'lg'
            ? 'text-base font-medium text-foreground truncate flex items-center gap-2.5 cursor-pointer hover:text-primary'
            : 'text-sm font-medium text-foreground truncate flex items-center gap-2 cursor-pointer hover:text-primary';

    const keyTextClass =
        iconSize === 'sm' ? 'text-[11px]' : iconSize === 'lg' ? 'text-sm' : 'text-xs';

    const dateTextClass =
        iconSize === 'sm' ? 'text-[11px]' : iconSize === 'lg' ? 'text-sm' : 'text-xs';

    const actionIconClass =
        iconSize === 'sm' ? 'w-3 h-3' : iconSize === 'lg' ? 'w-4 h-4' : 'w-3.5 h-3.5';

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...listeners}
            {...attributes}
            className={`group flex items-center justify-between ${rowPaddingClass} bg-card hover:bg-muted/50 border-b border-border/50 cursor-grab active:cursor-grabbing transition-colors`}
        >
            <div className="flex items-center gap-3 overflow-hidden">
                <input type="checkbox" className={`${actionIconClass} rounded-sm border-muted-foreground/30 text-primary focus:ring-primary opacity-100 [@media(hover:hover)]:opacity-50 [@media(hover:hover)]:group-hover:opacity-100 transition-opacity`} />
                {getTypeIcon(typeName, iconSize)}
                <span onClick={() => onIssueClick?.(issue)} className={`${keyTextClass} font-medium text-muted-foreground w-16 truncate hover:underline cursor-pointer`} title={issue.id}>{t('TES-')}{issue.id.substring(0,3)}</span>
                <span onClick={() => onIssueClick?.(issue)} className={titleClass}>
                    {issue.title}
                    <Pencil className={`${actionIconClass} text-muted-foreground opacity-100 [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 transition-opacity`} />
                </span>
            </div>
            <div className="flex items-center gap-3.5 flex-shrink-0">
                <div onPointerDown={(e) => e.stopPropagation()} className="cursor-default">
                    <StatusDropdown issue={issue} lookups={lookups} updateIssue={updateIssue} />
                </div>
                <span className={`${dateTextClass} text-muted-foreground w-12 text-right`}>{dateFormatted}</span>
                <div onPointerDown={(e) => e.stopPropagation()} className="cursor-default">
                    <EstimatePopover issue={issue} updateIssue={updateIssue} />
                </div>
                <div onPointerDown={(e) => e.stopPropagation()} className="cursor-default">
                    <AssigneeDropdown issue={issue} lookups={lookups} assignIssue={assignIssue} disabled={!isOwner} />
                </div>
                <MoreHorizontal className={`${actionIconClass} text-muted-foreground opacity-100 [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 cursor-pointer`} />
            </div>
        </div>
    );
};

const InlineCreateIssue = ({ projectId, sprintId, lookups }: { projectId: string, sprintId: string | null, lookups?: LookupData }) => {
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
                    priority_id: lookups?.priorities?.[0]?.id, // Default to first priority
                    sprint_id: sprintId
                } as any); // Cast as any to avoid type issues if sprint_id is not allowed
                setTitle('');
            } catch (err) {
                console.error("Failed to create issue", err);
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
}

const DroppableSprint = ({ sprint, issues, lookups, onStart, onComplete, projectId, updateIssue, assignIssue, isOwner, onIssueClick, onEditSprint }: { sprint: Sprint, issues: Issue[], lookups?: LookupData, onStart: (id: string) => void, onComplete: (id: string) => void, projectId: string, updateIssue: any, assignIssue: any, isOwner: boolean, onIssueClick?: (issue: Issue) => void, onEditSprint: (sprint: Sprint, mode: 'edit'|'start') => void }) => {
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
                    <span onClick={() => isOwner && onEditSprint(sprint, 'edit')} className={`text-xs text-muted-foreground mx-2 ${isOwner ? "cursor-pointer hover:underline hover:text-foreground" : ""}`}>{sprint.start_date ? `${format(new Date(sprint.start_date), 'd MMM')} - ${format(new Date(sprint.end_date || new Date()), 'd MMM')}` : 'Add dates'}</span>
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
                        issues.map(issue => <DraggableIssue key={issue.id} issue={issue} lookups={lookups} updateIssue={updateIssue} assignIssue={assignIssue} isOwner={isOwner} onIssueClick={onIssueClick} />)
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

const DroppableBacklog = ({ issues, lookups, projectId, updateIssue, assignIssue, isOwner, onIssueClick, onCreateSprint }: { issues: Issue[], lookups?: LookupData, projectId: string, updateIssue: any, assignIssue: any, isOwner: boolean, onIssueClick?: (issue: Issue) => void, onCreateSprint: () => void }) => {
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
                    {isOwner && <Button size="sm" variant="secondary" onClick={onCreateSprint} className={`${backlogBtnClass} bg-muted hover:bg-muted/80 text-foreground`}>{t('Create sprint')}</Button>}
                </div>
            </div>
            
            {isExpanded && (
                <div ref={setNodeRef} className="bg-background flex flex-col">
                    {issues.length > 0 ? (
                        issues.map(issue => <DraggableIssue key={issue.id} issue={issue} lookups={lookups} updateIssue={updateIssue} assignIssue={assignIssue} isOwner={isOwner} onIssueClick={onIssueClick} />)
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

export const BacklogManager: React.FC<BacklogManagerProps> = ({ projectId, issues, lookups, onIssueClick }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { sprints, startSprint, completeSprint, createSprint, isLoading, isCompleting } = useSprints(projectId);
    const { auth } = usePage<any>().props;
    const isOwner = auth.user?.roles?.includes('Workspace Owner') || auth.user?.roles?.includes('Superadmin');
    const [searchQuery, setSearchQuery] = useState('');
    const [assigneeFilters, setAssigneeFilters] = useState<string[]>([]);

    const filteredIssues = useMemo(() => {
        return issues.filter(issue => {
            let matchesSearch = true;
            if (searchQuery) {
                matchesSearch = issue.title.toLowerCase().includes(searchQuery.toLowerCase()) || 
                               (((issue as any).issue_key) && ((issue as any).issue_key).toLowerCase().includes(searchQuery.toLowerCase()));
            }
            let matchesAssignee = true;
            if (assigneeFilters.length > 0) {
                if (assigneeFilters.includes('unassigned')) {
                    matchesAssignee = !issue.assignee_id || assigneeFilters.includes(String(issue.assignee_id));
                } else {
                    matchesAssignee = assigneeFilters.includes(String(issue.assignee_id || ''));
                }
            }
            
            // Sembunyikan issue yang sudah Done dari halaman Backlog (kecuali jika filter khusus diterapkan nanti)
            const isDone = issue.status?.category === 'DONE' || issue.status?.name?.toLowerCase() === 'done';
            if (isDone) return false;
            return matchesSearch && matchesAssignee;
        });
    }, [issues, searchQuery, assigneeFilters]);

    const handleCreateSprint = async () => {
        const num = sprints ? sprints.length + 1 : 1;
        try {
            await createSprint({ project_id: projectId, name: `Sprint ${num}` });
        } catch (err) {
            console.error("Failed to create sprint", err);
        }
    };
    const { updateIssue, assignIssue } = useIssues(projectId);
    const [activeIssue, setActiveIssue] = React.useState<Issue | null>(null);
    const [sprintDialog, setSprintDialog] = useState<{sprint: Sprint, mode: 'edit'|'start'} | null>(null);
    const [sprintToComplete, setSprintToComplete] = useState<Sprint | null>(null);
    const [isCompleteDialogOpen, setIsCompleteDialogOpen] = useState(false);

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

    const sortedSprints = useMemo(() => {
        if (!sprints) return [];
        return [...sprints].filter(s => s.state !== 'COMPLETED').sort((a, b) => {
            if (a.state?.toUpperCase() === 'ACTIVE' && b.state?.toUpperCase() !== 'ACTIVE') return -1;
            if (b.state?.toUpperCase() === 'ACTIVE' && a.state?.toUpperCase() !== 'ACTIVE') return 1;
            return 0;
        });
    }, [sprints]);

    const backlogIssues = useMemo(() => {
        return filteredIssues.filter(i => {
            if (i.sprint_id) return false;
            const status = lookups?.statuses?.find((s: any) => s.id === i.status_id);
            if (status && (status.category === 'DONE' || status.name.toLowerCase() === 'done')) {
                return false;
            }
            return true;
        });
    }, [filteredIssues, lookups]);
    
    const handleDragStart = (event: DragStartEvent) => {
        const { active } = event;
        if (active.data.current?.type === 'Issue') {
            setActiveIssue(active.data.current.issue as Issue);
        }
    };

    const handleDragEnd = async (event: DragEndEvent) => {
        setActiveIssue(null);
        const { active, over } = event;
        
        if (!over) return;

        const activeIssueData = active.data.current?.issue as Issue;
        if (!activeIssueData) return;

        let targetSprintId: string | null = null;
        if (over.id === 'backlog') {
            targetSprintId = null;
        } else {
            targetSprintId = over.id as string;
        }

        if (activeIssueData.sprint_id !== targetSprintId) {
            try {
                await updateIssue({
                    id: activeIssueData.id,
                    payload: getFullPayload(activeIssueData, { sprint_id: targetSprintId }) as any
                });
            } catch (error) {
                console.error("Failed to update issue sprint", error);
            }
        }
    };

    if (isLoading) return <div>{t('Loading sprints...')}</div>;

    return (
        <>
        <DndContext sensors={sensors} collisionDetection={closestCorners} onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
            <div className="flex flex-col py-2">
                {/* Top Action Bar matching Jira Mockup */}
                <div className="flex justify-between items-center mb-6 px-1">
                    <div className="flex items-center gap-3">
                        <div className="relative">
                            <input 
                                type="text" 
                                placeholder={t('Search backlog')}
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                className={`${iconSize === 'sm' ? 'h-7.5 w-44 text-xs' : iconSize === 'lg' ? 'h-9.5 w-56 text-sm' : 'h-8 w-48 text-xs'} rounded-md border border-input bg-background pl-8 pr-3 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring`}
                            />
                            <svg xmlns="http://www.w3.org/2000/svg" className="absolute left-2.5 top-2 h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <div className="flex items-center -space-x-1.5 pl-1">
                            <Avatar 
                                onClick={() => setAssigneeFilters(prev => prev.includes('unassigned') ? prev.filter(p => p !== 'unassigned') : [...prev, 'unassigned'])}
                                className={`${iconSize === 'sm' ? 'h-7 w-7' : iconSize === 'lg' ? 'h-9 w-9' : 'h-8 w-8'} rounded-full border-2 border-background cursor-pointer transition-all ${assigneeFilters.includes('unassigned') ? 'ring-2 ring-offset-1 ring-primary z-10' : 'hover:z-10 hover:-translate-y-0.5'}`}
                                title={t('Unassigned')}
                            >
                                <AvatarFallback className="bg-muted text-muted-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={iconSize === 'sm' ? "w-3.5 h-3.5" : iconSize === 'lg' ? "w-4.5 h-4.5" : "w-4 h-4"}><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </AvatarFallback>
                            </Avatar>
                            {lookups?.users?.map(u => (
                                <Avatar 
                                    key={u.id} 
                                    onClick={() => setAssigneeFilters(prev => prev.includes(String(u.id)) ? prev.filter(p => p !== String(u.id)) : [...prev, String(u.id)])}
                                    className={`${iconSize === 'sm' ? 'h-7 w-7' : iconSize === 'lg' ? 'h-9 w-9' : 'h-8 w-8'} rounded-full border-2 border-background cursor-pointer transition-all ${assigneeFilters.includes(String(u.id)) ? 'ring-2 ring-offset-1 ring-primary z-10' : 'hover:z-10 hover:-translate-y-0.5'}`}
                                    title={u.name}
                                >
                                    <AvatarFallback className={`${iconSize === 'sm' ? 'text-[10px]' : iconSize === 'lg' ? 'text-xs' : 'text-[11px]'} font-medium bg-[#DE350B] text-white`}>{u.name.split(' ').map((n: string)=>n[0]).join('').substring(0, 2).toUpperCase()}</AvatarFallback>
                                </Avatar>
                            ))}
                        </div>
                        <Button variant="outline" size="sm" className={`${iconSize === 'sm' ? 'h-7.5 text-xs px-2.5' : iconSize === 'lg' ? 'h-9.5 text-sm px-3.5' : 'h-8 text-xs px-3'} font-medium ${(searchQuery || assigneeFilters.length > 0) ? 'bg-primary/10 text-primary border-primary/20 hover:bg-primary/20' : 'text-muted-foreground'}`}>
                            <SlidersHorizontal className="w-3.5 h-3.5 mr-1.5" /> {t('Filter')}
                            {(searchQuery || assigneeFilters.length > 0) && <span className="ml-1.5 bg-primary/20 text-primary rounded-full px-1.5 py-0.5 text-[10px] leading-none">{(searchQuery ? 1 : 0) + assigneeFilters.length}</span>}
                        </Button>
                        {(searchQuery || assigneeFilters.length > 0) && (
                            <button onClick={() => { setSearchQuery(''); setAssigneeFilters([]); }} className="text-xs font-medium text-muted-foreground hover:text-foreground mx-1">{t('Clear filters')}</button>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5">
                        <Button variant="outline" size="icon" className={`${iconSize === 'sm' ? 'h-7.5 w-7.5' : iconSize === 'lg' ? 'h-9.5 w-9.5' : 'h-8 w-8'} text-muted-foreground`}><LineChart className="w-4 h-4" /></Button>
                        <Button variant="outline" size="icon" className={`${iconSize === 'sm' ? 'h-7.5 w-7.5' : iconSize === 'lg' ? 'h-9.5 w-9.5' : 'h-8 w-8'} text-muted-foreground`}><SlidersHorizontal className="w-4 h-4" /></Button>
                        <Button variant="outline" size="icon" className={`${iconSize === 'sm' ? 'h-7.5 w-7.5' : iconSize === 'lg' ? 'h-9.5 w-9.5' : 'h-8 w-8'} text-muted-foreground`}><MoreHorizontal className="w-4 h-4" /></Button>
                    </div>
                </div>

                {/* Sprints List */}
                <div>
                    {sortedSprints.map(sprint => (
                        <DroppableSprint 
                            key={sprint.id} 
                            sprint={sprint} 
                            issues={filteredIssues.filter(i => i.sprint_id === sprint.id)} 
                            lookups={lookups}
                            onStart={(id) => {
                                startSprint(id)
                                    .then(() => toast.success(t('Sprint started successfully')))
                                    .catch((err: any) => toast.error(err.response?.data?.error || err.response?.data?.message || 'Failed to start sprint'));
                            }}
                            onComplete={(id) => {
                                const sprint = sortedSprints.find(s => s.id === id);
                                if (sprint) {
                                    setSprintToComplete(sprint);
                                    setIsCompleteDialogOpen(true);
                                }
                            }}
                            projectId={projectId}
                            updateIssue={updateIssue}
                            assignIssue={assignIssue}
                            isOwner={isOwner}
                            onEditSprint={(s, m) => setSprintDialog({sprint: s, mode: m})} 
                            onIssueClick={onIssueClick}
                        />
                    ))}
                </div>
                
                {/* Backlog List */}
                <DroppableBacklog issues={backlogIssues} lookups={lookups} projectId={projectId} updateIssue={updateIssue} assignIssue={assignIssue} isOwner={isOwner} onIssueClick={onIssueClick} onCreateSprint={handleCreateSprint} />
            </div>

            <DragOverlay>
                {activeIssue ? (
                    <div className="opacity-95 rotate-2 scale-105 shadow-xl">
                        <DraggableIssue issue={activeIssue} lookups={lookups} updateIssue={updateIssue} assignIssue={assignIssue} isOwner={isOwner} onIssueClick={onIssueClick} />
                    </div>
                ) : null}
            </DragOverlay>
        </DndContext>
            
                        <CompleteSprintDialog 
                open={isCompleteDialogOpen}
                onOpenChange={setIsCompleteDialogOpen}
                sprint={sprintToComplete}
                issues={issues}
                availableSprints={sortedSprints}
                isCompleting={isCompleting}
                onComplete={async (sprintId, moveToSprintId) => {
                    try {
                        await completeSprint({ sprintId, moveToSprintId });
                        toast.success(t('Sprint completed successfully'));
                    } catch (err: any) {
                        toast.error(err.response?.data?.error || err.response?.data?.message || 'Failed to complete sprint');
                        throw err; // Re-throw to prevent dialog from closing
                    }
                }}
            />
            <SprintFormDialog 
                open={!!sprintDialog}
                onOpenChange={(open) => !open && setSprintDialog(null)}
                sprint={sprintDialog?.sprint || null}
                mode={sprintDialog?.mode}
                projectId={projectId}
            />
        </>
    );
};