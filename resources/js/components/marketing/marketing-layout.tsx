import { Head, usePage } from '@inertiajs/react';
import * as React from 'react';
import { MarketingFooter } from '@/components/marketing/footer';
import { MarketingNav } from '@/components/marketing/nav';
import { usePublicCopy } from '@/hooks/use-public-copy';
import PublicLayout from '@/layouts/public-layout';

export function MarketingLayout({
    title,
    route,
    description = 'Frostline OPS is the calm, reliable back office for snow removal businesses: clients, seasonal contracts, payments, and receipts in one place.',
    structuredData,
    index = true,
    children,
}: {
    title: string;
    route: string;
    description?: string;
    structuredData?: Record<string, unknown>;
    index?: boolean;
    children: React.ReactNode;
}) {
    const { copy } = usePublicCopy();

    const { appUrl } = usePage().props;
    const canonicalUrl = new URL(route, `${appUrl}/`).toString();
    const socialImageUrl = new URL(
        '/images/plow-night.jpg',
        `${appUrl}/`,
    ).toString();
    return (
        <PublicLayout className="flex min-h-screen flex-col">
            <Head title={copy(title)}>
                <meta
                    name="description"
                    content={copy(description)}
                    head-key="description"
                />
                <link
                    rel="canonical"
                    href={canonicalUrl}
                    head-key="canonical"
                />
                <meta
                    property="og:type"
                    content={copy('website')}
                    head-key="og:type"
                />
                <meta
                    property="og:site_name"
                    content={copy('Frostline OPS')}
                    head-key="og:site_name"
                />
                <meta
                    property="og:title"
                    content={copy(title)}
                    head-key="og:title"
                />
                <meta
                    property="og:description"
                    content={copy(description)}
                    head-key="og:description"
                />
                <meta
                    property="og:url"
                    content={copy(canonicalUrl)}
                    head-key="og:url"
                />
                <meta
                    name="twitter:card"
                    content={copy('summary_large_image')}
                    head-key="twitter:card"
                />
                <meta
                    name="twitter:title"
                    content={copy(title)}
                    head-key="twitter:title"
                />
                <meta
                    name="twitter:description"
                    content={copy(description)}
                    head-key="twitter:description"
                />
                <meta
                    property="og:image"
                    content={copy(socialImageUrl)}
                    head-key="og:image"
                />
                <meta
                    name="twitter:image"
                    content={copy(socialImageUrl)}
                    head-key="twitter:image"
                />
                {!index ? (
                    <meta
                        name="robots"
                        content={copy('noindex, nofollow')}
                        head-key="robots"
                    />
                ) : null}
                {structuredData ? (
                    <script
                        type="application/ld+json"
                        head-key="structured-data"
                    >
                        {JSON.stringify(structuredData)}
                    </script>
                ) : null}
            </Head>
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-100 focus:rounded-md focus:bg-primary focus:px-3 focus:py-1.5 focus:text-sm focus:text-primary-foreground"
            >
                {copy('Skip to main content')}
            </a>
            <MarketingNav route={route} />
            <main id="main" className="flex-1">
                {children}
            </main>
            <MarketingFooter />
        </PublicLayout>
    );
}
