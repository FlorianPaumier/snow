import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import * as React from 'react';
import { ROUTES } from '@/components/marketing/routes';
import { Button } from '@/components/ui/button';
import { usePublicCopy } from '@/hooks/use-public-copy';
import { cn } from '@/lib/utils';

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
    const { copy } = usePublicCopy();

    return (
        <section className="preview-hero relative overflow-hidden">
            <div
                className={cn(
                    'relative z-10 mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8',
                    compact ? 'py-0' : 'py-2 md:py-4',
                )}
            >
                <div
                    className={cn(
                        'max-w-3xl',
                        align === 'center' && 'mx-auto text-center',
                    )}
                >
                    <p className="inline-flex items-center rounded-full border border-glacier-200 bg-glacier-50/70 px-3 py-1 text-sm font-medium text-glacier-800 dark:border-glacier-800/60 dark:bg-glacier-950/40 dark:text-glacier-300">
                        {copy(eyebrow)}
                    </p>
                    <h1 className="preview-display mt-5 text-balance">
                        {copy(title)}
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
                            {copy(description)}
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
    const { copy } = usePublicCopy();

    return (
        <section id={id} className={cn('preview-section relative', className)}>
            <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    className={cn(
                        'max-w-2xl',
                        align === 'center' && 'mx-auto text-center',
                    )}
                >
                    {eyebrow ? (
                        <p className="text-sm font-medium text-glacier-700 dark:text-glacier-400">
                            {copy(eyebrow)}
                        </p>
                    ) : null}
                    <h2 className="preview-title mt-3 text-balance">
                        {copy(title)}
                    </h2>
                    {description ? (
                        <p className="mt-4 text-base leading-relaxed text-muted-foreground">
                            {copy(description)}
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
    description = 'Set up your clients, contracts, and payment schedules before the first flake — so when the snow flies, the paperwork is already done. Guided onboarding imports your client list and gets your first season live fast.',
    primaryLabel = 'Request a free trial',
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
    const { copy } = usePublicCopy();

    return (
        <section className="relative overflow-hidden bg-secondary/60">
            <div className="relative z-10 mx-auto w-full max-w-6xl px-4 py-16 text-center sm:px-6 md:py-24 lg:px-8">
                <h2 className="preview-title mx-auto max-w-2xl text-balance">
                    {copy(title)}
                </h2>
                <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                    {copy(description)}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button asChild size="lg">
                        <Link href={primaryTo}>
                            {copy(primaryLabel)}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                    <Button asChild size="lg" variant="outline">
                        <Link href={secondaryTo}>{copy(secondaryLabel)}</Link>
                    </Button>
                </div>
                <p className="mt-6 text-sm text-muted-foreground">
                    {copy('14 days of Pro · no card required · by invitation')}
                </p>
            </div>
        </section>
    );
}
