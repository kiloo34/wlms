import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { IssuesManager } from '@/modules/Workload/Issues/components/IssuesManager';
import { Project } from '@/modules/Workload/Projects/types';
import { Button } from '@/components/ui/button';
import { index as projectsIndex } from '@/routes/projects';
import { PriorityBadge } from '@/components/PriorityBadge';
import { useTranslate } from "@/hooks/useTranslate";

interface LookupItem {
    id: string;
    name: string;
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

export default function ProjectShow({ project, lookups }: ProjectShowProps) {
    const { t } = useTranslate();
    const handleBack = () => {
        router.visit(projectsIndex({ query: { workspace_id: project.workspace_id } }).url);
    };

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
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{project.name}</h1>
                        <div className="flex items-center gap-3 mt-1">
                            <p className="text-muted-foreground">
                                Key: {project.key}
                            </p>
                            {project.priority_id && lookups.priorities && (
                                <PriorityBadge 
                                    name={lookups.priorities.find(p => p.id === project.priority_id)?.name || 'Unknown'} 
                                    category={undefined} 
                                />
                            )}
                        </div>
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
