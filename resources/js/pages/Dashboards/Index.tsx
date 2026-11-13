import { Head, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    Gauge,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslate } from '@/hooks/useTranslate';
import { DashboardDetailSheet } from '@/modules/DashboardCatalog/components/DashboardDetailSheet';
import { DashboardFormDialog } from '@/modules/DashboardCatalog/components/DashboardFormDialog';
import {
    DEFAULT_MAPPING_BADGE_CLASS,
    MAPPING_BADGE_CLASS,
    type DashboardCatalogOptions,
    type DashboardCatalogPermissions,
    type TableauDashboard,
} from '@/modules/DashboardCatalog/types';

interface Props {
    dashboards: TableauDashboard[];
    options: DashboardCatalogOptions;
    nextNumber: number;
    can: DashboardCatalogPermissions;
}

const ALL = '__all__';
const PAGE_SIZE = 25;

/**
 * Katalog Dashboard Tableau: daftar, filter, tambah, ubah, hapus.
 */
export default function DashboardCatalogIndex({
    dashboards,
    options,
    nextNumber,
    can,
}: Props) {
    const { t } = useTranslate();

    const [search, setSearch] = useState('');
    const [project, setProject] = useState(ALL);
    const [mapping, setMapping] = useState(ALL);
    const [userMapping, setUserMapping] = useState(ALL);
    const [page, setPage] = useState(1);

    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<TableauDashboard | null>(null);
    const [viewing, setViewing] = useState<TableauDashboard | null>(null);
    const [deleting, setDeleting] = useState<TableauDashboard | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const mappingCounts = useMemo(() => {
        const counts = new Map<string, number>();
        dashboards.forEach((item) => {
            const key = item.mapping || t('Not set');
            counts.set(key, (counts.get(key) ?? 0) + 1);
        });
        return Array.from(counts.entries());
    }, [dashboards, t]);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return dashboards.filter((item) => {
            if (project !== ALL && item.tableau_project !== project) {
                return false;
            }
            if (mapping !== ALL && item.mapping !== mapping) {
                return false;
            }
            if (userMapping !== ALL && item.user_mapping !== userMapping) {
                return false;
            }
            if (!term) {
                return true;
            }

            const haystack = [
                item.number ? String(item.number) : '',
                item.name,
                item.definition,
                item.contents,
                item.tableau_project,
                item.mapping,
                item.user_permission,
                item.user_mapping,
                item.owner,
                item.notes,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return haystack.includes(term);
        });
    }, [dashboards, search, project, mapping, userMapping]);

    const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    const currentPage = Math.min(page, totalPages);
    const pageItems = filtered.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );
    const from = filtered.length === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
    const to = Math.min(currentPage * PAGE_SIZE, filtered.length);

    const hasActiveFilter =
        search !== '' ||
        project !== ALL ||
        mapping !== ALL ||
        userMapping !== ALL;

    const resetFilters = () => {
        setSearch('');
        setProject(ALL);
        setMapping(ALL);
        setUserMapping(ALL);
        setPage(1);
    };

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (dashboard: TableauDashboard) => {
        setViewing(null);
        setEditing(dashboard);
        setFormOpen(true);
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setIsDeleting(true);
        router.delete(`/dashboards/${deleting.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('Dashboard removed from the catalog'));
                setDeleting(null);
            },
            onError: () => {
                toast.error(t('Failed to delete dashboard'));
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    const mappingBadge = (value: string | null) =>
        value ? (
            <Badge
                variant="outline"
                className={
                    MAPPING_BADGE_CLASS[value] ?? DEFAULT_MAPPING_BADGE_CLASS
                }
            >
                {value}
            </Badge>
        ) : (
            <span className="text-muted-foreground">-</span>
        );

    return (
        <>
            <Head title={t('Dashboard Catalog')} />

            <div className="flex h-full w-full flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight">
                            <Gauge className="size-6 text-blue-600 dark:text-blue-400" />
                            {t('Dashboard Catalog')}
                        </h1>
                        <p className="text-muted-foreground mt-1 max-w-2xl">
                            {t(
                                'Record every Tableau dashboard here before it is published: name, project, mapping, permissions and owner.',
                            )}
                        </p>
                    </div>
                    {can.create && (
                        <Button onClick={openCreate}>
                            <Plus className="size-4" />
                            {t('Register Dashboard')}
                        </Button>
                    )}
                </div>

                {/* Summary by mapping */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                    <button
                        type="button"
                        onClick={() => {
                            setMapping(ALL);
                            setPage(1);
                        }}
                        className={`bg-card rounded-xl border p-3 text-left transition-colors ${
                            mapping === ALL
                                ? 'border-blue-500/60 ring-1 ring-blue-500/30'
                                : 'border-border hover:border-blue-500/40'
                        }`}
                    >
                        <p className="text-muted-foreground text-[11px] font-medium uppercase">
                            {t('Total dashboards')}
                        </p>
                        <p className="mt-1 text-2xl font-bold tabular-nums">
                            {dashboards.length}
                        </p>
                    </button>
                    {mappingCounts.map(([key, count]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => {
                                setMapping(mapping === key ? ALL : key);
                                setPage(1);
                            }}
                            className={`bg-card rounded-xl border p-3 text-left transition-colors ${
                                mapping === key
                                    ? 'border-blue-500/60 ring-1 ring-blue-500/30'
                                    : 'border-border hover:border-blue-500/40'
                            }`}
                        >
                            <p className="text-muted-foreground text-[11px] font-medium uppercase">
                                {key}
                            </p>
                            <p className="mt-1 text-2xl font-bold tabular-nums">
                                {count}
                            </p>
                        </button>
                    ))}
                </div>

                {/* Toolbar */}
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            placeholder={t('Search dashboards...')}
                            className="pl-9"
                        />
                    </div>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:flex">
                        <Select
                            value={project}
                            onValueChange={(value) => {
                                setProject(value);
                                setPage(1);
                            }}
                        >
                            <SelectTrigger className="lg:w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    {t('All projects')}
                                </SelectItem>
                                {options.projects.map((item) => (
                                    <SelectItem key={item} value={item}>
                                        {item}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={mapping}
                            onValueChange={(value) => {
                                setMapping(value);
                                setPage(1);
                            }}
                        >
                            <SelectTrigger className="lg:w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    {t('All mappings')}
                                </SelectItem>
                                {options.mappings.map((item) => (
                                    <SelectItem key={item} value={item}>
                                        {item}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={userMapping}
                            onValueChange={(value) => {
                                setUserMapping(value);
                                setPage(1);
                            }}
                        >
                            <SelectTrigger className="lg:w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    {t('All user mappings')}
                                </SelectItem>
                                {options.user_mappings.map((item) => (
                                    <SelectItem key={item} value={item}>
                                        {item}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {hasActiveFilter && (
                        <Button variant="ghost" onClick={resetFilters}>
                            <X className="size-4" />
                            {t('Reset filters')}
                        </Button>
                    )}
                </div>

                {/* Table */}
                <div className="bg-card overflow-hidden rounded-xl border">
                    <div>
                        <Table className="table-fixed">
                            <TableHeader>
                                <TableRow className="bg-muted/40">
                                    <TableHead className="w-12 text-center">
                                        {t('No')}
                                    </TableHead>
                                    <TableHead>{t('Dashboard Name')}</TableHead>
                                    <TableHead className="w-[16%]">
                                        {t('Tableau Project')}
                                    </TableHead>
                                    <TableHead className="w-[15%]">
                                        {t('Mapping')}
                                    </TableHead>
                                    <TableHead className="hidden w-[12%] md:table-cell">
                                        {t('User Mapping')}
                                    </TableHead>
                                    <TableHead className="hidden w-[13%] md:table-cell">
                                        {t('Owner')}
                                    </TableHead>
                                    <TableHead className="w-28 text-right">
                                        {t('Actions')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pageItems.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="text-muted-foreground h-32 text-center"
                                        >
                                            {t(
                                                'No dashboards match your filters.',
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    pageItems.map((item) => (
                                        <TableRow
                                            key={item.id}
                                            className="cursor-pointer"
                                            onClick={() => setViewing(item)}
                                        >
                                            <TableCell className="text-muted-foreground text-center tabular-nums">
                                                {item.number ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium break-words">
                                                    {item.name}
                                                </p>
                                                {item.definition && (
                                                    <p className="text-muted-foreground mt-0.5 line-clamp-1 text-xs">
                                                        {item.definition}
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell className="break-words">
                                                {item.tableau_project ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                {mappingBadge(item.mapping)}
                                            </TableCell>
                                            <TableCell className="hidden break-words md:table-cell">
                                                {item.user_mapping ?? '-'}
                                            </TableCell>
                                            <TableCell className="hidden break-words md:table-cell">
                                                {item.owner ?? '-'}
                                            </TableCell>
                                            <TableCell
                                                className="text-right"
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                            >
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        title={t(
                                                            'View details',
                                                        )}
                                                        onClick={() =>
                                                            setViewing(item)
                                                        }
                                                    >
                                                        <Eye className="size-4" />
                                                    </Button>
                                                    {can.edit && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            title={t('Edit')}
                                                            onClick={() =>
                                                                openEdit(item)
                                                            }
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Button>
                                                    )}
                                                    {can.delete && (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="text-destructive hover:text-destructive size-8"
                                                            title={t('Delete')}
                                                            onClick={() =>
                                                                setDeleting(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="text-muted-foreground flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm sm:flex-row">
                        <span>
                            {t('Showing :from-:to of :total', {
                                from,
                                to,
                                total: filtered.length,
                            })}
                        </span>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={currentPage <= 1}
                                onClick={() => setPage(currentPage - 1)}
                            >
                                <ChevronLeft className="size-4" />
                                {t('Previous')}
                            </Button>
                            <span className="tabular-nums">
                                {currentPage} / {totalPages}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={currentPage >= totalPages}
                                onClick={() => setPage(currentPage + 1)}
                            >
                                {t('Next')}
                                <ChevronRight className="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <DashboardFormDialog
                open={formOpen}
                onOpenChange={setFormOpen}
                dashboard={editing}
                options={options}
                nextNumber={nextNumber}
            />

            <DashboardDetailSheet
                dashboard={viewing}
                onOpenChange={(open) => !open && setViewing(null)}
                canEdit={can.edit}
                onEdit={openEdit}
            />

            <AlertDialog
                open={deleting !== null}
                onOpenChange={(open) =>
                    !open && !isDeleting && setDeleting(null)
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {t('Delete dashboard?')}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {t(
                                'This entry will be removed from the catalog. The record is kept in the database.',
                            )}
                            {deleting && (
                                <span className="text-foreground mt-2 block font-medium">
                                    {deleting.name}
                                </span>
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={isDeleting}>
                            {t('Cancel')}
                        </AlertDialogCancel>
                        <AlertDialogAction
                            disabled={isDeleting}
                            onClick={(e) => {
                                e.preventDefault();
                                confirmDelete();
                            }}
                            className="bg-destructive hover:bg-destructive/90 text-white"
                        >
                            {t('Delete')}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
