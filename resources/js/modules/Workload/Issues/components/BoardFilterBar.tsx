import React from 'react';
import { SlidersHorizontal, X } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { useTranslate } from "@/hooks/useTranslate";

export interface BoardFilterState {
    assigneeIds: string[];
    priorityIds: string[];
    issueTypeIds: string[];
}

interface LookupItem {
    id: string;
    name: string;
}

interface BoardFilterBarProps {
    users: LookupItem[];
    priorities: LookupItem[];
    issueTypes: LookupItem[];
    filters: BoardFilterState;
    onChange: (filters: BoardFilterState) => void;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
}

const AVATAR_COLORS = [
    'bg-red-500',
    'bg-orange-500',
    'bg-amber-500',
    'bg-green-500',
    'bg-teal-500',
    'bg-blue-500',
    'bg-violet-500',
    'bg-pink-500',
];

function getAvatarColor(id: string): string {
    let hash = 0;
    for (let i = 0; i < id.length; i++) {
        hash = id.charCodeAt(i) + ((hash << 5) - hash);
    }
    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

/**
 * Dumb component: renders the Board filter bar.
 * All state is owned by the parent (IssuesManager).
 */
export const BoardFilterBar: React.FC<BoardFilterBarProps> = ({
    users,
    priorities,
    issueTypes,
    filters,
    onChange,
}) => {
    const { t } = useTranslate();
    const activeCount =
        filters.priorityIds.length + filters.issueTypeIds.length;

    const toggleAssignee = (id: string) => {
        const next = filters.assigneeIds.includes(id)
            ? filters.assigneeIds.filter((a) => a !== id)
            : [...filters.assigneeIds, id];
        onChange({ ...filters, assigneeIds: next });
    };

    const togglePriority = (id: string) => {
        const next = filters.priorityIds.includes(id)
            ? filters.priorityIds.filter((p) => p !== id)
            : [...filters.priorityIds, id];
        onChange({ ...filters, priorityIds: next });
    };

    const toggleIssueType = (id: string) => {
        const next = filters.issueTypeIds.includes(id)
            ? filters.issueTypeIds.filter((t) => t !== id)
            : [...filters.issueTypeIds, id];
        onChange({ ...filters, issueTypeIds: next });
    };

    const clearAll = () => {
        onChange({ assigneeIds: [], priorityIds: [], issueTypeIds: [] });
    };

    const hasAnyFilter =
        filters.assigneeIds.length > 0 || activeCount > 0;

    return (
        <div className="flex items-center gap-2 flex-wrap">
            {/* Assignee avatar row */}
            <div className="flex items-center -space-x-1">
                {users.map((user) => {
                    const selected = filters.assigneeIds.includes(String(user.id));
                    return (
                        <button
                            key={user.id}
                            title={user.name}
                            onClick={() => toggleAssignee(String(user.id))}
                            className={cn(
                                'relative rounded-full transition-all focus:outline-none',
                                selected
                                    ? 'ring-2 ring-offset-1 ring-primary z-10 scale-110'
                                    : 'hover:z-10 hover:scale-105 opacity-70 hover:opacity-100'
                            )}
                        >
                            <Avatar className="h-8 w-8 border-2 border-background">
                                <AvatarFallback
                                    className={cn(
                                        'text-[11px] font-semibold text-white',
                                        getAvatarColor(String(user.id))
                                    )}
                                >
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </button>
                    );
                })}
            </div>

            {users.length > 0 && <Separator orientation="vertical" className="h-6" />}

            {/* Filter popover */}
            <Popover>
                <PopoverTrigger asChild>
                    <Button variant="outline" size="sm" className="gap-1.5 relative">
                        <SlidersHorizontal className="h-3.5 w-3.5" />
                        {t('Filter')}
                                                {activeCount > 0 && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1.5 -right-1.5 h-4 w-4 p-0 flex items-center justify-center text-[10px] rounded-full"
                            >
                                {activeCount}
                            </Badge>
                        )}
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-64 p-3 space-y-4">
                    {/* Priority filter */}
                    <div className="space-y-2">
                        <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            {t('Priority')}
                                                    </p>
                        <div className="flex flex-wrap gap-1.5">
                            {priorities.map((p) => {
                                const active = filters.priorityIds.includes(String(p.id));
                                return (
                                    <Badge
                                        key={p.id}
                                        variant={active ? 'default' : 'outline'}
                                        className="cursor-pointer select-none"
                                        onClick={() => togglePriority(String(p.id))}
                                    >
                                        {p.name}
                                    </Badge>
                                );
                            })}
                        </div>
                    </div>

                    <Separator />

                    {/* Issue type filter */}
                    <div className="space-y-2">
                        <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            {t('Type')}
                                                    </p>
                        <div className="flex flex-wrap gap-1.5">
                            {issueTypes.map((t) => {
                                const active = filters.issueTypeIds.includes(String(t.id));
                                return (
                                    <Badge
                                        key={t.id}
                                        variant={active ? 'default' : 'outline'}
                                        className="cursor-pointer select-none"
                                        onClick={() => toggleIssueType(String(t.id))}
                                    >
                                        {t.name}
                                    </Badge>
                                );
                            })}
                        </div>
                    </div>
                </PopoverContent>
            </Popover>

            {/* Clear all */}
            {hasAnyFilter && (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={clearAll}
                    className="gap-1 text-muted-foreground hover:text-foreground h-8 px-2"
                >
                    <X className="h-3.5 w-3.5" />
                    {t('Clear')}
                                    </Button>
            )}
        </div>
    );
};

// Jprime butuh data dari fact segment daily 
// fact segment daily butuh data dari LLOAN jtm dan jas 
// jika salah 1 LLOAN blm keload maka data yang kerluar tidak valid 
