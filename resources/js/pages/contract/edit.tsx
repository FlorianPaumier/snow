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

type EditableContract = {
    id: string;
    clientId: string;
    season: string;
    services: Array<{ id: number; label: string; amount: number }>;
    payments: Record<string, { amount: number; paid: boolean }>;
};

export default function Edit({
    contract,
    clients,
    seasons,
    slots,
}: {
    contract: EditableContract;
    clients: ClientOption[];
    seasons: string[];
    slots: SlotOption[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Contrats', href: index().url },
        {
            title: `Modifier ${contract.season}`,
            href: ContractController.edit.url({ contract: contract.id }),
        },
    ];

    // Cents back to dollars for the form inputs...
    const initialServices = contract.services.map((service) => ({
        id: service.id,
        label: service.label,
        amount: (service.amount / 100).toFixed(2),
    }));

    const initialPayments = Object.fromEntries(
        slots.map((slot) => {
            const payment = contract.payments[slot.value];

            return [
                slot.value,
                {
                    amount: payment ? (payment.amount / 100).toFixed(2) : '',
                    paid: payment?.paid ?? false,
                },
            ];
        }),
    );

    return (
        <AppFormLayout
            title={`Modifier le contrat ${contract.season}`}
            description="Mettre à jour les services et les versements de l'entente"
            breadcrumbs={breadcrumbs}
            backHref={ContractController.show.url({ contract: contract.id })}
            backLabel="Contrat"
        >
            <ContractForm
                action={
                    ContractController.update.form({
                        contract: contract.id,
                    }) as Record<string, unknown>
                }
                cancelHref={ContractController.show.url({
                    contract: contract.id,
                })}
                submitLabel="Enregistrer les modifications"
                initialClientId={contract.clientId}
                initialSeason={contract.season}
                initialServices={initialServices}
                initialPayments={initialPayments}
                clients={clients}
                seasons={seasons}
                slots={slots}
            />
        </AppFormLayout>
    );
}
