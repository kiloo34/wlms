export interface TableauDashboard {
    id: string;
    number: number | null;
    name: string;
    definition: string | null;
    contents: string | null;
    purpose: string | null;
    tableau_project: string | null;
    mapping: string | null;
    user_permission: string | null;
    user_mapping: string | null;
    owner: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface DashboardCatalogOptions {
    projects: string[];
    mappings: string[];
    user_mappings: string[];
    purposes: string[];
    owners: string[];
}

export interface DashboardCatalogPermissions {
    create: boolean;
    edit: boolean;
    delete: boolean;
}

export type TableauDashboardFormData = {
    number: string;
    name: string;
    definition: string;
    contents: string;
    purpose: string;
    tableau_project: string;
    mapping: string;
    user_permission: string;
    user_mapping: string;
    owner: string;
    notes: string;
};

/** Warna badge per mapping bisnis agar mudah dipindai di tabel. */
export const MAPPING_BADGE_CLASS: Record<string, string> = {
    'Business Funding':
        'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300',
    'Business Lending':
        'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    'Digital Services':
        'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    'Finance Performance':
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    Report: 'border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-300',
    Risk: 'border-blue-500/30 bg-blue-500/10 text-blue-700 dark:text-blue-300',
};

export const DEFAULT_MAPPING_BADGE_CLASS =
    'border-border bg-muted text-muted-foreground';

export function toFormData(
    dashboard: TableauDashboard | null,
    nextNumber: number,
): TableauDashboardFormData {
    return {
        number: dashboard?.number
            ? String(dashboard.number)
            : String(nextNumber),
        name: dashboard?.name ?? '',
        definition: dashboard?.definition ?? '',
        contents: dashboard?.contents ?? '',
        purpose: dashboard?.purpose ?? '',
        tableau_project: dashboard?.tableau_project ?? '',
        mapping: dashboard?.mapping ?? '',
        user_permission: dashboard?.user_permission ?? '',
        user_mapping: dashboard?.user_mapping ?? '',
        owner: dashboard?.owner ?? '',
        notes: dashboard?.notes ?? '',
    };
}
