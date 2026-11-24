import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import {
    FolderKanban,
    Gauge,
    Loader2,
    MessageSquare,
    Search,
    SquareCheck,
} from 'lucide-react';
import axios from '@/lib/axios';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslate } from '@/hooks/useTranslate';
import { WorkTypeBadge } from '@/modules/Workload/shared/WorkTypeBadge';

interface SearchResults {
    query: string;
    total: number;
    references_only: boolean;
    dashboards: {
        id: string;
        number: number | null;
        name: string;
        tableau_project: string | null;
        mapping: string | null;
        owner: string | null;
        matched_in: string | null;
        snippet: string | null;
    }[];
    projects: {
        id: string;
        key: string;
        name: string;
        workspace_name: string | null;
        owner_name: string | null;
        snippet: string | null;
    }[];
    issues: {
        id: string;
        key: string;
        title: string;
        work_type: string;
        assignee_name: string | null;
        status: { name: string | null };
        project: { id: string; name: string };
        snippet: string | null;
    }[];
    comments: {
        id: string;
        issue_id: string;
        issue_key: string;
        issue_title: string;
        project_id: string;
        author_name: string | null;
        snippet: string | null;
    }[];
}

const MIN_LENGTH = 2;

/** Tebalkan bagian teks yang cocok dengan kata kunci (tidak peka huruf besar/kecil). */
function Highlight({ text, term }: { text: string; term: string }) {
    if (!term) return <>{text}</>;
    const lower = text.toLowerCase();
    const needle = term.toLowerCase();
    const parts: { value: string; match: boolean }[] = [];
    let cursor = 0;
    let index = lower.indexOf(needle);
    while (index !== -1 && needle.length > 0) {
        if (index > cursor) {
            parts.push({ value: text.slice(cursor, index), match: false });
        }
        parts.push({
            value: text.slice(index, index + needle.length),
            match: true,
        });
        cursor = index + needle.length;
        index = lower.indexOf(needle, cursor);
    }
    parts.push({ value: text.slice(cursor), match: false });

    return (
        <>
            {parts.map((part, i) =>
                part.match ? (
                    <mark
                        key={i}
                        className="bg-primary/15 text-foreground rounded-sm px-0.5"
                    >
                        {part.value}
                    </mark>
                ) : (
                    <span key={i}>{part.value}</span>
                ),
            )}
        </>
    );
}

/**
 * Pencarian global: semua teks di project (nama/kode/deskripsi), tugas
 * (judul/kode/deskripsi), dan komentar yang mengandung kata kunci.
 */
export function GlobalSearch() {
    const { t } = useTranslate();
    const [open, setOpen] = useState(false);
    const [input, setInput] = useState('');
    const [term, setTerm] = useState('');

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                setOpen((value) => !value);
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    useEffect(() => {
        const handle = window.setTimeout(() => setTerm(input.trim()), 250);
        return () => window.clearTimeout(handle);
    }, [input]);

    // Awalan "@" = mode rujukan: jelajahi/cari project & dashboard katalog (kamusnya).
    const referenceMode = term.startsWith('@');
    const highlightTerm = referenceMode ? term.slice(1).trim() : term;
    const enabled = open && (referenceMode || term.length >= MIN_LENGTH);
    const { data, isFetching } = useQuery<SearchResults>({
        queryKey: ['global-search', term],
        queryFn: async () => {
            const { data } = await axios.get('/api/search', {
                params: { q: term, limit: 8 },
            });
            return data.data;
        },
        enabled,
        placeholderData: keepPreviousData,
        staleTime: 30 * 1000,
    });

    const go = (url: string) => {
        setOpen(false);
        router.visit(url);
    };

    const results = enabled ? data : undefined;

    return (
        <>
            <Button
                variant="outline"
                size="sm"
                className="text-muted-foreground mr-2 h-9 gap-2 px-2.5 sm:w-56 sm:justify-start"
                onClick={() => setOpen(true)}
                aria-label={t('Search')}
            >
                <Search className="h-4 w-4" />
                <span className="hidden sm:inline">{t('Search...')}</span>
                <kbd className="bg-muted ml-auto hidden rounded px-1.5 font-mono text-[10px] sm:inline">
                    Ctrl K
                </kbd>
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="overflow-hidden p-0 sm:max-w-2xl">
                    <DialogTitle className="sr-only">{t('Search')}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {t('Search projects, tasks, descriptions, comments...')}
                    </DialogDescription>
                    {/* Hasil sudah difilter server, jadi pencocokan bawaan cmdk dimatikan. */}
                    <Command
                        shouldFilter={false}
                        className="[&_[cmdk-group-heading]]:text-muted-foreground [&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group]]:px-2 [&_[cmdk-input]]:h-12"
                    >
                        <CommandInput
                            value={input}
                            onValueChange={setInput}
                            placeholder={t(
                                'Search projects, tasks, descriptions, comments...',
                            )}
                        />
                        <CommandList className="max-h-[420px]">
                            {!enabled ? (
                                <div className="text-muted-foreground space-y-1 p-6 text-center text-sm">
                                    <p>
                                        {t(
                                            'Type at least 2 characters to search.',
                                        )}
                                    </p>
                                    <p className="text-xs">
                                        {t(
                                            'Start with @ to browse projects and catalog dashboards with their dictionary.',
                                        )}
                                    </p>
                                </div>
                            ) : isFetching && !results ? (
                                <div className="text-muted-foreground flex items-center justify-center gap-2 p-6 text-sm">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    {t('Searching...')}
                                </div>
                            ) : (
                                <>
                                    <CommandEmpty>
                                        {t('No results found.')}
                                    </CommandEmpty>

                                    {results && results.projects.length > 0 && (
                                        <CommandGroup heading={t('Projects')}>
                                            {results.projects.map((project) => (
                                                <CommandItem
                                                    key={`p-${project.id}`}
                                                    value={`p-${project.id}`}
                                                    onSelect={() =>
                                                        go(
                                                            `/projects/${project.id}`,
                                                        )
                                                    }
                                                    className="items-start gap-3"
                                                >
                                                    <FolderKanban className="mt-0.5 h-4 w-4 shrink-0" />
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">
                                                            <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                                <Highlight
                                                                    text={
                                                                        project.key
                                                                    }
                                                                    term={
                                                                        highlightTerm
                                                                    }
                                                                />
                                                            </span>
                                                            <Highlight
                                                                text={
                                                                    project.name
                                                                }
                                                                term={
                                                                    highlightTerm
                                                                }
                                                            />
                                                        </p>
                                                        <p className="text-muted-foreground text-xs">
                                                            {[
                                                                project.workspace_name,
                                                                project.owner_name
                                                                    ? `${t('Owner')}: ${project.owner_name}`
                                                                    : null,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(' · ')}
                                                        </p>
                                                        {project.snippet && (
                                                            <p className="text-muted-foreground line-clamp-2 text-xs">
                                                                <Highlight
                                                                    text={
                                                                        project.snippet
                                                                    }
                                                                    term={
                                                                        highlightTerm
                                                                    }
                                                                />
                                                            </p>
                                                        )}
                                                    </div>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    )}

                                    {results &&
                                        results.dashboards.length > 0 && (
                                            <CommandGroup
                                                heading={t(
                                                    'Dashboards (catalog)',
                                                )}
                                            >
                                                {results.dashboards.map(
                                                    (dashboard) => (
                                                        <CommandItem
                                                            key={`d-${dashboard.id}`}
                                                            value={`d-${dashboard.id}`}
                                                            onSelect={() =>
                                                                go(
                                                                    `/dashboards?dashboard=${dashboard.id}`,
                                                                )
                                                            }
                                                            className="items-start gap-3"
                                                        >
                                                            <Gauge className="mt-0.5 h-4 w-4 shrink-0" />
                                                            <div className="min-w-0">
                                                                <p className="truncate text-sm font-medium">
                                                                    {dashboard.number !==
                                                                        null && (
                                                                        <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                                            #
                                                                            {
                                                                                dashboard.number
                                                                            }
                                                                        </span>
                                                                    )}
                                                                    <Highlight
                                                                        text={
                                                                            dashboard.name
                                                                        }
                                                                        term={
                                                                            highlightTerm
                                                                        }
                                                                    />
                                                                </p>
                                                                <p className="text-muted-foreground text-xs">
                                                                    {[
                                                                        dashboard.tableau_project,
                                                                        dashboard.mapping,
                                                                        dashboard.owner,
                                                                    ]
                                                                        .filter(
                                                                            Boolean,
                                                                        )
                                                                        .join(
                                                                            ' · ',
                                                                        )}
                                                                </p>
                                                                {dashboard.snippet && (
                                                                    <p className="text-muted-foreground line-clamp-2 text-xs">
                                                                        <Highlight
                                                                            text={
                                                                                dashboard.snippet
                                                                            }
                                                                            term={
                                                                                highlightTerm
                                                                            }
                                                                        />
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </CommandItem>
                                                    ),
                                                )}
                                            </CommandGroup>
                                        )}

                                    {results && results.issues.length > 0 && (
                                        <CommandGroup heading={t('Tasks')}>
                                            {results.issues.map((issue) => (
                                                <CommandItem
                                                    key={`i-${issue.id}`}
                                                    value={`i-${issue.id}`}
                                                    onSelect={() =>
                                                        go(
                                                            `/projects/${issue.project.id}?issue=${issue.id}`,
                                                        )
                                                    }
                                                    className="items-start gap-3"
                                                >
                                                    <SquareCheck className="mt-0.5 h-4 w-4 shrink-0" />
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <p className="min-w-0 truncate text-sm font-medium">
                                                                <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                                    <Highlight
                                                                        text={
                                                                            issue.key
                                                                        }
                                                                        term={
                                                                            term
                                                                        }
                                                                    />
                                                                </span>
                                                                <Highlight
                                                                    text={
                                                                        issue.title
                                                                    }
                                                                    term={
                                                                        highlightTerm
                                                                    }
                                                                />
                                                            </p>
                                                            <WorkTypeBadge
                                                                value={
                                                                    issue.work_type
                                                                }
                                                            />
                                                        </div>
                                                        <p className="text-muted-foreground text-xs">
                                                            {[
                                                                issue.project
                                                                    .name,
                                                                issue.status
                                                                    .name,
                                                                issue.assignee_name,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(' · ')}
                                                        </p>
                                                        {issue.snippet && (
                                                            <p className="text-muted-foreground line-clamp-2 text-xs">
                                                                <Highlight
                                                                    text={
                                                                        issue.snippet
                                                                    }
                                                                    term={
                                                                        highlightTerm
                                                                    }
                                                                />
                                                            </p>
                                                        )}
                                                    </div>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    )}

                                    {results && results.comments.length > 0 && (
                                        <CommandGroup heading={t('Comments')}>
                                            {results.comments.map((comment) => (
                                                <CommandItem
                                                    key={`c-${comment.id}`}
                                                    value={`c-${comment.id}`}
                                                    onSelect={() =>
                                                        go(
                                                            `/projects/${comment.project_id}?issue=${comment.issue_id}`,
                                                        )
                                                    }
                                                    className="items-start gap-3"
                                                >
                                                    <MessageSquare className="mt-0.5 h-4 w-4 shrink-0" />
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">
                                                            <span className="text-muted-foreground mr-1.5 font-mono text-[11px]">
                                                                {
                                                                    comment.issue_key
                                                                }
                                                            </span>
                                                            {
                                                                comment.issue_title
                                                            }
                                                        </p>
                                                        {comment.snippet && (
                                                            <p className="text-muted-foreground line-clamp-2 text-xs">
                                                                {comment.author_name && (
                                                                    <span className="text-foreground">
                                                                        {
                                                                            comment.author_name
                                                                        }
                                                                        :{' '}
                                                                    </span>
                                                                )}
                                                                <Highlight
                                                                    text={
                                                                        comment.snippet
                                                                    }
                                                                    term={
                                                                        highlightTerm
                                                                    }
                                                                />
                                                            </p>
                                                        )}
                                                    </div>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    )}
                                </>
                            )}
                        </CommandList>
                    </Command>
                </DialogContent>
            </Dialog>
        </>
    );
}
