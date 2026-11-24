import React, { useEffect, useMemo, useRef, useState } from 'react';
import { AtSign, Loader2, User } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Textarea } from '@/components/ui/textarea';
import { useTranslate } from '@/hooks/useTranslate';
import { REFERENCE_ICON } from './ReferenceText';
import {
    buildReferenceToken,
    ReferenceSuggestion,
    useReferenceSearch,
} from './references';

export interface MentionPerson {
    id: string | number;
    name: string;
    email?: string;
}

type Item =
    | { kind: 'person'; person: MentionPerson }
    | { kind: 'reference'; reference: ReferenceSuggestion };

interface ReferenceTextareaProps extends Omit<
    React.ComponentProps<'textarea'>,
    'value' | 'onChange'
> {
    value: string;
    onChange: (value: string) => void;
    /** Bila diisi (mis. di komentar), orang juga bisa di-mention dengan @. */
    people?: MentionPerson[];
    /** Posisi daftar saran: di bawah (default) atau di atas textarea. */
    placement?: 'bottom' | 'top';
}

const MAX_QUERY_LENGTH = 40;

/**
 * Textarea yang memunculkan saran saat mengetik "@": project, dashboard katalog
 * (dicocokkan dengan kamusnya), dan opsional anggota tim.
 */
export function ReferenceTextarea({
    value,
    onChange,
    people,
    placement = 'bottom',
    onKeyDown,
    className,
    ...props
}: ReferenceTextareaProps) {
    const { t } = useTranslate();
    const ref = useRef<HTMLTextAreaElement | null>(null);
    const [query, setQuery] = useState<string | null>(null);
    const [anchor, setAnchor] = useState(-1);
    const [active, setActive] = useState(0);
    const [debounced, setDebounced] = useState('');

    useEffect(() => {
        const handle = window.setTimeout(() => setDebounced(query ?? ''), 200);
        return () => window.clearTimeout(handle);
    }, [query]);

    const isOpen = query !== null;
    const { data, isFetching } = useReferenceSearch(debounced, isOpen);

    const items: Item[] = useMemo(() => {
        if (!isOpen) return [];
        const q = (query ?? '').toLowerCase();
        const matchedPeople = (people ?? [])
            .filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    (p.email ?? '').toLowerCase().includes(q),
            )
            .slice(0, 5)
            .map((person): Item => ({ kind: 'person', person }));
        const references = [
            ...(data?.projects ?? []),
            ...(data?.dashboards ?? []),
        ].map((reference): Item => ({ kind: 'reference', reference }));
        return [...matchedPeople, ...references];
    }, [isOpen, query, people, data]);

    useEffect(() => {
        setActive(0);
    }, [debounced]);

    const close = () => {
        setQuery(null);
        setAnchor(-1);
    };

    const detect = (text: string, cursor: number) => {
        const before = text.slice(0, cursor);
        const at = before.lastIndexOf('@');
        if (at === -1) return close();

        // "@" harus di awal teks atau setelah spasi (bukan bagian email).
        if (at > 0 && !/\s/.test(before[at - 1])) return close();

        const q = before.slice(at + 1);
        if (
            q.length > MAX_QUERY_LENGTH ||
            /[\r\n[\]]/.test(q) ||
            /\s{2}/.test(q)
        ) {
            return close();
        }

        setQuery(q);
        setAnchor(at);
    };

    const select = (item: Item) => {
        const el = ref.current;
        const cursor = el?.selectionStart ?? anchor + (query?.length ?? 0) + 1;
        const insertion =
            item.kind === 'person'
                ? // Format lama mention user agar notifikasi mention tetap jalan.
                  `@${item.person.name.replace(/\s+/g, '')} `
                : `${buildReferenceToken(item.reference.type, item.reference.id, item.reference.label)} `;
        const next = value.slice(0, anchor) + insertion + value.slice(cursor);
        onChange(next);
        close();

        const caret = anchor + insertion.length;
        window.requestAnimationFrame(() => {
            el?.focus();
            el?.setSelectionRange(caret, caret);
        });
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (isOpen && items.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive((i) => (i + 1) % items.length);
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive((i) => (i - 1 + items.length) % items.length);
                return;
            }
            if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                select(items[Math.min(active, items.length - 1)]);
                return;
            }
        }
        if (isOpen && e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            close();
            return;
        }
        onKeyDown?.(e);
    };

    const groupHeading = (item: Item, index: number) => {
        const kind = item.kind === 'person' ? 'person' : item.reference.type;
        const prev = items[index - 1];
        const prevKind = prev
            ? prev.kind === 'person'
                ? 'person'
                : prev.reference.type
            : null;
        if (kind === prevKind) return null;
        const label =
            kind === 'person'
                ? t('Team members')
                : kind === 'project'
                  ? t('Projects')
                  : t('Dashboards (catalog)');
        return (
            <div className="text-muted-foreground px-2 pt-2 pb-1 text-[10px] font-semibold uppercase">
                {label}
            </div>
        );
    };

    return (
        <div className="relative">
            <Textarea
                {...props}
                ref={ref}
                value={value}
                className={className}
                onChange={(e) => {
                    onChange(e.target.value);
                    detect(e.target.value, e.target.selectionStart);
                }}
                onKeyDown={handleKeyDown}
                onBlur={(e) => {
                    // Beri waktu klik pada saran sebelum daftar ditutup.
                    window.setTimeout(close, 150);
                    props.onBlur?.(e);
                }}
                aria-autocomplete="list"
                aria-expanded={isOpen}
            />

            {isOpen && (
                <div
                    className={cn(
                        'bg-popover text-popover-foreground absolute left-0 z-50 w-full max-w-md overflow-hidden rounded-lg border shadow-md',
                        placement === 'top'
                            ? 'bottom-full mb-1'
                            : 'top-full mt-1',
                    )}
                    role="listbox"
                >
                    <div className="bg-muted/40 text-muted-foreground flex items-center gap-1 border-b px-2.5 py-1.5 text-[11px] font-semibold">
                        <AtSign className="h-3 w-3" />
                        <span>{t('Refer to a project or dashboard')}</span>
                        {isFetching && (
                            <Loader2 className="ml-auto h-3 w-3 animate-spin" />
                        )}
                    </div>
                    <div className="max-h-64 overflow-y-auto p-1">
                        {items.length === 0 ? (
                            <p className="text-muted-foreground px-2 py-3 text-xs">
                                {isFetching
                                    ? t('Searching...')
                                    : t('No results found.')}
                            </p>
                        ) : (
                            items.map((item, index) => {
                                const selected = index === active;
                                const Icon =
                                    item.kind === 'person'
                                        ? User
                                        : REFERENCE_ICON[item.reference.type];
                                const title =
                                    item.kind === 'person'
                                        ? item.person.name
                                        : item.reference.label;
                                const subtitle =
                                    item.kind === 'person'
                                        ? item.person.email
                                        : [
                                              item.reference.subtitle,
                                              item.reference.owner,
                                          ]
                                              .filter(Boolean)
                                              .join(' · ');
                                const detail =
                                    item.kind === 'reference'
                                        ? item.reference.description
                                        : null;
                                return (
                                    <React.Fragment
                                        key={
                                            item.kind === 'person'
                                                ? `u-${item.person.id}`
                                                : `${item.reference.type}-${item.reference.id}`
                                        }
                                    >
                                        {groupHeading(item, index)}
                                        <button
                                            type="button"
                                            role="option"
                                            aria-selected={selected}
                                            onMouseDown={(e) => {
                                                e.preventDefault();
                                                select(item);
                                            }}
                                            onMouseEnter={() =>
                                                setActive(index)
                                            }
                                            className={cn(
                                                'flex w-full items-start gap-2 rounded-md px-2 py-1.5 text-left text-xs',
                                                selected
                                                    ? 'bg-accent text-accent-foreground'
                                                    : 'hover:bg-accent/60',
                                            )}
                                        >
                                            <Icon className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate font-medium">
                                                    {title}
                                                </span>
                                                {subtitle && (
                                                    <span className="text-muted-foreground block truncate text-[10px]">
                                                        {subtitle}
                                                    </span>
                                                )}
                                                {detail && (
                                                    <span className="text-muted-foreground line-clamp-2 block text-[10px]">
                                                        {detail}
                                                    </span>
                                                )}
                                            </span>
                                        </button>
                                    </React.Fragment>
                                );
                            })
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
