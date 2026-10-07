import { useState, useMemo } from 'react';
import { MoreHorizontal, Plus, Pencil, Trash, Search } from 'lucide-react';
import { Project } from '../types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    const [searchQuery, setSearchQuery] = useState('');
    const [priorityFilter, setPriorityFilter] = useState('all');

    const filteredProjects = useMemo(() => {
        return projects.filter(p => {
            const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                  p.key.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesPriority = priorityFilter === 'all' || p.priority_id === priorityFilter || (priorityFilter === 'none' && !p.priority_id);
            return matchesSearch && matchesPriority;
        });
    }, [projects, searchQuery, priorityFilter]);

    const priorityOptions = [{ value: 'all', label: 'All Priorities' }, { value: 'none', label: 'No Priority' }, ...(priorities || []).map(p => ({ value: p.id, label: p.name }))];

    return (
        <div className="space-y-4">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
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
                <div className="w-full sm:w-48">
                    <Combobox
                        options={priorityOptions}
                        value={priorityFilter}
                        onChange={setPriorityFilter}
                        placeholder={t('Filter by priority...')}
                    />
                </div>
                {canManageProjects && (
                    <Button onClick={onCreate} size="sm" className="w-full sm:w-auto">
                        <Plus className="mr-2 h-4 w-4" />
                        {t('Create Project')}
                                            </Button>
                )}
            </div>

            {filteredProjects.length === 0 ? (
                <Card>
                    <CardContent className="flex h-32 flex-col items-center justify-center text-muted-foreground">
                        <p>{projects.length === 0 ? 'No projects found in this workspace.' : 'No projects match your search.'}</p>
                    </CardContent>
                </Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 items-stretch">
                    {filteredProjects.map((project) => (
                        <Card key={project.id} className="flex flex-col h-full hover:border-primary/50 transition-colors">
                            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                                <div className="space-y-1">
                                    <Link href={`/projects/${project.id}`}>
                                        <CardTitle className="text-base font-semibold hover:underline hover:text-primary cursor-pointer line-clamp-1" title={project.name}>
                                            {project.name}
                                        </CardTitle>
                                    </Link>
                                    <div className="flex items-center gap-2">
                                        <CardDescription>Key: {project.key}</CardDescription>
                                        {project.priority_id && priorities && (
                                            <PriorityBadge 
                                                name={priorities.find(p => p.id === project.priority_id)?.name || 'Unknown'} 
                                                category={priorities.find(p => p.id === project.priority_id)?.category} 
                                            />
                                        )}
                                    </div>
                                </div>
                                {canManageProjects && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" className="h-8 w-8 p-0 shrink-0">
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
                            <CardContent className="flex-1 mt-2">
                                <p className="text-sm text-muted-foreground line-clamp-3" title={project.description || ''}>
                                    {project.description || 'No description provided.'}
                                </p>
                            
                                {project.total_issues_count !== undefined && (
                                    <div className="mt-4 space-y-1">
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>{t('Progress')}</span>
                                            <span>{project.completed_issues_count || 0} / {project.total_issues_count || 0}</span>
                                        </div>
                                        <Progress value={project.total_issues_count > 0 ? ((project.completed_issues_count || 0) / project.total_issues_count) * 100 : 0} className="h-1.5" />
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </div>
    );
}
