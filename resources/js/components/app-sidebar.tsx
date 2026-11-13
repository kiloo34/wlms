import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Briefcase,
    HelpCircle,
    CheckSquare,
    Users,
    Folder,
    FolderKanban,
    Gauge,
    KeyRound,
    Settings,
    Shield,
    Ticket,
    User,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { useTranslate } from '@/hooks/useTranslate';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import type { LucideIcon } from 'lucide-react';

// Type from database API (Simulated)
type DBMenuItem = {
    title: string;
    url: string;
    icon_name: string;
};

// Icon Dictionary to map string from DB to React Component
const ICON_MAP: Record<string, LucideIcon> = {
    dashboard: LayoutGrid,
    'layout-dashboard': LayoutGrid,
    workspaces: Briefcase,
    briefcase: Briefcase,
    issues: CheckSquare,
    ticket: Ticket,
    users: Users,
    user: User,
    folder: Folder,
    'folder-kanban': FolderKanban,
    dashboards: Gauge,
    gauge: Gauge,
    settings: Settings,
    key: KeyRound,
    shield: Shield,
};

export function AppSidebar() {
    const { auth } = usePage().props as unknown as {
        auth: { user: { menus?: DBMenuItem[] } };
    };
    const dashboardUrl = '/dashboard';
    const { t } = useTranslate();

    // 1. DEFAULT MENU (Selalu ada untuk semua user)
    // Katalog Dashboard terbuka untuk semua user login, jadi selalu tampil.
    const defaultNavItems: NavItem[] = [
        {
            title: t('Dashboard'),
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: t('Dashboard Catalog'),
            href: '/dashboards',
            icon: Gauge,
        },
    ];

    // 2. DYNAMIC MENUS (Datang dari database/RBAC user via Shared Props)
    const mappedMenusFromDB: DBMenuItem[] = auth?.user?.menus || [];

    // Menerjemahkan DBMenuItem menjadi NavItem (menyisipkan Icon component)
    const dynamicNavItems: NavItem[] = mappedMenusFromDB.map((dbMenu) => {
        const iconName = dbMenu.icon_name ? dbMenu.icon_name.toLowerCase() : '';
        const IconComponent = ICON_MAP[iconName] || HelpCircle;
        return {
            title: t(dbMenu.title),
            href: dbMenu.url,
            icon: IconComponent,
        };
    });

    // 3. GABUNGKAN DENGAN MENCEGAH DUPLIKASI
    // Pastikan menu dinamis tidak me-render ulang menu default (misal: Dashboard).
    // Menu Pengaturan disembunyikan dari sidebar karena sudah tersedia di menu profil (NavUser).
    const hrefToString = (href: NavItem['href']): string =>
        typeof href === 'string' ? href : href.url;
    const defaultHrefs = defaultNavItems.map((item) => hrefToString(item.href));
    const filteredDynamicNavItems = dynamicNavItems.filter((item) => {
        const href = hrefToString(item.href);
        return !defaultHrefs.includes(href) && !href.startsWith('/settings');
    });

    const mainNavItems: NavItem[] = [
        ...defaultNavItems,
        ...filteredDynamicNavItems,
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {/* NavMain (Dumb Component) merender array final */}
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
