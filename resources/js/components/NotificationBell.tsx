import React, { useState } from 'react';
import { Bell, MessageSquare, ClipboardList, CheckCircle2, RefreshCw } from 'lucide-react';
import { useNotifications, Notification } from '@/hooks/useNotifications';
import { usePage } from '@inertiajs/react';
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
    const { notifications, unreadCount, markAllRead, isLoading } = useNotifications(auth?.user?.id);
    const [open, setOpen] = useState(false);
    const { t } = useTranslate();

    const handleOpenChange = (newOpen: boolean) => {
        setOpen(newOpen);
        if (newOpen && unreadCount > 0) {
            markAllRead();
        }
    };

    const getIcon = (type: string) => {
        if (type === 'issue.assigned') return <ClipboardList className="h-4 w-4 text-primary" />;
        if (type === 'comment.added') return <MessageSquare className="h-4 w-4 text-primary" />;
        if (type === 'issue.transitioned') return <RefreshCw className="h-4 w-4 text-primary" />;
        if (type === 'sprint.state_changed') return <CheckCircle2 className="h-4 w-4 text-primary" />;
        return <Bell className="h-4 w-4 text-muted-foreground" />;
    };

    const getMessage = (notification: Notification) => {
        const { type, data } = notification;
        if (type === 'issue.assigned') return t('You were assigned to issue :number', { number: data?.issue_number });
        if (type === 'comment.added') return t('New comment: :preview', { preview: data?.body_preview });
        if (type === 'issue.transitioned') return t('Issue status changed');
        if (type === 'sprint.state_changed') return t('Sprint :name is now :state', { name: data?.sprint_name, state: data?.new_state });
        return t('New notification');
    };

    return (
        <Popover open={open} onOpenChange={handleOpenChange}>
            <PopoverTrigger asChild>
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="h-5 w-5" />
                    {unreadCount > 0 && (
                        <Badge 
                            variant="destructive" 
                            className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs rounded-full"
                        >
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80 p-0" align="end">
                <div className="flex items-center justify-between px-4 py-3 border-b">
                    <span className="font-semibold text-sm">{t('Notifications')}</span>
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
                        <div className="p-4 space-y-4">
                            <div className="flex gap-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <div className="space-y-2 flex-1">
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-3 w-20" />
                                </div>
                            </div>
                            <div className="flex gap-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <div className="space-y-2 flex-1">
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-3 w-20" />
                                </div>
                            </div>
                        </div>
                    ) : notifications.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">
                            {t('No notifications yet')}
                        </div>
                    ) : (
                        <div className="divide-y">
                            {notifications.map((notification) => (
                                <div 
                                    key={notification.id} 
                                    className={`p-4 flex gap-3 hover:bg-muted/50 transition-colors ${!notification.read_at ? 'bg-muted/20' : ''}`}
                                >
                                    <div className="mt-0.5">
                                        {getIcon(notification.type)}
                                    </div>
                                    <div className="flex-1 space-y-1">
                                        <p className="text-sm leading-tight">
                                            {getMessage(notification)}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                                        </p>
                                    </div>
                                    {!notification.read_at && (
                                        <div className="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0" />
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
