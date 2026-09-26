import React from 'react';
import { Progress } from '@/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SprintWorkloadMember } from '../hooks/useSprints';
import { cn } from '@/lib/utils';

interface SprintWorkloadWidgetProps {
    workload: SprintWorkloadMember[];
    isLoading?: boolean;
}

export const SprintWorkloadWidget: React.FC<SprintWorkloadWidgetProps> = ({ workload, isLoading }) => {
    if (isLoading) {
        return (
            <div className="space-y-4">
                <div className="h-4 bg-muted animate-pulse rounded w-1/3"></div>
                <div className="h-8 bg-muted animate-pulse rounded"></div>
            </div>
        );
    }

    if (!workload || workload.length === 0) {
        return null;
    }

    return (
        <div className="space-y-4 py-2">
            <h4 className="text-sm font-medium">Sprint Capacity</h4>
            <div className="space-y-3">
                {workload.map((member) => {
                    const capacityHours = Math.round(member.capacity_seconds / 3600 * 10) / 10;
                    const allocatedHours = Math.round(member.allocated_seconds / 3600 * 10) / 10;
                    
                    let percentage = 0;
                    if (member.capacity_seconds > 0) {
                        percentage = (member.allocated_seconds / member.capacity_seconds) * 100;
                    } else if (member.allocated_seconds > 0) {
                        percentage = 100; // Overloaded
                    }

                    let progressColorClass = "bg-emerald-500";
                    let textColorClass = "text-emerald-600 dark:text-emerald-400 font-medium";
                    
                    if (percentage >= 90) {
                        progressColorClass = "bg-destructive";
                        textColorClass = "text-destructive font-medium";
                    } else if (percentage >= 75) {
                        progressColorClass = "bg-amber-500";
                        textColorClass = "text-amber-600 dark:text-amber-400 font-medium";
                    } else if (percentage === 0) {
                        progressColorClass = "";
                        textColorClass = "text-muted-foreground";
                    }

                    return (
                        <div key={member.user_id} className="space-y-1">
                            <div className="flex items-center justify-between text-xs">
                                <div className="flex items-center space-x-2">
                                    <Avatar className="h-5 w-5">
                                        <AvatarImage src={member.avatar || undefined} />
                                        <AvatarFallback className="text-[10px]">{member.name.split(' ').map((n: string) => n[0]).join('').substring(0, 2).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                    <span className="font-medium">{member.name}</span>
                                </div>
                                <span className={cn("text-muted-foreground", textColorClass)}>
                                    {allocatedHours}h / {capacityHours}h
                                </span>
                            </div>
                            <Progress 
                                value={Math.min(percentage, 100)} 
                                className="h-1.5"
                                indicatorColor={progressColorClass}
                            />
                        </div>
                    );
                })}
            </div>
        </div>
    );
};
