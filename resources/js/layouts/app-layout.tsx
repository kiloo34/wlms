import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { GlobalIssueModalProvider } from '@/contexts/GlobalIssueModalContext';
import { GlobalIssueCreateModal } from '@/components/GlobalIssueCreateModal';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <GlobalIssueModalProvider>
            <AppLayoutTemplate breadcrumbs={breadcrumbs}>
                {children}
            </AppLayoutTemplate>
            <GlobalIssueCreateModal />
        </GlobalIssueModalProvider>
    );
}
