import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Briefcase,
    CheckCircle2,
    FolderKanban,
    Gauge,
    KeyRound,
    LogIn,
    Menu,
    ShieldCheck,
    Ticket,
    X,
} from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeToggle } from '@/components/ThemeToggle';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useChangeLocale } from '@/hooks/useChangeLocale';
import { useTranslate } from '@/hooks/useTranslate';
import { dashboard, login } from '@/routes';
import type { SharedData } from '@/types';

/**
 * Landing page publik: pintu masuk sebelum login.
 * Alur: Landing -> Login -> Beranda (dashboard).
 */
export default function Landing() {
    const { auth, name } = usePage<SharedData>().props;
    const { t, locale } = useTranslate();
    const { changeLocale } = useChangeLocale();
    const [menuOpen, setMenuOpen] = useState(false);

    const brandName = name && name !== 'Laravel' ? name : 'WorkPulse';
    const isAuthenticated = Boolean(auth?.user);
    const primaryHref = isAuthenticated ? dashboard() : login();
    const primaryLabel = isAuthenticated
        ? t('Go to Dashboard')
        : t('Sign in to continue');

    const navLinks = [
        { href: '#features', label: t('Features') },
        { href: '#flow', label: t('How it works') },
        { href: '#catalog', label: t('Dashboard Catalog') },
    ];

    const features = [
        {
            icon: Briefcase,
            title: t('Workspaces & Teams'),
            description: t(
                'Group people by organization unit, assign roles and capacity hours per member.',
            ),
        },
        {
            icon: FolderKanban,
            title: t('Projects & Sprints'),
            description: t(
                'Plan work in sprints with backlog, board and timeline views.',
            ),
        },
        {
            icon: Ticket,
            title: t('Tickets & Worklogs'),
            description: t(
                'Track every task from creation to completion, including time spent.',
            ),
        },
        {
            icon: Gauge,
            title: t('Dashboard Catalog'),
            description: t(
                'Register every Tableau dashboard with its project, mapping, permissions and owner.',
            ),
        },
        {
            icon: ShieldCheck,
            title: t('Contextual RBAC'),
            description: t(
                'Permissions are scoped to global, workspace or project level.',
            ),
        },
        {
            icon: KeyRound,
            title: t('Secure sign in'),
            description: t(
                'Two-factor authentication and passkeys protect every account.',
            ),
        },
    ];

    const steps = [
        {
            title: t('Open the landing page'),
            description: t(
                'Learn what the platform offers and head to the sign-in page.',
            ),
        },
        {
            title: t('Sign in securely'),
            description: t('Use your account, two-factor code or passkey.'),
        },
        {
            title: t('Land on your home'),
            description: t(
                'See your tasks, projects and activities right away.',
            ),
        },
    ];

    const catalogColumns = [
        t('Dashboard Name'),
        t('Tableau Project'),
        t('Mapping'),
        t('User Permission'),
        t('User Mapping'),
        t('Owner'),
    ];

    return (
        <>
            <Head title={t('Workload Management System')} />

            <div className="bg-background text-foreground min-h-screen">
                {/* Ambient background */}
                <div className="pointer-events-none fixed inset-0 overflow-hidden">
                    <div className="absolute -top-48 left-1/2 h-[520px] w-[900px] -translate-x-1/2 rounded-full bg-gradient-to-b from-blue-500/15 via-indigo-500/5 to-transparent blur-3xl" />
                    <div className="absolute top-[45%] -left-32 h-[360px] w-[420px] rounded-full bg-blue-600/5 blur-3xl" />
                    <div className="absolute top-[70%] -right-32 h-[360px] w-[420px] rounded-full bg-indigo-600/5 blur-3xl" />
                </div>

                {/* Header */}
                <header className="border-border/40 bg-background/80 sticky top-0 z-50 w-full border-b backdrop-blur-md">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <div className="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm ring-1 ring-white/20">
                                <AppLogoIcon className="size-5 fill-current text-white" />
                            </div>
                            <div className="grid leading-none">
                                <span className="text-base font-bold tracking-tight">
                                    {brandName}
                                </span>
                                <span className="text-muted-foreground mt-0.5 text-[10px] font-medium tracking-wider uppercase">
                                    {t('Workload Management System')}
                                </span>
                            </div>
                        </Link>

                        <nav className="text-muted-foreground hidden items-center gap-8 text-sm font-medium md:flex">
                            {navLinks.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className="hover:text-foreground transition-colors"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </nav>

                        <div className="flex items-center gap-1">
                            <LanguageSwitcher
                                locale={locale}
                                onChange={changeLocale}
                            />
                            <ThemeToggle />
                            <Button
                                asChild
                                className="ml-2 hidden bg-blue-600 text-white shadow-sm hover:bg-blue-700 sm:inline-flex"
                            >
                                <Link href={primaryHref}>
                                    {isAuthenticated ? (
                                        <ArrowRight className="size-4" />
                                    ) : (
                                        <LogIn className="size-4" />
                                    )}
                                    {isAuthenticated
                                        ? t('Go to Dashboard')
                                        : t('Sign in')}
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="md:hidden"
                                aria-label="Menu"
                                onClick={() => setMenuOpen((open) => !open)}
                            >
                                {menuOpen ? (
                                    <X className="size-5" />
                                ) : (
                                    <Menu className="size-5" />
                                )}
                            </Button>
                        </div>
                    </div>

                    {menuOpen && (
                        <div className="border-border/40 bg-background border-t px-4 py-4 md:hidden">
                            <nav className="flex flex-col gap-3 text-sm font-medium">
                                {navLinks.map((link) => (
                                    <a
                                        key={link.href}
                                        href={link.href}
                                        onClick={() => setMenuOpen(false)}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        {link.label}
                                    </a>
                                ))}
                                <Button
                                    asChild
                                    className="mt-2 bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    <Link href={primaryHref}>
                                        {primaryLabel}
                                    </Link>
                                </Button>
                            </nav>
                        </div>
                    )}
                </header>

                <main className="relative">
                    {/* Hero */}
                    <section className="mx-auto grid max-w-7xl items-center gap-12 px-4 pt-16 pb-20 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:pt-24 lg:pb-28">
                        <div>
                            <Badge
                                variant="outline"
                                className="border-blue-500/30 bg-blue-500/5 px-3 py-1 text-xs font-semibold text-blue-600 dark:text-blue-400"
                            >
                                <CheckCircle2 className="size-3.5" />
                                {t('Internal platform for IT Division')}
                            </Badge>
                            <h1 className="mt-6 text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                {t('Plan, track and measure')}{' '}
                                <span className="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                    {t('team workload')}
                                </span>{' '}
                                {t('in one place')}
                            </h1>
                            <p className="text-muted-foreground mt-6 max-w-xl text-lg leading-relaxed text-pretty">
                                {t(
                                    'Workspaces, projects, sprints, tickets and worklogs with contextual role-based access, so every team member knows what to do and management sees the real picture.',
                                )}
                            </p>
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Button
                                    asChild
                                    size="lg"
                                    className="bg-blue-600 text-white shadow-md hover:bg-blue-700"
                                >
                                    <Link href={primaryHref}>
                                        {primaryLabel}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <a href="#features">
                                        {t('Explore features')}
                                    </a>
                                </Button>
                            </div>
                        </div>

                        {/* Illustrative preview card */}
                        <div className="relative">
                            <div className="absolute -inset-4 rounded-3xl bg-gradient-to-br from-blue-500/20 to-indigo-500/10 blur-2xl" />
                            <div className="bg-card text-card-foreground border-border/60 relative rounded-2xl border p-5 shadow-xl">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-muted-foreground text-xs font-medium uppercase">
                                            {t('Sprint overview')}
                                        </p>
                                        <p className="mt-1 text-sm font-semibold">
                                            Sprint 12 · Core Banking
                                        </p>
                                    </div>
                                    <Badge variant="info">
                                        {t('In progress')}
                                    </Badge>
                                </div>

                                <div className="mt-5 grid grid-cols-3 gap-3">
                                    {[
                                        {
                                            label: t('Capacity used'),
                                            value: '82%',
                                        },
                                        {
                                            label: t('Open tickets'),
                                            value: '37',
                                        },
                                        { label: t('Overdue'), value: '3' },
                                    ].map((stat) => (
                                        <div
                                            key={stat.label}
                                            className="bg-muted/50 rounded-xl p-3"
                                        >
                                            <p className="text-muted-foreground text-[11px]">
                                                {stat.label}
                                            </p>
                                            <p className="mt-1 text-xl font-bold tabular-nums">
                                                {stat.value}
                                            </p>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-5 space-y-2">
                                    {[
                                        { name: 'Migrasi core API', w: '85%' },
                                        {
                                            name: 'Rekon dashboard QRIS',
                                            w: '60%',
                                        },
                                        { name: 'Audit log RBAC', w: '35%' },
                                    ].map((row) => (
                                        <div
                                            key={row.name}
                                            className="flex items-center gap-3"
                                        >
                                            <span className="w-40 truncate text-xs font-medium">
                                                {row.name}
                                            </span>
                                            <div className="bg-muted h-2 flex-1 overflow-hidden rounded-full">
                                                <div
                                                    className="h-full rounded-full bg-gradient-to-r from-blue-600 to-indigo-500"
                                                    style={{ width: row.w }}
                                                />
                                            </div>
                                            <span className="text-muted-foreground w-9 text-right text-[11px] tabular-nums">
                                                {row.w}
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                <div className="border-border/60 mt-5 flex items-center justify-between border-t pt-4">
                                    <div className="flex -space-x-2">
                                        {['AR', 'RW', 'NV', 'DS'].map(
                                            (initials) => (
                                                <div
                                                    key={initials}
                                                    className="bg-background flex size-7 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-blue-500 to-indigo-500 text-[10px] font-semibold text-white dark:border-neutral-900"
                                                >
                                                    {initials}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    <span className="text-muted-foreground text-xs">
                                        12 {t('team members')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Features */}
                    <section
                        id="features"
                        className="border-border/40 bg-muted/30 border-y py-20"
                    >
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    {t('Features')}
                                </p>
                                <h2 className="mt-2 text-3xl font-bold tracking-tight">
                                    {t(
                                        'Everything a team needs to run its workload',
                                    )}
                                </h2>
                            </div>
                            <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature) => (
                                    <div
                                        key={feature.title}
                                        className="bg-card border-border/60 group rounded-2xl border p-6 shadow-sm transition-shadow hover:shadow-md"
                                    >
                                        <div className="flex size-11 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white dark:text-blue-400">
                                            <feature.icon className="size-5" />
                                        </div>
                                        <h3 className="mt-4 text-base font-semibold">
                                            {feature.title}
                                        </h3>
                                        <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                            {feature.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Flow */}
                    <section id="flow" className="py-20">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    {t('How it works')}
                                </p>
                                <h2 className="mt-2 text-3xl font-bold tracking-tight">
                                    {t('Three steps to get started')}
                                </h2>
                            </div>
                            <ol className="mt-10 grid gap-6 md:grid-cols-3">
                                {steps.map((step, index) => (
                                    <li
                                        key={step.title}
                                        className="bg-card border-border/60 relative rounded-2xl border p-6"
                                    >
                                        <span className="flex size-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-bold text-white">
                                            {index + 1}
                                        </span>
                                        <h3 className="mt-4 font-semibold">
                                            {step.title}
                                        </h3>
                                        <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                            {step.description}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    {/* Catalog spotlight */}
                    <section
                        id="catalog"
                        className="border-border/40 border-t py-20"
                    >
                        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                            <div>
                                <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    {t('Dashboard Catalog')}
                                </p>
                                <h2 className="mt-2 text-3xl font-bold tracking-tight">
                                    {t(
                                        'One source of truth for Tableau dashboards',
                                    )}
                                </h2>
                                <p className="text-muted-foreground mt-4 leading-relaxed">
                                    {t(
                                        'New dashboards are recorded here first: name, definition, Tableau project, business mapping, user permission, user mapping and owner. Add, update and remove entries without losing history.',
                                    )}
                                </p>
                                <Button
                                    asChild
                                    className="mt-6 bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    <Link
                                        href={
                                            isAuthenticated
                                                ? '/dashboards'
                                                : login()
                                        }
                                    >
                                        {t('Open the catalog')}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>
                            <div className="bg-card border-border/60 overflow-hidden rounded-2xl border shadow-lg">
                                <div className="bg-muted/60 border-border/60 grid grid-cols-6 gap-2 border-b px-4 py-2 text-[10px] font-semibold tracking-wide uppercase">
                                    {catalogColumns.map((column) => (
                                        <span key={column} className="truncate">
                                            {column}
                                        </span>
                                    ))}
                                </div>
                                {[
                                    [
                                        'Dash DPK Valas',
                                        'Dashboard DPK',
                                        'Business Funding',
                                        'KC, Divisi',
                                        'Divisi & Cabang',
                                        'Dana & Jasa',
                                    ],
                                    [
                                        'Dashboard Kredit Cabang',
                                        'Dashboard Kredit',
                                        'Business Lending',
                                        'KC, Kredit',
                                        'Divisi & Cabang',
                                        'Konsumer, Mikro',
                                    ],
                                    [
                                        'Pelaporan QRIS',
                                        'Dashboard E-Channel',
                                        'Digital Services',
                                        'Divisi Digital',
                                        'Divisi',
                                        'Dana & Jasa',
                                    ],
                                    [
                                        'Dashboard Keuangan',
                                        'Dashboard Keuangan',
                                        'Finance Performance',
                                        'admin',
                                        'Admin',
                                        'Perencanaan',
                                    ],
                                ].map((row) => (
                                    <div
                                        key={row[0]}
                                        className="border-border/40 grid grid-cols-6 gap-2 border-b px-4 py-3 text-xs last:border-b-0"
                                    >
                                        {row.map((cell, index) => (
                                            <span
                                                key={`${row[0]}-${index}`}
                                                className={
                                                    index === 0
                                                        ? 'truncate font-medium'
                                                        : 'text-muted-foreground truncate'
                                                }
                                            >
                                                {cell}
                                            </span>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* CTA */}
                    <section className="pb-20">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 px-6 py-14 text-center text-white shadow-xl sm:px-12">
                                <div className="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-white/10 blur-3xl" />
                                <h2 className="text-3xl font-bold tracking-tight">
                                    {t('Ready to start?')}
                                </h2>
                                <p className="mx-auto mt-3 max-w-xl text-white/80">
                                    {t(
                                        'Sign in with your account to reach your home dashboard.',
                                    )}
                                </p>
                                <Button
                                    asChild
                                    size="lg"
                                    className="mt-8 bg-white text-blue-700 shadow-md hover:bg-blue-50"
                                >
                                    <Link href={primaryHref}>
                                        {primaryLabel}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-border/40 border-t py-8">
                    <div className="text-muted-foreground mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 text-xs sm:flex-row sm:px-6 lg:px-8">
                        <span>
                            © {new Date().getFullYear()} {brandName}.{' '}
                            {t('All rights reserved.')}
                        </span>
                        <div className="flex items-center gap-4">
                            <Link
                                href="/docs"
                                className="hover:text-foreground transition-colors"
                            >
                                {t('Documentation')}
                            </Link>
                            <Link
                                href={login()}
                                className="hover:text-foreground transition-colors"
                            >
                                {t('Sign in')}
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
