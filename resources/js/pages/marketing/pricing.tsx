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
import { usePublicCopy } from '@/hooks/use-public-copy';
import { cn, toUrl } from '@/lib/utils';
import { index as billingIndex } from '@/routes/billing';
import register from '@/routes/register';

/* ── Tier cards ─────────────────────────────────────────────────── */
function TierCard({ tier, ctaHref }: { tier: PricingTier; ctaHref: string }) {
    const { copy, locale } = usePublicCopy();

    const isFree = tier.annual === null;

    return (
        <div
            className={cn(
                'relative flex flex-col rounded-2xl border bg-card p-6 shadow-frost-xs md:p-7',
                tier.featured
                    ? 'border-glacier-500/50 shadow-[var(--preview-elevation)]'
                    : 'border-border',
            )}
        >
            {tier.featured ? (
                <Badge className="absolute -top-3 left-1/2 z-10 -translate-x-1/2 bg-glacier-600 text-white dark:bg-glacier-500 dark:text-glacier-950">
                    {copy('Most popular')}
                </Badge>
            ) : null}

            <h3 className="text-lg font-semibold">{copy(tier.name)}</h3>
            <p className="mt-1 text-sm text-muted-foreground">
                {copy(tier.tagline)}
            </p>

            <div className="mt-5 flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
                <span className="text-4xl font-semibold tabular-nums">
                    {new Intl.NumberFormat(
                        locale === 'fr' ? 'fr-CA' : 'en-CA',
                        {
                            style: 'currency',
                            currency: 'CAD',
                            maximumFractionDigits: 0,
                        },
                    ).format(tier.annual ?? 0)}
                </span>
                {!isFree && (
                    <span className="text-sm text-muted-foreground">
                        {copy('/year')}
                    </span>
                )}
            </div>
            <p className="mt-1.5 text-sm text-muted-foreground">
                {copy(isFree ? 'free forever' : 'billed once a year')}
            </p>
            <p className="mt-2 text-sm text-muted-foreground">
                {copy(tier.limits)}
            </p>

            <Separator className="my-5" />

            <ul className="flex flex-col gap-2.5">
                {tier.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2.5">
                        <Check
                            aria-hidden="true"
                            className="mt-0.5 size-4 shrink-0 text-success"
                        />
                        <span className="text-sm">{copy(feature)}</span>
                    </li>
                ))}
            </ul>

            <div className="mt-auto pt-6">
                <Button
                    asChild
                    variant={tier.featured ? 'default' : 'outline'}
                    className="w-full"
                >
                    <Link href={ctaHref}>{copy(tier.cta)}</Link>
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
    values: [ComparisonValue, ComparisonValue];
}

interface ComparisonGroup {
    group: string;
    rows: ComparisonRow[];
}

/* Values must match what ships: the Free plan includes the whole
   workflow, capped at 1 user and 15 clients; Pro is unlimited. */
const comparisonGroups: ComparisonGroup[] = [
    {
        group: 'Clients & contracts',
        rows: [
            { feature: 'Client directory', values: [true, true] },
            {
                feature: 'Seasonal contracts & services',
                values: [true, true],
            },
            { feature: 'Max clients', values: ['15', 'Unlimited'] },
            { feature: 'Max users', values: ['1', 'Unlimited'] },
        ],
    },
    {
        group: 'Payments & receipts',
        rows: [
            {
                feature: 'Payment tracking & mark-paid',
                values: [true, true],
            },
            {
                feature: 'Printable invoices & receipts',
                values: [true, true],
            },
            {
                feature: 'Invoice & receipt PDF download',
                values: [true, true],
            },
            { feature: 'Payment reminders', values: [false, true] },
            { feature: 'Weekly payment digest', values: [false, true] },
        ],
    },
    {
        group: 'Data & dashboards',
        rows: [
            { feature: 'Season CSV export', values: [true, true] },
            {
                feature: 'Revenue & client dashboard',
                values: [true, true],
            },
            {
                feature: 'Bilingual EN/FR interface',
                values: [true, true],
            },
        ],
    },
    {
        group: 'Support',
        rows: [{ feature: 'Support', values: ['Email', 'Email'] }],
    },
];

function ComparisonCell({ value }: { value: ComparisonValue }) {
    const { copy } = usePublicCopy();

    if (value === true) {
        return (
            <CheckCircle2
                role="img"
                aria-label={copy('Included')}
                className="mx-auto size-4 text-success"
            />
        );
    }
    if (value === false) {
        return (
            <Minus
                role="img"
                aria-label={copy('Not included')}
                className="mx-auto size-4 text-muted-foreground/40"
            />
        );
    }
    return <span className="text-sm">{copy(value)}</span>;
}

function ComparisonSection() {
    const { copy } = usePublicCopy();

    return (
        <MarketingSection
            align="left"
            eyebrow={copy('Compare')}
            title={copy('Every feature, side by side')}
            description={copy(
                'The same back office on every plan — the differences come down to the size of your client book and your team.',
            )}
        >
            <div className="overflow-hidden rounded-xl border border-border bg-card">
                <div
                    className="scrollbar-frost overflow-x-auto"
                    tabIndex={0}
                    role="region"
                    aria-label={copy('Plan comparison')}
                >
                    <table className="w-full min-w-[560px] caption-bottom text-sm">
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead scope="col" className="h-11 px-4">
                                    {copy('Feature')}
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
                                        {copy(name)}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {comparisonGroups.map((group) => (
                                <React.Fragment key={group.group}>
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell
                                            colSpan={3}
                                            className="bg-muted/50 px-4 py-2.5 text-sm font-semibold tracking-wide text-muted-foreground"
                                        >
                                            {copy(group.group)}
                                        </TableCell>
                                    </TableRow>
                                    {group.rows.map((row) => (
                                        <TableRow key={row.feature}>
                                            <TableCell className="px-4 py-3 text-sm">
                                                {copy(row.feature)}
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
    const { copy } = usePublicCopy();

    return (
        <MarketingSection
            eyebrow={copy('Pricing FAQ')}
            title={copy('The fine print, unhidden')}
        >
            <div className="mx-auto max-w-3xl rounded-xl border border-border bg-card px-5 md:px-6">
                <Accordion type="single" collapsible>
                    {pricingFaqs.map((faq) => (
                        <AccordionItem key={faq.q} value={faq.q}>
                            <AccordionTrigger>{copy(faq.q)}</AccordionTrigger>
                            <AccordionContent className="text-muted-foreground">
                                {copy(faq.a)}
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
    const { copy } = usePublicCopy();

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
        <MarketingLayout
            title={copy('Pricing — Frostline OPS')}
            route={ROUTES.pricing}
        >
            <PageHero
                eyebrow={copy('Pricing')}
                title={copy('Free to start, priced for the season')}
                description={copy(
                    'Start free and organize your whole season — client directory, contracts, payment tracking, invoices & receipts. Upgrade to Pro when your client book and your team outgrow the free caps.',
                )}
            />

            <section className="relative py-14 md:py-20">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <h2 className="sr-only">{copy('Plans')}</h2>
                    <div className="mx-auto mt-12 grid max-w-3xl gap-4 md:grid-cols-2">
                        {pricingTiers.map((tier) => (
                            <TierCard
                                key={tier.name}
                                tier={tier}
                                ctaHref={ctaHref}
                            />
                        ))}
                    </div>
                    <p className="mt-8 text-center text-sm text-muted-foreground">
                        {copy(
                            'Pro starts with a 14-day free trial · no card required · taxes calculated at checkout · cancel anytime',
                        )}
                    </p>
                </div>
            </section>

            <ComparisonSection />

            <FaqSection />

            <CTABand
                title={copy('Be ready before the first storm')}
                description={copy(
                    "Import your clients and set up your season's contracts and payment schedules now — so when the snow flies, the paperwork is already done.",
                )}
                primaryLabel={copy('Get started free')}
                primaryTo={ROUTES.register}
                secondaryLabel={copy('Talk to us')}
                secondaryTo={ROUTES.contact}
            />
        </MarketingLayout>
    );
}
