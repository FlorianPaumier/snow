import ContractController from '@/actions/App/Http/Controllers/ContractController';
import { useLocale } from '@/hooks/use-locale';
import AppFormLayout from '@/layouts/app-form/layout';
import { index } from '@/routes/contracts';
import type { BreadcrumbItem } from '@/types';
import type { CatalogService } from '@/types/catalog-service';
import ContractForm from './components/contract-form';

type ClientOption = {
    id: string;
    clientNumber: string;
    displayName: string;
};

type SlotOption = {
    value: string;
    label: string;
};

export default function Create({
    clients,
    catalogServices,
    seasons,
    slots,
}: {
    catalogServices: CatalogService[];
    clients: ClientOption[];
    seasons: string[];
    slots: SlotOption[];
}) {
    const { t } = useLocale();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.contracts.title, href: index().url },
        { title: t.contracts.newContract, href: '/contracts/create' },
    ];

    return (
        <AppFormLayout
            title={t.contracts.newContract}
            description={t.contracts.newContractDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.contracts.title}
        >
            <ContractForm
                catalogServices={catalogServices}
                action={
                    ContractController.store.form() as Record<string, unknown>
                }
                cancelHref={index().url}
                submitLabel={t.contracts.createContract}
                initialSeason={seasons[1] ?? seasons[0] ?? ''}
                initialPayments={Object.fromEntries(
                    slots.map((slot) => [
                        slot.value,
                        { amount: '', dueDate: '' },
                    ]),
                )}
                clients={clients}
                seasons={seasons}
                slots={slots}
            />
        </AppFormLayout>
    );
}
