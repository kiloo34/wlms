import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useTranslate } from '@/hooks/useTranslate';
import { home } from '@/routes';
import type { AuthLayoutProps, SharedData } from '@/types';

/**
 * Layout halaman auth (login, register, lupa password, dst).
 * Panel kiri: brand & nilai utama platform. Panel kanan: form.
 */
export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage<SharedData>().props;
    const { t } = useTranslate();
    const brandName = name && name !== 'Laravel' ? name : 'WorkPulse';

    const highlights = [
        t('Workspaces & Teams'),
        t('Projects & Sprints'),
        t('Tickets & Worklogs'),
        t('Dashboard Catalog'),
    ];

    return (
        <div className="bg-background relative grid min-h-svh lg:grid-cols-[0.95fr_1.05fr]">
            {/* Brand panel */}
            <div className="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 p-10 text-white lg:flex">
                <div className="pointer-events-none absolute -top-32 -right-32 size-96 rounded-full bg-white/10 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-40 -left-24 size-96 rounded-full bg-indigo-400/20 blur-3xl" />

                <Link
                    href={home()}
                    className="relative z-10 flex items-center gap-3"
                >
                    <div className="flex size-10 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/30">
                        <AppLogoIcon className="size-5 fill-current text-white" />
                    </div>
                    <div className="grid leading-none">
                        <span className="text-lg font-bold tracking-tight">
                            {brandName}
                        </span>
                        <span className="mt-0.5 text-[10px] font-medium tracking-wider text-white/70 uppercase">
                            {t('Workload Management System')}
                        </span>
                    </div>
                </Link>

                <div className="relative z-10 max-w-md">
                    <h2 className="text-3xl font-bold tracking-tight text-balance">
                        {t(
                            'Plan, track and measure team workload in one place',
                        )}
                    </h2>
                    <ul className="mt-8 space-y-3">
                        {highlights.map((item) => (
                            <li
                                key={item}
                                className="flex items-center gap-3 text-sm text-white/90"
                            >
                                <CheckCircle2 className="size-4 shrink-0 text-white" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative z-10 text-xs text-white/60">
                    © {new Date().getFullYear()} {brandName}.{' '}
                    {t('All rights reserved.')}
                </p>
            </div>

            {/* Form panel */}
            <div className="flex flex-col px-6 py-8 sm:px-10">
                <div className="flex items-center justify-between">
                    <Link
                        href={home()}
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 text-sm transition-colors"
                    >
                        <ArrowLeft className="size-4" />
                        {t('Back to home')}
                    </Link>
                    <Link
                        href={home()}
                        className="flex items-center gap-2 lg:hidden"
                    >
                        <div className="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-white">
                            <AppLogoIcon className="size-4 fill-current" />
                        </div>
                        <span className="text-sm font-bold">{brandName}</span>
                    </Link>
                </div>

                <div className="flex flex-1 items-center justify-center py-10">
                    <div className="w-full max-w-sm space-y-8">
                        <div className="space-y-2">
                            <h1 className="text-2xl font-bold tracking-tight">
                                {title}
                            </h1>
                            <p className="text-muted-foreground text-sm text-balance">
                                {description}
                            </p>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
