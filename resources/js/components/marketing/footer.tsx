import { Link } from '@inertiajs/react';
import { Snowflake } from 'lucide-react';
import { ROUTES } from '@/components/marketing/routes';

const footerGroups: {
    title: string;
    links: { label: string; to: string }[];
}[] = [
    {
        title: 'Product',
        links: [
            { label: 'Features', to: ROUTES.features },
            { label: 'Pricing', to: ROUTES.pricing },
            { label: 'Storm mode', to: ROUTES.features },
            { label: 'Driver app', to: ROUTES.features },
        ],
    },
    {
        title: 'Company',
        links: [
            { label: 'About', to: ROUTES.about },
            { label: 'Contact', to: ROUTES.contact },
            { label: 'Talk to sales', to: ROUTES.contact },
        ],
    },
    {
        title: 'Resources',
        links: [
            { label: 'Get started', to: ROUTES.contact },
            { label: 'Pricing FAQ', to: ROUTES.pricing },
        ],
    },
];

export function MarketingFooter() {
    return (
        <footer className="mt-auto border-t border-border bg-card/40 pb-[env(safe-area-inset-bottom)]">
            <div className="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-10 md:flex-row md:items-start md:justify-between">
                    <div className="max-w-sm">
                        <Link
                            href={ROUTES.home}
                            className="flex items-center gap-2.5 rounded-lg outline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
                        >
                            <span
                                aria-hidden="true"
                                className="flex size-8 items-center justify-center rounded-lg bg-linear-to-br from-glacier-600 to-glacier-400 text-white shadow-frost-sm dark:from-glacier-500 dark:to-glacier-300 dark:text-glacier-950"
                            >
                                <Snowflake className="size-4" />
                            </span>
                            <span className="text-sm font-semibold tracking-tight">
                                Frostline{' '}
                                <span className="text-glacier-600 dark:text-glacier-400">
                                    OPS
                                </span>
                            </span>
                        </Link>
                        <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                            Snow removal operations software — dispatch,
                            routing, proof, and billing for the crews who clear
                            the way. Field-tested through the toughest storm
                            nights.
                        </p>
                        <p className="mt-4 inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground">
                            <span
                                aria-hidden="true"
                                className="relative flex size-2"
                            >
                                <span className="absolute inline-flex size-full animate-ping rounded-full bg-success/60" />
                                <span className="relative inline-flex size-2 rounded-full bg-success" />
                            </span>
                            All systems clearing
                        </p>
                    </div>

                    <nav
                        aria-label="Footer"
                        className="grid grid-cols-2 gap-x-12 gap-y-8 sm:grid-cols-3"
                    >
                        {footerGroups.map((group) => (
                            <div key={group.title}>
                                <p className="font-mono text-[10px] font-medium tracking-[0.18em] text-muted-foreground uppercase">
                                    {group.title}
                                </p>
                                <ul className="mt-3 flex flex-col gap-2">
                                    {group.links.map((link) => (
                                        <li key={link.label}>
                                            <Link
                                                href={link.to}
                                                className="rounded-sm text-sm text-muted-foreground transition-colors hover:text-foreground"
                                            >
                                                {link.label}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </nav>
                </div>

                <div className="mt-10 flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs text-muted-foreground">
                        © 2025 Frostline — built for the crews who clear the
                        way.
                    </p>
                    <p className="font-mono text-[11px] text-muted-foreground">
                        Privacy · Terms · Status
                    </p>
                </div>
            </div>
        </footer>
    );
}
