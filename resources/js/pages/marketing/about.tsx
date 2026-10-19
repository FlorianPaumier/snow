import { Link } from '@inertiajs/react';
import {
    Accessibility,
    Crosshair,
    ShieldCheck,
    Waves,
    type LucideIcon,
} from 'lucide-react';
import { companyFacts } from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { Button } from '@/components/ui/button';
import { usePublicCopy } from '@/hooks/use-public-copy';

/* ── Values ────────────────────────────────────────────────────── */

interface Value {
    icon: LucideIcon;
    title: string;
    body: string;
    practice: string;
    iconClass: string;
    accentClass: string;
}

const VALUES: Value[] = [
    {
        icon: Crosshair,
        title: 'Clarity in the season',
        body: 'Office staff answer real questions in season: who paid, who owes, what was agreed. Ruthless hierarchy does the thinking, so the answer is obvious in seconds.',
        practice: 'one primary action per view',
        iconClass:
            'bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400',
        accentClass: 'text-glacier-700 dark:text-glacier-400',
    },
    {
        icon: Accessibility,
        title: 'Office-first',
        body: 'Every decision is made for the person reconciling payments at the end of a long winter — glanceable status, zero-friction flows, interfaces that stay readable after hours.',
        practice: '44px minimum touch targets',
        iconClass:
            'bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400',
        accentClass: 'text-ember-700 dark:text-ember-400',
    },
    {
        icon: Waves,
        title: 'Calm confidence',
        body: 'Glacier-calm surfaces keep the busy season readable. Ember only speaks when it matters, so urgency never becomes wallpaper.',
        practice: 'alerts earn their color',
        iconClass:
            'bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400',
        accentClass: 'text-glacier-700 dark:text-glacier-400',
    },
    {
        icon: ShieldCheck,
        title: 'Honest scope',
        body: 'We market what exists and label what does not. If a capability is on the roadmap, we say so — the same way we expect invoices to match the work.',
        practice: 'no invented features',
        iconClass:
            'bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400',
        accentClass: 'text-ember-700 dark:text-ember-400',
    },
];

export default function About() {
    const { copy } = usePublicCopy();

    return (
        <MarketingLayout
            title={copy('About — Frostline OPS')}
            route={ROUTES.about}
        >
            <PageHero
                eyebrow={copy('Our mission')}
                title={copy('Built for the people who run the season')}
                description={copy(
                    'Snow removal businesses still run their back office on binders, spreadsheets, and memory. Frostline OPS exists to replace that chaos with one system — from the first contract to the final balance.',
                )}
            >
                <Button asChild variant="outline">
                    <Link href={ROUTES.features}>
                        {copy('See what we built')}
                    </Link>
                </Button>
            </PageHero>

            {/* Mission */}
            <section className="py-16 md:py-24">
                <div className="mx-auto w-full max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-semibold tracking-tight text-balance">
                        {copy("The problem we're here to solve")}
                    </h2>
                    <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground">
                        <p>
                            {copy(
                                'Every snow contractor knows this version of the season: contracts are signed in a rush before the first snowfall and live in a binder. Payments come in pieces — a deposit here, an installment there — and nobody is quite sure who still owes what. Then spring arrives, the melt starts, and the balance chase begins with a shoebox of receipts and a spreadsheet nobody trusts.',
                            )}
                        </p>
                        <p>
                            {copy(
                                "It doesn't have to work that way. When clients, contracts, payments, and receipts live in one system, the end-of-season question stops being “who still owes us?” and becomes a number you already have.",
                            )}
                        </p>
                        <p>
                            {copy(
                                "That's the product we build — and the rule we build it by: the back office should be as reliable as the crews it supports.",
                            )}
                        </p>
                    </div>
                </div>
            </section>

            {/* Values */}
            <MarketingSection
                eyebrow={copy('How we work')}
                title={copy('Four rules, no exceptions')}
                description={copy(
                    'The same rules that govern the product govern the company.',
                )}
            >
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {VALUES.map((value) => {
                        const Icon = value.icon;
                        return (
                            <li
                                key={value.title}
                                className="flex flex-col rounded-xl border border-border bg-card p-6 shadow-frost-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--preview-elevation)]"
                            >
                                <span
                                    aria-hidden="true"
                                    className={`flex size-10 items-center justify-center rounded-lg ${value.iconClass}`}
                                >
                                    <Icon className="size-5" />
                                </span>
                                <h3 className="mt-4 text-base font-semibold tracking-tight">
                                    {copy(value.title)}
                                </h3>
                                <p className="mt-2 mb-5 text-sm leading-relaxed text-muted-foreground">
                                    {copy(value.body)}
                                </p>
                                <p className="mt-auto border-t border-border pt-4 text-sm leading-relaxed text-muted-foreground">
                                    <span className={value.accentClass}>
                                        {copy('In practice')}
                                    </span>
                                    {copy(' ')}· {copy(value.practice)}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            </MarketingSection>

            {/* Product facts band */}
            <section className="border-y border-border bg-card/40">
                <dl className="mx-auto grid w-full max-w-6xl grid-cols-2 gap-x-4 gap-y-8 px-4 py-10 sm:px-6 md:grid-cols-4 md:py-12 lg:px-8">
                    {companyFacts.map((fact) => (
                        <div
                            key={fact.label}
                            className="flex flex-col items-center gap-1.5 text-center"
                        >
                            <dt className="order-2 text-sm font-medium tracking-[0.14em] text-muted-foreground">
                                {copy(fact.label)}
                            </dt>
                            <dd className="order-1 text-2xl font-semibold tracking-tight tabular-nums md:text-3xl">
                                {copy(fact.value)}
                            </dd>
                        </div>
                    ))}
                </dl>
            </section>

            <CTABand
                title={copy('Run your season with us')}
                description={copy(
                    'Whether you manage 20 properties or 200, see what your season looks like when the clients, contracts, and payments organize themselves.',
                )}
                primaryLabel={copy('Talk to us')}
                primaryTo={ROUTES.contact}
                secondaryLabel={copy('See pricing')}
                secondaryTo={ROUTES.pricing}
            />
        </MarketingLayout>
    );
}
