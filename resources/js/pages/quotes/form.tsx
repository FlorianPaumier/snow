import { useLocale } from '@/hooks/use-locale';
import AppFormLayout from '@/layouts/app-form/layout';
import ContractForm from '@/pages/contract/components/contract-form';
import { index, store, update } from '@/routes/quotes';
import type { CatalogService } from '@/types/catalog-service';
import type { Quote } from '@/types/quote';

export default function QuoteForm({
    quote,
    clients,
    catalogServices,
    seasons,
    slots,
}: {
    quote?: Quote;
    catalogServices: CatalogService[];
    clients: { id: string; clientNumber: string; displayName: string }[];
    seasons: string[];
    slots: { value: string; label: string }[];
}) {
    const { t } = useLocale();
    return (
        <AppFormLayout
            title={quote ? t.quotes.edit : t.quotes.newQuote}
            description={t.quotes.description}
            breadcrumbs={[{ title: t.quotes.title, href: index() }]}
            backHref={index().url}
            backLabel={t.quotes.title}
        >
            <ContractForm
                catalogServices={catalogServices}
                action={
                    (quote ? update.form(quote.id) : store.form()) as Record<
                        string,
                        unknown
                    >
                }
                cancelHref={index().url}
                submitLabel={t.quotes.save}
                renewalReview={Boolean(quote?.source_contract_id)}
                initialClientId={quote?.client_id}
                initialSeason={quote?.season ?? seasons[1] ?? ''}
                initialServices={quote?.services.map((service) => ({
                    label: service.label,
                    amount: (service.amount / 100).toFixed(2),
                }))}
                initialPayments={Object.fromEntries(
                    slots.map((slot) => [
                        slot.value,
                        {
                            amount: quote
                                ? (
                                      (quote.payments[slot.value]?.amount ??
                                          0) / 100
                                  ).toFixed(2)
                                : '',
                            dueDate: quote?.payments[slot.value]?.dueDate ?? '',
                        },
                    ]),
                )}
                clients={
                    quote?.source_contract_id
                        ? clients.filter(
                              (client) => client.id === quote.client_id,
                          )
                        : clients
                }
                seasons={quote?.source_contract_id ? [quote.season] : seasons}
                slots={slots}
            />
        </AppFormLayout>
    );
}
