import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as rbacIndex } from '@/routes/rbac';
import { index as organizationIndex } from '@/routes/organization';
import { index as workflowsIndex } from '@/routes/workflows';
import { usePage } from '@inertiajs/react';
import {
    User,
    Lock,
    ShieldCheck,
    Users,
    Building,
    Settings as SettingsIcon,
    Database,
    BookOpen,
    BellRing,
} from 'lucide-react';
import type { NavItem } from '@/types';
import { Card } from '@/components/ui/card';

// ─── Sidebar Nav Groups ────────────────────────────────────────────────────────
type NavGroup = { title: string; items: NavItem[] };

const getSidebarNavGroups = (isSuperAdmin: boolean): NavGroup[] => {
    const groups: NavGroup[] = [
        {
            title: 'Personal',
            items: [
                { title: 'Profile', href: edit(), icon: User },
                { title: 'Password', href: editSecurity(), icon: Lock },
                {
                    title: 'Appearance',
                    href: editAppearance(),
                    icon: ShieldCheck,
                },
                {
                    title: 'Reminders',
                    href: '/settings/reminders',
                    icon: BellRing,
                },
            ],
        },
    ];

    if (isSuperAdmin) {
        groups.push({
            title: 'Company',
            items: [
                { title: 'Users', href: rbacIndex(), icon: Users },
                {
                    title: 'Organization',
                    href: organizationIndex(),
                    icon: Building,
                },
                {
                    title: 'Workflows',
                    href: workflowsIndex(),
                    icon: SettingsIcon,
                },
                {
                    title: 'SLA & Policies',
                    href: '/settings/workflows?tab=policies',
                    icon: ShieldCheck,
                },
                {
                    title: 'Documentation',
                    href: '/settings/docs',
                    icon: BookOpen,
                },
                {
                    title: 'Import Data',
                    href: '/admin/import-workload',
                    icon: Database,
                },
            ],
        });
    }

    return groups;
};

// ─── Layout ───────────────────────────────────────────────────────────────────
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage().props as unknown as {
        auth: { user: { permissions?: string[] } };
    };

    const isSuperAdmin = auth?.user?.permissions?.includes('*') ?? false;
    const navGroups = getSidebarNavGroups(isSuperAdmin);

    return (
        <div className="min-h-screen pb-12">
            <div className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                {/* Page header */}
                <Card className="gap-1 p-5 sm:p-6">
                    <h2 className="text-foreground text-2xl font-bold tracking-tight">
                        Settings
                    </h2>
                    <p className="text-muted-foreground text-sm sm:text-base">
                        Manage your account settings and set e-mail preferences.
                    </p>
                </Card>

                <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                    {/* ── Sidebar ── */}
                    <aside className="w-full lg:w-64 lg:shrink-0">
                        <Card className="p-5">
                            <nav
                                aria-label="Settings navigation"
                                className="flex flex-col gap-4"
                            >
                                {navGroups.map((group, groupIdx) => (
                                    <div
                                        key={group.title}
                                        className="space-y-2"
                                    >
                                        {groupIdx > 0 && (
                                            <Separator className="mb-4" />
                                        )}
                                        <h4 className="text-muted-foreground px-2 text-xs font-semibold tracking-wider uppercase">
                                            {group.title}
                                        </h4>
                                        <div className="-mx-1 flex [scrollbar-width:thin] flex-nowrap gap-2 overflow-x-auto px-1 pt-1 pb-3 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:p-0">
                                            {group.items.map((item, index) => {
                                                const isActive =
                                                    isCurrentOrParentUrl(
                                                        item.href,
                                                    );
                                                return (
                                                    <Link
                                                        key={`${toUrl(item.href)}-${index}`}
                                                        href={item.href}
                                                        className={cn(
                                                            'hover:bg-muted hover:text-foreground flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                                                            isActive
                                                                ? 'bg-muted text-foreground'
                                                                : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {item.icon && (
                                                            <item.icon className="h-4 w-4 shrink-0" />
                                                        )}
                                                        {item.title}
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </nav>
                        </Card>
                    </aside>

                    {/* ── Main content ── */}
                    <main className="min-w-0 flex-1">{children}</main>
                </div>
            </div>
        </div>
    );
}
