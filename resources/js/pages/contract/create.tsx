import ContractController from '@/actions/App/Http/Controllers/ContractController';
import AppFormLayout from '@/layouts/app-form/layout';
import { index } from '@/routes/contracts';
import type { BreadcrumbItem } from '@/types';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contrats', href: index().url },
    { title: 'Nouveau contrat', href: '/contracts/create' },
];

export default function Create({
    clients,
    seasons,
    slots,
}: {
    clients: ClientOption[];
    seasons: string[];
    slots: SlotOption[];
}) {
    return (
        <AppFormLayout
            title="Nouveau contrat"
            description="Créer une entente de saison pour un client"
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel="Contrats"
        >
            <ContractForm
                action={
                    ContractController.store.form() as Record<string, unknown>
                }
                cancelHref={index().url}
                submitLabel="Créer le contrat"
                initialSeason={seasons[1] ?? seasons[0] ?? ''}
                initialPayments={Object.fromEntries(
                    slots.map((slot) => [slot.value, { amount: '' }]),
                )}
                clients={clients}
                seasons={seasons}
                slots={slots}
            />
        </AppFormLayout>
    );
}
