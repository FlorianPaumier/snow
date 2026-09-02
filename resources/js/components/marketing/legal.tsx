import * as React from 'react';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import { PageHero } from '@/components/marketing/page-hero';

/* ── Shared shell for the public legal pages ────────────────────── */
export function LegalPage({
    title,
    route,
    description,
    updated,
    children,
}: {
    title: string;
    route: string;
    description: string;
    updated: string;
    children: React.ReactNode;
}) {
    return (
        <MarketingLayout title={`${title} — Frostline OPS`} route={route}>
            <PageHero
                eyebrow="Legal"
                title={title}
                description={description}
                compact
            />
            <section className="py-12 md:py-16">
                <div className="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
                    <p className="font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase">
                        Last updated {updated}
                    </p>
                    <div className="mt-10 flex flex-col gap-10">{children}</div>
                </div>
            </section>
        </MarketingLayout>
    );
}

/* ── Numbered legal section with prose body ─────────────────────── */
export function LegalSection({
    number,
    title,
    children,
}: {
    number: string;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section>
            <h2 className="flex items-baseline gap-3 text-xl font-semibold tracking-tight">
                <span className="font-mono text-xs text-glacier-700 dark:text-glacier-400">
                    {number}
                </span>
                {title}
            </h2>
            <div className="mt-3 space-y-3 text-base leading-relaxed text-muted-foreground [&_a]:font-medium [&_a]:text-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-foreground/80">
                {children}
            </div>
        </section>
    );
}
