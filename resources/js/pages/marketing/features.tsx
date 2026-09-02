import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Building2,
    Check,
    CreditCard,
    FileSpreadsheet,
    FileText,
    Mail,
    Receipt,
    ShieldCheck,
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

/* ── Visual 1 · Client directory ────────────────────────────────── */
const directoryClients = [
    {
        number: 'CL-0001',
        name: 'Gestion Summit Inc.',
        type: 'Commercial',
        active: true,
    },
    {
        number: 'CL-0002',
        name: 'M. Tremblay',
        type: 'Residential',
        active: true,
    },
    {
        number: 'CL-0003',
        name: 'Harborview Condos',
        type: 'Commercial',
        active: false,
    },
] as const;

function DirectoryVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between gap-3">
                <p className="font-mono text-xs font-medium tracking-[0.16em] text-muted-foreground uppercase">
                    Clients
                </p>
                <Badge
                    variant="outline"
                    className="border-info/25 bg-info/10 text-info"
                >
                    1 season · 3 records
                </Badge>
            </div>

            <div className="mt-4 flex flex-col gap-2.5">
                {directoryClients.map((client) => (
                    <div
                        key={client.number}
                        className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 px-3.5 py-2.5"
                    >
                        <div className="min-w-0">
                            <p className="truncate font-mono text-xs font-medium">
                                {client.name}
                            </p>
                            <p className="mt-0.5 text-[11px] text-muted-foreground">
                                {client.number} · {client.type}
                            </p>
                        </div>
                        <Badge
                            variant="outline"
                            className={cn(
                                'shrink-0',
                                client.active
                                    ? 'border-success/25 bg-success/10 text-success'
                                    : 'border-border bg-muted text-muted-foreground',
                            )}
                        >
                            {client.active ? 'Contracted' : 'No contract'}
                        </Badge>
                    </div>
                ))}
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-3.5">
                <div className="flex items-center gap-2">
                    <Building2
                        aria-hidden="true"
                        className="size-4 text-glacier-600 dark:text-glacier-400"
                    />
                    <p className="font-mono text-xs text-muted-foreground">
                        Address · phone · email · notes
                    </p>
                </div>
            </div>
        </div>
    );
}

/* ── Visual 2 · Contract with service lines ─────────────────────── */
const contractServices = [
    { label: 'Driveway — 2 parking spaces', amount: '$450' },
    { label: 'Walkway — front entrance', amount: '$180' },
    { label: 'Salting — on call', amount: '$220' },
] as const;

function ContractVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold">
                        Seasonal contract · 2026–27
                    </p>
                    <p className="mt-0.5 font-mono text-[11px] text-muted-foreground">
                        Gestion Summit Inc.
                    </p>
                </div>
                <Badge
                    variant="outline"
                    className="border-ember-500/30 bg-ember-500/10 text-ember-700 dark:text-ember-400"
                >
                    3 services
                </Badge>
            </div>

            <div className="mt-4 flex flex-col gap-2.5 rounded-lg border border-border bg-muted/30 p-3.5">
                {contractServices.map((service) => (
                    <div
                        key={service.label}
                        className="flex items-baseline justify-between gap-4"
                    >
                        <p className="font-mono text-xs text-muted-foreground">
                            {service.label}
                        </p>
                        <p className="font-mono text-xs font-medium">
                            {service.amount}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-4">
                <p className="font-mono text-[11px] text-muted-foreground">
                    Contract total
                </p>
                <p className="text-lg font-semibold tabular-nums">$850</p>
            </div>
        </div>
    );
}

/* ── Visual 3 · Payment slots ───────────────────────────────────── */
const paymentSlots = [
    { slot: 'Deposit', amount: '$200', status: 'paid' },
    { slot: '1st installment', amount: '$300', status: 'paid' },
    { slot: '2nd installment', amount: '$250', status: 'due' },
    { slot: 'Balance', amount: '$100', status: 'due' },
] as const;

const slotStatusStyles = {
    paid: {
        label: 'Paid · cheque',
        dot: 'bg-success',
        text: 'text-success',
    },
    due: {
        label: 'Not yet paid',
        dot: 'bg-muted-foreground/40',
        text: 'text-muted-foreground',
    },
} as const;

function PaymentsVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-semibold">Payments · contract</p>
                <Badge
                    variant="outline"
                    className="border-glacier-500/30 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400"
                >
                    2 of 4 collected
                </Badge>
            </div>

            <ol className="mt-4 flex flex-col gap-2.5">
                {paymentSlots.map((slot) => {
                    const status =
                        slotStatusStyles[slot.status as 'paid' | 'due'];
                    return (
                        <li
                            key={slot.slot}
                            className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 px-3.5 py-2.5"
                        >
                            <span className="min-w-0 flex-1 truncate font-mono text-xs font-medium">
                                {slot.slot}
                                <span className="ml-2 text-muted-foreground">
                                    {slot.amount}
                                </span>
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
                        $500 collected of $850
                    </p>
                    <p className="font-mono text-xs font-medium">59%</p>
                </div>
                <Progress
                    value={59}
                    aria-label="Payments collected: 500 of 850 dollars"
                    className="mt-2.5 h-1.5"
                />
            </div>
        </div>
    );
}

/* ── Visual 4 · Invoice & receipt ───────────────────────────────── */
const receiptLines = [
    { label: 'Deposit — received 12 Nov', amount: '$200' },
    { label: '1st installment — received 15 Jan', amount: '$300' },
] as const;

function InvoiceVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ember-500/10 text-ember-600 dark:text-ember-400">
                    <Receipt aria-hidden="true" className="size-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-sm font-medium">Receipt · contract</p>
                    <p className="mt-0.5 font-mono text-[11px] text-muted-foreground">
                        Gestion Summit Inc. · 2 payments
                    </p>
                </div>
            </div>

            <div className="mt-4 flex flex-col gap-2.5 rounded-lg border border-border bg-muted/30 p-3.5">
                {receiptLines.map((line) => (
                    <div
                        key={line.label}
                        className="flex items-baseline justify-between gap-4"
                    >
                        <p className="font-mono text-xs text-muted-foreground">
                            {line.label}
                        </p>
                        <p className="font-mono text-xs font-medium">
                            {line.amount}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-4">
                <div className="flex gap-1.5">
                    <span
                        className={cn(
                            buttonVariants({ variant: 'outline' }),
                            'h-9 px-3 text-xs',
                        )}
                    >
                        <FileText aria-hidden="true" className="size-3.5" />
                        Print
                    </span>
                    <span
                        className={cn(
                            buttonVariants({ variant: 'outline' }),
                            'h-9 px-3 text-xs',
                        )}
                    >
                        <Mail aria-hidden="true" className="size-3.5" />
                        Email
                    </span>
                </div>
                <p className="font-mono text-[11px] text-muted-foreground">
                    Amounts from the contract
                </p>
            </div>
        </div>
    );
}

/* ── Visual 5 · Dashboard ───────────────────────────────────────── */
function DashboardVisual() {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-md">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-semibold">Season dashboard</p>
                <Badge
                    variant="outline"
                    className="border-success/25 bg-success/10 text-success"
                >
                    Live totals
                </Badge>
            </div>

            {/* KPI row */}
            <div className="mt-4 grid grid-cols-3 gap-3">
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
                        Clients
                    </p>
                    <p className="mt-1.5 text-lg font-semibold tabular-nums">
                        118
                    </p>
                </div>
            </div>

            {/* Decorative revenue chart */}
            <div
                aria-hidden="true"
                className="mt-4 rounded-lg border border-border bg-muted/30 p-3"
            >
                <p className="font-mono text-[10px] tracking-wider text-muted-foreground uppercase">
                    Revenue by month
                </p>
                <div className="mt-3 flex h-24 items-end gap-1.5">
                    {[35, 55, 45, 70, 62, 82, 95].map((height, index) => (
                        <div
                            key={index}
                            style={{ height: `${height}%` }}
                            className="flex-1 rounded-t-sm bg-linear-to-t from-glacier-500/70 to-glacier-400/80"
                        />
                    ))}
                </div>
            </div>

            <div className="mt-4 flex items-center justify-between border-t border-border pt-3.5">
                <div className="flex items-center gap-2">
                    <BarChart3
                        aria-hidden="true"
                        className="size-4 text-glacier-600 dark:text-glacier-400"
                    />
                    <p className="font-mono text-xs text-muted-foreground">
                        Revenue & client growth
                    </p>
                </div>
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

/* NOTE: The "print or email" item below is a launch commitment — it must
   ship before this page goes live. The season CSV export is live on the
   contracts page of the app. */
const featureRows: FeatureRowData[] = [
    {
        icon: Building2,
        eyebrow: '01 · Clients',
        title: 'A client directory that ends the phone book',
        copy: 'Every snow business runs on the same fragile system: a binder, a spreadsheet, and one person who remembers which entrance has the hidden fire hydrant. Frostline OPS gives every client a record — commercial or residential, billing and postal address, phone numbers, email, and the notes that only you know.',
        bullets: [
            'Commercial & residential clients',
            'Billing & postal addresses, phones, email',
            'Free-form remarks per client',
        ],
        tint: 'glacier',
        Visual: DirectoryVisual,
    },
    {
        icon: FileSpreadsheet,
        eyebrow: '02 · Contracts',
        title: 'Seasonal contracts, written down',
        copy: 'One contract per client per season, made of itemized service lines — driveway, walkway, salting, whatever you actually sell. The total is the sum of its lines, so the number you and the client agreed on is the number in the system. When someone asks what they paid for, the answer is a list, not a shrug.',
        bullets: [
            'One contract per client per season',
            'Itemized service lines with amounts',
            'Contract totals computed from lines',
        ],
        tint: 'ember',
        Visual: ContractVisual,
    },
    {
        icon: CreditCard,
        eyebrow: '03 · Payments',
        title: 'Payment tracking without the shoebox',
        copy: 'Snow contracts are paid in pieces — a deposit in October, installments through the winter, the balance in spring. Each piece gets a slot on the contract, marked paid with the method and date as the money arrives. The question "who still owes us?" stops requiring an afternoon of reconciliation.',
        bullets: [
            'Deposit, installments & balance slots',
            'Method & date recorded when marked paid',
            'At-a-glance collected vs. outstanding',
        ],
        tint: 'glacier',
        Visual: PaymentsVisual,
    },
    {
        icon: Receipt,
        eyebrow: '04 · Invoices & receipts',
        title: 'Invoices and receipts, from the numbers you already have',
        copy: 'Retyping amounts into a word-processor template is how errors — and arguments — start. Print or email an invoice or payment receipt straight from the contract; the amounts come from the services and payments already recorded, so what the client receives matches what you track.',
        bullets: [
            'Printable invoices & payment receipts',
            'Email directly from the contract',
            'Amounts drawn from recorded services & payments',
        ],
        tint: 'ember',
        Visual: InvoiceVisual,
    },
    {
        icon: BarChart3,
        eyebrow: '05 · Dashboard',
        title: 'See the season before it ends',
        copy: 'Most snow businesses discover their collections problem in April, when it is too late to fix it. The dashboard charts revenue and client growth as the season unfolds, so you can see where the money actually is — collected, outstanding, and by which clients — while there is still snow on the ground.',
        bullets: [
            'Revenue chart for the season',
            'Client growth over time',
            'Collected vs. outstanding at a glance',
        ],
        tint: 'glacier',
        Visual: DashboardVisual,
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

/* ── Also-included strip ────────────────────────────────────────── */
const alsoIncluded: { name: string; icon: LucideIcon }[] = [
    { name: 'Season CSV export', icon: FileSpreadsheet },
    { name: 'Payment notifications', icon: Mail },
    { name: 'Team & roles', icon: ShieldCheck },
    { name: 'Bilingual EN/FR', icon: FileText },
    { name: 'Dark mode', icon: BarChart3 },
];

function AlsoIncludedStrip() {
    return (
        <MarketingSection
            eyebrow="Also included"
            title="The rest of the back office"
            className="py-12 md:py-16"
        >
            <div className="flex flex-wrap items-center justify-center gap-2.5">
                {alsoIncluded.map((item) => {
                    const Icon = item.icon;
                    return (
                        <span
                            key={item.name}
                            className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm font-medium transition hover:border-glacier-500/40 hover:shadow-frost-xs"
                        >
                            <Icon
                                aria-hidden="true"
                                className="size-4 text-glacier-600 dark:text-glacier-400"
                            />
                            {item.name}
                        </span>
                    );
                })}
            </div>
            <p className="mt-6 text-center font-mono text-xs tracking-[0.14em] text-muted-foreground uppercase">
                On every plan — no feature gates on the basics
            </p>
        </MarketingSection>
    );
}

/* ── Scope note ─────────────────────────────────────────────────── */
function ScopeNote() {
    return (
        <MarketingSection
            eyebrow="Honest scope"
            title="What we deliberately don't do (yet)"
            className="py-12 md:py-16"
        >
            <div className="mx-auto max-w-2xl rounded-xl border border-border bg-card px-5 text-center md:px-6">
                <p className="text-sm leading-relaxed text-muted-foreground">
                    Frostline OPS is a back office, not a dispatch system.
                    Weather-triggered dispatch, route optimization, a driver
                    app, photo proof-of-service, and a client portal are on our
                    roadmap — but they are not in the product today, and we
                    would rather you know that now than discover it in January.
                    If running crews in the field is your priority this season,
                    we will happily tell you we are not your tool — yet.
                </p>
            </div>
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
                eyebrow="The product"
                title="The back office your snow business runs on"
                description="Clients, contracts, payments, invoices — the five systems that replace the binder, the spreadsheet, and the shoebox of receipts. Built for the season, from first contract to final balance."
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

            <AlsoIncludedStrip />

            <ScopeNote />

            <CTABand
                title="Set up before the snow flies"
                description="Bring your client list — we'll import it and set up your first season's contracts and payment schedules with you."
            />
        </MarketingLayout>
    );
}
