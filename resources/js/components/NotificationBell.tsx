import React, { useState } from 'react';
import {
    Bell,
    MessageSquare,
    ClipboardList,
    CheckCircle2,
    RefreshCw,
    AlertTriangle,
    Clock,
    AtSign,
    BellRing,
} from 'lucide-react';
import { getWorkType } from '@/modules/Workload/shared/workTypes';
import { useNotifications, Notification } from '@/hooks/useNotifications';
import { router, usePage } from '@inertiajs/react';
import { useTranslate } from '@/hooks/useTranslate';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDistanceToNow } from 'date-fns';

export const NotificationBell = () => {
    const { auth } = usePage().props as any;
    const { notifications, unreadCount, markAllRead, isLoading } =
        useNotifications(auth?.user?.id);
    const [open, setOpen] = useState(false);
    const { t } = useTranslate();

    const handleOpenChange = (newOpen: boolean) => {
        setOpen(newOpen);
        if (newOpen && unreadCount > 0) {
            markAllRead();
        }
    };

    const getIcon = (type: string) => {
        if (type.startsWith('reminder.'))
            return <BellRing className="h-4 w-4 text-amber-500" />;
        if (type === 'issue.overdue')
            return <AlertTriangle className="text-destructive h-4 w-4" />;
        if (type === 'issue.due_soon')
            return <Clock className="h-4 w-4 text-amber-500" />;
        if (type === 'issue.assigned' || type === 'issue_assigned')
            return <ClipboardList className="text-primary h-4 w-4" />;
        if (type === 'comment.mentioned')
            return <AtSign className="text-primary h-4 w-4" />;
        if (type === 'comment.added')
            return <MessageSquare className="text-primary h-4 w-4" />;
        if (type === 'issue.transitioned')
            return <RefreshCw className="text-primary h-4 w-4" />;
        if (type === 'sprint.state_changed')
            return <CheckCircle2 className="text-primary h-4 w-4" />;
        return <Bell className="text-muted-foreground h-4 w-4" />;
    };

    const getMessage = (notification: Notification) => {
        const { type, data } = notification;
        const typeLabel = t(getWorkType(data?.work_type).label);
        if (type === 'reminder.work_type')
            return t('Reminder (:type): you have :count open tasks', {
                type: typeLabel,
                count: data?.count ?? 0,
            });
        if (type === 'reminder.deadline')
            return Number(data?.days_left) === 0
                ? t('Reminder (:type): [:number] :title is due today', {
                      type: typeLabel,
                      number: data?.issue_number,
                      title: data?.title ?? '',
                  })
                : t('Reminder (:type): [:number] :title is due in :days days', {
                      type: typeLabel,
                      number: data?.issue_number,
                      title: data?.title ?? '',
                      days: data?.days_left ?? '',
                  });
        if (type === 'reminder.start')
            return t('Reminder (:type): [:number] :title starts today', {
                type: typeLabel,
                number: data?.issue_number,
                title: data?.title ?? '',
            });
        if (type === 'reminder.project_deadline')
            return Number(data?.days_left) === 0
                ? t('Project :name ends today', {
                      name: data?.project_name ?? '',
                  })
                : t('Project :name ends in :days days', {
                      name: data?.project_name ?? '',
                      days: data?.days_left ?? '',
                  });
        if (type === 'issue.overdue')
            return `Overdue: [${data?.issue_number}] ${data?.title || ''} (Due: ${data?.due_date})`;
        if (type === 'issue.due_soon')
            return `Due soon: [${data?.issue_number}] ${data?.title || ''} (Due: ${data?.due_date})`;
        if (type === 'issue.assigned' || type === 'issue_assigned')
            return t('You were assigned to issue :number', {
                number: data?.issue_number,
            });
        if (type === 'comment.mentioned')
            return `${data?.author_name || t('Someone')} ${t('mentioned you')}: ${data?.body_preview || ''}`;
        if (type === 'comment.added')
            return t('New comment: :preview', { preview: data?.body_preview });
        if (type === 'issue.transitioned') return t('Issue status changed');
        if (type === 'sprint.state_changed')
            return t('Sprint :name is now :state', {
                name: data?.sprint_name,
                state: data?.new_state,
            });
        return t('New notification');
    };

    // Notifikasi yang terkait project/tugas bisa diklik untuk langsung membukanya.
    const openTarget = (notification: Notification) => {
        const projectId = notification.data?.project_id;
        if (!projectId) return;
        const issueId = notification.data?.issue_id;
        setOpen(false);
        router.visit(
            issueId
                ? `/projects/${projectId}?issue=${issueId}`
                : `/projects/${projectId}`,
        );
    };

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="h-5 w-5" />
                    {unreadCount > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full p-0 text-xs"
                        >
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80 p-0" align="end">
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <span className="text-sm font-semibold">
                        {t('Notifications')}
                    </span>
                    {unreadCount > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-auto px-2 py-1 text-xs"
                            onClick={() => markAllRead()}
                        >
                            {t('Mark all as read')}
                        </Button>
                    )}
                </div>
                <div className="max-h-[300px] overflow-y-auto">
                    {isLoading ? (
                        <div className="space-y-4 p-4">
                            <div className="flex gap-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <div className="flex-1 space-y-2">
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-3 w-20" />
                                </div>
                            </div>
                            <div className="flex gap-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <div className="flex-1 space-y-2">
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-3 w-20" />
                                </div>
                            </div>
                        </div>
                    ) : notifications.length === 0 ? (
                        <div className="text-muted-foreground p-8 text-center text-sm">
                            {t('No notifications yet')}
                        </div>
                    ) : (
                        <div className="divide-y">
                            {notifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    role={
                                        notification.data?.project_id
                                            ? 'button'
                                            : undefined
                                    }
                                    tabIndex={
                                        notification.data?.project_id
                                            ? 0
                                            : undefined
                                    }
                                    onClick={() => openTarget(notification)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter')
                                            openTarget(notification);
                                    }}
                                    className={`hover:bg-muted/50 flex gap-3 p-4 transition-colors ${!notification.read_at ? 'bg-muted/20' : ''} ${notification.data?.project_id ? 'cursor-pointer' : ''}`}
                                >
                                    <div className="mt-0.5">
                                        {getIcon(notification.type)}
                                    </div>
                                    <div className="flex-1 space-y-1">
                                        <p className="text-sm leading-tight">
                                            {getMessage(notification)}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {formatDistanceToNow(
                                                new Date(
                                                    notification.created_at,
                                                ),
                                                { addSuffix: true },
                                            )}
                                        </p>
                                    </div>
                                    {!notification.read_at && (
                                        <div className="bg-primary mt-1.5 h-2 w-2 shrink-0 rounded-full" />
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </PopoverContent>
        </Popover>
    );
};
