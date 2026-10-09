import { useState, useMemo } from 'react';
import { MoreHorizontal, Plus, Pencil, Trash, Search, LayoutGrid, List, Calendar } from 'lucide-react';
import { Project } from '../types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Combobox } from '@/components/ui/combobox';
import { PriorityBadge } from '@/components/PriorityBadge';
import { Progress } from '@/components/ui/progress';
import { useTranslate } from "@/hooks/useTranslate";
import { useIconSize, IconSize } from '@/hooks/use-appearance';

const getProjectAvatarColor = (name: string) => {
    const colors = [
        'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
        'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
        'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
        'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash) % colors.length;
    return colors[index];
};

export function ProjectList({
    projects,
    canManageProjects,
    priorities,
    onEdit,
    onDelete,
    onCreate,
}: {
    projects: Project[];
    canManageProjects: boolean;
    priorities?: { id: string; name: string; category: string }[];
    onEdit: (project: Project) => void;
    onDelete: (project: Project) => void;
    onCreate: () => void;
}) {
    const { t } = useTranslate();
    const { iconSize, updateIconSize } = useIconSize();
    const [searchQuery, setSearchQuery] = useState('');
    const [priorityFilter, setPriorityFilter] = useState('all');
    
    const [viewMode, setViewMode] = useState<'grid' | 'table'>(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('wlms_projects_view_mode') as 'grid' | 'table';
            if (saved === 'grid' || saved === 'table') return saved;
        }
        return 'grid';
    });

    const handleViewModeChange = (mode: 'grid' | 'table') => {
        setViewMode(mode);
        if (typeof window !== 'undefined') {
            localStorage.setItem('wlms_projects_view_mode', mode);
        }
    };

    const filteredProjects = useMemo(() => {
        return projects.filter(p => {
            const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                  p.key.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesPriority = priorityFilter === 'all' || p.priority_id === priorityFilter || (priorityFilter === 'none' && !p.priority_id);
            return matchesSearch && matchesPriority;
        });
    }, [projects, searchQuery, priorityFilter]);

    const priorityOptions = [{ value: 'all', label: t('All Priorities') }, { value: 'none', label: t('No Priority') }, ...(priorities || []).map(p => ({ value: p.id, label: p.name }))];

    return (
        <div className="space-y-4">
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div className="flex flex-1 items-center gap-2">
                    <div className="relative w-full sm:w-72">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder={t('Search projects...')}
                            className="pl-8"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <div className="w-44 shrink-0">
                        <Combobox
                            options={priorityOptions}
                            value={priorityFilter}
                            onChange={setPriorityFilter}
                            placeholder={t('Filter by priority...')}
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-2 shrink-0">
                    <div className="flex items-center border rounded-md p-0.5 bg-muted/40">
                        <Button
                            variant={viewMode === 'grid' ? 'secondary' : 'ghost'}
                            size="sm"
                            className="h-8 px-2.5"
                            onClick={() => handleViewModeChange('grid')}
                            title={t('Grid View')}
                        >
                            <LayoutGrid className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={viewMode === 'table' ? 'secondary' : 'ghost'}
                            size="sm"
                            className="h-8 px-2.5"
                            onClick={() => handleViewModeChange('table')}
                            title={t('Table View')}
                        >
                            <List className="h-4 w-4" />
                        </Button>
                    </div>

                    {/* Global Icon / Card Size Selector (S, M, L) */}
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

                    {canManageProjects && (
                        <Button onClick={onCreate} size="sm" className="h-9">
                            <Plus className="mr-2 h-4 w-4" />
                            {t('Create Project')}
                        </Button>
                    )}
                </div>
            </div>

            {filteredProjects.length === 0 ? (
                <Card>
                    <CardContent className="flex h-32 flex-col items-center justify-center text-muted-foreground">
                        <p>{projects.length === 0 ? t('No projects found in this workspace.') : t('No projects match your search.')}</p>
                    </CardContent>
                </Card>
            ) : viewMode === 'grid' ? (
                <div className={`grid gap-4 items-stretch ${
                    iconSize === 'sm'
                        ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3'
                        : iconSize === 'lg'
                        ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5'
                        : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
                }`}>
                    {filteredProjects.map((project) => {
                        const total = project.total_issues_count || 0;
                        const completed = project.completed_issues_count || 0;
                        const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
                        const avatarColor = getProjectAvatarColor(project.name);

                        const avatarSizeClass =
                            iconSize === 'sm'
                                ? 'h-7 w-7 text-xs rounded-md'
                                : iconSize === 'lg'
                                ? 'h-12 w-12 text-base rounded-xl font-bold'
                                : 'h-9 w-9 text-sm rounded-lg';

                        const titleSizeClass =
                            iconSize === 'sm'
                                ? 'text-sm font-semibold'
                                : iconSize === 'lg'
                                ? 'text-lg font-bold'
                                : 'text-base font-semibold';

                        const cardHeaderPadding =
                            iconSize === 'sm'
                                ? 'p-3 pb-1.5'
                                : iconSize === 'lg'
                                ? 'p-6 pb-3'
                                : 'p-5 pb-2';

                        const cardContentPadding =
                            iconSize === 'sm'
                                ? 'p-3 pt-0 text-xs'
                                : iconSize === 'lg'
                                ? 'p-6 pt-0 text-sm'
                                : 'p-5 pt-0 text-sm';

                        return (
                            <Card key={project.id} className="flex flex-col h-full hover:border-primary/50 transition-colors">
                                <CardHeader className={`flex flex-row items-start justify-between space-y-0 ${cardHeaderPadding}`}>
                                    <div className="flex items-start gap-3 min-w-0 flex-1">
                                        <div className={`flex shrink-0 items-center justify-center border font-bold ${avatarSizeClass} ${avatarColor}`}>
                                            {project.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div className="space-y-1 min-w-0 flex-1">
                                            <Link href={`/projects/${project.id}`}>
                                                <CardTitle className={`${titleSizeClass} hover:underline hover:text-primary cursor-pointer line-clamp-1`} title={project.name}>
                                                    {project.name}
                                                </CardTitle>
                                            </Link>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <CardDescription className="font-mono text-xs">Key: {project.key}</CardDescription>
                                                {project.priority_id && priorities && (
                                                    <PriorityBadge 
                                                         name={priorities.find(p => p.id === project.priority_id)?.name || 'Unknown'} 
                                                         category={priorities.find(p => p.id === project.priority_id)?.category} 
                                                         size={iconSize}
                                                     />
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    {canManageProjects && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0 shrink-0 ml-1">
                                                    <span className="sr-only">{t('Open menu')}</span>
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => onEdit(project)}>
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    {t('Edit')}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    className="text-destructive focus:text-destructive"
                                                    onClick={() => onDelete(project)}
                                                >
                                                    <Trash className="mr-2 h-4 w-4" />
                                                    {t('Delete')}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </CardHeader>
                                <CardContent className={`flex-1 flex flex-col justify-between mt-1 ${cardContentPadding}`}>
                                    <div>
                                        {project.description && (
                                            <p className={`text-muted-foreground ${iconSize === 'sm' ? 'text-xs line-clamp-1' : iconSize === 'lg' ? 'text-sm line-clamp-3' : 'text-sm line-clamp-2'}`} title={project.description}>
                                                {project.description}
                                            </p>
                                        )}
                                        {(project.start_date || project.end_date) && (
                                            <div className={`text-muted-foreground flex items-center gap-1.5 ${iconSize === 'sm' ? 'mt-1.5 text-[11px]' : 'mt-2.5 text-xs'}`}>
                                                <Calendar className={`${iconSize === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5'}`} />
                                                <span>{project.start_date || '...'} → {project.end_date || '...'}</span>
                                            </div>
                                        )}
                                    </div>
                                
                                    {project.total_issues_count !== undefined && (
                                        <div className={`space-y-1 border-t border-border/50 ${iconSize === 'sm' ? 'mt-2 pt-1.5' : iconSize === 'lg' ? 'mt-4 pt-3' : 'mt-3 pt-2'}`}>
                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                <span>{t('Progress')}</span>
                                                <span className="font-medium">{completed} / {total} ({pct}%)</span>
                                            </div>
                                            <Progress value={pct} className={iconSize === 'sm' ? 'h-1' : iconSize === 'lg' ? 'h-2' : 'h-1.5'} />
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            ) : (
                <div className="rounded-md border bg-card overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Project')}</TableHead>
                                <TableHead className="w-[100px]">{t('Key')}</TableHead>
                                <TableHead className="w-[140px]">{t('Priority')}</TableHead>
                                <TableHead className="w-[200px]">{t('Progress')}</TableHead>
                                <TableHead className="w-[180px]">{t('Timeline')}</TableHead>
                                {canManageProjects && <TableHead className="w-[70px] text-right">{t('Actions')}</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredProjects.map((project) => {
                                const total = project.total_issues_count || 0;
                                const completed = project.completed_issues_count || 0;
                                const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
                                const avatarColor = getProjectAvatarColor(project.name);
                                const priority = priorities?.find(p => p.id === project.priority_id);

                                const tableAvatarSizeClass =
                                    iconSize === 'sm'
                                        ? 'h-6 w-6 text-[10px] rounded'
                                        : iconSize === 'lg'
                                        ? 'h-10 w-10 text-sm rounded-lg'
                                        : 'h-8 w-8 text-xs rounded-md';

                                return (
                                    <TableRow key={project.id} className={`hover:bg-muted/50 ${iconSize === 'sm' ? 'h-11' : iconSize === 'lg' ? 'h-16' : 'h-14'}`}>
                                        <TableCell>
                                            <Link href={`/projects/${project.id}`} className="flex items-center gap-3 group">
                                                <div className={`flex shrink-0 items-center justify-center border font-semibold ${tableAvatarSizeClass} ${avatarColor}`}>
                                                    {project.name.charAt(0).toUpperCase()}
                                                </div>
                                                <div className="min-w-0">
                                                    <span className={`font-medium text-foreground group-hover:underline group-hover:text-primary ${iconSize === 'sm' ? 'text-xs' : 'text-sm'}`}>
                                                        {project.name}
                                                    </span>
                                                    {project.description && (
                                                        <p className="text-xs text-muted-foreground line-clamp-1">{project.description}</p>
                                                    )}
                                                </div>
                                            </Link>
                                        </TableCell>
                                        <TableCell className="font-mono text-xs font-semibold">{project.key}</TableCell>
                                        <TableCell>
                                            {project.priority_id && priority ? (
                                                <PriorityBadge name={priority.name} category={priority.category} size={iconSize} />
                                            ) : (
                                                <span className="text-xs text-muted-foreground">-</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="space-y-1">
                                                <div className="flex justify-between text-xs text-muted-foreground">
                                                    <span>{completed}/{total}</span>
                                                    <span className="font-medium">{pct}%</span>
                                                </div>
                                                <Progress value={pct} className="h-1.5" />
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground">
                                            {project.start_date || project.end_date ? (
                                                <span>{project.start_date || '...'} → {project.end_date || '...'}</span>
                                            ) : (
                                                <span className="text-muted-foreground/60">-</span>
                                            )}
                                        </TableCell>
                                        {canManageProjects && (
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem onClick={() => onEdit(project)}>
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            {t('Edit')}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => onDelete(project)}
                                                        >
                                                            <Trash className="mr-2 h-4 w-4" />
                                                            {t('Delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            )}
        </div>
    );
}
