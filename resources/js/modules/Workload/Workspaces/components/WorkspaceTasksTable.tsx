import React, { useState, useMemo } from 'react';
import { useWorkspaceIssues } from '../hooks/useWorkspaceIssues';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { IssueFormDialog, SelectOption } from '@/modules/Workload/Issues/components/IssueFormDialog';
import { Plus, Search, Pencil, ChevronLeft, ChevronRight, User, FolderKanban } from 'lucide-react';
import { CreateIssuePayload, UpdateIssuePayload } from '@/modules/Workload/Issues/hooks/useIssues';
import { Input } from '@/components/ui/input';
import { PriorityBadge } from '@/components/PriorityBadge';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from '@/lib/axios';
import { useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { useTranslate } from '@/hooks/useTranslate';
import { useIconSize } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Project } from '@/modules/Workload/Projects/types';
import { Issue } from '@/types/issue';
import { Checkbox } from '@/components/ui/checkbox';
import { useBulkUpdateIssues } from '@/modules/Workload/Issues/hooks/useIssues';

interface WorkspaceTasksTableProps {
    workspaceId: string;
    projects: Project[];
    lookups?: any;
    currentUserId?: string;
}

export const WorkspaceTasksTable: React.FC<WorkspaceTasksTableProps> = ({
    workspaceId,
    projects,
    lookups,
    currentUserId,
}) => {
    const { t } = useTranslate();
    const { iconSize, updateIconSize } = useIconSize();
    const queryClient = useQueryClient();

    // Filters State
    const [selectedProject, setSelectedProject] = useState<string>('all');
    const [selectedAssignee, setSelectedAssignee] = useState<string>('all');
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState<string>('');

    // Pagination State
    const [perPage, setPerPage] = useState<number>(10);
    const [currentPage, setCurrentPage] = useState<number>(1);

    // Form Modal State (Create & Edit)
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingIssue, setEditingIssue] = useState<Issue | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    
    // Bulk Update State
    const [selectedIssueIds, setSelectedIssueIds] = useState<string[]>([]);
    const { mutateAsync: bulkUpdateIssues, isPending: isBulkUpdating } = useBulkUpdateIssues();

    // Fetch All Workspace Issues
    const { data: issues = [], isLoading } = useWorkspaceIssues(workspaceId);

    // Available users / assignees
    const users: SelectOption[] = lookups?.users || [];
    const projectOptions: SelectOption[] = projects.map((p) => ({
        id: p.id,
        name: p.name,
    }));

    // Filter Logic
    const filteredIssues = useMemo(() => {
        return issues.filter((issue: any) => {
            // Project filter
            if (selectedProject !== 'all' && String(issue.project_id) !== String(selectedProject)) {
                return false;
            }

            // Assignee filter
            if (selectedAssignee === 'assigned_to_me') {
                if (String(issue.assignee_id) !== String(currentUserId)) return false;
            } else if (selectedAssignee === 'unassigned') {
                if (issue.assignee_id) return false;
            } else if (selectedAssignee !== 'all') {
                if (String(issue.assignee_id) !== String(selectedAssignee)) return false;
            }

            // Status category filter
            if (statusFilter !== 'all') {
                const category = issue.status?.category?.toUpperCase();
                if (category !== statusFilter) return false;
            }

            // Search query filter
            if (searchQuery.trim()) {
                const query = searchQuery.toLowerCase();
                const titleMatch = issue.title?.toLowerCase().includes(query);
                const idMatch = issue.id?.toLowerCase().includes(query);
                if (!titleMatch && !idMatch) return false;
            }

            return true;
        });
    }, [issues, selectedProject, selectedAssignee, statusFilter, searchQuery, currentUserId]);

    // Pagination Calculations
    const totalItems = filteredIssues.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
    const safeCurrentPage = Math.min(currentPage, totalPages);

    const paginatedIssues = useMemo(() => {
        const start = (safeCurrentPage - 1) * perPage;
        return filteredIssues.slice(start, start + perPage);
    }, [filteredIssues, safeCurrentPage, perPage]);

    // Reset pagination to page 1 on filter change
    const handleFilterChange = (setter: (val: any) => void, val: any) => {
        setter(val);
        setCurrentPage(1);
    };

    // Open Create Modal
    const handleOpenCreate = () => {
        setEditingIssue(null);
        setIsFormOpen(true);
    };

    // Open Edit Modal
    const handleOpenEdit = (issue: Issue) => {
        setEditingIssue(issue);
        setIsFormOpen(true);
    };

    // Handle Create or Update Submit
    const handleFormSubmit = async (payload: CreateIssuePayload | UpdateIssuePayload) => {
        setIsSubmitting(true);
        try {
            if (editingIssue) {
                // Update existing issue
                await axios.put(`/api/issues/${editingIssue.id}`, payload);
                toast.success(t('Task updated successfully'));
            } else {
                // Create new issue
                const createPayload = payload as CreateIssuePayload;
                const targetProjectId = createPayload.project_id || projects[0]?.id;
                if (!targetProjectId) {
                    toast.error(t('Project is required'));
                    return;
                }
                await axios.post(`/api/projects/${targetProjectId}/issues`, createPayload);
                toast.success(t('Task created successfully'));
            }

            queryClient.invalidateQueries({ queryKey: ['workspaces', workspaceId, 'issues'] });
            setIsFormOpen(false);
            setEditingIssue(null);
        } catch (error: any) {
            toast.error(error?.response?.data?.message || t('Failed to save task'));
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="space-y-4">
            {/* Filter Bar */}
            <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-card p-4 rounded-xl border shadow-xs">
                <div className="flex flex-wrap items-center gap-2 flex-1">
                    {/* Search Input */}
                    <div className="relative w-full sm:w-64">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder={t('Search task title or key...')}
                            className="pl-8 h-9 text-xs"
                            value={searchQuery}
                            onChange={(e) => handleFilterChange(setSearchQuery, e.target.value)}
                        />
                    </div>

                    {/* Filter Project */}
                    <div className="w-full sm:w-48">
                        <Select
                            value={selectedProject}
                            onValueChange={(val) => handleFilterChange(setSelectedProject, val)}
                        >
                            <SelectTrigger className="h-9 text-xs">
                                <div className="flex items-center gap-1.5 truncate">
                                    <FolderKanban className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                    <SelectValue placeholder={t('All Projects')} />
                                </div>
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

                    {/* Filter Assignee */}
                    <div className="w-full sm:w-48">
                        <Select
                            value={selectedAssignee}
                            onValueChange={(val) => handleFilterChange(setSelectedAssignee, val)}
                        >
                            <SelectTrigger className="h-9 text-xs">
                                <div className="flex items-center gap-1.5 truncate">
                                    <User className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                    <SelectValue placeholder={t('All Assignees')} />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('All Assignees')}</SelectItem>
                                {currentUserId && (
                                    <SelectItem value="assigned_to_me">{t('Assigned to Me')}</SelectItem>
                                )}
                                <SelectItem value="unassigned">{t('Unassigned')}</SelectItem>
                                {users.map((u) => (
                                    <SelectItem key={u.id} value={u.id}>
                                        {u.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Status Category Filter */}
                    <div className="w-full sm:w-36">
                        <Select
                            value={statusFilter}
                            onValueChange={(val) => handleFilterChange(setStatusFilter, val)}
                        >
                            <SelectTrigger className="h-9 text-xs">
                                <SelectValue placeholder={t('All Statuses')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('All Statuses')}</SelectItem>
                                <SelectItem value="TODO">{t('To Do')}</SelectItem>
                                <SelectItem value="IN_PROGRESS">{t('In Progress')}</SelectItem>
                                <SelectItem value="DONE">{t('Done')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Density Selector & Create Task Button */}
                <div className="flex items-center justify-end gap-2 shrink-0">
                    <div className="flex items-center gap-1.5 bg-muted/60 p-1 rounded-lg border">
                        <span className="text-[11px] font-medium text-muted-foreground px-1 hidden md:inline">{t('Density')}:</span>
                        <Button
                            variant={iconSize === 'sm' ? 'default' : 'ghost'}
                            size="sm"
                            className="h-7 px-2.5 text-xs font-semibold"
                            onClick={() => updateIconSize('sm')}
                            title={t('Small density')}
                        >
                            S
                        </Button>
                        <Button
                            variant={iconSize === 'md' ? 'default' : 'ghost'}
                            size="sm"
                            className="h-7 px-2.5 text-xs font-semibold"
                            onClick={() => updateIconSize('md')}
                            title={t('Medium density')}
                        >
                            M
                        </Button>
                        <Button
                            variant={iconSize === 'lg' ? 'default' : 'ghost'}
                            size="sm"
                            className="h-7 px-2.5 text-xs font-semibold"
                            onClick={() => updateIconSize('lg')}
                            title={t('Large density')}
                        >
                            L
                        </Button>
                    </div>

                    <Button size="sm" className="h-9 text-xs shrink-0" onClick={handleOpenCreate}>
                        <Plus className="mr-1.5 h-4 w-4" />
                        {t('Create Task')}
                    </Button>
                </div>
            </div>

            {/* Tasks Data Table */}
            <div className="rounded-xl border bg-card overflow-hidden shadow-xs">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-[40px] px-4">
                                <Checkbox 
                                    checked={paginatedIssues.length > 0 && paginatedIssues.every((i: any) => selectedIssueIds.includes(String(i.id)))}
                                    onCheckedChange={(checked) => {
                                        if (checked) {
                                            const ids = new Set([...selectedIssueIds, ...paginatedIssues.map((i: any) => String(i.id))]);
                                            setSelectedIssueIds(Array.from(ids));
                                        } else {
                                            const idsToRemove = new Set(paginatedIssues.map((i: any) => String(i.id)));
                                            setSelectedIssueIds(selectedIssueIds.filter(id => !idsToRemove.has(id)));
                                        }
                                    }}
                                    aria-label="Select all"
                                />
                            </TableHead>
                            <TableHead className="w-[180px]">{t('Project')}</TableHead>
                            <TableHead>{t('Task Title')}</TableHead>
                            <TableHead className="w-[150px]">{t('Status')}</TableHead>
                            <TableHead className="w-[130px]">{t('Priority')}</TableHead>
                            <TableHead className="w-[160px]">{t('Assignee')}</TableHead>
                            <TableHead className="w-[80px] text-right">{t('Action')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-12 text-muted-foreground text-sm">
                                    {t('Loading tasks...')}
                                </TableCell>
                            </TableRow>
                        ) : paginatedIssues.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-12 text-muted-foreground text-sm">
                                    {t('No tasks found matching your filter criteria.')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            paginatedIssues.map((issue: any) => {
                                const proj = projects.find((p) => p.id === issue.project_id) || issue.project;

                                return (
                                    <TableRow
                                        key={issue.id}
                                        className={`hover:bg-muted/40 transition-colors ${
                                            iconSize === 'sm' ? 'h-11 text-xs' : iconSize === 'lg' ? 'h-16 text-sm' : 'h-13'
                                        }`}
                                    >
                                        <TableCell className="px-4">
                                            <Checkbox 
                                                checked={selectedIssueIds.includes(String(issue.id))}
                                                onCheckedChange={(checked) => {
                                                    if (checked) {
                                                        setSelectedIssueIds(prev => [...prev, String(issue.id)]);
                                                    } else {
                                                        setSelectedIssueIds(prev => prev.filter(id => id !== String(issue.id)));
                                                    }
                                                }}
                                                aria-label="Select row"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {proj ? (
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs font-normal max-w-[160px] truncate bg-background"
                                                    title={proj.name}
                                                >
                                                    {proj.name}
                                                </Badge>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">-</span>
                                            )}
                                        </TableCell>

                                        <TableCell>
                                            <div
                                                onClick={() => handleOpenEdit(issue)}
                                                className="font-medium text-sm text-foreground hover:underline hover:text-primary cursor-pointer line-clamp-1"
                                                title={issue.title}
                                            >
                                                {issue.title}
                                            </div>
                                            {issue.id && (
                                                <span className="text-[11px] font-mono text-muted-foreground">
                                                    {issue.id.slice(0, 8)}
                                                </span>
                                            )}
                                        </TableCell>

                                        <TableCell>
                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-muted text-foreground">
                                                {issue.status?.name || t('Unknown')}
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            {issue.priority ? (
                                                <PriorityBadge
                                                    name={issue.priority.name}
                                                    category={issue.priority.category}
                                                    size={iconSize}
                                                />
                                            ) : (
                                                <span className="text-xs text-muted-foreground">-</span>
                                            )}
                                        </TableCell>

                                        <TableCell className="text-xs text-muted-foreground">
                                            {issue.assignee?.name || (
                                                <span className="text-muted-foreground/60 italic">{t('Unassigned')}</span>
                                            )}
                                        </TableCell>

                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => handleOpenEdit(issue)}
                                                title={t('Edit / Update Task')}
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })
                        )}
                    </TableBody>
                </Table>

                {/* Pagination Controls Footer */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 border-t bg-card text-xs text-muted-foreground">
                    <div className="flex items-center gap-3">
                        <span>
                            {t('Showing')}{' '}
                            <strong className="text-foreground">
                                {totalItems === 0 ? 0 : (safeCurrentPage - 1) * perPage + 1}
                            </strong>{' '}
                            {t('to')}{' '}
                            <strong className="text-foreground">
                                {Math.min(safeCurrentPage * perPage, totalItems)}
                            </strong>{' '}
                            {t('of')} <strong className="text-foreground">{totalItems}</strong> {t('tasks')}
                        </span>

                        <div className="flex items-center gap-1.5 ml-2">
                            <span>{t('Rows per page:')}</span>
                            <Select
                                value={perPage.toString()}
                                onValueChange={(val) => {
                                    setPerPage(Number(val));
                                    setCurrentPage(1);
                                }}
                            >
                                <SelectTrigger className="h-7 w-18 text-xs bg-background">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="20">20</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="flex items-center gap-1.5">
                        <span className="mr-2">
                            {t('Page')} <strong className="text-foreground">{safeCurrentPage}</strong> {t('of')}{' '}
                            <strong className="text-foreground">{totalPages}</strong>
                        </span>

                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 w-7 p-0"
                            disabled={safeCurrentPage <= 1}
                            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                        >
                            <ChevronLeft className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 w-7 p-0"
                            disabled={safeCurrentPage >= totalPages}
                            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                        >
                            <ChevronRight className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Floating Action Bar */}
            {selectedIssueIds.length > 0 && (
                <div className="fixed bottom-6 left-1/2 -translate-x-1/2 bg-card border shadow-lg rounded-full px-6 py-3 flex items-center gap-4 z-50 animate-in slide-in-from-bottom-5">
                    <span className="text-sm font-medium whitespace-nowrap">
                        {selectedIssueIds.length} {t('tasks selected')}
                    </span>
                    <div className="h-4 w-px bg-border" />
                    
                    <Select onValueChange={async (val) => {
                        try {
                            await bulkUpdateIssues({ issueIds: selectedIssueIds, payload: { status_id: val } });
                            toast.success(t('Tasks updated successfully'));
                            setSelectedIssueIds([]);
                        } catch (e) {
                            toast.error(t('Failed to update tasks'));
                        }
                    }}>
                        <SelectTrigger className="h-8 text-xs w-[130px] border-none shadow-none focus:ring-0">
                            <SelectValue placeholder={t('Update Status')} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {lookups?.statuses?.map((s: any) => (
                                <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select onValueChange={async (val) => {
                        try {
                            await bulkUpdateIssues({ issueIds: selectedIssueIds, payload: { assignee_id: val === 'unassigned' ? null : val } });
                            toast.success(t('Tasks assigned successfully'));
                            setSelectedIssueIds([]);
                        } catch (e) {
                            toast.error(t('Failed to assign tasks'));
                        }
                    }}>
                        <SelectTrigger className="h-8 text-xs w-[140px] border-none shadow-none focus:ring-0">
                            <SelectValue placeholder={t('Update Assignee')} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectItem value="unassigned">{t('Unassigned')}</SelectItem>
                            {users.map((u: any) => (
                                <SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Button 
                        size="sm" 
                        variant="ghost" 
                        className="h-8 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                        onClick={async () => {
                            if (window.confirm(t('Are you sure you want to delete selected tasks?'))) {
                                try {
                                    const promises = selectedIssueIds.map(id => axios.delete(`/api/issues/${id}`));
                                    await Promise.all(promises);
                                    queryClient.invalidateQueries({ queryKey: ['workspaces', workspaceId, 'issues'] });
                                    toast.success(t('Tasks deleted successfully'));
                                    setSelectedIssueIds([]);
                                } catch (e) {
                                    toast.error(t('Failed to delete tasks'));
                                }
                            }
                        }}
                    >
                        {t('Delete')}
                    </Button>
                </div>
            )}

            {/* Create & Edit Modal Dialog */}
            <IssueFormDialog
                isOpen={isFormOpen}
                onClose={() => {
                    setIsFormOpen(false);
                    setEditingIssue(null);
                }}
                onSubmit={handleFormSubmit}
                issue={editingIssue}
                isLoading={isSubmitting}
                projects={projectOptions}
                issueTypes={lookups?.issueTypes || []}
                priorities={lookups?.priorities || []}
                statuses={lookups?.statuses || []}
                assignees={users}
            />
        </div>
    );
};
