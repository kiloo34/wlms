import { Repeat } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useTranslate } from '@/hooks/useTranslate';
import { getWorkType } from './workTypes';

export function WorkTypeBadge({
    value,
    className,
}: {
    value?: string | null;
    className?: string;
}) {
    const { t } = useTranslate();
    const type = getWorkType(value);

    return (
        <span
            title={t(type.hint)}
            className={cn(
                'inline-flex shrink-0 items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-medium whitespace-nowrap',
                type.className,
                className,
            )}
        >
            <Repeat className="h-3 w-3" aria-hidden />
            {t(type.label)}
        </span>
    );
}
