export type WorkType =
    | 'daily'
    | 'weekly'
    | 'monthly'
    | 'annually'
    | 'incidental'
    | 'project';

export interface WorkTypeOption {
    value: WorkType;
    /** Kunci terjemahan (bahasa Inggris); label Indonesia ada di lang/id.json. */
    label: string;
    /** Penjelasan singkat jadwal pengingat default. */
    hint: string;
    className: string;
}

export const DEFAULT_WORK_TYPE: WorkType = 'project';

// Kelas warna mengikuti pola badge yang sudah dipakai di aplikasi (bg-x/10 + teks x).
export const WORK_TYPES: WorkTypeOption[] = [
    {
        value: 'daily',
        label: 'Daily',
        hint: 'Reminder every day at 08:00',
        className:
            'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    },
    {
        value: 'weekly',
        label: 'Weekly',
        hint: 'Reminder every Monday',
        className:
            'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    },
    {
        value: 'monthly',
        label: 'Monthly',
        hint: 'Reminder on the 1st and 15th',
        className:
            'border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
    {
        value: 'annually',
        label: 'Annually',
        hint: 'Reminder 2 months before year end',
        className:
            'border-fuchsia-500/30 bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-300',
    },
    {
        value: 'incidental',
        label: 'Incidental',
        hint: 'Reminder follows its due date',
        className:
            'border-orange-500/30 bg-orange-500/10 text-orange-700 dark:text-orange-300',
    },
    {
        value: 'project',
        label: 'Project',
        hint: 'Reminder follows its due date',
        className:
            'border-slate-500/30 bg-slate-500/10 text-slate-700 dark:text-slate-300',
    },
];

export function getWorkType(value?: string | null): WorkTypeOption {
    return (
        WORK_TYPES.find((type) => type.value === value) ??
        (WORK_TYPES.find(
            (type) => type.value === DEFAULT_WORK_TYPE,
        ) as WorkTypeOption)
    );
}
