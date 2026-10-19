import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import QuoteDetails from '@/components/quote-details';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { show as showContract } from '@/routes/contracts';
import { edit, index, replace, send } from '@/routes/quotes';
import { decline } from '@/routes/renewals';
import type { Quote } from '@/types/quote';

export default function ShowQuote({ quote }: { quote: Quote }) {
    const { t } = useLocale();
    return (
        <AppLayout breadcrumbs={[{ title: t.quotes.title, href: index() }]}>
            <Head title={t.quotes.title} />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-6">
                <h1 className="text-2xl font-semibold">
                    {t.quotes.title} — {t.quotes[quote.status]}
                </h1>
                <QuoteDetails quote={quote} />
                {quote.source_contract_id &&
                    quote.status === 'draft' &&
                    !quote.renewal_reviewed_at && (
                        <p role="status">{t.renewals.reviewRequired}</p>
                    )}
                {quote.source_contract_id &&
                    ['draft', 'sent'].includes(quote.status) && (
                        <Form {...decline.form(quote.id)}>
                            {({ processing, errors }) => (
                                <>
                                    <Button
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {t.renewals.decline}
                                    </Button>
                                    <InputError message={errors.quote} />
                                </>
                            )}
                        </Form>
                    )}
                {quote.status === 'draft' && (
                    <div className="flex gap-4">
                        <Button asChild variant="outline">
                            <Link href={edit(quote.id)}>{t.quotes.edit}</Link>
                        </Button>
                        <Form {...send.form(quote.id)}>
                            {({ processing, errors }) => (
                                <>
                                    <Button
                                        disabled={
                                            processing ||
                                            Boolean(
                                                quote.source_contract_id &&
                                                !quote.renewal_reviewed_at,
                                            )
                                        }
                                    >
                                        {t.quotes.send}
                                    </Button>
                                    <InputError message={errors.quote} />
                                </>
                            )}
                        </Form>
                    </div>
                )}
                {(quote.status === 'sent' ||
                    (quote.source_contract_id &&
                        quote.status === 'declined')) && (
                    <div className="space-y-3">
                        <p className="text-sm text-muted-foreground">
                            {t.quotes.replaceHint}
                        </p>
                        <Form {...replace.form(quote.id)}>
                            {({ processing, errors }) => (
                                <>
                                    <Button
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {t.quotes.replace}
                                    </Button>
                                    <InputError message={errors.quote} />
                                </>
                            )}
                        </Form>
                    </div>
                )}
                {quote.contract_id && (
                    <Button asChild>
                        <Link href={showContract(quote.contract_id)}>
                            {t.quotes.viewContract}
                        </Link>
                    </Button>
                )}
            </div>
        </AppLayout>
    );
}
