import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ExternalLink, FolderKanban, Gauge, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslate } from '@/hooks/useTranslate';
import { RemainingDaysBadge } from './RemainingDaysBadge';
import {
    parseReferences,
    ReferenceType,
    useReferenceDetail,
} from './references';

export const REFERENCE_ICON = {
    project: FolderKanban,
    dashboard: Gauge,
} as const;

function ReferenceChip({
    type,
    id,
    label,
}: {
    type: ReferenceType;
    id: string;
    label: string;
}) {
    const { t } = useTranslate();
    const [open, setOpen] = useState(false);
    const { data, isLoading, isError } = useReferenceDetail(type, id, open);
    const Icon = REFERENCE_ICON[type];

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    onClick={(e) => {
                        // Jangan ikut memicu klik baris/link induk; buka-tutup manual
                        // karena preventDefault menonaktifkan toggle bawaan Radix.
                        e.preventDefault();
                        e.stopPropagation();
                        setOpen((value) => !value);
                    }}
                    className={cn(
                        'inline items-center rounded px-1 py-px align-baseline text-[0.95em] font-medium underline-offset-2 hover:underline',
                        type === 'dashboard'
                            ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300'
                            : 'bg-primary/10 text-primary',
                    )}
                    title={t(
                        type === 'dashboard'
                            ? 'Dashboard reference'
                            : 'Project reference',
                    )}
                >
                    <Icon
                        className="mr-0.5 inline h-3 w-3 align-[-1px]"
                        aria-hidden
                    />
                    {label}
                </button>
            </PopoverTrigger>
            <PopoverContent
                className="w-80 p-0"
                align="start"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-start gap-2 border-b p-3">
                    <Icon className="mt-0.5 h-4 w-4 shrink-0" aria-hidden />
                    <div className="min-w-0 flex-1">
                        <p className="text-muted-foreground text-[11px] font-medium uppercase">
                            {t(type === 'dashboard' ? 'Dashboard' : 'Project')}
                        </p>
                        <p className="text-sm font-semibold">
                            {data?.label ?? label}
                        </p>
                    </div>
                    {data?.remaining_days !== undefined && (
                        <RemainingDaysBadge days={data.remaining_days} />
                    )}
                </div>
                <div className="max-h-72 overflow-y-auto p-3">
                    {isLoading ? (
                        <div className="text-muted-foreground flex items-center gap-2 text-sm">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            {t('Loading...')}
                        </div>
                    ) : isError || !data ? (
                        <p className="text-muted-foreground text-sm">
                            {t(
                                'This reference is not available or you do not have access to it.',
                            )}
                        </p>
                    ) : (
                        <dl className="space-y-2">
                            {data.dictionary
                                .filter(
                                    (row) =>
                                        row.value !== null &&
                                        String(row.value).trim() !== '',
                                )
                                .map((row) => (
                                    <div key={row.key}>
                                        <dt className="text-muted-foreground text-[11px] font-medium">
                                            {t(row.label)}
                                        </dt>
                                        <dd className="text-sm whitespace-pre-line">
                                            {String(row.value)}
                                        </dd>
                                    </div>
                                ))}
                        </dl>
                    )}
                </div>
                {data && (
                    <div className="border-t p-2">
                        <Button
                            size="sm"
                            variant="ghost"
                            className="w-full justify-center"
                            onClick={() => {
                                setOpen(false);
                                router.visit(data.url);
                            }}
                        >
                            <ExternalLink className="mr-2 h-3.5 w-3.5" />
                            {t(
                                type === 'dashboard'
                                    ? 'Open in Dashboard Catalog'
                                    : 'Open project',
                            )}
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}

/**
 * Menampilkan teks dengan token rujukan "@[Nama](tipe:id)" sebagai chip yang bisa
 * diklik untuk melihat kamusnya. Mention user lama (`@nama`) bisa ikut disorot.
 */
export function ReferenceText({
    text,
    highlightUserMentions = false,
}: {
    text: string | null | undefined;
    highlightUserMentions?: boolean;
}) {
    if (!text) return null;

    return (
        <>
            {parseReferences(text).map((segment, index) => {
                if (segment.kind === 'reference') {
                    return (
                        <ReferenceChip
                            key={`${segment.id}-${index}`}
                            type={segment.type}
                            id={segment.id}
                            label={segment.label}
                        />
                    );
                }

                if (!highlightUserMentions) {
                    return <span key={index}>{segment.value}</span>;
                }

                return segment.value
                    .split(/(@[a-zA-Z0-9._-]+)/g)
                    .map((part, partIndex) =>
                        part.startsWith('@') ? (
                            <span
                                key={`${index}-${partIndex}`}
                                className="bg-primary/10 text-primary rounded px-1.5 py-0.5 text-xs font-semibold"
                            >
                                {part}
                            </span>
                        ) : (
                            <span key={`${index}-${partIndex}`}>{part}</span>
                        ),
                    );
            })}
        </>
    );
}
