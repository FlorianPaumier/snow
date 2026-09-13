import { Head } from '@inertiajs/react';
import * as React from 'react';
import { MarketingFooter } from '@/components/marketing/footer';
import { MarketingNav } from '@/components/marketing/nav';

export function MarketingLayout({
    title,
    route,
    description = 'Frostline OPS is the calm, reliable back office for snow removal businesses: clients, seasonal contracts, payments, and receipts in one place.',
    structuredData,
    children,
}: {
    title: string;
    route: string;
    description?: string;
    structuredData?: Record<string, unknown>;
    children: React.ReactNode;
}) {
    const canonicalUrl = new URL(route, window.location.origin).toString();
    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title}>
                <meta name="description" content={description} head-key="description" />
                <link rel="canonical" href={canonicalUrl} head-key="canonical" />
                <meta property="og:type" content="website" head-key="og:type" />
                <meta property="og:site_name" content="Frostline OPS" head-key="og:site_name" />
                <meta property="og:title" content={title} head-key="og:title" />
                <meta property="og:description" content={description} head-key="og:description" />
                <meta property="og:url" content={canonicalUrl} head-key="og:url" />
                <meta name="twitter:card" content="summary" head-key="twitter:card" />
                <meta name="twitter:title" content={title} head-key="twitter:title" />
                <meta name="twitter:description" content={description} head-key="twitter:description" />
                {structuredData ? (
                    <script type="application/ld+json" head-key="structured-data">
                        {JSON.stringify(structuredData)}
                    </script>
                ) : null}
            </Head>
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-100 focus:rounded-md focus:bg-primary focus:px-3 focus:py-1.5 focus:text-sm focus:text-primary-foreground"
            >
                Skip to main content
            </a>
            <MarketingNav route={route} />
            <main id="main" className="flex-1">
                {children}
            </main>
            <MarketingFooter />
        </div>
    );
}
