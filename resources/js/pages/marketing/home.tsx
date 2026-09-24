import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Building2,
    Check,
    CreditCard,
    FileSpreadsheet,
    Lock,
    Receipt,
    ShieldCheck,
    Snowflake,
    Sparkles,
    type LucideIcon,
} from 'lucide-react';
import {
    clientLogos,
    companyFacts,
    heroFacts,
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
            {/* Floating payment chip (straddles the frame's bottom-left corner) */}
            <div
                className="pointer-events-none absolute -bottom-5 -left-4 z-20 animate-drift"
                style={{ animationDelay: '-4s' }}
            >
                <span className="flex items-center gap-2 rounded-lg bg-ember px-3.5 py-2.5 text-sm font-medium text-ember-foreground shadow-glow-ember">
                    <Receipt className="size-4" />
                    Receipt printed · $300
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
                            app.frostline.ops/dashboard
                        </span>
                    </div>
                    <span className="w-10 shrink-0" />
                </div>

                <div className="space-y-4 p-4 sm:p-5">
                    {/* KPI row */}
                    <div className="grid grid-cols-3 gap-3">
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Collected
                            </p>
                            <p className="mt-1.5 text-lg font-semibold tabular-nums">
                                $41.2k
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Outstanding
                            </p>
                            <p className="mt-1.5 text-lg font-semibold tabular-nums">
                                $12.8k
                            </p>
                        </div>
                        <div className="rounded-lg border border-border bg-muted/40 p-3">
                            <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                                Contracts
                            </p>
                            <p className="mt-1.5 text-lg font-semibold tabular-nums">
                                96
                            </p>
                        </div>
                    </div>

                    {/* Revenue chart */}
                    <div className="overflow-hidden rounded-lg border border-border">
                        <div className="flex items-center justify-between gap-3 border-b border-border px-3 py-2">
                            <p className="text-xs font-semibold">
                                Revenue · season 2026–27
                            </p>
                            <p className="font-mono text-[10px] text-muted-foreground">
                                BY MONTH
                            </p>
                        </div>
                        <div className="flex h-20 items-end gap-1.5 px-3 py-3">
                            {[35, 55, 45, 70, 62, 82, 95].map(
                                (height, index) => (
                                    <div
                                        key={index}
                                        style={{ height: `${height}%` }}
                                        className="flex-1 rounded-t-sm bg-linear-to-t from-glacier-500/70 to-glacier-400/80"
                                    />
                                ),
                            )}
                        </div>
                    </div>

                    {/* Payments card */}
                    <div className="overflow-hidden rounded-lg border border-border">
                        <div className="flex items-center justify-between gap-3 border-b border-border px-3 py-2">
                            <p className="text-xs font-semibold">
                                Payments collected
                            </p>
                            <p className="font-mono text-[10px] text-muted-foreground">
                                76%
                            </p>
                        </div>
                        <div className="px-3 py-2.5">
                            <Progress value={76} className="h-1" />
                            <p className="mt-2 font-mono text-[10px] text-muted-foreground">
                                Deposit · installments · balance
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

/* ── Season workflow band bullets ────────────────────────────────── */
const SEASON_FEATURES: {
    icon: LucideIcon;
    iconClass: string;
    title: string;
    copy: string;
}[] = [
    {
        icon: Building2,
        iconClass: 'text-glacier-300',
        title: 'Set up in the fall',
        copy: 'Import your client list, write the season\u2019s contracts with itemized services, and set the payment schedule before the first snowfall.',
    },
    {
        icon: CreditCard,
        iconClass: 'text-ember-300',
        title: 'Track through the winter',
        copy: 'Mark payments paid with method and date as they come in — deposit, installments, balance — and print receipts without retyping a number.',
    },
    {
        icon: BarChart3,
        iconClass: 'text-glacier-300',
        title: 'Close out in spring',
        copy: 'The dashboard shows what was collected and what is outstanding all season, so the balance chase starts in March, not in May.',
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
        icon: FileSpreadsheet,
        tint: GLACIER_TINT,
        title: 'Import your clients',
        copy: 'Bring a CSV from any system — spreadsheets, property-management software, or lists you already keep. Your clients become records with addresses, phones, email, and notes, not rows in someone\u2019s memory.',
        caption: 'Setup: one afternoon',
    },
    {
        icon: Receipt,
        tint: EMBER_TINT,
        title: 'Write the season\u2019s contracts',
        copy: 'One contract per client per season, with itemized service lines — driveway, walkway, salting. The total is the sum of its lines, and payment slots are set when you save it.',
        caption: 'Contracts: itemized & totaled',
    },
    {
        icon: ShieldCheck,
        tint: GLACIER_TINT,
        title: 'Track payments to the balance',
        copy: 'As money arrives, mark the slot paid — the method and date are recorded, receipts are a click away, and the dashboard keeps collected vs. outstanding in view until the final balance clears.',
        caption: 'Payments: slot by slot',
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
    const { appUrl } = usePage().props;

    return (
        <MarketingLayout
            title="Frostline OPS — Snow Removal Contract & Payment Software"
            route={ROUTES.home}
            description="Snow removal business software for client management, seasonal contracts, payment tracking, invoices, receipts, and season reporting. Start a 14-day trial with Frostline OPS."
            structuredData={{
                '@context': 'https://schema.org',
                '@graph': [
                    {
                        '@type': 'SoftwareApplication',
                        name: 'Frostline OPS',
                        applicationCategory: 'BusinessApplication',
                        operatingSystem: 'Web',
                        description:
                            'Back-office software for snow removal businesses.',
                        url: new URL(ROUTES.home, `${appUrl}/`).toString(),
                    },
                    {
                        '@type': 'FAQPage',
                        mainEntity: homeFaqs.map((faq) => ({
                            '@type': 'Question',
                            name: faq.q,
                            acceptedAnswer: {
                                '@type': 'Answer',
                                text: faq.a,
                            },
                        })),
                    },
                ],
            }}
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
                                Season 2026–27 is booking — set up now
                            </Badge>
                            <h1
                                id="home-hero-heading"
                                className="mt-6 text-4xl font-semibold tracking-tighter text-balance sm:text-5xl xl:text-6xl"
                            >
                                Your season,{' '}
                                <span className="bg-linear-to-r from-glacier-700 to-glacier-400 bg-clip-text text-transparent dark:from-glacier-300 dark:to-glacier-500">
                                    organized to the last balance
                                </span>
                                .
                            </h1>
                            <p className="mt-5 text-lg leading-relaxed text-muted-foreground">
                                Snow removal contracts are sold in the fall,
                                serviced all winter, and paid in pieces.
                                Frostline OPS is the back office that keeps up:
                                a client directory, seasonal contracts with
                                itemized services, payment tracking slot by
                                slot, and receipts that go out before the snow
                                melts.
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
                                14-day free trial · no card required · cancel
                                anytime
                            </p>
                        </div>
                        <DashboardMock />
                    </div>

                    {/* Proof: facts + industries */}
                    <div className="mt-14 border-t border-border/60 pt-8 md:mt-16 lg:mt-20">
                        <dl className="grid max-w-2xl grid-cols-3 gap-4 sm:gap-6">
                            {heroFacts.map((fact) => (
                                <div key={fact.label}>
                                    <dd className="text-2xl font-semibold tabular-nums md:text-3xl">
                                        {fact.value}
                                    </dd>
                                    <dt className="mt-1.5 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                                        {fact.label}
                                    </dt>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-10 font-mono text-[11px] tracking-[0.18em] text-muted-foreground uppercase">
                            Built for snow-country contract businesses
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
                eyebrow="The product"
                title="Everything the season demands of the office"
                description="If your season runs on a binder, a spreadsheet, and the receipts in a shoebox — this replaces all three. Clients, contracts, payments, invoices, and dashboards in one system."
            >
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {homeFeatures.map((feature, index) => {
                        const Icon = feature.icon;
                        // Contracts + invoices carry the ember tint
                        const isEmber = index === 1 || index === 3;
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

            {/* 3 · Season workflow band */}
            <section
                aria-labelledby="season-workflow-heading"
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
                            The season, end to end
                        </p>
                        <h2
                            id="season-workflow-heading"
                            className="mt-3 text-3xl font-semibold tracking-tight text-balance text-white md:text-4xl"
                        >
                            Fall to spring, one system for the whole season.
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-frost-200">
                            The money side of snow removal has its own rhythm:
                            contracts signed before the first snowfall, payments
                            collected through the winter, balances chased in
                            spring. Frostline OPS follows that rhythm instead of
                            fighting it — every client, contract, and payment in
                            one place from setup to close-out.
                        </p>
                    </div>
                    <ul className="mt-10 grid gap-4 sm:grid-cols-3">
                        {SEASON_FEATURES.map((feature) => {
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
                            See the workflow in detail
                            <ArrowRight aria-hidden="true" className="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* 4 · How it works */}
            <MarketingSection
                id="how-it-works"
                eyebrow="How it works"
                title="From first import to final balance"
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

            {/* 5 · Facts band */}
            <section
                aria-label="By the numbers"
                className="border-y border-border bg-card/40"
            >
                <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 md:py-12 lg:px-8">
                    <dl className="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">
                        {companyFacts.map((fact) => (
                            <div key={fact.label} className="text-center">
                                <dd className="text-2xl font-semibold tabular-nums md:text-3xl">
                                    {fact.value}
                                </dd>
                                <dt className="mt-1.5 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                                    {fact.label}
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
                description="Simple plans that scale with your client book. One payment covers the season."
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
                                    {tier.annual === null
                                        ? '$0'
                                        : `$${tier.annual}`}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {tier.annual === null ? 'forever' : '/year'}
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
                description="The questions owners and office managers ask before their first organized season."
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
