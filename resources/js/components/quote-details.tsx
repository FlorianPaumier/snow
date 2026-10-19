import { useLocale } from '@/hooks/use-locale';
import { useCurrencyFormatter } from '@/lib/utils';
import type { Quote } from '@/types/quote';

export default function QuoteDetails({ quote }: { quote: Quote }) {
    const { t } = useLocale();
    const currency = useCurrencyFormatter();
    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-lg font-semibold">
                    {quote.organization_name}
                </h2>
                <p>{quote.customer?.name}</p>
                <p>{quote.customer?.address}</p>
                <p>{quote.season}</p>
            </div>
            <ul className="divide-y">
                {quote.services.map((service, i) => (
                    <li key={i} className="flex justify-between gap-4 py-3">
                        <span>{service.label}</span>
                        <span>{currency(service.amount)}</span>
                    </li>
                ))}
            </ul>
            <p className="font-semibold">
                {t.quotes.total}:{' '}
                {currency(
                    quote.services.reduce(
                        (total, service) => total + service.amount,
                        0,
                    ),
                )}
            </p>
            <h2 className="font-semibold">{t.quotes.schedule}</h2>
            <ul className="space-y-2">
                {Object.entries(quote.payments).map(([slot, payment]) => (
                    <li key={slot} className="flex justify-between gap-4">
                        <span>
                            {t.paymentSlots[
                                slot as
                                    | 'deposit'
                                    | 'first'
                                    | 'second'
                                    | 'balance'
                            ] ?? slot}{' '}
                            {payment.dueDate}
                        </span>
                        <span>{currency(payment.amount)}</span>
                    </li>
                ))}
            </ul>
            {quote.expires_at && (
                <p>
                    {t.quotes.expires}: {quote.expires_at.slice(0, 10)}
                </p>
            )}
        </div>
    );
}
