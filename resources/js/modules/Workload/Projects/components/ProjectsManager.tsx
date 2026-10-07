import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Project, CreateProjectPayload, UpdateProjectPayload } from '../types';
import {
    useGetProjects,
    useCreateProject,
    useUpdateProject,
    useDeleteProject,
} from '../hooks/useProjects';
import { ProjectList } from './ProjectList';
import { ProjectFormDialog } from './ProjectFormDialog';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useTranslate } from "@/hooks/useTranslate";

interface ProjectsManagerProps {
    workspaceId: string;
    priorities?: { id: string; name: string; category: string }[];
}

export function ProjectsManager({ workspaceId, priorities = [] }: ProjectsManagerProps) {
    const { t } = useTranslate();
    const { props } = usePage<any>();
    const permissions = props.auth?.user?.permissions || [];
    const canManageProjects = permissions.includes('projects:manage') || permissions.includes('*');

    const { data: projects = [], isLoading, isError } = useGetProjects(workspaceId);
    
    const createProject = useCreateProject();
    const updateProject = useUpdateProject();
    const deleteProject = useDeleteProject();

    const [formOpen, setFormOpen] = useState(false);
    const [selectedProject, setSelectedProject] = useState<Project | null>(null);

    const [deleteAlertOpen, setDeleteAlertOpen] = useState(false);
    const [projectToDelete, setProjectToDelete] = useState<Project | null>(null);

    const handleCreateClick = () => {
        setSelectedProject(null);
        setFormOpen(true);
    };

    const handleEditClick = (project: Project) => {
        setSelectedProject(project);
        setFormOpen(true);
    };

    const handleDeleteClick = (project: Project) => {
        setProjectToDelete(project);
        setDeleteAlertOpen(true);
    };

    const handleFormSubmit = (payload: CreateProjectPayload | UpdateProjectPayload) => {
        if (selectedProject) {
            updateProject.mutate(
                { id: selectedProject.id, workspaceId, payload: payload as UpdateProjectPayload },
                {
                    onSuccess: () => {
                        toast.success(t('Project updated successfully.'));
                        setFormOpen(false);
                    },
                    onError: () => {
                        toast.error(t('Failed to update project.'));
                    },
                }
            );
        } else {
            createProject.mutate(payload as CreateProjectPayload, {
                onSuccess: () => {
                    toast.success(t('Project created successfully.'));
                    setFormOpen(false);
                },
                onError: () => {
                    toast.error(t('Failed to create project.'));
                },
            });
        }
    };

    const handleConfirmDelete = () => {
        if (!projectToDelete) return;
        
        deleteProject.mutate(
            { id: projectToDelete.id, workspaceId },
            {
                onSuccess: () => {
                    toast.success(t('Project deleted successfully.'));
                    setDeleteAlertOpen(false);
                    setProjectToDelete(null);
                },
                onError: () => {
                    toast.error(t('Failed to delete project.'));
                },
            }
        );
    };

    if (isLoading) {
        return <div className="p-4 text-sm text-muted-foreground">{t('Loading projects...')}</div>;
    }

    if (isError) {
        return <div className="p-4 text-sm text-destructive">{t('Failed to load projects.')}</div>;
    }

    return (
        <div className="space-y-4">
            <ProjectList
                projects={projects}
                canManageProjects={canManageProjects}
                priorities={priorities}
                onCreate={handleCreateClick}
                onEdit={handleEditClick}
                onDelete={handleDeleteClick}
            />

            <ProjectFormDialog
                open={formOpen}
                onOpenChange={setFormOpen}
                project={selectedProject}
                workspaceId={workspaceId}
                priorities={priorities}
                onSubmit={handleFormSubmit}
                isPending={createProject.isPending || updateProject.isPending}
            />

            <AlertDialog open={deleteAlertOpen} onOpenChange={setDeleteAlertOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{t('Are you absolutely sure?')}</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the project "{projectToDelete?.name}" and remove its data from our servers.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={deleteProject.isPending}>{t('Cancel')}</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleConfirmDelete}
                            disabled={deleteProject.isPending}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            {deleteProject.isPending ? 'Deleting...' : 'Delete'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
