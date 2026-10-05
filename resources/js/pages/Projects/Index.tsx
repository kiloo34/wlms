import { Head, usePage, router } from '@inertiajs/react';
import { ProjectsManager } from '@/modules/Workload/Projects/components/ProjectsManager';
import { useGetWorkspaces } from '@/modules/Workload/Workspaces/hooks/useWorkspaces';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { index as projectsIndex } from '@/routes/projects';
import { index as workspacesIndex } from '@/routes/workspaces';
import { Combobox } from '@/components/ui/combobox';
import { WorkspaceMembersDialog } from '@/modules/Workload/Workspaces/components/WorkspaceMembersDialog';
import { Users, CheckSquare } from 'lucide-react';
import { useState } from 'react';

export default function ProjectsIndex() {
    const { url, props } = usePage<any>();
    const urlParams = new URLSearchParams(url.split('?')[1] || '');
    const workspaceId = urlParams.get('workspace_id');
    
    const { data: workspaces = [], isLoading } = useGetWorkspaces();
    const { auth, lookups } = props;
    const priorities = lookups?.priorities || [];
    const hasManagePermission = auth.user.permissions.includes('workspaces:manage') || auth.user.permissions.includes('*');
    const [membersOpen, setMembersOpen] = useState(false);
    
    const activeWorkspace = workspaces.find((w: any) => w.id === workspaceId);

    const handleWorkspaceChange = (value: string) => {
        router.visit(projectsIndex({ query: { workspace_id: value } }).url, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Projects" />
            
            <div className="flex h-full w-full flex-col gap-6 p-6">
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
                        <p className="text-muted-foreground mt-1">
                            Manage projects within your workspace.
                        </p>
                    </div>
                    
                    <div className="w-full sm:w-auto flex items-center gap-3"><span className="text-sm font-medium text-muted-foreground whitespace-nowrap">Active Workspace:</span>
                        <Combobox
                            options={workspaces.map(ws => ({ value: ws.id, label: ws.name }))}
                            value={workspaceId || ''}
                            onChange={handleWorkspaceChange}
                            disabled={isLoading}
                            placeholder="Select Workspace..."
                        />
                        {workspaceId && activeWorkspace && hasManagePermission && (
                            <>
                                <Button variant="outline" className="h-9 px-3 shrink-0 flex items-center gap-2" onClick={() => setMembersOpen(true)} title="Manage Workspace Members">
                                    <span className="sr-only sm:not-sr-only sm:text-xs">Members</span>
                                    <Users className="h-4 w-4" />
                                </Button>
                                
                                <Button variant="outline" className="h-9 px-3 shrink-0 flex items-center gap-2" onClick={() => router.visit(`/workspaces/${activeWorkspace.id}/issues`)} title="All Tasks in Workspace">
                                    <span className="sr-only sm:not-sr-only sm:text-xs">All Tasks</span>
                                    <CheckSquare className="h-4 w-4" />
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
                            <CardTitle>No Workspace Selected</CardTitle>
                            <CardDescription>
                                Please select a workspace from the dropdown above to view its projects.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button onClick={() => router.visit(workspacesIndex().url)} variant="outline">
                                Go to Workspaces Directory
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="mt-4">
                        <ProjectsManager workspaceId={workspaceId} priorities={priorities} />
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
