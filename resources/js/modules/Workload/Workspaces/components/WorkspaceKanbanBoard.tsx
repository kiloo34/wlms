import React, { useState, useMemo } from 'react';
import { useWorkspaceIssues } from '../hooks/useWorkspaceIssues';
import { useStatuses, useCreateStatus } from '@/modules/Workload/Workflows/hooks/useStatuses';
import { Project } from '@/modules/Workload/Projects/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { PriorityBadge } from '@/components/PriorityBadge';
import { Plus, X, Search, MoveRight, CheckCircle2, Circle, Clock } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { toast } from 'sonner';
import { useQueryClient } from '@tanstack/react-query';
import axios from '@/lib/axios';
import { useTranslate } from '@/hooks/useTranslate';

interface WorkspaceKanbanBoardProps {
    workspaceId: string;
    projects: Project[];
    lookups?: any;
}

export const WorkspaceKanbanBoard: React.FC<WorkspaceKanbanBoardProps> = ({
    workspaceId,
    projects,
    lookups,
}) => {
    const { t } = useTranslate();
    const queryClient = useQueryClient();

    // Data fetching
    const { data: statuses = [], isLoading: isLoadingStatuses } = useStatuses();
    const { data: issues = [], isLoading: isLoadingIssues } = useWorkspaceIssues(workspaceId);

    // Filters
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedProjectFilter, setSelectedProjectFilter] = useState('all');

    // "+ Add another list" (New Status Column) State
    const [isAddingList, setIsAddingList] = useState(false);
    const [newListName, setNewListName] = useState('');
    const [newListCategory, setNewListCategory] = useState<'TODO' | 'IN_PROGRESS' | 'DONE'>('IN_PROGRESS');
    const createStatusMutation = useCreateStatus();

    // "+ Add a card" State per column
    const [activeAddingCardStatusId, setActiveAddingCardStatusId] = useState<string | null>(null);
    const [newCardTitle, setNewCardTitle] = useState('');
    const [newCardProjectId, setNewCardProjectId] = useState<string>(projects[0]?.id || '');
    const [isSubmittingCard, setIsSubmittingCard] = useState(false);

    // Filtered Issues
    const filteredIssues = useMemo(() => {
        return issues.filter((issue: any) => {
            const matchesSearch =
                issue.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (issue.id && issue.id.toLowerCase().includes(searchQuery.toLowerCase()));
            const matchesProject =
                selectedProjectFilter === 'all' || issue.project_id === selectedProjectFilter;
            return matchesSearch && matchesProject;
        });
    }, [issues, searchQuery, selectedProjectFilter]);

    // Handle adding a new list (Trello-style)
    const handleAddListSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const trimmed = newListName.trim();
        if (!trimmed) {
            toast.error(t('List name is required'));
            return;
        }

        const slug = trimmed
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '') || `status-${Date.now()}`;

        createStatusMutation.mutate(
            {
                name: trimmed,
                slug: `${slug}-${Math.floor(Math.random() * 1000)}`,
                category: newListCategory,
            },
            {
                onSuccess: () => {
                    toast.success(t('List added successfully'));
                    setNewListName('');
                    setIsAddingList(false);
                },
                onError: (err: any) => {
                    toast.error(err?.response?.data?.message || t('Failed to add list'));
                },
            }
        );
    };

    // Handle adding a card inside a column
    const handleAddCardSubmit = async (statusId: string) => {
        const title = newCardTitle.trim();
        if (!title) {
            toast.error(t('Task title is required'));
            return;
        }

        const targetProjectId = newCardProjectId || projects[0]?.id;
        if (!targetProjectId) {
            toast.error(t('Please select a project for this task'));
            return;
        }

        setIsSubmittingCard(true);
        try {
            await axios.post(`/api/projects/${targetProjectId}/issues`, {
                title,
                status_id: statusId,
                priority_id: lookups?.priorities?.[0]?.id || null,
                issue_type_id: lookups?.issueTypes?.[0]?.id || null,
            });

            toast.success(t('Task added successfully'));
            queryClient.invalidateQueries({ queryKey: ['workspaces', workspaceId, 'issues'] });
            setNewCardTitle('');
            setActiveAddingCardStatusId(null);
        } catch (error: any) {
            toast.error(error?.response?.data?.message || t('Failed to create task'));
        } finally {
            setIsSubmittingCard(false);
        }
    };

    // Handle moving/transitioning an issue
    const handleTransitionIssue = async (issueId: string, toStatusId: string) => {
        try {
            await axios.post(`/api/issues/${issueId}/transition`, {
                to_status_id: toStatusId,
            });
            toast.success(t('Task moved'));
            queryClient.invalidateQueries({ queryKey: ['workspaces', workspaceId, 'issues'] });
        } catch (err: any) {
            toast.error(err?.response?.data?.message || t('Failed to move task'));
        }
    };

    // Category badge helpers
    const getCategoryIcon = (category?: string) => {
        switch (category?.toUpperCase()) {
            case 'DONE':
                return <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />;
            case 'IN_PROGRESS':
                return <Clock className="h-3.5 w-3.5 text-blue-500" />;
            default:
                return <Circle className="h-3.5 w-3.5 text-muted-foreground" />;
        }
    };

    if (isLoadingStatuses && isLoadingIssues) {
        return (
            <div className="flex h-64 items-center justify-center text-muted-foreground">
                <p>{t('Loading board...')}</p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Filter Bar */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-card p-3 rounded-lg border">
                <div className="flex flex-1 items-center gap-2">
                    <div className="relative w-full sm:w-64">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder={t('Search tasks in board...')}
                            className="pl-8 h-9"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <div className="w-52 shrink-0">
                        <Select value={selectedProjectFilter} onValueChange={setSelectedProjectFilter}>
                            <SelectTrigger className="h-9">
                                <SelectValue placeholder={t('Filter by project')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('All Projects')}</SelectItem>
                                {projects.map((p) => (
                                    <SelectItem key={p.id} value={p.id}>
                                        {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="text-xs text-muted-foreground flex items-center gap-2">
                    <span>{t('Total Tasks')}: <strong className="text-foreground">{filteredIssues.length}</strong></span>
                </div>
            </div>

            {/* Kanban Columns Horizontal Container */}
            <div className="flex items-start gap-4 overflow-x-auto pb-6 pt-2">
                {statuses.map((status) => {
                    const columnIssues = filteredIssues.filter((i: any) => i.status_id === status.id);
                    const isAddingCard = activeAddingCardStatusId === status.id;

                    return (
                        <div
                            key={status.id}
                            className="w-80 shrink-0 flex flex-col rounded-xl bg-muted/60 dark:bg-muted/30 border p-3 shadow-sm"
                        >
                            {/* Column Header */}
                            <div className="flex items-center justify-between pb-3 px-1">
                                <div className="flex items-center gap-2 min-w-0">
                                    {getCategoryIcon(status.category)}
                                    <h3 className="font-semibold text-sm text-foreground truncate" title={status.name}>
                                        {status.name}
                                    </h3>
                                </div>
                                <span className="inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold rounded-full bg-background border text-muted-foreground">
                                    {columnIssues.length}
                                </span>
                            </div>

                            {/* Column Issues Cards */}
                            <div className="space-y-2.5 min-h-[40px] flex-1 overflow-y-auto max-h-[65vh] pr-0.5">
                                {columnIssues.map((issue: any) => {
                                    const proj = projects.find((p) => p.id === issue.project_id) || issue.project;

                                    return (
                                        <Card
                                            key={issue.id}
                                            className="bg-card hover:border-primary/50 transition-all shadow-xs cursor-pointer group"
                                        >
                                            <CardContent className="p-3 space-y-2">
                                                {/* Project Badge & Move action */}
                                                <div className="flex items-center justify-between gap-1">
                                                    {proj && (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-[10px] font-normal px-1.5 py-0 bg-background/80 truncate max-w-[170px]"
                                                            title={proj.name}
                                                        >
                                                            {proj.name}
                                                        </Badge>
                                                    )}

                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                                                title={t('Move to another status')}
                                                            >
                                                                <MoveRight className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-44">
                                                            <div className="px-2 py-1 text-xs font-semibold text-muted-foreground">
                                                                {t('Move to status:')}
                                                            </div>
                                                            {statuses
                                                                .filter((s) => s.id !== status.id)
                                                                .map((targetStatus) => (
                                                                    <DropdownMenuItem
                                                                        key={targetStatus.id}
                                                                        onClick={() => handleTransitionIssue(issue.id, targetStatus.id)}
                                                                        className="text-xs"
                                                                    >
                                                                        {targetStatus.name}
                                                                    </DropdownMenuItem>
                                                                ))}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </div>

                                                {/* Issue Title & Key */}
                                                <div>
                                                    <p className="text-sm font-medium leading-snug text-foreground">
                                                        {issue.title}
                                                    </p>
                                                    {issue.id && (
                                                        <span className="text-[11px] font-mono text-muted-foreground">
                                                            {issue.id.slice(0, 8)}
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Footer: Priority & Assignee */}
                                                <div className="flex items-center justify-between pt-1 border-t border-border/40 text-xs">
                                                    {issue.priority ? (
                                                        <PriorityBadge
                                                            name={issue.priority.name}
                                                            category={issue.priority.category}
                                                        />
                                                    ) : (
                                                        <span />
                                                    )}

                                                    <span className="text-muted-foreground text-[11px] truncate max-w-[110px]" title={issue.assignee?.name || t('Unassigned')}>
                                                        {issue.assignee?.name || t('Unassigned')}
                                                    </span>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    );
                                })}

                                {columnIssues.length === 0 && !isAddingCard && (
                                    <div className="flex items-center justify-center h-16 border border-dashed rounded-lg text-xs text-muted-foreground/60">
                                        {t('No tasks')}
                                    </div>
                                )}
                            </div>

                            {/* Inline "+ Add a card" Form or Button */}
                            <div className="pt-2 mt-auto">
                                {isAddingCard ? (
                                    <div className="space-y-2 bg-card p-2.5 rounded-lg border shadow-xs">
                                        <Input
                                            autoFocus
                                            placeholder={t('Enter task title...')}
                                            value={newCardTitle}
                                            onChange={(e) => setNewCardTitle(e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') handleAddCardSubmit(status.id);
                                                if (e.key === 'Escape') setActiveAddingCardStatusId(null);
                                            }}
                                            className="text-sm h-8"
                                        />
                                        {projects.length > 1 && (
                                            <Select value={newCardProjectId} onValueChange={setNewCardProjectId}>
                                                <SelectTrigger className="h-7 text-xs">
                                                    <SelectValue placeholder={t('Select Project')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {projects.map((p) => (
                                                        <SelectItem key={p.id} value={p.id} className="text-xs">
                                                            {p.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                        <div className="flex items-center gap-1.5 pt-1">
                                            <Button
                                                size="sm"
                                                className="h-7 text-xs px-3"
                                                disabled={isSubmittingCard}
                                                onClick={() => handleAddCardSubmit(status.id)}
                                            >
                                                {isSubmittingCard ? t('Adding...') : t('Add card')}
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-7 w-7 p-0"
                                                onClick={() => {
                                                    setActiveAddingCardStatusId(null);
                                                    setNewCardTitle('');
                                                }}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="w-full justify-start text-xs text-muted-foreground hover:text-foreground h-8 px-2"
                                        onClick={() => {
                                            setActiveAddingCardStatusId(status.id);
                                            setNewCardTitle('');
                                            if (!newCardProjectId && projects[0]) {
                                                setNewCardProjectId(projects[0].id);
                                            }
                                        }}
                                    >
                                        <Plus className="mr-1.5 h-3.5 w-3.5" />
                                        {t('Add a card')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    );
                })}

                {/* "+ Add another list" Column (Trello Style) */}
                <div className="w-80 shrink-0">
                    {isAddingList ? (
                        <div className="rounded-xl bg-muted/60 dark:bg-muted/40 border p-3 shadow-sm space-y-3">
                            <form onSubmit={handleAddListSubmit} className="space-y-2.5">
                                <Input
                                    autoFocus
                                    placeholder={t('Enter list name...')}
                                    value={newListName}
                                    onChange={(e) => setNewListName(e.target.value)}
                                    className="text-sm h-9 bg-background"
                                />
                                <div className="space-y-1">
                                    <label className="text-xs text-muted-foreground font-medium">{t('Category')}</label>
                                    <Select
                                        value={newListCategory}
                                        onValueChange={(val: any) => setNewListCategory(val)}
                                    >
                                        <SelectTrigger className="h-8 text-xs bg-background">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="TODO">{t('To Do')}</SelectItem>
                                            <SelectItem value="IN_PROGRESS">{t('In Progress')}</SelectItem>
                                            <SelectItem value="DONE">{t('Done')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-center gap-2 pt-1">
                                    <Button
                                        type="submit"
                                        size="sm"
                                        className="h-8 text-xs px-3"
                                        disabled={createStatusMutation.isPending}
                                    >
                                        {createStatusMutation.isPending ? t('Adding...') : t('Add list')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="h-8 w-8 p-0"
                                        onClick={() => {
                                            setIsAddingList(false);
                                            setNewListName('');
                                        }}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setIsAddingList(true)}
                            className="w-full flex items-center gap-2 rounded-xl border border-dashed border-muted-foreground/30 hover:border-primary/60 bg-muted/30 hover:bg-muted/60 p-3.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-all cursor-pointer text-left"
                        >
                            <Plus className="h-4 w-4" />
                            <span>{t('Add another list')}</span>
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

