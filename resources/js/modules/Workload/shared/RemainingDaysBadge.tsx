import {
    AlertTriangle,
    CalendarClock,
    CheckCircle2,
    Clock,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useTranslate } from '@/hooks/useTranslate';

/**
 * Sisa waktu menuju tanggal selesai. Status selalu memakai ikon + teks,
 * tidak hanya warna.
 */
export function RemainingDaysBadge({
    days,
    done = false,
    className,
}: {
    days: number | null | undefined;
    done?: boolean;
    className?: string;
}) {
    const { t } = useTranslate();
    const base =
        'inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-medium whitespace-nowrap';

    if (done) {
        return (
            <span
                className={cn(
                    base,
                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                    className,
                )}
            >
                <CheckCircle2 className="h-3 w-3" aria-hidden />
                {t('Done')}
            </span>
        );
    }

    if (days === null || days === undefined) {
        return (
            <span
                className={cn(
                    base,
                    'text-muted-foreground border-dashed',
                    className,
                )}
            >
                <CalendarClock className="h-3 w-3" aria-hidden />
                {t('No due date')}
            </span>
        );
    }

    if (days < 0) {
        return (
            <span
                className={cn(
                    base,
                    'border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-300',
                    className,
                )}
            >
                <AlertTriangle className="h-3 w-3" aria-hidden />
                {t(':days days overdue', { days: Math.abs(days) })}
            </span>
        );
    }

    const urgent = days <= 3;
    return (
        <span
            className={cn(
                base,
                urgent
                    ? 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                    : 'text-muted-foreground',
                className,
            )}
        >
            <Clock className="h-3 w-3" aria-hidden />
            {days === 0 ? t('Due today') : t(':days days left', { days })}
        </span>
    );
}
