import { Link } from '@inertiajs/react';
import { ArrowRight, Snowflake } from 'lucide-react';
import * as React from 'react';
import { ROUTES } from '@/components/marketing/routes';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/* ── Ambient backdrop shared by marketing heroes ────────────────── */
function HeroBackdrop({ compact = false }: { compact?: boolean }) {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 overflow-hidden"
        >
            <div className="bg-grid-frost absolute inset-0 [mask-image:radial-gradient(ellipse_85%_70%_at_50%_0%,black_25%,transparent_100%)]" />
            <div
                className={cn(
                    'absolute -top-40 -left-32 rounded-full bg-glacier-400/20 blur-3xl dark:bg-glacier-500/10',
                    compact ? 'size-96' : 'size-[34rem]',
                )}
            />
            <div
                className={cn(
                    'absolute -right-32 -bottom-44 rounded-full bg-ember-400/15 blur-3xl dark:bg-ember-500/10',
                    compact ? 'size-80' : 'size-[30rem]',
                )}
            />
            <Snowflake className="absolute top-[18%] right-[12%] size-8 animate-drift text-glacier-400/40" />
            <Snowflake
                className="absolute top-[30%] left-[6%] hidden size-5 animate-drift text-glacier-400/30 md:block"
                style={{ animationDelay: '-6s' }}
            />
        </div>
    );
}

/* ── PageHero: standard hero for public pages ───────────────────── */
export function PageHero({
    eyebrow,
    title,
    description,
    children,
    align = 'center',
    compact = false,
}: {
    eyebrow: string;
    title: React.ReactNode;
    description?: string;
    children?: React.ReactNode;
    align?: 'center' | 'left';
    compact?: boolean;
}) {
    return (
        <section className="relative overflow-hidden border-b border-border">
            <HeroBackdrop compact={compact} />
            <div
                className={cn(
                    'relative z-10 mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8',
                    compact ? 'py-14 md:py-20' : 'py-20 md:py-28',
                )}
            >
                <div
                    className={cn(
                        'max-w-3xl',
                        align === 'center' && 'mx-auto text-center',
                    )}
                >
                    <p className="inline-flex items-center rounded-full border border-glacier-200 bg-glacier-50/70 px-3 py-1 font-mono text-[11px] font-medium tracking-[0.16em] text-glacier-800 uppercase dark:border-glacier-800/60 dark:bg-glacier-950/40 dark:text-glacier-300">
                        {eyebrow}
                    </p>
                    <h1 className="mt-5 text-4xl font-semibold tracking-tighter text-balance md:text-6xl">
                        {title}
                    </h1>
                    {description ? (
                        <p
                            className={cn(
                                'mt-5 text-base leading-relaxed text-muted-foreground md:text-lg',
                                align === 'center'
                                    ? 'mx-auto max-w-2xl'
                                    : 'max-w-xl',
                            )}
                        >
                            {description}
                        </p>
                    ) : null}
                    {children ? (
                        <div
                            className={cn(
                                'mt-8 flex flex-wrap items-center gap-3',
                                align === 'center' && 'justify-center',
                            )}
                        >
                            {children}
                        </div>
                    ) : null}
                </div>
            </div>
        </section>
    );
}

/* ── MarketingSection: standard section shell ───────────────────── */
export function MarketingSection({
    eyebrow,
    title,
    description,
    children,
    className,
    align = 'center',
    id,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
    align?: 'center' | 'left';
    id?: string;
}) {
    return (
        <section id={id} className={cn('relative py-16 md:py-24', className)}>
            <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    className={cn(
                        'max-w-2xl',
                        align === 'center' && 'mx-auto text-center',
                    )}
                >
                    {eyebrow ? (
                        <p className="font-mono text-xs font-medium tracking-[0.18em] text-glacier-700 uppercase dark:text-glacier-400">
                            {eyebrow}
                        </p>
                    ) : null}
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance md:text-4xl">
                        {title}
                    </h2>
                    {description ? (
                        <p className="mt-4 text-base leading-relaxed text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                </div>
                <div className="mt-10 md:mt-14">{children}</div>
            </div>
        </section>
    );
}

/* ── CTABand: reusable closing call-to-action ───────────────────── */
export function CTABand({
    title = 'Ready for the next storm?',
    description = 'Set up your properties before the first flake. Your first storm season on Frostline comes with white-glove onboarding.',
    primaryLabel = 'Start free trial',
    primaryTo = ROUTES.contact,
    secondaryLabel = 'See pricing',
    secondaryTo = ROUTES.pricing,
}: {
    title?: string;
    description?: string;
    primaryLabel?: string;
    primaryTo?: string;
    secondaryLabel?: string;
    secondaryTo?: string;
}) {
    return (
        <section className="relative overflow-hidden border-t border-border">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-linear-to-br from-glacier-500/10 via-transparent to-ember-500/10"
            />
            <div className="relative z-10 mx-auto w-full max-w-6xl px-4 py-16 text-center sm:px-6 md:py-24 lg:px-8">
                <h2 className="mx-auto max-w-2xl text-3xl font-semibold tracking-tight text-balance md:text-4xl">
                    {title}
                </h2>
                <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                    {description}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button asChild size="lg">
                        <Link href={primaryTo}>
                            {primaryLabel}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                    <Button asChild size="lg" variant="outline">
                        <Link href={secondaryTo}>{secondaryLabel}</Link>
                    </Button>
                </div>
                <p className="mt-6 font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase">
                    14-day trial · no card required · cancel anytime
                </p>
            </div>
        </section>
    );
}
