import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import PublicLayout from '@/layouts/public-layout';
import { useCurrencyFormatter } from '@/lib/utils';
import { store } from '@/routes/connected-payments';

type Payment = {
    id: number;
    organization: string;
    merchant: string;
    amount: number;
    slot: string;
    paid: boolean;
    status: string | null;
    reversedAmount: number;
    available: boolean;
};

export default function Review({
    payment,
    signature,
    expires,
}: {
    payment: Payment;
    signature: string;
    expires: string;
}) {
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();
    const status =
        payment.status && payment.status in t.connect
            ? t.connect[payment.status as keyof typeof t.connect]
            : null;
    return (
        <PublicLayout className="px-4 py-10 sm:py-16">
            <main
                className="mx-auto max-w-xl space-y-6 rounded-2xl border border-border bg-card px-6 py-12 shadow-[var(--preview-elevation)]"
                data-public-review="payments"
            >
                <Head title={t.connect.title}>
                    <meta name="robots" content="noindex, nofollow" />
                    <meta name="referrer" content="no-referrer" />
                </Head>
                <h1 className="text-2xl font-semibold">
                    {t.connect.merchant} {payment.merchant}
                </h1>
                <p>
                    {payment.slot} · {formatCurrency(payment.amount)}
                </p>
                {status && <p role="status">{status}</p>}
                {payment.reversedAmount > 0 && (
                    <p>
                        {t.connect.reversed}:{' '}
                        {formatCurrency(payment.reversedAmount)}
                    </p>
                )}
                {payment.paid ? (
                    <p>{t.connect.paid}</p>
                ) : payment.status === 'processing' ? (
                    <p>{t.connect.processing}</p>
                ) : payment.available ? (
                    <Form
                        {...store.form(
                            {
                                organization: payment.organization,
                                payment: payment.id,
                            },
                            { query: { signature, expires } },
                        )}
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-3">
                                <Button disabled={processing}>
                                    {t.connect.pay}
                                </Button>
                                <InputError message={errors.payment} />
                            </div>
                        )}
                    </Form>
                ) : (
                    <p>{t.connect.unavailable}</p>
                )}
                <p className="text-sm text-muted-foreground">
                    {t.connect.private}
                </p>
            </main>
        </PublicLayout>
    );
}
