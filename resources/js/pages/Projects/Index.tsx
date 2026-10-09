import { Head, usePage, router } from '@inertiajs/react';
import { ProjectsManager } from '@/modules/Workload/Projects/components/ProjectsManager';
import { useGetWorkspaces } from '@/modules/Workload/Workspaces/hooks/useWorkspaces';
import { useGetProjects } from '@/modules/Workload/Projects/hooks/useProjects';
import { WorkspaceTasksTable } from '@/modules/Workload/Workspaces/components/WorkspaceTasksTable';
import { WorkspaceAnalyticsView } from '@/modules/Workload/Workspaces/components/WorkspaceAnalyticsView';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { index as projectsIndex } from '@/routes/projects';
import { index as workspacesIndex } from '@/routes/workspaces';
import { Combobox } from '@/components/ui/combobox';
import { WorkspaceMembersDialog } from '@/modules/Workload/Workspaces/components/WorkspaceMembersDialog';
import { Users, FolderKanban, CheckSquare, BarChart2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslate } from "@/hooks/useTranslate";

export default function ProjectsIndex() {
    const { t } = useTranslate();
    const { url, props } = usePage<any>();
    const urlParams = new URLSearchParams(url.split('?')[1] || '');
    const workspaceId = urlParams.get('workspace_id');
    const tabParam = urlParams.get('tab') as 'overview' | 'tasks' | 'analytics' | null;
    
    const [activeTab, setActiveTab] = useState<'overview' | 'tasks' | 'analytics'>(
        tabParam === 'tasks' ? 'tasks' : tabParam === 'analytics' ? 'analytics' : 'overview'
    );
    
    const { data: workspaces = [], isLoading } = useGetWorkspaces();
    const { data: projects = [] } = useGetProjects(workspaceId || undefined);
    
    const { auth, lookups } = props;
    const priorities = lookups?.priorities || [];
    const hasManagePermission = auth.user.permissions.includes('workspaces:manage') || auth.user.permissions.includes('*');
    const [membersOpen, setMembersOpen] = useState(false);
    
    const activeWorkspace = workspaces.find((w: any) => w.id === workspaceId);

    const handleWorkspaceChange = (value: string) => {
        router.visit(projectsIndex({ query: { workspace_id: value, tab: activeTab } }).url, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleTabChange = (tab: 'overview' | 'tasks' | 'analytics') => {
        setActiveTab(tab);
        if (workspaceId) {
            router.visit(projectsIndex({ query: { workspace_id: workspaceId, tab } }).url, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title={t('Projects')} />
            
            <div className="flex h-full w-full flex-col gap-6 p-6">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Projects')}</h1>
                        <p className="text-muted-foreground mt-1">
                            {t('Manage projects and workflow within your workspace.')}
                        </p>
                    </div>
                    
                    <div className="w-full sm:w-auto flex items-center gap-3">
                        <span className="text-sm font-medium text-muted-foreground whitespace-nowrap">Active Workspace:</span>
                        <Combobox
                            options={workspaces.map(ws => ({ value: ws.id, label: ws.name }))}
                            value={workspaceId || ''}
                            onChange={handleWorkspaceChange}
                            disabled={isLoading}
                            placeholder={t('Select Workspace...')}
                        />
                        {workspaceId && activeWorkspace && hasManagePermission && (
                            <>
                                <Button variant="outline" className="h-9 px-3 shrink-0 flex items-center gap-2" onClick={() => setMembersOpen(true)} title={t('Manage Workspace Members')}>
                                    <span className="sr-only sm:not-sr-only sm:text-xs">{t('Members')}</span>
                                    <Users className="h-4 w-4" />
                                </Button>
                                <WorkspaceMembersDialog
                                    workspaceId={activeWorkspace.id}
                                    workspaceName={activeWorkspace.name}
                                    open={membersOpen}
                                    onOpenChange={setMembersOpen}
                                />
                            </>
                        )}
                    </div>
                </div>

                {!workspaceId ? (
                    <Card className="mt-4">
                        <CardHeader>
                            <CardTitle>{t('No Workspace Selected')}</CardTitle>
                            <CardDescription>
                                {t('Please select a workspace from the dropdown above to view its projects.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button onClick={() => router.visit(workspacesIndex().url)} variant="outline">
                                {t('Go to Workspaces Directory')}
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-6">
                        {/* Segmented Top View Tab Switcher */}
                        <div className="flex items-center gap-2 border-b pb-3">
                            <Button
                                variant={activeTab === 'overview' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-9 gap-2 text-xs font-medium"
                                onClick={() => handleTabChange('overview')}
                            >
                                <FolderKanban className="h-4 w-4" />
                                <span>{t('Overview Projects')}</span>
                                <span className="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-background/20 font-mono">
                                    {projects.length}
                                </span>
                            </Button>

                            <Button
                                variant={activeTab === 'tasks' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-9 gap-2 text-xs font-medium"
                                onClick={() => handleTabChange('tasks')}
                            >
                                <CheckSquare className="h-4 w-4" />
                                <span>{t('All Tasks')}</span>
                            </Button>

                            <Button
                                variant={activeTab === 'analytics' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-9 gap-2 text-xs font-medium"
                                onClick={() => handleTabChange('analytics')}
                            >
                                <BarChart2 className="h-4 w-4" />
                                <span>{t('Analytics')}</span>
                            </Button>
                        </div>

                        {/* Active Tab Content */}
                        {activeTab === 'overview' && (
                            <ProjectsManager workspaceId={workspaceId} priorities={priorities} />
                        )}

                        {activeTab === 'tasks' && (
                            <WorkspaceTasksTable
                                workspaceId={workspaceId}
                                projects={projects}
                                lookups={lookups}
                                currentUserId={auth?.user?.id}
                            />
                        )}

                        {activeTab === 'analytics' && (
                            <WorkspaceAnalyticsView workspaceId={workspaceId} />
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: projectsIndex(),
        },
    ],
};
