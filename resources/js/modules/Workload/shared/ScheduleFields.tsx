import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslate } from '@/hooks/useTranslate';
import {
    durationBetween,
    endDateFromDuration,
    formatDisplayDate,
    remainingDays,
} from './schedule';
import { RemainingDaysBadge } from './RemainingDaysBadge';

interface ScheduleFieldsProps {
    idPrefix: string;
    startDate: string;
    endDate: string;
    onChange: (next: { startDate: string; endDate: string }) => void;
    endLabel?: string;
    disabled?: boolean;
}

/**
 * Tanggal mulai + durasi (hari) <-> tanggal selesai. Mengubah salah satu
 * menghitung ulang yang lain, sehingga tanggal selesai selalu jelas.
 */
export function ScheduleFields({
    idPrefix,
    startDate,
    endDate,
    onChange,
    endLabel,
    disabled,
}: ScheduleFieldsProps) {
    const { t, locale } = useTranslate();
    const duration = durationBetween(startDate, endDate);

    const handleStart = (value: string) => {
        // Pertahankan durasi saat tanggal mulai digeser.
        const nextEnd =
            value && duration ? endDateFromDuration(value, duration) : endDate;
        onChange({ startDate: value, endDate: nextEnd ?? '' });
    };

    const handleDuration = (value: string) => {
        const days = Number(value);
        if (!value || !Number.isFinite(days) || days < 1) {
            return;
        }
        const nextEnd = endDateFromDuration(startDate, Math.floor(days));
        if (nextEnd) onChange({ startDate, endDate: nextEnd });
    };

    return (
        <div className="space-y-2">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div className="space-y-2">
                    <Label htmlFor={`${idPrefix}_start_date`}>
                        {t('Start Date')}
                    </Label>
                    <Input
                        id={`${idPrefix}_start_date`}
                        type="date"
                        value={startDate}
                        onChange={(e) => handleStart(e.target.value)}
                        disabled={disabled}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`${idPrefix}_duration`}>
                        {t('Duration (days)')}
                    </Label>
                    <Input
                        id={`${idPrefix}_duration`}
                        type="number"
                        min={1}
                        step={1}
                        inputMode="numeric"
                        placeholder={startDate ? '5' : t('Set start date')}
                        value={duration ?? ''}
                        onChange={(e) => handleDuration(e.target.value)}
                        disabled={disabled || !startDate}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`${idPrefix}_end_date`}>
                        {endLabel ?? t('End Date')}
                    </Label>
                    <Input
                        id={`${idPrefix}_end_date`}
                        type="date"
                        min={startDate || undefined}
                        value={endDate}
                        onChange={(e) =>
                            onChange({ startDate, endDate: e.target.value })
                        }
                        disabled={disabled}
                    />
                </div>
            </div>
            {endDate && (
                <div className="text-muted-foreground flex flex-wrap items-center gap-2 text-xs">
                    <span>
                        {t('Finishes on :date', {
                            date: formatDisplayDate(endDate, locale),
                        })}
                    </span>
                    <RemainingDaysBadge days={remainingDays(endDate)} />
                </div>
            )}
        </div>
    );
}
