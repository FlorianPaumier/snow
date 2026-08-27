import { Head } from '@inertiajs/react';
import * as React from 'react';
import { MarketingFooter } from '@/components/marketing/footer';
import { MarketingNav } from '@/components/marketing/nav';

export function MarketingLayout({
    title,
    route,
    children,
}: {
    title: string;
    route: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title} />
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
