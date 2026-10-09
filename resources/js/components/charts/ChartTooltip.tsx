import React from 'react';
import { useIconSize, type IconSize } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export interface ChartTooltipItem {
    label: string;
    value: string | number;
    color?: string;
    badge?: string;
}

export interface ChartTooltipProps {
    active?: boolean;
    x?: number;
    y?: number;
    title?: React.ReactNode;
    items?: ChartTooltipItem[];
    customContent?: React.ReactNode;
    containerWidth?: number;
    containerHeight?: number;
    className?: string;
    offset?: { x?: number; y?: number };
}

export function ChartTooltip({
    active = false,
    x = 0,
    y = 0,
    title,
    items = [],
    customContent,
    containerWidth = 600,
    containerHeight = 300,
    className,
    offset = { x: 12, y: 12 },
}: ChartTooltipProps) {
    const { iconSize } = useIconSize();

    if (!active) {
        return null;
    }

    const estimatedTooltipWidth = 180;
    const estimatedTooltipHeight = 32 + (items.length * 20);

    const offsetX = offset.x ?? 12;
    const offsetY = offset.y ?? 12;

    let left = x + offsetX;
    let top = y + offsetY;

    // Boundary clamping: if too close to the right edge, flip to left of cursor
    if (containerWidth && left + estimatedTooltipWidth > containerWidth) {
        left = Math.max(8, x - estimatedTooltipWidth - offsetX);
    }

    // Boundary clamping: if too close to bottom edge, flip upward
    if (containerHeight && top + estimatedTooltipHeight > containerHeight) {
        top = Math.max(8, y - estimatedTooltipHeight - offsetY);
    }

    const densityStyles: Record<
        IconSize,
        { container: string; title: string; item: string; dot: string }
    > = {
        sm: {
            container: 'p-2 text-[11px] gap-1 rounded-md min-w-[120px]',
            title: 'text-[11px] font-semibold pb-1 mb-1',
            item: 'text-[10px] gap-1.5',
            dot: 'size-1.5',
        },
        md: {
            container: 'p-2.5 text-xs gap-1.5 rounded-lg min-w-[140px]',
            title: 'text-xs font-semibold pb-1.5 mb-1.5',
            item: 'text-[11px] gap-2',
            dot: 'size-2',
        },
        lg: {
            container: 'p-3 text-sm gap-2 rounded-xl min-w-[170px]',
            title: 'text-sm font-semibold pb-2 mb-2',
            item: 'text-xs gap-2.5',
            dot: 'size-2.5',
        },
    };

    const currentDensity = densityStyles[iconSize];

    return (
        <div
            className={cn(
                'pointer-events-none absolute z-50 transition-transform duration-75 ease-out',
                'bg-popover text-popover-foreground border border-border/80 shadow-lg backdrop-blur-xs',
                'flex flex-col',
                currentDensity.container,
                className
            )}
            style={{
                left: `${left}px`,
                top: `${top}px`,
            }}
            data-slot="chart-tooltip"
            role="tooltip"
        >
            {customContent ? (
                customContent
            ) : (
                <>
                    {title && (
                        <div
                            className={cn(
                                'border-b border-border/60 text-foreground truncate',
                                currentDensity.title
                            )}
                        >
                            {title}
                        </div>
                    )}
                    {items && items.length > 0 && (
                        <div className="flex flex-col gap-1">
                            {items.map((item, index) => (
                                <div
                                    key={`${item.label}-${index}`}
                                    className={cn(
                                        'flex items-center justify-between',
                                        currentDensity.item
                                    )}
                                >
                                    <div className="flex items-center gap-1.5 truncate">
                                        {item.color && (
                                            <span
                                                className={cn(
                                                    'rounded-full shrink-0',
                                                    currentDensity.dot
                                                )}
                                                style={{ backgroundColor: item.color }}
                                            />
                                        )}
                                        <span className="text-muted-foreground truncate">
                                            {item.label}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1 font-medium tabular-nums ml-2 shrink-0">
                                        <span>{item.value}</span>
                                        {item.badge && (
                                            <span className="text-[10px] bg-muted px-1 rounded text-muted-foreground">
                                                {item.badge}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

export default ChartTooltip;
