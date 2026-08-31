import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    Check,
    CloudSnow,
    Eye,
    Lock,
    MapPinned,
    Receipt,
    Route,
    Snowflake,
    Sparkles,
    Zap,
    type LucideIcon,
} from 'lucide-react';
import {
    clientLogos,
    companyStats,
    heroStats,
    homeFaqs,
    homeFeatures,
    pricingTiers,
} from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import { CTABand, MarketingSection } from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

/* ── Tint recipes (complete literal strings, never constructed) ──── */
const GLACIER_TINT = 'bg-glacier-500/10 text-glacier-700 dark:text-glacier-400';
const EMBER_TINT = 'bg-ember-500/10 text-ember-600 dark:text-ember-400';

/* ── Hero backdrop: blueprint grid, glows, drifting snow ─────────── */
function HeroBackdrop() {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 overflow-hidden"
        >
            <div
                className="bg-grid-frost absolute inset-0"
                style={{
                    maskImage:
                        'radial-gradient(ellipse 90% 75% at 50% 0%, black 30%, transparent 100%)',
                    WebkitMaskImage:
                        'radial-gradient(ellipse 90% 75% at 50% 0%, black 30%, transparent 100%)',
                }}
            />
            <div className="absolute -top-40 -left-32 size-[34rem] rounded-full bg-glacier-400/20 blur-3xl dark:bg-glacier-500/10" />
            <div className="absolute -right-32 -bottom-44 size-[30rem] rounded-full bg-ember-400/15 blur-3xl dark:bg-ember-500/10" />
            <Snowflake
                className="absolute top-[14%] left-[4%] size-10 animate-drift text-glacier-400/50"
                style={{ animationDelay: '-2s' }}
            />
            <Snowflake
                className="absolute top-[8%] right-[8%] size-14 animate-drift text-glacier-300/60 dark:text-glacier-600/40"
                style={{ animationDelay: '-6s' }}
            />
            <Snowflake
                className="absolute right-[30%] bottom-[16%] hidden size-6 animate-drift text-glacier-400/40 md:block"
                style={{ animationDelay: '-9s' }}
            />
            <Snowflake
                className="absolute top-[42%] right-[38%] hidden size-5 animate-drift text-glacier-400/30 lg:block"
                style={{ animationDelay: '-12s' }}
            />
        </div>
    );
}

/* ── Hero dashboard mock (decorative product preview, lg+) ───────── */
function DashboardMock() {
    return (
        <div aria-hidden="true" className="relative hidden lg:block">
            {/* Floating dispatch chip (straddles the frame's bottom-left corner) */}
            <div
                className="pointer-events-none absolute -bottom-5 -left-4 z-20 animate-drift"
                style={{ animationDelay: '-4s' }}
            >
                <span className="flex items-center gap-2 rounded-lg bg-ember px-3.5 py-2.5 text-sm font-medium text-ember-foreground shadow-glow-ember">
                    <Zap className="size-4" />
                    Crew dispatched · Truck 07
                </span>
            </div>

            <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-frost-lg">
                {/* Browser chrome */}
                <div className="flex items-center gap-2 border-b border-border bg-muted/50 px-4 py-3">
                    <div className="flex w-10 shrink-0 items-center gap-1.5">
                        <span className="size-2.5 rounded-full bg-frost-300 dark:bg-frost-600" />
                        <span className="size-2.5 rounded-full bg-frost-300/70 dark:bg-frost-700" />
                        <span className="size-2.5 rounded-full bg-frost-300/40 dark:bg-frost-800" />
                    </div>
                    <div className="flex flex-1 justify-center px-2">
                        <span className="flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1 font-mono text-[10px] text-muted-foreground">
                            <Lock className="size-3" />
                            app.frostline.ops/operations
                        </span>
                    </div>
                    <span className="w-10 shrink-0" />
                </div>

                <div className="space-y-4 p-4 sm:p-5">
                    {/* Storm banner */}
                    <div className="flex items-center gap-3 rounded-lg border-l-4 border-ember-500 bg-ember-500/10 px-3 py-2.5">
                        <CloudSnow className="size-4 shrink-0 text-ember-600 dark:text-ember-400" />
                        <div className="min-w-0 flex-1">
                            <p className="text-xs leading-tight font-semibold">
                                Winter storm warning
                            </p>
                            <p className="mt-0.5 text-[11px] leading-tight text-muted-foreground">
                                18–24 cm tonight · auto-dispatch armed
                            </p>
                        </div>
                        <Badge className="border-ember-500/30 bg-ember-500/15 text-ember-700 dark:border-ember-400/30 dark:bg-ember-400/10 dark:text-ember-300">
                            Active
                        </Badge>
                    </div>

                    {/* KPI row */}
                    <div className="grid grid-cols-3 gap-3">
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Active storms
                            </p>
                            <p className="mt-1.5 flex items-center gap-1.5 text-lg font-semibold tabular-nums">
                                2
                                <span className="size-1.5 animate-pulse-soft rounded-full bg-info" />
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Cleared
                            </p>
                            <p className="mt-1.5 text-lg font-semibold tabular-nums">
                                128/156
                            </p>
                            <Progress value={82} className="mt-2 h-1" />
                        </div>
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Salt used
                            </p>
                            <p className="mt-1.5 text-lg font-semibold tabular-nums">
                                3.4 t
                            </p>
                        </div>
                    </div>

                    {/* Route card */}
                    <div className="overflow-hidden rounded-lg border border-border">
                        <div className="flex items-center justify-between gap-3 border-b border-border px-3 py-2">
                            <p className="text-xs font-semibold">
                                Route 12 · Northside
                            </p>
                            <p className="font-mono text-[10px] text-muted-foreground">
                                TRUCK 07
                            </p>
                        </div>
                        <ul className="divide-y divide-border">
                            <li className="flex items-center justify-between gap-3 px-3 py-2">
                                <span className="truncate font-mono text-xs">
                                    128 Harbor View Ln
                                </span>
                                <span className="flex shrink-0 items-center gap-1.5">
                                    <span className="size-1.5 rounded-full bg-info" />
                                    <span className="text-[10px] font-medium text-info">
                                        En route
                                    </span>
                                </span>
                            </li>
                            <li className="flex items-center justify-between gap-3 px-3 py-2">
                                <span className="truncate font-mono text-xs">
                                    82 Summit Ridge Rd
                                </span>
                                <span className="flex shrink-0 items-center gap-1.5">
                                    <span className="size-1.5 rounded-full bg-glacier-500" />
                                    <span className="text-[10px] font-medium text-glacier-600 dark:text-glacier-400">
                                        In progress
                                    </span>
                                </span>
                            </li>
                            <li className="flex items-center justify-between gap-3 px-3 py-2">
                                <span className="truncate font-mono text-xs">
                                    9 Maplewood Ct
                                </span>
                                <span className="flex shrink-0 items-center gap-1.5">
                                    <span className="size-1.5 rounded-full bg-success" />
                                    <span className="text-[10px] font-medium text-success">
                                        Completed
                                    </span>
                                </span>
                            </li>
                        </ul>
                        <div className="flex items-center justify-between border-t border-border px-3 py-2">
                            <p className="font-mono text-[10px] text-muted-foreground">
                                ETA 12 MIN
                            </p>
                            <p className="flex items-center gap-1.5 font-mono text-[10px] text-muted-foreground">
                                <span className="size-1.5 animate-pulse-soft rounded-full bg-glacier-500" />
                                LIVE
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

/* ── Storm mode band bullets ─────────────────────────────────────── */
const STORM_FEATURES: {
    icon: LucideIcon;
    iconClass: string;
    title: string;
    copy: string;
}[] = [
    {
        icon: Route,
        iconClass: 'text-glacier-300',
        title: 'Priority re-sorting',
        copy: 'Hospitals, priority-1 lots, and escalations re-queue to the front the moment conditions change.',
    },
    {
        icon: Bell,
        iconClass: 'text-ember-300',
        title: 'Escalation alerts',
        copy: 'Stalled trucks and missed ETAs page the right supervisor automatically.',
    },
    {
        icon: Eye,
        iconClass: 'text-glacier-300',
        title: 'Crew-wide visibility',
        copy: 'Every plow, stop, and salt spread on one live map, from the cab to the dispatch desk.',
    },
];

/* ── How it works steps ──────────────────────────────────────────── */
interface HowItWorksStep {
    icon: LucideIcon;
    tint: string;
    title: string;
    copy: string;
    caption: string;
}

const HOW_IT_WORKS: HowItWorksStep[] = [
    {
        icon: MapPinned,
        tint: GLACIER_TINT,
        title: 'Map your properties',
        copy: 'Your sites shouldn\u2019t live in one dispatcher\u2019s memory. Import from any CSV, set a trigger depth per contract, and tag surfaces so the router knows a loading dock from a cul-de-sac.',
        caption: 'Setup: one afternoon',
    },
    {
        icon: CloudSnow,
        tint: EMBER_TINT,
        title: 'Let the storm work for you',
        copy: 'Stop waking crews manually. Weather feeds watch each site — when snow crosses your trigger depth, drivers wake to routes already optimized on their phones.',
        caption: 'Dispatch: 11 min median',
    },
    {
        icon: Receipt,
        tint: GLACIER_TINT,
        title: 'Clear, prove, and bill',
        copy: 'Disputes shouldn\u2019t take phone calls, and invoicing shouldn\u2019t take days. Geo-stamped photos and timestamps compile into invoices — per storm, per push, or seasonal — within 24 hours of the final pass.',
        caption: 'Billing: NET-14 standard',
    },
];

function StepCard({ step, index }: { step: HowItWorksStep; index: number }) {
    const Icon = step.icon;
    return (
        <li className="relative rounded-xl border border-border bg-card p-6 shadow-frost-xs">
            <div className="flex items-center justify-between gap-3">
                <span
                    className={cn(
                        'flex size-10 items-center justify-center rounded-lg',
                        step.tint,
                    )}
                >
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                <span className="font-mono text-sm font-semibold text-muted-foreground">
                    {String(index + 1).padStart(2, '0')}
                </span>
            </div>
            <h3 className="mt-4 font-semibold">{step.title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                {step.copy}
            </p>
            <p className="mt-5 border-t border-border pt-3 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                {step.caption}
            </p>
        </li>
    );
}

/* ── Page ────────────────────────────────────────────────────────── */
export default function Home() {
    return (
        <MarketingLayout
            title="Frostline OPS — Snow Removal Operations Software"
            route={ROUTES.home}
        >
            {/* 1 · Hero */}
            <section
                aria-labelledby="home-hero-heading"
                className="relative overflow-hidden border-b border-border py-20 md:py-28"
            >
                <HeroBackdrop />
                <div className="relative z-10 mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                        <div className="mx-auto max-w-xl text-center lg:mx-0 lg:text-left">
                            <Badge
                                variant="outline"
                                className="gap-1.5 rounded-full border-glacier-200 bg-glacier-50/70 px-3 py-1 text-xs font-medium text-glacier-800 dark:border-glacier-800/60 dark:bg-glacier-950/40 dark:text-glacier-300"
                            >
                                <Sparkles aria-hidden="true" />
                                Storm season is coming — get set up now
                            </Badge>
                            <h1
                                id="home-hero-heading"
                                className="mt-6 text-4xl font-semibold tracking-tighter text-balance sm:text-5xl xl:text-6xl"
                            >
                                Snow removal,{' '}
                                <span className="bg-linear-to-r from-glacier-700 to-glacier-400 bg-clip-text text-transparent dark:from-glacier-300 dark:to-glacier-500">
                                    run like clockwork
                                </span>
                                .
                            </h1>
                            <p className="mt-5 text-lg leading-relaxed text-muted-foreground">
                                When the forecast turns at 2 a.m., your
                                operation shouldn't run on a whiteboard and a
                                group text. Trigger-based dispatch, smart
                                routes, photo proof on every pass, and invoices
                                that go out before the snow melts.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                                <Button asChild size="lg">
                                    <Link href={ROUTES.contact}>
                                        Start free trial
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <Link href={ROUTES.features}>
                                        See how it works
                                    </Link>
                                </Button>
                            </div>
                            <p className="mt-6 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                                Trusted on 4.2M properties · NET-14 billing ·
                                -30° tested
                            </p>
                        </div>
                        <DashboardMock />
                    </div>

                    {/* Proof: stats + trusted-by */}
                    <div className="mt-14 border-t border-border/60 pt-8 md:mt-16 lg:mt-20">
                        <dl className="grid max-w-2xl grid-cols-3 gap-4 sm:gap-6">
                            {heroStats.map((stat) => (
                                <div key={stat.label}>
                                    <dd className="text-2xl font-semibold tabular-nums md:text-3xl">
                                        {stat.value}
                                    </dd>
                                    <dt className="mt-1.5 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                                        {stat.label}
                                    </dt>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-10 font-mono text-[11px] tracking-[0.18em] text-muted-foreground uppercase">
                            Trusted across snow-country operations
                        </p>
                        <ul className="mt-4 flex flex-wrap items-center gap-x-8 gap-y-3">
                            {clientLogos.map((logo) => (
                                <li
                                    key={logo}
                                    className="text-sm font-semibold text-frost-500 opacity-70 transition-opacity hover:opacity-100 dark:text-frost-400"
                                >
                                    {logo}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>

            {/* 2 · Features grid */}
            <MarketingSection
                id="features"
                eyebrow="The platform"
                title="Everything the storm demands"
                description="If your storm nights run on a whiteboard, a group text, and the spreadsheet your accountant fears — this replaces all three. Dispatch, routing, proof of service, and billing in one system."
            >
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {homeFeatures.map((feature, index) => {
                        const Icon = feature.icon;
                        // Storm mode + billing carry the ember tint
                        const isEmber = index === 2 || index === 5;
                        return (
                            <li
                                key={feature.title}
                                className="rounded-xl border border-border bg-card p-6 shadow-frost-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-frost-md"
                            >
                                <span
                                    className={cn(
                                        'flex size-10 items-center justify-center rounded-lg',
                                        isEmber ? EMBER_TINT : GLACIER_TINT,
                                    )}
                                >
                                    <Icon
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                </span>
                                <h3 className="mt-4 font-semibold">
                                    {feature.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {feature.copy}
                                </p>
                            </li>
                        );
                    })}
                </ul>
                <div className="mt-10 flex justify-center">
                    <Button asChild variant="link">
                        <Link href={ROUTES.features}>
                            Explore all features
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </MarketingSection>

            {/* 3 · Storm mode band */}
            <section
                aria-labelledby="storm-mode-heading"
                className="relative overflow-hidden bg-cover bg-center py-20 md:py-28"
                style={{ backgroundImage: 'url(/images/plow-night.jpg)' }}
            >
                <div
                    aria-hidden="true"
                    className="absolute inset-0 bg-linear-to-b from-frost-950/85 via-frost-950/70 to-frost-950/90 dark:from-frost-950/90 dark:via-frost-950/80 dark:to-frost-950/95"
                />
                <div className="relative z-10 mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-2xl">
                        <p className="font-mono text-xs font-medium tracking-[0.18em] text-ember-400 uppercase">
                            Storm mode
                        </p>
                        <h2
                            id="storm-mode-heading"
                            className="mt-3 text-3xl font-semibold tracking-tight text-balance text-white md:text-4xl"
                        >
                            The forecast turns. One toggle, and the night
                            reorganizes itself.
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-frost-200">
                            When a storm escalates, every route needs to change
                            at once — and a whiteboard can't keep up. Storm mode
                            re-sorts every route by contract priority, escalates
                            the alerts that matter, and lights the dashboard in
                            ember for the decisions you make at 3 a.m.
                        </p>
                    </div>
                    <ul className="mt-10 grid gap-4 sm:grid-cols-3">
                        {STORM_FEATURES.map((feature) => {
                            const Icon = feature.icon;
                            return (
                                <li
                                    key={feature.title}
                                    className="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm"
                                >
                                    <div className="flex items-center gap-3">
                                        <span
                                            className={cn(
                                                'flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/10',
                                                feature.iconClass,
                                            )}
                                        >
                                            <Icon
                                                aria-hidden="true"
                                                className="size-4"
                                            />
                                        </span>
                                        <h3 className="text-sm font-semibold text-white">
                                            {feature.title}
                                        </h3>
                                    </div>
                                    <p className="mt-3 text-xs leading-relaxed text-frost-300">
                                        {feature.copy}
                                    </p>
                                </li>
                            );
                        })}
                    </ul>
                    <div className="mt-10">
                        <Link
                            href={ROUTES.features}
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-white/25 bg-white/10 px-6 text-sm font-medium text-white/90 backdrop-blur-sm transition-colors hover:bg-white/20 hover:text-white focus-visible:ring-[3px] focus-visible:ring-white/50 focus-visible:outline-none"
                        >
                            See storm mode in action
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* 4 · How it works */}
            <MarketingSection
                id="how-it-works"
                eyebrow="How it works"
                title="From first flake to final invoice"
            >
                <div className="relative">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute top-11 right-0 left-0 hidden border-t border-dashed border-border lg:block"
                    />
                    <ol className="grid gap-4 sm:gap-5 lg:grid-cols-3 lg:gap-6">
                        {HOW_IT_WORKS.map((step, index) => (
                            <StepCard
                                key={step.title}
                                step={step}
                                index={index}
                            />
                        ))}
                    </ol>
                </div>
            </MarketingSection>

            {/* 5 · Stats band */}
            <section
                aria-label="By the numbers"
                className="border-y border-border bg-card/40"
            >
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 md:py-12 lg:px-8">
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">
                        {companyStats.map((stat) => (
                            <div key={stat.label} className="text-center">
                                <dd className="text-2xl font-semibold tabular-nums md:text-3xl">
                                    {stat.value}
                                </dd>
                                <dt className="mt-1.5 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                                    {stat.label}
                                </dt>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            {/* 7 · Pricing preview */}
            <MarketingSection
                id="pricing"
                eyebrow="Pricing"
                title="Priced for the season, not per seat-per-snowflake"
                description="Simple plans that scale with your route book. Two months free on annual."
            >
                <ul className="grid gap-4 md:grid-cols-3 lg:gap-6">
                    {pricingTiers.map((tier) => (
                        <li
                            key={tier.name}
                            className={cn(
                                'relative flex flex-col rounded-xl border bg-card p-6 shadow-frost-xs',
                                tier.featured
                                    ? 'border-glacier-500/50 shadow-glow-cyan'
                                    : 'border-border',
                            )}
                        >
                            {tier.featured ? (
                                <Badge className="absolute -top-3 left-1/2 -translate-x-1/2 bg-glacier-600 text-white dark:bg-glacier-500 dark:text-glacier-950">
                                    Most popular
                                </Badge>
                            ) : null}
                            <h3 className="font-semibold">{tier.name}</h3>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {tier.tagline}
                            </p>
                            <p className="mt-5 flex items-baseline gap-1.5">
                                <span className="text-3xl font-semibold tabular-nums">
                                    ${tier.monthly}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    /month
                                </span>
                            </p>
                            <p className="mt-2 font-mono text-[11px] leading-relaxed text-muted-foreground">
                                {tier.limits}
                            </p>
                            <ul className="mt-5 space-y-2.5 border-t border-border pt-5">
                                {tier.features.map((feature) => (
                                    <li
                                        key={feature}
                                        className="flex items-start gap-2 text-sm"
                                    >
                                        <Check
                                            aria-hidden="true"
                                            className="mt-0.5 size-3.5 shrink-0 text-success"
                                        />
                                        <span>{feature}</span>
                                    </li>
                                ))}
                            </ul>
                            <div className="mt-auto pt-6">
                                <Button
                                    asChild
                                    variant={
                                        tier.featured ? 'default' : 'outline'
                                    }
                                    className="w-full"
                                >
                                    <Link href={tier.ctaTo}>{tier.cta}</Link>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
                <div className="mt-10 flex justify-center">
                    <Button asChild variant="link">
                        <Link href={ROUTES.pricing}>
                            Compare every plan
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </MarketingSection>

            {/* 8 · FAQ */}
            <MarketingSection
                id="faq"
                eyebrow="FAQ"
                title="Before the first flake"
                description="The questions owners and ops managers ask before their first dispatched storm."
            >
                <Accordion
                    type="single"
                    collapsible
                    className="mx-auto w-full max-w-3xl rounded-xl border border-border bg-card px-6 shadow-frost-xs"
                >
                    {homeFaqs.map((faq, index) => (
                        <AccordionItem key={faq.q} value={`item-${index}`}>
                            <AccordionTrigger className="md:text-base">
                                {faq.q}
                            </AccordionTrigger>
                            <AccordionContent className="leading-relaxed text-muted-foreground">
                                {faq.a}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </MarketingSection>

            {/* 9 · Final CTA */}
            <CTABand />
        </MarketingLayout>
    );
}
