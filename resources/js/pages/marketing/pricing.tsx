import { Link, usePage } from '@inertiajs/react';
import { Check, CheckCircle2, Minus } from 'lucide-react';
import * as React from 'react';
import {
    pricingFaqs,
    pricingTiers,
    type PricingTier,
} from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn, toUrl } from '@/lib/utils';
import { index as billingIndex } from '@/routes/billing';
import register from '@/routes/register';

/* ── Billing period toggle ──────────────────────────────────────── */
function BillingToggle({
    annual,
    onChange,
}: {
    annual: boolean;
    onChange: (next: boolean) => void;
}) {
    return (
        <div className="flex justify-center">
            <div className="relative">
                <Badge className="absolute -top-3 right-0 z-10 rounded-full border-success/40 bg-card text-success shadow-frost-xs">
                    2 months free
                </Badge>
                <div
                    role="group"
                    aria-label="Billing period"
                    className="inline-flex items-center gap-1 rounded-full border border-border bg-card p-1 shadow-frost-xs"
                >
                    <button
                        type="button"
                        aria-pressed={!annual}
                        onClick={() => onChange(false)}
                        className={cn(
                            'rounded-full px-5 py-1.5 text-sm font-medium transition-colors',
                            annual
                                ? 'text-muted-foreground hover:text-foreground'
                                : 'bg-primary text-primary-foreground shadow-xs',
                        )}
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        aria-pressed={annual}
                        onClick={() => onChange(true)}
                        className={cn(
                            'rounded-full px-5 py-1.5 text-sm font-medium transition-colors',
                            annual
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        Annual
                    </button>
                </div>
            </div>
        </div>
    );
}

/* ── Tier cards ─────────────────────────────────────────────────── */
function formatUsd(value: number): string {
    return `$${value.toLocaleString('en-US')}`;
}

function TierCard({
    tier,
    annual,
    ctaHref,
}: {
    tier: PricingTier;
    annual: boolean;
    ctaHref: string;
}) {
    const price = annual ? Math.round(tier.annual / 12) : tier.monthly;

    return (
        <div
            className={cn(
                'relative flex flex-col rounded-2xl border bg-card p-6 shadow-frost-xs md:p-7',
                tier.featured
                    ? 'border-glacier-500/50 shadow-glow-cyan'
                    : 'border-border',
            )}
        >
            {tier.featured ? (
                <Badge className="absolute -top-3 left-1/2 z-10 -translate-x-1/2 bg-glacier-600 text-white dark:bg-glacier-500 dark:text-glacier-950">
                    Most popular
                </Badge>
            ) : null}

            <h3 className="text-lg font-semibold">{tier.name}</h3>
            <p className="mt-1 text-xs text-muted-foreground">{tier.tagline}</p>

            <div className="mt-5 flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                <span
                    aria-hidden="true"
                    className="text-lg font-medium text-muted-foreground"
                >
                    $
                </span>
                <span className="text-4xl font-semibold tabular-nums">
                    {price}
                </span>
                <span className="text-sm text-muted-foreground">
                    {annual ? '/month, billed annually' : '/month'}
                </span>
            </div>
            <p className="mt-1.5 font-mono text-[11px] text-muted-foreground">
                {annual
                    ? `billed yearly (${formatUsd(tier.annual)})`
                    : 'billed monthly'}
            </p>
            <p className="mt-2 font-mono text-[11px] text-muted-foreground">
                {tier.limits}
            </p>

            <Separator className="my-5" />

            <ul className="flex flex-col gap-2.5">
                {tier.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2.5">
                        <Check
                            aria-hidden="true"
                            className="mt-0.5 size-4 shrink-0 text-success"
                        />
                        <span className="text-sm">{feature}</span>
                    </li>
                ))}
            </ul>

            <div className="mt-auto pt-6">
                <Button
                    asChild
                    variant={tier.featured ? 'default' : 'outline'}
                    className="w-full"
                >
                    <Link href={ctaHref}>{tier.cta}</Link>
                </Button>
            </div>
        </div>
    );
}

/* ── Feature comparison table ───────────────────────────────────── */
const tierNames = pricingTiers.map((tier) => tier.name);

type ComparisonValue = boolean | string;

interface ComparisonRow {
    feature: string;
    values: [ComparisonValue, ComparisonValue, ComparisonValue];
}

interface ComparisonGroup {
    group: string;
    rows: ComparisonRow[];
}

/* Values must match what ships: dashboards land on Pro+, payment
   notifications on Pro+ (launch commitment — verify before launch). */
const comparisonGroups: ComparisonGroup[] = [
    {
        group: 'Clients & contracts',
        rows: [
            { feature: 'Client directory', values: [true, true, true] },
            {
                feature: 'Seasonal contracts & services',
                values: [true, true, true],
            },
            { feature: 'Max clients', values: ['25', '150', 'Unlimited'] },
            { feature: 'Max users', values: ['2', '15', 'Unlimited'] },
        ],
    },
    {
        group: 'Payments & receipts',
        rows: [
            {
                feature: 'Payment tracking & mark-paid',
                values: [true, true, true],
            },
            {
                feature: 'Printable invoices & receipts',
                values: [true, true, true],
            },
            {
                feature: 'Email invoices & receipts',
                values: [true, true, true],
            },
            { feature: 'Payment notifications', values: [false, true, true] },
        ],
    },
    {
        group: 'Data & dashboards',
        rows: [
            { feature: 'Season CSV export', values: [true, true, true] },
            {
                feature: 'Revenue & client dashboard',
                values: [false, true, true],
            },
            {
                feature: 'Bilingual EN/FR interface',
                values: [true, true, true],
            },
        ],
    },
    {
        group: 'Support',
        rows: [
            {
                feature: 'Support',
                values: ['Email', 'Priority', 'Dedicated manager'],
            },
        ],
    },
];

function ComparisonCell({ value }: { value: ComparisonValue }) {
    if (value === true) {
        return (
            <CheckCircle2
                role="img"
                aria-label="Included"
                className="mx-auto size-4 text-success"
            />
        );
    }
    if (value === false) {
        return (
            <Minus
                role="img"
                aria-label="Not included"
                className="mx-auto size-4 text-muted-foreground/40"
            />
        );
    }
    return <span className="text-xs">{value}</span>;
}

function ComparisonSection() {
    return (
        <MarketingSection
            align="left"
            eyebrow="Compare"
            title="Every feature, side by side"
            description="The same back office on every plan — the differences come down to the size of your client book and the level of support."
        >
            <div className="overflow-hidden rounded-xl border border-border bg-card">
                <div className="scrollbar-frost overflow-x-auto">
                    <table className="w-full min-w-[640px] caption-bottom text-sm">
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead scope="col" className="h-11 px-4">
                                    Feature
                                </TableHead>
                                {tierNames.map((name) => (
                                    <TableHead
                                        key={name}
                                        scope="col"
                                        className={cn(
                                            'h-11 w-24 text-center',
                                            name === 'Pro' &&
                                                'bg-glacier-500/5 font-semibold text-glacier-700 dark:text-glacier-400',
                                        )}
                                    >
                                        {name}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {comparisonGroups.map((group) => (
                                <React.Fragment key={group.group}>
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell
                                            colSpan={4}
                                            className="bg-muted/50 px-4 py-2.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            {group.group}
                                        </TableCell>
                                    </TableRow>
                                    {group.rows.map((row) => (
                                        <TableRow key={row.feature}>
                                            <TableCell className="px-4 py-3 text-sm">
                                                {row.feature}
                                            </TableCell>
                                            {row.values.map((value, index) => (
                                                <TableCell
                                                    key={tierNames[index]}
                                                    className="w-24 py-3 text-center"
                                                >
                                                    <ComparisonCell
                                                        value={value}
                                                    />
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    ))}
                                </React.Fragment>
                            ))}
                        </TableBody>
                    </table>
                </div>
            </div>
        </MarketingSection>
    );
}

/* ── FAQ ────────────────────────────────────────────────────────── */
function FaqSection() {
    return (
        <MarketingSection
            eyebrow="Pricing FAQ"
            title="The fine print, unhidden"
        >
            <div className="mx-auto max-w-3xl rounded-xl border border-border bg-card px-5 md:px-6">
                <Accordion type="single" collapsible>
                    {pricingFaqs.map((faq) => (
                        <AccordionItem key={faq.q} value={faq.q}>
                            <AccordionTrigger>{faq.q}</AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {faq.a}
                            </AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        </MarketingSection>
    );
}

/* ── Page ────────────────────────────────────────────────────────── */
export default function Pricing() {
    const [annual, setAnnual] = React.useState(true);
    const { auth } = usePage().props;

    // Signed-in admins land on billing; everyone else starts a trial by
    // registering. "Talk to sales" stays a contact link...
    const ctaHref = toUrl(
        auth.user
            ? auth.can?.manageBilling
                ? billingIndex()
                : ROUTES.contact
            : register.store(),
    );

    return (
        <MarketingLayout title="Pricing — Frostline OPS" route={ROUTES.pricing}>
            <PageHero
                eyebrow="Pricing"
                title="Priced for the season"
                description="Plans that scale with your client book, not your headcount. Every tier includes the client directory, contracts, payment tracking, and invoices & receipts."
            />

            <section className="relative py-14 md:py-20">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <h2 className="sr-only">Plans</h2>
                    <BillingToggle annual={annual} onChange={setAnnual} />
                    <div className="mx-auto mt-12 grid max-w-5xl gap-4 md:grid-cols-3">
                        {pricingTiers.map((tier) => (
                            <TierCard
                                key={tier.name}
                                tier={tier}
                                annual={annual}
                                ctaHref={ctaHref}
                            />
                        ))}
                    </div>
                    <p className="mt-8 text-center font-mono text-xs text-muted-foreground">
                        All plans: 14-day free trial · no card required · cancel
                        anytime
                    </p>
                </div>
            </section>

            <ComparisonSection />

            <FaqSection />

            <CTABand
                title="Be ready before the first storm"
                description="Import your clients and set up your season's contracts and payment schedules now — so when the snow flies, the paperwork is already done."
                primaryLabel="Start free trial"
                primaryTo={ROUTES.contact}
                secondaryLabel="Talk to sales"
                secondaryTo={ROUTES.contact}
            />
        </MarketingLayout>
    );
}
