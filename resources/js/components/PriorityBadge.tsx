import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import { ArrowDown, ArrowRight, ArrowUp, LucideIcon } from "lucide-react";

interface PriorityBadgeProps {
    name: string;
    category?: string;
    className?: string;
}

export function PriorityBadge({ name, category, className }: PriorityBadgeProps) {
    let Icon: LucideIcon = ArrowRight;
    let colorClass = "bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700";

    const normalizedCategory = category?.toUpperCase() || name.toUpperCase();

    if (normalizedCategory.includes('HIGH') || normalizedCategory.includes('CRITICAL') || normalizedCategory.includes('HIGHEST')) {
        Icon = ArrowUp;
        colorClass = "bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50";
    } else if (normalizedCategory.includes('LOW') || normalizedCategory.includes('TRIVIAL') || normalizedCategory.includes('LOWEST')) {
        Icon = ArrowDown;
        colorClass = "bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50";
    } else if (normalizedCategory.includes('MEDIUM') || normalizedCategory.includes('NORMAL')) {
        Icon = ArrowRight;
        colorClass = "bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:hover:bg-yellow-900/50";
    }

    return (
        <Badge variant="outline" className={cn("flex items-center gap-1 font-medium border-0", colorClass, className)}>
            <Icon className="w-3 h-3" />
            <span>{name}</span>
        </Badge>
    );
}
