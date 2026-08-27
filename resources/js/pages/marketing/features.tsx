import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Building,
    Camera,
    Check,
    Cloudy,
    CreditCard,
    Download,
    FileCheck,
    MessageSquare,
    Radar,
    Receipt,
    Route,
    Smartphone,
    type LucideIcon,
} from 'lucide-react';
import * as React from 'react';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

/* ── Literal tint classes (never constructed at runtime) ─────────── */
const iconTints = {
    glacier: 'bg-glacier-500/10 text-glacier-600 dark:text-glacier-400',
    ember: 'bg-ember-500/10 text-ember-600 dark:text-ember-400',
} as const;

type IconTint = keyof typeof iconTints;

/* ── Visual 1 · Trigger configuration ───────────────────────────── */
const triggerProperties = [
    {
        address: '418 Summit Ave',
        depth: '2.5 cm',
        active: true,
        note: 'Priority 1 · plaza',
    },
    {
        address: '82 Industrial Pkwy',
        depth: '5 cm',
        active: true,
        note: 'Priority 2 · warehouse',
    },
    {
        address: '1201 Nicollet Ave',
        depth: '10 cm',
        active: false,
        note: 'paused · resumes Dec 1',
    },
];

/* Static look-alike of the Switch — decorative state indicator only,
   so the mock contains no focusable controls. */
function SwitchMock({ on }: { on: boolean }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-transparent shadow-xs',
                on ? 'bg-primary' : 'bg-input',
            )}
        >
            <span
                className={cn(
                    'pointer-events-none block size-4 rounded-full transition-transform',
                    on
                        ? 'translate-x-[calc(100%-2px)] bg-background dark:bg-primary-foreground'
                        : 'translate-x-0 bg-background dark:bg-foreground',
                )}
            />
        </span>
    );
}

function TriggerVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <p className="font-mono text-xs font-medium tracking-[0.16em] text-muted-foreground uppercase">
                Trigger configuration
            </p>

            <div className="mt-4 flex flex-col gap-2.5">
                {triggerProperties.map((property) => (
                    <div
                        key={property.address}
                        className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 px-3.5 py-2.5"
                    >
                        <div className="min-w-0">
                            <p className="truncate font-mono text-xs font-medium">
                                {property.address}
                            </p>
                            <p className="mt-0.5 text-[11px] text-muted-foreground">
                                {property.note}
                            </p>
                        </div>
                        <div className="flex shrink-0 items-center gap-3">
                            <span className="rounded-full bg-glacier-500/10 px-2.5 py-1 font-mono text-xs text-glacier-700 dark:text-glacier-400">
                                {property.depth}
                            </span>
                            <SwitchMock on={property.active} />
                        </div>
                    </div>
                ))}
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-3.5">
                <div className="flex items-center gap-2">
                    <Radar
                        aria-hidden="true"
                        className="size-4 text-glacier-600 dark:text-glacier-400"
                    />
                    <p className="font-mono text-xs text-muted-foreground">
                        Snowfall 1.4 cm/hr · rising
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className="border-info/25 bg-info/10 text-info"
                >
                    Armed
                </Badge>
            </div>
        </div>
    );
}

/* ── Visual 2 · Route sheet ─────────────────────────────────────── */
const stopStatuses = {
    completed: {
        label: 'Completed',
        dot: 'bg-success',
        text: 'text-success',
    },
    active: {
        label: 'In progress',
        dot: 'bg-glacier-500',
        text: 'text-glacier-600 dark:text-glacier-400',
    },
    enroute: { label: 'En route', dot: 'bg-info', text: 'text-info' },
    scheduled: {
        label: 'Scheduled',
        dot: 'bg-muted-foreground/40',
        text: 'text-muted-foreground',
    },
} as const;

type StopStatus = keyof typeof stopStatuses;

const routeStops: { address: string; status: StopStatus }[] = [
    { address: '706 Birchwood Ln', status: 'completed' },
    { address: '118 Maple Grove Dr', status: 'active' },
    { address: '44 Frontage Rd', status: 'enroute' },
    { address: '9 Old Mill Ct', status: 'scheduled' },
];

function RouteVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold">
                        Route 12 · Northside
                    </p>
                    <p className="mt-0.5 font-mono text-[11px] text-muted-foreground">
                        Crew 4 · truck 12
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className="border-ember-500/30 bg-ember-500/10 text-ember-700 dark:text-ember-400"
                >
                    Priority re-sort
                </Badge>
            </div>

            <ol className="mt-4 flex flex-col gap-2.5">
                {routeStops.map((stop, index) => {
                    const status = stopStatuses[stop.status];
                    return (
                        <li
                            key={stop.address}
                            className="flex items-center gap-3 rounded-lg border border-border bg-muted/30 px-3.5 py-2.5"
                        >
                            <span className="flex size-6 shrink-0 items-center justify-center rounded-full border border-border font-mono text-[11px] text-muted-foreground">
                                {index + 1}
                            </span>
                            <span className="min-w-0 flex-1 truncate font-mono text-xs font-medium">
                                {stop.address}
                            </span>
                            <span
                                className={cn(
                                    'flex shrink-0 items-center gap-1.5 text-xs font-medium',
                                    status.text,
                                )}
                            >
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'size-1.5 rounded-full',
                                        status.dot,
                                    )}
                                />
                                {status.label}
                            </span>
                        </li>
                    );
                })}
            </ol>

            <div className="mt-4 border-t border-border pt-3.5">
                <div className="flex items-center justify-between">
                    <p className="font-mono text-xs text-muted-foreground">
                        9 stops · 14.2 km · ETA 12 min
                    </p>
                    <p className="font-mono text-xs font-medium">56%</p>
                </div>
                <Progress
                    value={56}
                    aria-label="Route 12 progress: 5 of 9 stops complete"
                    className="mt-2.5 h-1.5"
                />
            </div>
        </div>
    );
}

/* ── Visual 3 · Driver app phone mock ───────────────────────────── */
function DriverAppVisual() {
    return (
        <div className="mx-auto w-64 rounded-[2rem] border border-border bg-card p-3 shadow-frost-lg">
            <div className="flex items-center justify-between px-1.5 pt-1">
                <p className="font-mono text-[11px] font-medium">3:12</p>
                <div aria-hidden="true" className="flex items-end gap-0.5">
                    <span className="h-1 w-0.5 rounded-full bg-foreground/60" />
                    <span className="h-1.5 w-0.5 rounded-full bg-foreground/60" />
                    <span className="h-2 w-0.5 rounded-full bg-foreground/60" />
                    <span className="h-2.5 w-0.5 rounded-full bg-foreground/25" />
                </div>
            </div>

            <div className="mt-3 rounded-2xl bg-muted/40 p-3.5">
                <p className="font-mono text-[10px] font-medium tracking-[0.16em] text-muted-foreground uppercase">
                    Next stop
                </p>
                <p className="mt-1.5 truncate font-mono text-sm font-medium">
                    118 Maple Grove Dr
                </p>
                <Badge
                    variant="outline"
                    className="mt-2.5 border-info/25 bg-info/10 text-info"
                >
                    En route
                </Badge>
                <div className="mt-3 flex gap-1.5">
                    <Badge
                        variant="outline"
                        className="px-2 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Plow
                    </Badge>
                    <Badge
                        variant="outline"
                        className="px-2 font-mono text-[10px] tracking-wider uppercase"
                    >
                        Salt
                    </Badge>
                </div>
            </div>

            <div className="mt-3 flex flex-col gap-2">
                <span className={cn(buttonVariants(), 'h-12 w-full text-sm')}>
                    Arrived · start pass
                </span>
                <span
                    className={cn(
                        buttonVariants({ variant: 'outline' }),
                        'h-12 w-full text-sm',
                    )}
                >
                    <Camera aria-hidden="true" className="size-4" />
                    Photo proof
                </span>
            </div>

            <p className="mt-3 pb-1 text-center font-mono text-[10px] text-muted-foreground">
                Sync queued · offline
            </p>
        </div>
    );
}

/* ── Visual 4 · Pass log timeline ───────────────────────────────── */
const passEvents = [
    {
        time: '01:58',
        label: 'Plow pass complete',
        caption: '44.981° N, 93.258° W · Lot A',
        verified: false,
    },
    {
        time: '02:17',
        label: 'Salt applied · 220 kg',
        caption: '44.981° N, 93.258° W · Driveways',
        verified: false,
    },
    {
        time: '02:41',
        label: 'Plow pass complete',
        caption: '44.978° N, 93.265° W · Lot B',
        verified: true,
    },
] as const;

function ProofVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between">
                <p className="text-sm font-semibold">Pass log · JOB-2041</p>
                <p className="font-mono text-[11px] text-muted-foreground">
                    Crew 4 · Feb 3
                </p>
            </div>

            <div className="relative mt-5">
                <div
                    aria-hidden="true"
                    className="absolute top-4 bottom-4 left-[4.25rem] w-px bg-border"
                />
                <ol className="flex flex-col gap-5">
                    {passEvents.map((event) => (
                        <li key={event.time} className="flex items-start gap-3">
                            <span className="w-10 shrink-0 pt-2 font-mono text-xs text-muted-foreground">
                                {event.time}
                            </span>
                            {/* Opaque card base + ring masks the timeline line; inner span carries the tint */}
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-card ring-4 ring-card">
                                <span className="flex size-8 items-center justify-center rounded-lg bg-glacier-500/10 text-glacier-600 dark:text-glacier-400">
                                    <Camera
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </span>
                            </span>
                            <div className="min-w-0 pt-0.5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-sm font-medium">
                                        {event.label}
                                    </p>
                                    {event.verified ? (
                                        <Badge
                                            variant="outline"
                                            className="border-glacier-500/30 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400"
                                        >
                                            Verified
                                        </Badge>
                                    ) : null}
                                </div>
                                <p className="mt-1 font-mono text-[11px] text-muted-foreground">
                                    {event.caption}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </div>
    );
}

/* ── Visual 5 · Invoice ─────────────────────────────────────────── */
const invoiceItems = [
    { label: 'Plow pass — driveways ×2', amount: '$180' },
    { label: 'Salting — lot B · 220 kg', amount: '$410' },
    { label: 'Priority dispatch surcharge', amount: '$690' },
];

function InvoiceVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ember-500/10 text-ember-600 dark:text-ember-400">
                    <Receipt aria-hidden="true" className="size-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-sm font-medium">Invoice #FL-2043</p>
                    <p className="mt-0.5 font-mono text-[11px] text-muted-foreground">
                        Issued 06:12 · 2h after final pass
                    </p>
                </div>
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-4">
                <p className="text-2xl font-semibold tabular-nums">
                    1,280.00{' '}
                    <span className="text-sm font-normal text-muted-foreground">
                        USD
                    </span>
                </p>
                <Badge
                    variant="outline"
                    className="border-success/25 bg-success/10 text-success"
                >
                    Paid
                </Badge>
            </div>

            <div className="mt-4 flex flex-col gap-2.5 rounded-lg border border-border bg-muted/30 p-3.5">
                {invoiceItems.map((item) => (
                    <div
                        key={item.label}
                        className="flex items-baseline justify-between gap-4"
                    >
                        <p className="font-mono text-xs text-muted-foreground">
                            {item.label}
                        </p>
                        <p className="font-mono text-xs font-medium">
                            {item.amount}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-4">
                <span
                    className={cn(
                        buttonVariants({ variant: 'outline', size: 'sm' }),
                    )}
                >
                    <Download aria-hidden="true" className="size-3.5" />
                    View invoice
                </span>
                <p className="font-mono text-[11px] text-muted-foreground">
                    NET-14 · auto-sent
                </p>
            </div>
        </div>
    );
}

/* ── Deep-dive rows ─────────────────────────────────────────────── */
interface FeatureRowData {
    icon: LucideIcon;
    eyebrow: string;
    title: string;
    copy: string;
    bullets: string[];
    tint: IconTint;
    Visual: React.ComponentType;
}

const featureRows: FeatureRowData[] = [
    {
        icon: Radar,
        eyebrow: '01 · Dispatch',
        title: 'Weather-triggered dispatch',
        copy: 'Every property carries its own trigger depth, armed by pavement sensors and hyperlocal weather feeds. The moment accumulation crosses the line, Frostline dispatches the closest crew — before the roads get dangerous, not after the phones start ringing.',
        bullets: [
            'Per-contract trigger depths',
            'Pavement-temperature aware',
            'Full manual override',
        ],
        tint: 'glacier',
        Visual: TriggerVisual,
    },
    {
        icon: Route,
        eyebrow: '02 · Routes',
        title: 'Smart route optimization',
        copy: 'The optimizer sequences every stop by priority, fuel, and shift hours — not just shortest distance. And when a truck drops out mid-storm, the remaining stops are re-sorted and pushed to every driver in seconds.',
        bullets: [
            'Priority-aware sequencing',
            'Live re-route on breakdown',
            'Fuel and shift-hour constraints',
        ],
        tint: 'ember',
        Visual: RouteVisual,
    },
    {
        icon: Smartphone,
        eyebrow: '03 · Driver app',
        title: 'Driver-first mobile app',
        copy: 'Offline-first and built for gloves, glare, and -30° mornings. From arrival to photo proof, a full stop takes three taps or fewer — everything else syncs when signal returns.',
        bullets: [
            'Works in dead zones',
            'Glove-friendly 44px+ targets',
            'Photo + timestamp in one tap',
        ],
        tint: 'glacier',
        Visual: DriverAppVisual,
    },
    {
        icon: Camera,
        eyebrow: '04 · Proof',
        title: 'Proof of service',
        copy: 'Every pass ends with a geo-stamped photo and a field-verified timestamp, attached to the exact invoice line it belongs to. When a property manager asks whether lot B was actually cleared, the answer is a photo — not a phone call.',
        bullets: [
            'Dispute-ending photo trail',
            'Geo + time verified in the field, not on upload',
            'Client portal access',
        ],
        tint: 'ember',
        Visual: ProofVisual,
    },
    {
        icon: Receipt,
        eyebrow: '05 · Billing',
        title: 'Billing that keeps up',
        copy: 'Per-storm, seasonal, and per-push contracts compile into invoices within 24 hours of the final pass — photos, salt usage, and surcharges itemized. NET-14 is the standard, not an upgrade.',
        bullets: [
            'Mixed contract types per client',
            'Photos + salt usage itemized',
            'QuickBooks + Xero sync',
        ],
        tint: 'glacier',
        Visual: InvoiceVisual,
    },
];

function FeatureRow({ row, index }: { row: FeatureRowData; index: number }) {
    const { icon: Icon, Visual, tint } = row;
    const reversed = index % 2 === 1;

    return (
        <section
            className={cn(reversed && 'border-y border-border bg-card/40')}
        >
            <div className="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div className={cn(reversed && 'lg:order-2')}>
                    <span
                        className={cn(
                            'flex size-11 items-center justify-center rounded-xl',
                            iconTints[tint],
                        )}
                    >
                        <Icon aria-hidden="true" className="size-5" />
                    </span>
                    <p className="mt-5 font-mono text-xs font-medium tracking-[0.18em] text-glacier-700 uppercase dark:text-glacier-400">
                        {row.eyebrow}
                    </p>
                    <h3 className="mt-3 text-2xl font-semibold tracking-tight text-balance md:text-3xl">
                        {row.title}
                    </h3>
                    <p className="mt-4 leading-relaxed text-muted-foreground">
                        {row.copy}
                    </p>
                    <ul className="mt-6 flex flex-col gap-3">
                        {row.bullets.map((bullet) => (
                            <li
                                key={bullet}
                                className="flex items-center gap-2.5"
                            >
                                <Check
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-success"
                                />
                                <span className="text-sm">{bullet}</span>
                            </li>
                        ))}
                    </ul>
                </div>
                <div className={cn(reversed && 'lg:order-1')}>
                    <Visual />
                </div>
            </div>
        </section>
    );
}

/* ── Integrations strip ─────────────────────────────────────────── */
const integrations: { name: string; icon: LucideIcon }[] = [
    { name: 'QuickBooks', icon: FileCheck },
    { name: 'Xero', icon: BarChart3 },
    { name: 'OpenWeather', icon: Cloudy },
    { name: 'Twilio', icon: MessageSquare },
    { name: 'Stripe', icon: CreditCard },
    { name: 'Gusto', icon: Building },
];

function IntegrationsStrip() {
    return (
        <MarketingSection
            eyebrow="Connections"
            title="Plays well with your back office"
            className="py-12 md:py-16"
        >
            <div className="flex flex-wrap items-center justify-center gap-2.5">
                {integrations.map((integration) => {
                    const Icon = integration.icon;
                    return (
                        <span
                            key={integration.name}
                            className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm font-medium transition hover:border-glacier-500/40 hover:shadow-frost-xs"
                        >
                            <Icon
                                aria-hidden="true"
                                className="size-4 text-glacier-600 dark:text-glacier-400"
                            />
                            {integration.name}
                        </span>
                    );
                })}
            </div>
            <p className="mt-6 text-center font-mono text-xs tracking-[0.14em] text-muted-foreground uppercase">
                API + webhooks on Fleet plans
            </p>
        </MarketingSection>
    );
}

/* ── Page ────────────────────────────────────────────────────────── */
export default function Features() {
    return (
        <MarketingLayout
            title="Features — Frostline OPS"
            route={ROUTES.features}
        >
            <PageHero
                eyebrow="The platform"
                title="Built for the night the forecast turns"
                description="Six systems that replace the whiteboard, the group chat, and the shoebox of receipts — from the first flake to the final invoice."
            >
                <Button asChild size="lg">
                    <Link href={ROUTES.contact}>
                        Start free trial
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
                <Button asChild size="lg" variant="outline">
                    <Link href={ROUTES.pricing}>See pricing</Link>
                </Button>
            </PageHero>

            <div>
                <h2 className="sr-only">Product deep dives</h2>
                {featureRows.map((row, index) => (
                    <FeatureRow key={row.eyebrow} row={row} index={index} />
                ))}
            </div>

            <IntegrationsStrip />

            <CTABand
                title="See it on your routes"
                description="Bring your property list to a 20-minute demo — we'll import it live and route your first storm before the call ends."
            />
        </MarketingLayout>
    );
}
