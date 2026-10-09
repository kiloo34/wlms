import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { IssuesManager } from '@/modules/Workload/Issues/components/IssuesManager';
import { Project } from '@/modules/Workload/Projects/types';
import { Button } from '@/components/ui/button';
import { index as projectsIndex } from '@/routes/projects';
import { PriorityBadge } from '@/components/PriorityBadge';
import { useTranslate } from "@/hooks/useTranslate";
import { useIconSize } from '@/hooks/use-appearance';

interface LookupItem {
    id: string;
    name: string;
    category?: string;
}

interface ProjectShowProps {
    project: Project;
    lookups: {
        issueTypes: LookupItem[];
        priorities: LookupItem[];
        statuses: LookupItem[];
        users: LookupItem[];
    };
}

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

export default function ProjectShow({ project, lookups }: ProjectShowProps) {
    const { t } = useTranslate();
    const { iconSize, updateIconSize } = useIconSize();
    const avatarColor = getProjectAvatarColor(project.name);

    const avatarSizeClass =
        iconSize === 'sm'
            ? 'h-8 w-8 text-sm rounded-md'
            : iconSize === 'lg'
            ? 'h-13 w-13 text-xl rounded-xl'
            : 'h-10 w-10 text-base rounded-lg';

    const titleSizeClass =
        iconSize === 'sm'
            ? 'text-xl font-bold tracking-tight'
            : iconSize === 'lg'
            ? 'text-3xl font-extrabold tracking-tight'
            : 'text-2xl font-bold tracking-tight';

    const handleBack = () => {
        router.visit(projectsIndex({ query: { workspace_id: project.workspace_id } }).url);
    };

    const currentPriority = lookups.priorities?.find(p => p.id === project.priority_id);

    return (
        <>
            <Head title={`Project: ${project.name}`} />
            
            <div className="flex h-full w-full flex-col gap-4 p-6">
                <div>
                    <Button variant="ghost" size="sm" onClick={handleBack} className="-ml-2 text-muted-foreground">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        {t('Back to Projects')}
                    </Button>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className={`flex shrink-0 items-center justify-center border font-bold ${avatarSizeClass} ${avatarColor}`}>
                            {project.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h1 className={titleSizeClass}>{project.name}</h1>
                            <div className="flex items-center gap-3 mt-1">
                                <p className="text-muted-foreground font-mono text-xs">
                                    Key: {project.key}
                                </p>
                                {project.priority_id && currentPriority && (
                                    <PriorityBadge 
                                        name={currentPriority.name} 
                                        category={currentPriority.category} 
                                        size={iconSize}
                                    />
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-1.5 bg-muted/60 p-1 rounded-lg border">
                        <span className="text-[11px] font-medium text-muted-foreground px-1.5 hidden sm:inline">{t('Density')}:</span>
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
                </div>

                <div className="mt-4">
                    <IssuesManager 
                        projectId={project.id} 
                        lookups={lookups}
                    />
                </div>
            </div>
        </>
    );
}

ProjectShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: projectsIndex(),
        },
        {
            title: 'Project Board',
            href: '',
        }
    ],
};
