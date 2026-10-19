import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import QuoteDetails from '@/components/quote-details';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import PublicLayout from '@/layouts/public-layout';
import { accept } from '@/routes/quotes';
import type { Quote } from '@/types/quote';

export default function Review({
    quote,
    signature,
    expires,
}: {
    quote: Quote;
    signature: string;
    expires: string;
}) {
    const { t } = useLocale();
    return (
        <PublicLayout className="px-4 py-10 sm:py-16">
            <main
                className="mx-auto max-w-2xl space-y-6 rounded-2xl border border-border bg-card px-6 py-12 shadow-[var(--preview-elevation)]"
                data-public-review="quotes"
            >
                <Head title={t.quotes.review}>
                    <meta name="robots" content="noindex, nofollow" />
                    <meta name="referrer" content="no-referrer" />
                </Head>
                <h1 className="text-2xl font-semibold">
                    {quote.accepted_at
                        ? t.quotes.acceptedTitle
                        : t.quotes.review}
                </h1>
                <QuoteDetails quote={quote} />
                {quote.accepted_at ? (
                    <p role="status">{t.quotes.acceptedText}</p>
                ) : (
                    <Form
                        {...accept.form(
                            {
                                organization: quote.organization_id,
                                quote: quote.id,
                            },
                            { query: { signature, expires } },
                        )}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <label className="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="accepted"
                                        value="1"
                                        required
                                        className="mt-1 size-5"
                                    />
                                    <span>{t.quotes.confirm}</span>
                                </label>
                                <InputError
                                    message={errors.accepted ?? errors.quote}
                                />
                                <Button disabled={processing}>
                                    {t.quotes.accept}
                                </Button>
                            </>
                        )}
                    </Form>
                )}
                <p className="text-sm text-muted-foreground">
                    {t.quotes.private}
                </p>
            </main>
        </PublicLayout>
    );
}
