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
import { User, Lock, ShieldCheck, Users, Building, Settings as SettingsIcon, Database } from 'lucide-react';
import type { NavItem } from '@/types';
import { Card, CardTitle } from '@/components/ui/card';

// ─── Sidebar Nav Groups ────────────────────────────────────────────────────────
type NavGroup = { title: string; items: NavItem[] };

const getSidebarNavGroups = (isSuperAdmin: boolean): NavGroup[] => {
    const groups: NavGroup[] = [
        {
            title: 'Personal',
            items: [
                { title: 'Profile',    href: edit(),            icon: User },
                { title: 'Password',   href: editSecurity(),    icon: Lock },
                { title: 'Appearance', href: editAppearance(),  icon: ShieldCheck },
            ]
        }
    ];

    if (isSuperAdmin) {
        groups.push({
            title: 'Company',
            items: [
                { title: 'Users',  href: rbacIndex(),          icon: Users },
                { title: 'Organization',  href: organizationIndex(),   icon: Building },
                { title: 'Workflows',  href: workflowsIndex(),   icon: SettingsIcon },
                { title: 'Import Data', href: '/admin/import-workload', icon: Database },
            ]
        });
    }

    return groups;
};

// ─── Layout ───────────────────────────────────────────────────────────────────
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage().props as unknown as { auth: { user: { permissions?: string[] } } };

    const isSuperAdmin = auth?.user?.permissions?.includes('*') ?? false;
    const navGroups = getSidebarNavGroups(isSuperAdmin);

    return (
        <div className="min-h-screen pb-12">
            <div className="flex-1 w-full max-w-7xl mx-auto flex h-full flex-col gap-6 py-8 px-4 sm:px-6 lg:px-8">
                {/* Page header */}
                <div className="space-y-0.5">
                    <Card className='p-6'>
                        <h2 className="text-2xl font-bold tracking-tight text-foreground">Settings</h2>
                        <p className="text-muted-foreground">
                            Manage your account settings and set e-mail preferences.
                        </p>
                    </Card>
                </div>

                <div className="flex flex-col space-y-8 lg:flex-row lg:space-x-16 lg:space-y-0">
                    {/* ── Sidebar ── */}
                    <aside className="mx-4 lg:pr-6 lg:mx-0 lg:w-64 lg:shrink-0 overflow-x-auto pb-2 lg:pb-0">
                        <nav className="flex flex-row px-4 lg:px-0 space-x-4 lg:flex-col lg:space-x-0 lg:space-y-1">
                            <Card className='p-6'>
                                {navGroups.map((group, groupIdx) => (
                                    <div key={groupIdx} className="flex flex-row space-x-2 lg:flex-col lg:space-x-0 flex-shrink-0">
                                        <h4 className="hidden lg:block mb-1 rounded-md px-2 py-1 text-sm font-semibold text-foreground">
                                            {group.title}
                                        </h4>
                                        {group.items.map((item, index) => {
                                            const isActive = isCurrentOrParentUrl(item.href);
                                            return (
                                                <Link
                                                    key={`${toUrl(item.href)}-${index}`}
                                                    href={item.href}
                                                    className={cn(
                                                        'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-muted hover:text-foreground whitespace-nowrap',
                                                        isActive 
                                                            ? 'bg-muted text-foreground' 
                                                            : 'text-muted-foreground'
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
                                ))}
                            </Card>
                        </nav>
                    </aside>

                    {/* ── Main content ── */}
                    <main className="flex-1 min-w-0">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}

