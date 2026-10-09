import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { useIssues, CreateIssuePayload, UpdateIssuePayload } from '../hooks/useIssues';
import { IssueList } from './IssueList';
import { KanbanBoard } from './KanbanBoard';
import { IssueFormDialog, SelectOption } from './IssueFormDialog';
import { LogWorkDialog, LogWorkPayload } from './LogWorkDialog';
import { BoardFilterBar, BoardFilterState } from './BoardFilterBar';
import { Issue } from '@/types/issue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Search, Activity, BarChart2 } from 'lucide-react';
import { ProjectActivitySheet } from './ProjectActivitySheet';
import { BacklogManager } from './BacklogManager';
import { ProjectAnalyticsDashboard } from '@/modules/Workload/Projects/components/ProjectAnalyticsDashboard';
import { useSprints } from '../hooks/useSprints';
import { toast } from 'sonner';
import { useTranslate } from "@/hooks/useTranslate";
import { useIconSize } from '@/hooks/use-appearance';

interface LookupItem {
    id: string;
    name: string;
}

interface IssuesManagerProps {
    projectId: string;
    lookups: {
        issueTypes: LookupItem[];
        priorities: LookupItem[];
        statuses: LookupItem[];
        users: LookupItem[];
    };
}

export const IssuesManager: React.FC<IssuesManagerProps> = ({ projectId, lookups }) => {
    const { t } = useTranslate();
    const { iconSize } = useIconSize();
    const { 
        issues, 
        isLoading, 
        createIssue, 
        updateIssue, 
        deleteIssue,
        transitionIssue,
        logWork,
        isCreating,
        isUpdating,
        isLoggingWork,
    } = useIssues(projectId);
    const { sprints } = useSprints(projectId);

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedIssue, setSelectedIssue] = useState<Issue | null>(null);
    const { url, props } = usePage<any>();
    const activeProject = props.project;
    const [viewMode, setViewModeState] = useState<'list' | 'board' | 'backlog' | 'analytics'>(() => {
        // Safe URL parsing for both SSR and Client
        const search = url.split('?')[1];
        if (search) {
            const params = new URLSearchParams('?' + search);
            const tab = params.get('tab');
            if (tab === 'list' || tab === 'board' || tab === 'backlog' || tab === 'analytics') return tab;
        }
        
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(`project-${projectId}-viewMode`);
            if (saved === 'list' || saved === 'board' || saved === 'backlog' || saved === 'analytics') return saved;
        }
        return 'board';
    });

    const setViewMode = (mode: 'list' | 'board' | 'backlog' | 'analytics') => {
        setViewModeState(mode);
        if (typeof window !== 'undefined') {
            localStorage.setItem(`project-${projectId}-viewMode`, mode);
            const url = new URL(window.location.href);
            url.searchParams.set('tab', mode);
            window.history.replaceState({}, '', url.toString());
        }
    };
    
    // Add effect to sync URL if it lacks the tab on first load
    React.useEffect(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            if (!params.get('tab')) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', viewMode);
                window.history.replaceState({}, '', url.toString());
            }
        }
    }, [viewMode]);

    // State for Log Work Dialog
    const [isLogWorkOpen, setIsLogWorkOpen] = useState(false);
    const [selectedLogWorkIssue, setSelectedLogWorkIssue] = useState<Issue | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isActivityOpen, setIsActivityOpen] = useState(false);
    const [boardFilters, setBoardFilters] = useState<BoardFilterState>({
        assigneeIds: [],
        priorityIds: [],
        issueTypeIds: [],
    });

    const filteredIssues = issues.filter(issue => {
        const matchesSearch = issue.title.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesSearch;
    });

    // Board-specific filtering (assignee + priority + type on top of search)
    const boardFilteredIssues = filteredIssues.filter(issue => {
        const matchesAssignee =
            boardFilters.assigneeIds.length === 0 ||
            boardFilters.assigneeIds.includes(String(issue.assignee_id));
        const matchesPriority =
            boardFilters.priorityIds.length === 0 ||
            boardFilters.priorityIds.includes(String(issue.priority_id));
        const matchesType =
            boardFilters.issueTypeIds.length === 0 ||
            boardFilters.issueTypeIds.includes(String(issue.issue_type_id));
        return matchesAssignee && matchesPriority && matchesType;
    });

    const activeSprint = sprints.find(s => s.state?.toUpperCase() === 'ACTIVE');
    const kanbanIssues = activeSprint
        ? boardFilteredIssues.filter(i => i.sprint_id === activeSprint.id)
        : boardFilteredIssues;

    const doneStatusIds = lookups.statuses
        .filter(s => ['done', 'closed', 'resolved'].includes(s.name.toLowerCase()))
        .map(s => s.id);
    
    const finalDoneIds = doneStatusIds.length > 0 ? doneStatusIds : [lookups.statuses[lookups.statuses.length - 1]?.id];

    const totalIssues = issues.length;
    const closedIssues = issues.filter(issue => finalDoneIds.includes(issue.status_id)).length;
    const progressPercentage = totalIssues === 0 ? 0 : Math.round((closedIssues / totalIssues) * 100);

    const handleCreateClick = () => {
        setSelectedIssue(null);
        setIsDialogOpen(true);
    };

    const handleEditClick = (issue: Issue) => {
        setSelectedIssue(issue);
        setIsDialogOpen(true);
    };

    const handleLogWorkClick = (issue: Issue) => {
        setSelectedLogWorkIssue(issue);
        setIsLogWorkOpen(true);
    };

    const handleDelete = async (id: string) => {
        if (true) {
            await deleteIssue(id);
        }
    };

    const handleTransition = async (issueId: string, toStatusId: string) => {
        try {
            await transitionIssue({ id: issueId, toStatusId });
        } catch (error: any) {
            console.error('Failed to transition issue:', error);
            if (error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error(t('Gagal memindahkan tiket.'));
            }
        }
    };

    const handleCloseDialog = () => {
        setIsDialogOpen(false);
        setSelectedIssue(null);
    };

    const handleCloseLogWork = () => {
        setIsLogWorkOpen(false);
        setSelectedLogWorkIssue(null);
    };

    const handleSubmit = async (payload: CreateIssuePayload | UpdateIssuePayload) => {
        try {
            if (selectedIssue) {
                await updateIssue({ id: selectedIssue.id, payload: payload as UpdateIssuePayload });
            } else {
                await createIssue(payload as CreateIssuePayload);
            }
            handleCloseDialog();
        } catch (error) {
            console.error('Failed to save issue:', error);
            
        }
    };

    const handleLogWorkSubmit = async (payload: LogWorkPayload) => {
        if (!selectedLogWorkIssue) return;
        try {
            await logWork({ id: selectedLogWorkIssue.id, payload });
            toast.success(t('Worklog berhasil ditambahkan'));
            handleCloseLogWork();
        } catch (error) {
            console.error('Failed to log work:', error);
            toast.error(t('Gagal menambahkan worklog'));
        }
    };

    const searchInputClass =
        iconSize === 'sm' ? 'h-8 text-xs pl-8' : iconSize === 'lg' ? 'h-10 text-sm pl-8' : 'h-9 text-xs pl-8';

    const tabBtnClass =
        iconSize === 'sm' ? 'h-7.5 px-2.5 text-xs' : iconSize === 'lg' ? 'h-9.5 px-4 text-sm' : 'h-8 px-3 text-xs';

    const progressCardPadding =
        iconSize === 'sm' ? 'p-3 text-xs' : iconSize === 'lg' ? 'p-5 text-base' : 'p-4 text-sm';

    const progressBarHeight =
        iconSize === 'sm' ? 'h-1.5' : iconSize === 'lg' ? 'h-2.5' : 'h-2';

    const boardTitleClass =
        iconSize === 'sm' ? 'text-base font-semibold' : iconSize === 'lg' ? 'text-xl font-bold' : 'text-lg font-medium';

    const createBtnClass =
        iconSize === 'sm' ? 'h-8 text-xs px-3' : iconSize === 'lg' ? 'h-10 text-sm px-4.5' : 'h-9 text-xs px-3.5';

    return (
        <div className="w-full space-y-4">
            <div className="flex flex-col gap-4">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <div className="relative w-full sm:w-72">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder={t('Search issues...')}
                                className={searchInputClass}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => setIsActivityOpen(true)} 
                            className={`shrink-0 flex items-center gap-2 ${iconSize === 'sm' ? 'h-8 text-xs' : iconSize === 'lg' ? 'h-10 text-sm' : 'h-9 text-xs'}`}
                        >
                            <Activity className="h-4 w-4" />
                            <span className="hidden sm:inline">{t('Activity History')}</span>
                        </Button>
                    </div>

                    <div className="flex gap-1.5 bg-muted p-1 rounded-lg shrink-0">
                        <Button 
                            variant={viewMode === 'board' ? 'default' : 'ghost'} 
                            size="sm" 
                            className={tabBtnClass}
                            onClick={() => setViewMode('board')}
                        >
                            {t('Board')}
                        </Button>
                        <Button 
                            variant={viewMode === 'list' ? 'default' : 'ghost'} 
                            size="sm" 
                            className={tabBtnClass}
                            onClick={() => setViewMode('list')}
                        >
                            {t('List')}
                        </Button>
                        <Button 
                            variant={viewMode === 'backlog' ? 'default' : 'ghost'} 
                            size="sm" 
                            className={tabBtnClass}
                            onClick={() => setViewMode('backlog')}
                        >
                            {t('Backlog')}
                        </Button>
                        <Button 
                            variant={viewMode === 'analytics' ? 'default' : 'ghost'} 
                            size="sm" 
                            className={tabBtnClass}
                            onClick={() => setViewMode('analytics')}
                        >
                            <BarChart2 className="h-3.5 w-3.5 mr-1" />
                            {t('Analytics')}
                        </Button>
                    </div>
                </div>
            </div>

            <div className={`flex items-center gap-4 bg-card border rounded-lg ${progressCardPadding}`}>
                <div className="flex-1">
                    <div className="flex justify-between mb-2">
                        <span className="font-medium">{t('Project Progress')}</span>
                        <span className="font-medium">{progressPercentage}%</span>
                    </div>
                    <Progress value={progressPercentage} className={progressBarHeight} />
                </div>
                <div className="text-muted-foreground text-right min-w-[100px]">
                    {closedIssues} / {totalIssues} {t('Done')}
                </div>
            </div>

            {viewMode === 'analytics' ? (
                <ProjectAnalyticsDashboard projectId={projectId} />
            ) : viewMode === 'list' ? (
                <IssueList
                    issues={filteredIssues}
                    isLoading={isLoading}
                    onCreateClick={handleCreateClick}
                    onEdit={handleEditClick}
                    onDelete={handleDelete}
                    onLogWork={handleLogWorkClick}
                    issueTypes={lookups.issueTypes}
                    priorities={lookups.priorities}
                    statuses={lookups.statuses}
                    assignees={lookups.users}
                />
            ) : viewMode === 'backlog' ? (
                <BacklogManager 
                    projectId={projectId}
                    issues={filteredIssues}
                    lookups={lookups}
                    onIssueClick={handleEditClick}
                />
            ) : (
                <div className="space-y-4">
                    <div className="flex items-center justify-between gap-4 flex-wrap">
                        <div className="flex items-center gap-3">
                            <h3 className={boardTitleClass}>{t('Issues Board')}</h3>
                            <BoardFilterBar
                                users={lookups.users}
                                priorities={lookups.priorities}
                                issueTypes={lookups.issueTypes}
                                filters={boardFilters}
                                onChange={setBoardFilters}
                            />
                        </div>
                        <Button onClick={handleCreateClick} className={createBtnClass}>{t('Create Task')}</Button>
                    </div>
                    {isLoading ? (
                        <div className="text-center p-4">{t('Loading board...')}</div>
                    ) : (
                        <KanbanBoard 
                            issues={kanbanIssues}
                            statuses={lookups.statuses}
                            onTransition={handleTransition}
                            onEdit={handleEditClick}
                            onLogWork={handleLogWorkClick}
                            lookups={lookups}
                        />
                    )}
                </div>
            )}

            <IssueFormDialog
                isOpen={isDialogOpen}
                onClose={handleCloseDialog}
                onSubmit={handleSubmit}
                issue={selectedIssue}
                isLoading={isCreating || isUpdating}
                projectId={projectId}
                projects={activeProject ? [{ id: String(activeProject.id), name: activeProject.name }] : []}
                issueTypes={lookups.issueTypes}
                priorities={lookups.priorities}
                statuses={lookups.statuses}
                assignees={lookups.users}
            />

            <LogWorkDialog
                isOpen={isLogWorkOpen}
                onClose={handleCloseLogWork}
                onSubmit={handleLogWorkSubmit}
                issue={selectedLogWorkIssue}
                isLoading={isLoggingWork}
            />

            <ProjectActivitySheet 
                projectId={projectId}
                isOpen={isActivityOpen}
                onClose={() => setIsActivityOpen(false)}
            />
        </div>
    );
};
