import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { useCurrencyFormatter } from '@/lib/utils';
import { create, index, show } from '@/routes/quotes';
import { index as renewals } from '@/routes/renewals';
import type { Quote } from '@/types/quote';

type Row = Pick<Quote, 'id' | 'season' | 'status'> & {
    name: string;
    total: number;
};
export default function Quotes({
    quotes,
}: {
    quotes: {
        data: Row[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}) {
    const { t } = useLocale();
    const currency = useCurrencyFormatter();
    return (
        <AppLayout breadcrumbs={[{ title: t.quotes.title, href: index() }]}>
            <Head title={t.quotes.title} />
            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{t.quotes.title}</h1>
                    <div className="flex flex-wrap gap-3">
                        <Button asChild variant="outline">
                            <Link href={renewals()}>{t.renewals.title}</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>{t.quotes.newQuote}</Link>
                        </Button>
                    </div>
                </div>
                {!quotes.data.length && <p>{t.quotes.empty}</p>}
                {quotes.data.map((quote) => (
                    <article
                        key={quote.id}
                        className="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-5"
                    >
                        <div>
                            <h2 className="font-semibold">
                                {quote.name} — {quote.season}
                            </h2>
                            <p>
                                {t.quotes[quote.status]} ·{' '}
                                {currency(quote.total)}
                            </p>
                        </div>
                        <Link className="underline" href={show(quote.id)}>
                            {t.quotes.open}
                        </Link>
                    </article>
                ))}
                <div className="flex gap-4">
                    {quotes.prev_page_url && (
                        <Link href={quotes.prev_page_url}>
                            {t.quotes.previous}
                        </Link>
                    )}
                    {quotes.next_page_url && (
                        <Link href={quotes.next_page_url}>{t.quotes.next}</Link>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
