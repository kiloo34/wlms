import React from 'react';
import { format } from 'date-fns';
import { Worklog } from '../hooks/useIssues';

interface WorklogHistoryProps {
    worklogs: Worklog[];
    isLoading?: boolean;
}

const formatDuration = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0 && minutes > 0) return `${hours}h ${minutes}m`;
    if (hours > 0) return `${hours}h`;
    return `${minutes}m`;
};

export const WorklogHistory: React.FC<WorklogHistoryProps> = ({ worklogs, isLoading }) => {
    if (isLoading) {
        return <div className="text-center text-sm text-muted-foreground py-4">Loading worklogs...</div>;
    }

    if (!worklogs || worklogs.length === 0) {
        return <div className="text-center text-sm text-muted-foreground py-4">No worklogs found.</div>;
    }

    return (
        <div className="space-y-4 mt-4">
            {worklogs.map((worklog) => (
                <div key={worklog.id} className="flex gap-4 p-4 border rounded-lg bg-background">
                    <div className="flex-1 space-y-1">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium">{worklog.author?.name || 'Unknown User'}</p>
                            <span className="text-xs font-semibold px-2 py-1 bg-secondary rounded-md">
                                {formatDuration(worklog.time_spent_seconds)}
                            </span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {format(new Date(worklog.started_at), "MMM d, yyyy 'at' h:mm a")}
                        </p>
                        {worklog.description && (
                            <p className="text-sm mt-2 text-foreground whitespace-pre-wrap">
                                {worklog.description}
                            </p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
};
