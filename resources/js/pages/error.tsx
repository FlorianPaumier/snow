import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { home } from '@/routes';

export default function ErrorPage({
    code,
    title,
    homeLabel,
}: {
    code: number;
    title: string;
    homeLabel: string;
}) {
    return (
        <PublicLayout className="grid place-items-center px-6 py-12">
            <Head title={`${code} — ${title}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <main className="text-center">
                <p className="text-sm font-medium text-muted-foreground tabular-nums">
                    {code}
                </p>
                <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                    {title}
                </h1>
                <Button asChild className="mt-8">
                    <a href={home.url()}>{homeLabel}</a>
                </Button>
            </main>
        </PublicLayout>
    );
}
