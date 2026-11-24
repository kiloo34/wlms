import { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { BellRing, Building2, Loader2, RotateCcw, Save } from 'lucide-react';
import { toast } from 'sonner';
import axios from '@/lib/axios';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { useTranslate } from '@/hooks/useTranslate';
import { WorkTypeBadge } from '@/modules/Workload/shared/WorkTypeBadge';

interface DeadlineRule {
    enabled: boolean;
    days_before_due: number[];
    on_start_date: boolean;
}

interface ReminderConfig {
    timezone: string;
    time: string;
    daily: { enabled: boolean };
    weekly: { enabled: boolean; weekdays: number[] };
    monthly: {
        enabled: boolean;
        days_of_month: number[];
        first_monday: boolean;
    };
    annually: { enabled: boolean; month: number; day: number };
    project: DeadlineRule;
    incidental: DeadlineRule;
}

interface ReminderResponse {
    data: ReminderConfig;
    defaults: ReminderConfig;
    has_override: boolean;
    can_manage_defaults: boolean;
}

const QUERY_KEY = ['settings', 'reminders'];
const WEEKDAYS = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];
const MONTHS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

function parseNumberList(value: string, min: number, max: number): number[] {
    const numbers = value
        .split(/[\s,;]+/)
        .map((part) => Number(part))
        .filter((n) => Number.isInteger(n) && n >= min && n <= max);
    return Array.from(new Set(numbers)).sort((a, b) => a - b);
}

/** Input daftar angka ("1, 15") yang tetap bisa diketik bebas sebelum diparse. */
function NumberListInput({
    id,
    value,
    min,
    max,
    onChange,
    disabled,
    placeholder,
}: {
    id: string;
    value: number[];
    min: number;
    max: number;
    onChange: (value: number[]) => void;
    disabled?: boolean;
    placeholder?: string;
}) {
    const [text, setText] = useState(value.join(', '));
    useEffect(() => setText(value.join(', ')), [value]);

    return (
        <Input
            id={id}
            value={text}
            disabled={disabled}
            placeholder={placeholder}
            onChange={(e) => setText(e.target.value)}
            onBlur={() => onChange(parseNumberList(text, min, max))}
        />
    );
}

function RuleCard({
    type,
    title,
    description,
    enabled,
    onToggle,
    children,
}: {
    type: string;
    title: string;
    description: string;
    enabled: boolean;
    onToggle: (value: boolean) => void;
    children?: React.ReactNode;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardHeader className="flex flex-row items-start justify-between gap-4 p-4">
                <div className="space-y-1">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <WorkTypeBadge value={type} />
                        {title}
                    </CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                <Switch
                    checked={enabled}
                    onCheckedChange={onToggle}
                    aria-label={title}
                />
            </CardHeader>
            {children && (
                <CardContent
                    className={cn(
                        'border-t p-4',
                        !enabled && 'pointer-events-none opacity-50',
                    )}
                >
                    {children}
                </CardContent>
            )}
        </Card>
    );
}

export default function ReminderSettingsPage() {
    const { t } = useTranslate();
    const queryClient = useQueryClient();
    const [form, setForm] = useState<ReminderConfig | null>(null);

    const { data, isLoading } = useQuery<ReminderResponse>({
        queryKey: QUERY_KEY,
        queryFn: async () => (await axios.get('/api/settings/reminders')).data,
    });

    useEffect(() => {
        if (data) setForm(data.data);
    }, [data]);

    const onSaved = (message: string) => (response: ReminderResponse) => {
        queryClient.setQueryData(QUERY_KEY, response);
        toast.success(message);
    };

    const save = useMutation({
        mutationFn: async (payload: ReminderConfig) =>
            (await axios.put('/api/settings/reminders', payload)).data,
        onSuccess: onSaved(t('Your reminder schedule has been saved.')),
    });
    const reset = useMutation({
        mutationFn: async () =>
            (await axios.delete('/api/settings/reminders')).data,
        onSuccess: onSaved(t('Reminder schedule reset to default.')),
    });
    const saveDefault = useMutation({
        mutationFn: async (payload: ReminderConfig) =>
            (await axios.put('/api/settings/reminders/default', payload)).data,
        onSuccess: onSaved(t('Organization default has been saved.')),
    });

    const update = <K extends keyof ReminderConfig>(
        key: K,
        value: ReminderConfig[K],
    ) => setForm((prev) => (prev ? { ...prev, [key]: value } : prev));

    const busy = save.isPending || reset.isPending || saveDefault.isPending;

    return (
        <>
            <Head title={t('Reminder settings')} />

            <div className="space-y-6">
                <div>
                    <h2 className="flex items-center gap-2 text-xl font-semibold">
                        <BellRing className="h-5 w-5" aria-hidden />
                        {t('Work reminders')}
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        {t(
                            'Reminders are sent as notifications to the task owner based on each work type. Defaults are already set; adjust them to your needs.',
                        )}
                    </p>
                </div>

                {isLoading || !form || !data ? (
                    <div className="space-y-3">
                        <Skeleton className="h-24 w-full" />
                        <Skeleton className="h-24 w-full" />
                        <Skeleton className="h-24 w-full" />
                    </div>
                ) : (
                    <>
                        <Card className="gap-0 py-0">
                            <CardContent className="flex flex-wrap items-end gap-4 p-4">
                                <div className="space-y-2">
                                    <Label htmlFor="reminder_time">
                                        {t('Reminder time')}
                                    </Label>
                                    <Input
                                        id="reminder_time"
                                        type="time"
                                        className="w-32"
                                        value={form.time}
                                        onChange={(e) =>
                                            update('time', e.target.value)
                                        }
                                    />
                                </div>
                                <p className="text-muted-foreground pb-2 text-sm">
                                    {t('Time zone: :tz', {
                                        tz: form.timezone,
                                    })}
                                    {data.has_override
                                        ? ` · ${t('Using your personal schedule')}`
                                        : ` · ${t('Using the organization default')}`}
                                </p>
                            </CardContent>
                        </Card>

                        <RuleCard
                            type="daily"
                            title={t('Daily')}
                            description={t(
                                'Every day at the reminder time, listing your open daily tasks.',
                            )}
                            enabled={form.daily.enabled}
                            onToggle={(enabled) => update('daily', { enabled })}
                        />

                        <RuleCard
                            type="weekly"
                            title={t('Weekly')}
                            description={t(
                                'On the selected days of the week (default: Monday).',
                            )}
                            enabled={form.weekly.enabled}
                            onToggle={(enabled) =>
                                update('weekly', { ...form.weekly, enabled })
                            }
                        >
                            <div className="flex flex-wrap gap-1.5">
                                {WEEKDAYS.map((day) => {
                                    const active =
                                        form.weekly.weekdays.includes(
                                            day.value,
                                        );
                                    return (
                                        <Button
                                            key={day.value}
                                            type="button"
                                            size="sm"
                                            variant={
                                                active ? 'default' : 'outline'
                                            }
                                            aria-pressed={active}
                                            onClick={() =>
                                                update('weekly', {
                                                    ...form.weekly,
                                                    weekdays: active
                                                        ? form.weekly.weekdays.filter(
                                                              (d) =>
                                                                  d !==
                                                                  day.value,
                                                          )
                                                        : [
                                                              ...form.weekly
                                                                  .weekdays,
                                                              day.value,
                                                          ].sort(
                                                              (a, b) => a - b,
                                                          ),
                                                })
                                            }
                                        >
                                            {t(day.label)}
                                        </Button>
                                    );
                                })}
                            </div>
                        </RuleCard>

                        <RuleCard
                            type="monthly"
                            title={t('Monthly')}
                            description={t(
                                'On the selected dates (default: the 1st and 15th), optionally also on the first Monday of the month.',
                            )}
                            enabled={form.monthly.enabled}
                            onToggle={(enabled) =>
                                update('monthly', { ...form.monthly, enabled })
                            }
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="monthly_days">
                                        {t('Dates of the month')}
                                    </Label>
                                    <NumberListInput
                                        id="monthly_days"
                                        value={form.monthly.days_of_month}
                                        min={1}
                                        max={31}
                                        placeholder="1, 15"
                                        onChange={(days_of_month) =>
                                            update('monthly', {
                                                ...form.monthly,
                                                days_of_month,
                                            })
                                        }
                                    />
                                </div>
                                <div className="flex items-center gap-2 sm:pt-7">
                                    <Switch
                                        id="monthly_first_monday"
                                        checked={form.monthly.first_monday}
                                        onCheckedChange={(first_monday) =>
                                            update('monthly', {
                                                ...form.monthly,
                                                first_monday,
                                            })
                                        }
                                    />
                                    <Label
                                        htmlFor="monthly_first_monday"
                                        className="font-normal"
                                    >
                                        {t('Also on the first Monday')}
                                    </Label>
                                </div>
                            </div>
                        </RuleCard>

                        <RuleCard
                            type="annually"
                            title={t('Annually')}
                            description={t(
                                'Once a year (default: 1 November, two months before the year ends).',
                            )}
                            enabled={form.annually.enabled}
                            onToggle={(enabled) =>
                                update('annually', {
                                    ...form.annually,
                                    enabled,
                                })
                            }
                        >
                            <div className="flex flex-wrap gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="annual_day">
                                        {t('Day')}
                                    </Label>
                                    <Input
                                        id="annual_day"
                                        type="number"
                                        min={1}
                                        max={31}
                                        className="w-24"
                                        value={form.annually.day}
                                        onChange={(e) =>
                                            update('annually', {
                                                ...form.annually,
                                                day: Math.min(
                                                    31,
                                                    Math.max(
                                                        1,
                                                        Number(
                                                            e.target.value,
                                                        ) || 1,
                                                    ),
                                                ),
                                            })
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="annual_month">
                                        {t('Month')}
                                    </Label>
                                    <select
                                        id="annual_month"
                                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                        value={form.annually.month}
                                        onChange={(e) =>
                                            update('annually', {
                                                ...form.annually,
                                                month: Number(e.target.value),
                                            })
                                        }
                                    >
                                        {MONTHS.map((month, index) => (
                                            <option
                                                key={month}
                                                value={index + 1}
                                            >
                                                {t(month)}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </RuleCard>

                        {(['project', 'incidental'] as const).map((type) => (
                            <RuleCard
                                key={type}
                                type={type}
                                title={t(
                                    type === 'project'
                                        ? 'Project'
                                        : 'Incidental',
                                )}
                                description={t(
                                    'Follows each task due date: a reminder a few days before it is due, and on its start date.',
                                )}
                                enabled={form[type].enabled}
                                onToggle={(enabled) =>
                                    update(type, { ...form[type], enabled })
                                }
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor={`${type}_days`}>
                                            {t('Days before due date')}
                                        </Label>
                                        <NumberListInput
                                            id={`${type}_days`}
                                            value={form[type].days_before_due}
                                            min={0}
                                            max={365}
                                            placeholder="7, 3"
                                            onChange={(days_before_due) =>
                                                update(type, {
                                                    ...form[type],
                                                    days_before_due,
                                                })
                                            }
                                        />
                                        <p className="text-muted-foreground text-xs">
                                            {t(
                                                '0 = on the due date itself. Project owners also get these reminders before the project end date.',
                                            )}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 sm:pt-7">
                                        <Switch
                                            id={`${type}_start`}
                                            checked={form[type].on_start_date}
                                            onCheckedChange={(on_start_date) =>
                                                update(type, {
                                                    ...form[type],
                                                    on_start_date,
                                                })
                                            }
                                        />
                                        <Label
                                            htmlFor={`${type}_start`}
                                            className="font-normal"
                                        >
                                            {t('Remind on the start date')}
                                        </Label>
                                    </div>
                                </div>
                            </RuleCard>
                        ))}

                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                onClick={() => save.mutate(form)}
                                disabled={busy}
                            >
                                {save.isPending ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Save className="mr-2 h-4 w-4" />
                                )}
                                {t('Save my schedule')}
                            </Button>
                            {data.has_override && (
                                <Button
                                    variant="outline"
                                    onClick={() => reset.mutate()}
                                    disabled={busy}
                                >
                                    <RotateCcw className="mr-2 h-4 w-4" />
                                    {t('Reset to default')}
                                </Button>
                            )}
                            {data.can_manage_defaults && (
                                <Button
                                    variant="secondary"
                                    onClick={() => saveDefault.mutate(form)}
                                    disabled={busy}
                                    title={t(
                                        'Applies to every user who has not set a personal schedule.',
                                    )}
                                >
                                    <Building2 className="mr-2 h-4 w-4" />
                                    {t('Save as organization default')}
                                </Button>
                            )}
                        </div>
                    </>
                )}
            </div>
        </>
    );
}

ReminderSettingsPage.layout = {
    breadcrumbs: [
        {
            title: 'Reminder settings',
            href: '/settings/reminders',
        },
    ],
};
