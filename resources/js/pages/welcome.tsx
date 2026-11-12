import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    ArrowRight,
    CheckCircle2,
    ShieldCheck,
    Kanban,
    KeyRound,
    FileSpreadsheet,
    Activity,
    Users,
    BookOpen,
    Sparkles,
    Layers,
    Cpu,
    ExternalLink,
    Clock,
    Lock,
} from 'lucide-react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage<any>().props;
    const dashboardUrl = dashboard();
    const loginUrl = login();

    return (
        <>
            <Head title="WorkPulse — Enterprise Workload & Operations System" />

            <div className="bg-background text-foreground min-h-screen selection:bg-blue-600 selection:text-white">
                {/* Background ambient decorative glows */}
                <div className="pointer-events-none fixed inset-0 overflow-hidden">
                    <div className="absolute -top-40 left-1/2 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-gradient-to-b from-blue-500/10 via-indigo-500/5 to-transparent blur-3xl" />
                    <div className="absolute top-1/3 -left-40 h-[400px] w-[500px] rounded-full bg-blue-600/5 blur-3xl" />
                    <div className="absolute top-2/3 -right-40 h-[400px] w-[500px] rounded-full bg-indigo-600/5 blur-3xl" />
                </div>

                {/* 1. Header / Navigation */}
                <header className="border-border/40 bg-background/80 sticky top-0 z-50 w-full border-b backdrop-blur-md">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        {/* Brand Logo */}
                        <Link href="/" className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm ring-1 ring-white/20">
                                <AppLogoIcon className="h-5 w-5 fill-current text-white" />
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-foreground text-lg font-bold tracking-tight">
                                    WorkPulse
                                </span>
                                <Badge
                                    variant="outline"
                                    className="border-blue-500/30 bg-blue-500/5 text-[10px] font-semibold tracking-wider text-blue-600 uppercase dark:text-blue-400"
                                >
                                    Enterprise
                                </Badge>
                            </div>
                        </Link>

                        {/* Nav Links */}
                        <nav className="text-muted-foreground hidden items-center gap-8 text-sm font-medium md:flex">
                            <a
                                href="#features"
                                className="hover:text-foreground transition-colors"
                            >
                                Features
                            </a>
                            <a
                                href="#architecture"
                                className="hover:text-foreground transition-colors"
                            >
                                Architecture
                            </a>
                            <a
                                href="#security"
                                className="hover:text-foreground transition-colors"
                            >
                                Security & Passkeys
                            </a>
                            <Link
                                href="/docs"
                                className="hover:text-foreground flex items-center gap-1 transition-colors"
                            >
                                Documentation
                            </Link>
                        </nav>

                        {/* CTA / Auth actions */}
                        <div className="flex items-center gap-3">
                            {auth?.user ? (
                                <Button
                                    asChild
                                    className="bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    <Link href={dashboardUrl}>
                                        Dashboard
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        variant="ghost"
                                        asChild
                                        className="text-sm"
                                    >
                                        <Link href={loginUrl}>Sign in</Link>
                                    </Button>
                                    <Button
                                        asChild
                                        className="bg-blue-600 text-white shadow-sm hover:bg-blue-700"
                                    >
                                        <Link href={loginUrl}>
                                            Launch Console
                                            <ArrowRight className="ml-1.5 h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main className="relative">
                    {/* 2. Hero Section */}
                    <section className="relative overflow-hidden pt-20 pb-16 md:pt-28 md:pb-24">
                        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                            {/* Version Pill */}
                            <div className="animate-fade-in mb-8 inline-flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3.5 py-1 text-xs font-medium text-blue-600 backdrop-blur-sm dark:text-blue-400">
                                <Sparkles className="h-3.5 w-3.5" />
                                <span>
                                    WorkPulse v2.0 • Precision Workload &
                                    Governance
                                </span>
                            </div>

                            {/* Headline */}
                            <h1 className="text-foreground mx-auto mb-6 max-w-4xl text-4xl font-extrabold tracking-tight sm:text-6xl sm:leading-[1.1]">
                                Precision Workload Management for{' '}
                                <span className="bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 bg-clip-text text-transparent">
                                    High-Velocity Teams
                                </span>
                            </h1>

                            {/* Subtitle */}
                            <p className="text-muted-foreground mx-auto mb-10 max-w-2xl text-lg leading-relaxed sm:text-xl">
                                Unify cross-functional squads, hierarchical
                                organization units, and contextual RBAC
                                governance in one lightning-fast platform
                                powered by Clean Architecture.
                            </p>

                            {/* Hero CTAs */}
                            <div className="mb-16 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Button
                                    asChild
                                    size="lg"
                                    className="h-12 w-full bg-blue-600 px-8 text-base text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700 sm:w-auto"
                                >
                                    <Link
                                        href={
                                            auth?.user ? dashboardUrl : loginUrl
                                        }
                                    >
                                        {auth?.user
                                            ? 'Go to Dashboard'
                                            : 'Enter WorkPulse Console'}
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="border-border hover:bg-muted/50 h-12 w-full px-8 text-base sm:w-auto"
                                >
                                    <Link href={loginUrl}>
                                        <KeyRound className="mr-2 h-4 w-4 text-blue-600" />
                                        Sign in with Passkey
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="ghost"
                                    className="text-muted-foreground hover:text-foreground h-12 w-full px-6 text-base sm:w-auto"
                                >
                                    <Link href="/docs">
                                        <BookOpen className="mr-2 h-4 w-4" />
                                        Read Docs
                                    </Link>
                                </Button>
                            </div>

                            {/* 3. Hero Visual Preview / Application Mockup */}
                            <div className="border-border/80 bg-card/60 mx-auto max-w-5xl rounded-2xl border p-2 shadow-2xl backdrop-blur-xl sm:p-4">
                                <div className="border-border/60 bg-background overflow-hidden rounded-xl border">
                                    {/* Mock Browser Window Chrome */}
                                    <div className="border-border/60 bg-muted/40 flex items-center justify-between border-b px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-3 w-3 rounded-full bg-red-500/80" />
                                            <div className="h-3 w-3 rounded-full bg-amber-500/80" />
                                            <div className="h-3 w-3 rounded-full bg-emerald-500/80" />
                                            <span className="text-muted-foreground ml-2 font-mono text-xs font-medium">
                                                workpulse.enterprise /
                                                workspaces / core-banking
                                            </span>
                                        </div>
                                        <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                            <span className="flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                                                <span className="relative flex h-2 w-2">
                                                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                                    <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                                                </span>
                                                Live Sync
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="text-[11px] font-normal"
                                            >
                                                Sprint 14 Active
                                            </Badge>
                                        </div>
                                    </div>

                                    {/* Mock Board Content */}
                                    <div className="p-4 text-left sm:p-6">
                                        {/* Mock KPI Strip */}
                                        <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <div className="border-border/50 bg-muted/20 rounded-lg border p-3">
                                                <div className="text-muted-foreground text-[11px] font-medium uppercase">
                                                    Active Projects
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    24
                                                </div>
                                                <div className="text-[11px] font-medium text-emerald-600">
                                                    +12% this cycle
                                                </div>
                                            </div>
                                            <div className="border-border/50 bg-muted/20 rounded-lg border p-3">
                                                <div className="text-muted-foreground text-[11px] font-medium uppercase">
                                                    Task Completion
                                                </div>
                                                <div className="mt-1 text-xl font-bold">
                                                    94.8%
                                                </div>
                                                <div className="text-muted-foreground text-[11px]">
                                                    682 of 720 tasks
                                                </div>
                                            </div>
                                            <div className="border-border/50 bg-muted/20 rounded-lg border p-3">
                                                <div className="text-muted-foreground text-[11px] font-medium uppercase">
                                                    RBAC Security
                                                </div>
                                                <div className="mt-1 text-xl font-bold text-emerald-600">
                                                    Enforced
                                                </div>
                                                <div className="text-muted-foreground text-[11px]">
                                                    IDOR-Free Gates
                                                </div>
                                            </div>
                                            <div className="border-border/50 bg-muted/20 rounded-lg border p-3">
                                                <div className="text-muted-foreground text-[11px] font-medium uppercase">
                                                    Authentication
                                                </div>
                                                <div className="mt-1 text-xl font-bold text-blue-600">
                                                    FIDO2
                                                </div>
                                                <div className="text-muted-foreground text-[11px]">
                                                    Passkeys Enabled
                                                </div>
                                            </div>
                                        </div>

                                        {/* Mock Kanban Columns */}
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            {/* Column: To Do */}
                                            <div className="border-border/40 bg-muted/30 rounded-lg border p-3">
                                                <div className="mb-3 flex items-center justify-between text-xs font-semibold">
                                                    <span>TO DO</span>
                                                    <Badge
                                                        variant="secondary"
                                                        className="text-[10px]"
                                                    >
                                                        3
                                                    </Badge>
                                                </div>
                                                <div className="space-y-2">
                                                    <div className="border-border/60 bg-card rounded-md border p-3 shadow-sm">
                                                        <div className="text-xs font-medium">
                                                            Vendor User
                                                            Onboarding & Auth
                                                            API
                                                        </div>
                                                        <div className="border-border/40 text-muted-foreground mt-2 flex items-center justify-between border-t pt-2 text-[10px]">
                                                            <Badge
                                                                variant="outline"
                                                                className="border-amber-500/30 text-[9px] text-amber-600"
                                                            >
                                                                High
                                                            </Badge>
                                                            <span>WP-104</span>
                                                        </div>
                                                    </div>
                                                    <div className="border-border/60 bg-card rounded-md border p-3 shadow-sm">
                                                        <div className="text-xs font-medium">
                                                            OAuth2 Token
                                                            Rotation Pipeline
                                                        </div>
                                                        <div className="border-border/40 text-muted-foreground mt-2 flex items-center justify-between border-t pt-2 text-[10px]">
                                                            <Badge
                                                                variant="outline"
                                                                className="text-[9px]"
                                                            >
                                                                Medium
                                                            </Badge>
                                                            <span>WP-108</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Column: In Progress */}
                                            <div className="border-border/40 bg-muted/30 rounded-lg border p-3">
                                                <div className="mb-3 flex items-center justify-between text-xs font-semibold">
                                                    <span>IN PROGRESS</span>
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-blue-500/10 text-[10px] text-blue-600"
                                                    >
                                                        2
                                                    </Badge>
                                                </div>
                                                <div className="space-y-2">
                                                    <div className="border-border/60 bg-card rounded-md border p-3 shadow-sm ring-1 ring-blue-500/30">
                                                        <div className="text-foreground text-xs font-medium">
                                                            Spreadsheet Bulk
                                                            Import Engine
                                                        </div>
                                                        <div className="border-border/40 text-muted-foreground mt-2 flex items-center justify-between border-t pt-2 text-[10px]">
                                                            <Badge
                                                                variant="outline"
                                                                className="border-red-500/30 text-[9px] text-red-600"
                                                            >
                                                                Urgent
                                                            </Badge>
                                                            <span className="font-mono text-blue-600">
                                                                WP-98
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Column: Done */}
                                            <div className="border-border/40 bg-muted/30 rounded-lg border p-3">
                                                <div className="mb-3 flex items-center justify-between text-xs font-semibold">
                                                    <span>DONE</span>
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-emerald-500/10 text-[10px] text-emerald-600"
                                                    >
                                                        8
                                                    </Badge>
                                                </div>
                                                <div className="space-y-2">
                                                    <div className="border-border/60 bg-card rounded-md border p-3 opacity-90 shadow-sm">
                                                        <div className="text-muted-foreground text-xs font-medium line-through">
                                                            FIDO2 Passkeys &
                                                            Biometric Login
                                                        </div>
                                                        <div className="border-border/40 text-muted-foreground mt-2 flex items-center justify-between border-t pt-2 text-[10px]">
                                                            <Badge
                                                                variant="outline"
                                                                className="border-emerald-500/30 text-[9px] text-emerald-600"
                                                            >
                                                                Complete
                                                            </Badge>
                                                            <span>WP-82</span>
                                                        </div>
                                                    </div>
                                                    <div className="border-border/60 bg-card rounded-md border p-3 opacity-90 shadow-sm">
                                                        <div className="text-muted-foreground text-xs font-medium line-through">
                                                            Context-Aware RBAC
                                                            Policies
                                                        </div>
                                                        <div className="border-border/40 text-muted-foreground mt-2 flex items-center justify-between border-t pt-2 text-[10px]">
                                                            <Badge
                                                                variant="outline"
                                                                className="border-emerald-500/30 text-[9px] text-emerald-600"
                                                            >
                                                                Complete
                                                            </Badge>
                                                            <span>WP-76</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* 4. Core Features Bento Grid */}
                    <section
                        id="features"
                        className="border-border/50 bg-muted/20 border-t py-20"
                    >
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mx-auto mb-16 max-w-3xl text-center">
                                <h2 className="mb-2 text-xs font-bold tracking-widest text-blue-600 uppercase">
                                    Enterprise Capabilities
                                </h2>
                                <h3 className="text-foreground text-3xl font-bold tracking-tight sm:text-4xl">
                                    Engineered for Speed, Governance, and
                                    Control
                                </h3>
                                <p className="text-muted-foreground mt-4 text-base">
                                    Every component is tailored to eradicate
                                    bottlenecks, ensure bulletproof access
                                    control, and deliver instant clarity across
                                    complex teams.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {/* Feature 1 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600">
                                        <ShieldCheck className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        Context-Aware RBAC
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Grant global, workspace, or
                                        project-scoped roles with zero
                                        permission bleed. Anti-IDOR security
                                        guards protect every sensitive endpoint.
                                    </p>
                                </div>

                                {/* Feature 2 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600">
                                        <Kanban className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        Kanban & Sprint Lifecycles
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Seamlessly manage issues, transitions,
                                        backlog grooming, and worklogs. Adapts
                                        dynamically to S / M / L display
                                        density.
                                    </p>
                                </div>

                                {/* Feature 3 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                        <KeyRound className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        FIDO2 / WebAuthn Passkeys
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Passwordless biometric logins using
                                        Touch ID, Face ID, and hardware security
                                        keys. Phishing-proof public key
                                        cryptography.
                                    </p>
                                </div>

                                {/* Feature 4 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                                        <FileSpreadsheet className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        Spreadsheet Batch Importer
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Import hundreds of projects, issues, and
                                        bulk users via asynchronous background
                                        jobs with real-time live preview and
                                        typo correction.
                                    </p>
                                </div>

                                {/* Feature 5 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600">
                                        <Activity className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        Real-Time Analytics & Audit
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Executive dashboards with completion
                                        rates, overdue task tracking, workload
                                        distribution, and complete immutable
                                        audit trails.
                                    </p>
                                </div>

                                {/* Feature 6 */}
                                <div className="border-border/70 bg-card rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600">
                                        <Layers className="h-6 w-6" />
                                    </div>
                                    <h4 className="mb-2 text-lg font-semibold">
                                        Modular Clean Architecture
                                    </h4>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        Clean Domain-Application-Infrastructure
                                        separation, CQRS query patterns, PHPStan
                                        Level 7 enforcement, and 200+ unit &
                                        feature tests.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* 5. Architecture & Security Section */}
                    <section
                        id="architecture"
                        className="border-border/50 border-t py-20"
                    >
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                                <div>
                                    <Badge
                                        variant="outline"
                                        className="mb-4 border-blue-500/30 text-blue-600"
                                    >
                                        Architectural Invariants
                                    </Badge>
                                    <h3 className="text-foreground mb-6 text-3xl font-bold tracking-tight sm:text-4xl">
                                        Built on Enterprise Clean Architecture &
                                        CQRS
                                    </h3>
                                    <p className="text-muted-foreground mb-6 text-base leading-relaxed">
                                        WorkPulse strictly isolates business
                                        domain models from persistence layers.
                                        Every user action is audited, verified
                                        against RBAC permissions, and protected
                                        by asynchronous workers.
                                    </p>

                                    <div className="space-y-4">
                                        <div className="flex items-start gap-3">
                                            <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                            <div>
                                                <div className="text-sm font-semibold">
                                                    Automated Test Barrier
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    200+ Pest tests enforce
                                                    security, domain logic, and
                                                    architectural rules on every
                                                    commit.
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                            <div>
                                                <div className="text-sm font-semibold">
                                                    PostgreSQL 16 & Relational
                                                    Integrity
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    Cascade deletion,
                                                    transactional consistency,
                                                    and UUID identifiers
                                                    throughout.
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-3">
                                            <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                            <div>
                                                <div className="text-sm font-semibold">
                                                    Session Invalidation &
                                                    Re-Auth
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    Instant cross-device session
                                                    termination on password
                                                    rotation and sudo-mode
                                                    security gates.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Architecture Diagram Card */}
                                <div className="border-border bg-card rounded-xl border p-6 shadow-sm">
                                    <div className="text-muted-foreground mb-4 font-mono text-xs font-semibold tracking-wider uppercase">
                                        System Layers Diagram
                                    </div>
                                    <div className="space-y-3 font-mono text-xs">
                                        <div className="rounded-lg border border-blue-500/40 bg-blue-500/5 p-3">
                                            <div className="font-bold text-blue-600 dark:text-blue-400">
                                                1. Presentation Layer
                                            </div>
                                            <div className="text-muted-foreground mt-1 text-[11px]">
                                                React 19 • Inertia.js v2 •
                                                Tailwind CSS • shadcn/ui
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-indigo-500/40 bg-indigo-500/5 p-3">
                                            <div className="font-bold text-indigo-600 dark:text-indigo-400">
                                                2. Application / CQRS Layer
                                            </div>
                                            <div className="text-muted-foreground mt-1 text-[11px]">
                                                UseCases • Queries •
                                                WorkloadAccessChecker • Event
                                                Listeners
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-purple-500/40 bg-purple-500/5 p-3">
                                            <div className="font-bold text-purple-600 dark:text-purple-400">
                                                3. Domain Core
                                            </div>
                                            <div className="text-muted-foreground mt-1 text-[11px]">
                                                Pure Business Logic • Entities •
                                                Workflow Invariants
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/5 p-3">
                                            <div className="font-bold text-emerald-600 dark:text-emerald-400">
                                                4. Infrastructure & Security
                                            </div>
                                            <div className="text-muted-foreground mt-1 text-[11px]">
                                                PostgreSQL • FIDO2 WebAuthn •
                                                Queued Mailables • SessionGuard
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* 6. Call To Action (Bottom) */}
                    <section className="border-border/50 border-t bg-gradient-to-b from-transparent to-blue-500/5 py-20">
                        <div className="mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
                            <h3 className="mb-4 text-3xl font-extrabold tracking-tight sm:text-4xl">
                                Ready to Elevate Your Organization's Workflow?
                            </h3>
                            <p className="text-muted-foreground mx-auto mb-8 max-w-xl text-base">
                                Experience unified project tracking, contextual
                                RBAC governance, and biometric passkeys in one
                                platform.
                            </p>
                            <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Button
                                    asChild
                                    size="lg"
                                    className="h-12 bg-blue-600 px-8 text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700"
                                >
                                    <Link
                                        href={
                                            auth?.user ? dashboardUrl : loginUrl
                                        }
                                    >
                                        {auth?.user
                                            ? 'Open Dashboard'
                                            : 'Get Started Now'}
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="h-12 px-8"
                                >
                                    <Link href="/docs">
                                        Explore Documentation
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </section>
                </main>

                {/* 7. Footer */}
                <footer className="border-border/40 bg-card border-t py-12">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 sm:flex-row sm:px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm">
                                <AppLogoIcon className="h-4 w-4 fill-current text-white" />
                            </div>
                            <span className="text-foreground font-bold tracking-tight">
                                WorkPulse
                            </span>
                            <span className="text-muted-foreground text-xs">
                                • Enterprise Workload & Operations System
                            </span>
                        </div>

                        <div className="text-muted-foreground flex flex-wrap items-center gap-6 text-xs">
                            <Link
                                href="/dashboard"
                                className="hover:text-foreground transition-colors"
                            >
                                Dashboard
                            </Link>
                            <Link
                                href="/workspaces"
                                className="hover:text-foreground transition-colors"
                            >
                                Workspaces
                            </Link>
                            <Link
                                href="/projects"
                                className="hover:text-foreground transition-colors"
                            >
                                Projects
                            </Link>
                            <Link
                                href="/docs"
                                className="hover:text-foreground transition-colors"
                            >
                                Documentation
                            </Link>
                            <Link
                                href="/settings/security"
                                className="hover:text-foreground transition-colors"
                            >
                                Security
                            </Link>
                        </div>
                    </div>
                    <div className="text-muted-foreground/60 mx-auto mt-6 max-w-7xl px-4 text-center text-xs sm:px-6 lg:px-8">
                        © {new Date().getFullYear()} WorkPulse. All rights
                        reserved.
                    </div>
                </footer>
            </div>
        </>
    );
}
