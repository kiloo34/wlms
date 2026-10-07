import React from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from '@/lib/axios';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Activity } from 'lucide-react';
import { format } from 'date-fns';
import { useTranslate } from "@/hooks/useTranslate";

interface ProjectActivitySheetProps {
    projectId: string;
    isOpen: boolean;
    onClose: () => void;
}

interface ActivityItem {
    id: string;
    issue_id: string;
    issue_title: string;
    actor?: {
        id: string;
        name: string;
    };
    field_changed: string;
    old_value?: string;
    new_value?: string;
    created_at: string;
}

export const ProjectActivitySheet: React.FC<ProjectActivitySheetProps> = ({ projectId, isOpen, onClose }) => {
    const { t } = useTranslate();
    const { data: activities = [], isLoading } = useQuery<ActivityItem[]>({
        queryKey: ['projects', projectId, 'activity'],
        queryFn: async () => {
            const { data } = await axios.get(`/api/projects/${projectId}/activity`);
            return data.data || data;
        },
        enabled: isOpen && !!projectId,
    });

    return (
        <Sheet open={isOpen} onOpenChange={onClose}>
            <SheetContent className="w-full sm:max-w-md overflow-y-auto">
                <SheetHeader className="mb-6">
                    <SheetTitle className="flex items-center gap-2">
                        <Activity className="h-5 w-5" />
                        {t('Activity History')}
                                            </SheetTitle>
                    <SheetDescription>
                        {t('Recent activities in this project.')}
                                            </SheetDescription>
                </SheetHeader>

                <div className="space-y-4 p-6">
                    {isLoading ? (
                        <div className="text-sm text-muted-foreground text-center py-4">{t('Loading activities...')}</div>
                    ) : activities.length === 0 ? (
                        <div className="text-sm text-muted-foreground text-center py-4">{t('No activities found.')}</div>
                    ) : (
                        activities.map((activity) => (
                            <div key={activity.id} className="flex flex-col space-y-1 p-3 bg-muted/40 rounded-lg border">
                                <p className="text-sm">
                                    <span className="font-medium">{activity.actor?.name || 'System'}</span>{' '}
                                    {activity.field_changed === 'status_id' ? 'mengubah status' : activity.field_changed === 'assignee_id' ? 'mengubah penugasan' : 'memperbarui'} 
                                    <span className="font-medium ml-1">"{activity.issue_title}"</span>
                                </p>
                                <span className="text-xs text-muted-foreground">
                                    {format(new Date(activity.created_at), 'MMM d, yyyy HH:mm')}
                                </span>
                            </div>
                        ))
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
};
