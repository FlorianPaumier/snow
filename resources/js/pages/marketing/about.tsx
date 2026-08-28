import { Link } from '@inertiajs/react';
import {
    Accessibility,
    Crosshair,
    Truck,
    Waves,
    type LucideIcon,
} from 'lucide-react';
import { companyStats } from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { Button } from '@/components/ui/button';

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
        title: 'Clarity in the storm',
        body: 'Dispatchers make split-second calls at 3 a.m. Ruthless hierarchy does the thinking, so the next move is obvious in seconds.',
        practice: 'one primary action per view',
        iconClass:
            'bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400',
        accentClass: 'text-glacier-700 dark:text-glacier-400',
    },
    {
        icon: Truck,
        title: 'Field-first',
        body: 'Every decision is made for gloves, glare, and grime — glanceable status, zero-friction flows, targets you can hit with a mitt on.',
        practice: '44px minimum touch targets',
        iconClass:
            'bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400',
        accentClass: 'text-ember-700 dark:text-ember-400',
    },
    {
        icon: Waves,
        title: 'Calm confidence',
        body: 'Glacier-calm surfaces keep stressful nights readable. Ember only speaks when it matters, so urgency never becomes wallpaper.',
        practice: 'alerts earn their color',
        iconClass:
            'bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400',
        accentClass: 'text-glacier-700 dark:text-glacier-400',
    },
    {
        icon: Accessibility,
        title: 'Accessible always',
        body: 'AA contrast, full keyboard paths, and screen-reader-first semantics — in both themes, checked on every release.',
        practice: 'WCAG 2.1 AA, both themes',
        iconClass:
            'bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400',
        accentClass: 'text-ember-700 dark:text-ember-400',
    },
];

export default function About() {
    return (
        <MarketingLayout title="About — Frostline OPS" route={ROUTES.about}>
            <PageHero
                eyebrow="Our mission"
                title="Built for the crews who clear the way"
                description="Snow operations still run on whiteboards, group texts, and memory. Frostline OPS exists to replace that chaos with one system — from the first flake to the final invoice."
            >
                <Button asChild variant="outline">
                    <Link href={ROUTES.features}>See what we built</Link>
                </Button>
            </PageHero>

            {/* Mission */}
            <section className="py-16 md:py-24">
                <div className="mx-auto w-full max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-semibold tracking-tight text-balance">
                        The problem we're here to solve
                    </h2>
                    <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground">
                        <p>
                            Every snow contractor knows the version of this
                            night: the forecast shifts at 10 p.m., and someone
                            starts rebuilding routes from scratch — a clipboard,
                            a group chat, and hope. Coverage lives in one
                            person's head, proof of service lives in a shoebox
                            of photos, and billing waits weeks for someone to
                            reconcile it all.
                        </p>
                        <p>
                            It doesn't have to work that way. When triggers,
                            routes, proof, and billing live in one system, the 3
                            a.m. question stops being &ldquo;who is covering
                            which lot?&rdquo; and starts being answered before
                            anyone wakes up.
                        </p>
                        <p>
                            That's the product we build — and the rule we build
                            it by: if it doesn't work at -30° in gloves, it
                            doesn't ship.
                        </p>
                    </div>
                </div>
            </section>

            {/* Values */}
            <MarketingSection
                eyebrow="How we work"
                title="Four rules, no exceptions"
                description="The same rules that govern the product govern the company."
            >
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {VALUES.map((value) => {
                        const Icon = value.icon;
                        return (
                            <li
                                key={value.title}
                                className="flex flex-col rounded-xl border border-border bg-card p-6 shadow-frost-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-frost-sm"
                            >
                                <span
                                    aria-hidden="true"
                                    className={`flex size-10 items-center justify-center rounded-lg ${value.iconClass}`}
                                >
                                    <Icon className="size-5" />
                                </span>
                                <h3 className="mt-4 text-base font-semibold tracking-tight">
                                    {value.title}
                                </h3>
                                <p className="mt-2 mb-5 text-sm leading-relaxed text-muted-foreground">
                                    {value.body}
                                </p>
                                <p className="mt-auto border-t border-border pt-4 font-mono text-[11px] leading-relaxed text-muted-foreground">
                                    <span className={value.accentClass}>
                                        In practice
                                    </span>{' '}
                                    · {value.practice}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            </MarketingSection>

            {/* Stats band */}
            <section className="border-y border-border bg-card/40">
                <dl className="mx-auto grid w-full max-w-6xl grid-cols-2 gap-x-4 gap-y-8 px-4 py-10 sm:px-6 md:grid-cols-4 md:py-12 lg:px-8">
                    {companyStats.map((stat) => (
                        <div
                            key={stat.label}
                            className="flex flex-col items-center gap-1.5 text-center"
                        >
                            <dt className="order-2 font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                {stat.label}
                            </dt>
                            <dd className="order-1 text-2xl font-semibold tracking-tight tabular-nums md:text-3xl">
                                {stat.value}
                            </dd>
                        </div>
                    ))}
                </dl>
            </section>

            <CTABand
                title="Come run the night with us"
                description="Whether you run two trucks or two hundred, see what your storm nights look like when the software does the organizing."
                primaryLabel="Talk to us"
                primaryTo={ROUTES.contact}
                secondaryLabel="See pricing"
                secondaryTo={ROUTES.pricing}
            />
        </MarketingLayout>
    );
}
