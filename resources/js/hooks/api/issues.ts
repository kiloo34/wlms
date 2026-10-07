import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from '@/lib/axios';
import { toast } from 'sonner';

export function useMyWorkspaces() {
    return useQuery({
        queryKey: ['workspaces'],
        queryFn: async () => {
            const { data } = await axios.get('/api/workspaces');
            return data.data || data; // handle typical laravel response
        },
    });
}

export function useWorkspaceProjects(workspaceId: string | number | null) {
    return useQuery({
        queryKey: ['workspace', workspaceId, 'projects'],
        queryFn: async () => {
            const { data } = await axios.get(`/api/workspaces/${workspaceId}/projects`);
            return data.data || data;
        },
        enabled: !!workspaceId,
    });
}

export function useProjectLookups(projectId: string | number | null) {
    return useQuery({
        queryKey: ['project', projectId, 'lookups'],
        queryFn: async () => {
            const { data } = await axios.get(`/api/projects/${projectId}/lookups`);
            return data; // Maybe it returns multiple keys
        },
        enabled: !!projectId,
    });
}

export function useCreateIssue() {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: async (payload: any) => {
            const { data } = await axios.post('/api/issues', payload);
            return data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['issues'] });
            toast.success('Issue created successfully');
        }
    });
}
