import { Link } from '@inertiajs/react';
import {
    Accessibility,
    Crosshair,
    MapPin,
    Truck,
    Waves,
    type LucideIcon,
} from 'lucide-react';
import { companyStats, teamMembers } from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';

/* ── Values (mirrors the design-system principles for brand coherence) ── */

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

/* ── Avatar tints cycled across the crew (complete literal class strings) ── */

const AVATAR_TINTS = [
    'bg-glacier-500/15 text-glacier-700 dark:text-glacier-400',
    'bg-ember-500/15 text-ember-600 dark:text-ember-400',
    'bg-frost-200 text-frost-700 dark:bg-frost-800 dark:text-frost-300',
] as const;

export default function About() {
    return (
        <MarketingLayout title="About — Frostline OPS" route={ROUTES.about}>
            <PageHero
                eyebrow="Our story"
                title="Built where the plows run"
                description="Frostline started in a Minneapolis truck cab in 2021. Three winters later, we keep 1,200+ crews rolling from Duluth to Quebec."
            >
                <Button asChild variant="outline">
                    <Link href={ROUTES.features}>See what we built</Link>
                </Button>
            </PageHero>

            {/* Story */}
            <section className="py-16 md:py-24">
                <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <div>
                        <h2 className="text-3xl font-semibold tracking-tight text-balance">
                            From route cards on the dash to routes on every
                            phone
                        </h2>
                        <div className="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground">
                            <p>
                                Mika Halttunen plowed Twin Cities subdivisions
                                for eleven winters. By the end, the
                                &ldquo;system&rdquo; was a clipboard, a group
                                chat, and hope — routes lived in his head, and
                                every storm night opened with the same question:
                                who is covering which cul-de-sac?
                            </p>
                            <p>
                                In January 2021, a 16-inch storm buried the
                                metro. His cousin Jordan — a routing engineer —
                                watched him rebuild routes on paper at 2 a.m.
                                and said the quiet part out loud: a computer
                                should be doing this.
                            </p>
                            <p>
                                The first version of Frostline ran one truck,
                                Mika&rsquo;s. Today it dispatches 1,200+ crews
                                and clears 4.2M properties — still built around
                                one rule: if it doesn&rsquo;t work at -30° in
                                gloves, it doesn&rsquo;t ship.
                            </p>
                        </div>
                    </div>
                    <div
                        role="img"
                        aria-label="Aerial photograph of a snow-covered Minneapolis suburb at dusk, streets and cul-de-sacs blanketed in white"
                        className="relative aspect-[4/3] overflow-hidden rounded-2xl border border-border shadow-frost-md"
                        style={{
                            backgroundImage: 'url(/images/aerial-winter.jpg)',
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                        }}
                    >
                        <div
                            aria-hidden="true"
                            className="absolute inset-x-0 bottom-0 h-1/3 bg-linear-to-t from-background/90 to-transparent"
                        />
                        <span className="absolute bottom-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-card/90 px-3 py-1.5 font-mono text-[11px] text-foreground backdrop-blur">
                            <MapPin
                                aria-hidden="true"
                                className="size-3 text-glacier-600 dark:text-glacier-400"
                            />
                            Minneapolis · 44.978° N
                        </span>
                    </div>
                </div>
            </section>

            {/* Values */}
            <MarketingSection
                eyebrow="How we work"
                title="Four rules, no exceptions"
                description="The same rules that govern the product govern the company — borrowed straight from the Frostline design system."
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

            {/* Team */}
            <MarketingSection
                eyebrow="The crew"
                title="Small team, long winters"
                description="Six people, most with windshield time — a designer who rides along, a meteorologist who calls the storms, engineers who keep the app alive at -30°."
            >
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {teamMembers.map((member, index) => {
                        const tint = AVATAR_TINTS[index % AVATAR_TINTS.length];
                        return (
                            <li
                                key={member.name}
                                className="flex items-start gap-4 rounded-xl border border-border bg-card p-5 shadow-frost-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-frost-sm"
                            >
                                <Avatar className="size-12">
                                    <AvatarFallback
                                        className={`font-mono text-sm font-medium ${tint}`}
                                    >
                                        {member.initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0">
                                    <p className="font-medium">{member.name}</p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {member.role}
                                    </p>
                                    <p className="mt-1.5 font-mono text-[11px] leading-relaxed text-glacier-700 dark:text-glacier-400">
                                        {`// ${member.fact}`}
                                    </p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            </MarketingSection>

            <CTABand
                title="Come run the night with us"
                description="Whether you run two trucks or two hundred, we'd love to put Frostline on your windshield."
                primaryLabel="Talk to us"
                primaryTo={ROUTES.contact}
                secondaryLabel="See open roles"
                secondaryTo={ROUTES.contact}
            />
        </MarketingLayout>
    );
}
