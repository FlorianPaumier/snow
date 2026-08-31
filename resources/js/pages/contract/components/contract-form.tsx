import { Form } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import SearchableClientSelect from '@/components/searchable-client-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    CancelButton,
    FormFooter,
    FormSection,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { formatCurrency } from '@/lib/utils';

type ClientOption = {
    id: string;
    clientNumber: string;
    displayName: string;
};

type SlotOption = {
    value: string;
    label: string;
};

type ServiceRow = {
    id?: number;
    label: string;
    /** Dollars, as typed by the user (converted to cents on submit)... */
    amount: string;
};

type PaymentRow = {
    amount: string;
    paid?: boolean;
};

/**
 * Shared create/edit form for contracts: identity (saison + client),
 * dynamic service rows, and the four installment slots. Amounts are edited
 * in dollars and transformed to cents on submit.
 */
export default function ContractForm({
    action,
    cancelHref,
    submitLabel,
    initialClientId = '',
    initialSeason,
    initialServices = [{ label: '', amount: '' }],
    initialPayments,
    clients,
    seasons,
    slots,
}: {
    action: Record<string, unknown>;
    cancelHref: string;
    submitLabel: string;
    initialClientId?: string;
    initialSeason: string;
    initialServices?: ServiceRow[];
    initialPayments: Record<string, PaymentRow>;
    clients: ClientOption[];
    seasons: string[];
    slots: SlotOption[];
}) {
    const [services, setServices] = useState<ServiceRow[]>(initialServices);
    const [payments, setPayments] =
        useState<Record<string, PaymentRow>>(initialPayments);

    const servicesTotal = useMemo(
        () =>
            services.reduce(
                (sum, service) =>
                    sum + Math.round((Number(service.amount) || 0) * 100),
                0,
            ),
        [services],
    );

    const paymentsTotal = useMemo(
        () =>
            Object.values(payments).reduce(
                (sum, payment) =>
                    sum + Math.round((Number(payment.amount) || 0) * 100),
                0,
            ),
        [payments],
    );

    const addService = () =>
        setServices((current) => [...current, { label: '', amount: '' }]);

    const removeService = (index: number) =>
        setServices((current) => {
            if (current.length <= 1) {
                return current;
            }

            return current.filter((_, position) => position !== index);
        });

    const updateService = (
        index: number,
        field: keyof ServiceRow,
        value: string,
    ) =>
        setServices((current) =>
            current.map((service, position) =>
                position === index ? { ...service, [field]: value } : service,
            ),
        );

    const updatePayment = (slot: string, value: string) =>
        setPayments((current) => ({
            ...current,
            [slot]: { ...current[slot], amount: value },
        }));

    const balanced = servicesTotal === paymentsTotal;

    return (
        <Form
            {...(action as object)}
            disableWhileProcessing
            transform={(data) => ({
                ...data,
                // Dollars to cents, rounded to keep the invariant exact...
                services: services.map((service) => ({
                    ...(service.id !== undefined ? { id: service.id } : {}),
                    label: service.label,
                    amount: Math.round((Number(service.amount) || 0) * 100),
                })),
                payments: Object.fromEntries(
                    slots.map((slot) => [
                        slot.value,
                        Math.round(
                            (Number(payments[slot.value]?.amount) || 0) * 100,
                        ),
                    ]),
                ),
            })}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <FormSection
                        title="Saison et client"
                        description="À qui s'adresse l'entente et pour quelle saison"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="clientId">Client</Label>
                            <SearchableClientSelect
                                name="clientId"
                                id="clientId"
                                clients={clients}
                                defaultValue={initialClientId}
                            />
                            <InputError message={errors.clientId} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="season">Saison</Label>
                            <Select name="season" defaultValue={initialSeason}>
                                <SelectTrigger id="season" className="w-full">
                                    <SelectValue placeholder="Choisir une saison" />
                                </SelectTrigger>
                                <SelectContent>
                                    {seasons.map((seasonOption) => (
                                        <SelectItem
                                            key={seasonOption}
                                            value={seasonOption}
                                        >
                                            {seasonOption}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.season} />
                        </div>
                    </FormSection>

                    <FormSection
                        title="Services"
                        description="Les travaux inclus dans l'entente"
                    >
                        <div className="flex flex-col gap-4">
                            {services.map((service, index) => (
                                <div
                                    key={service.id ?? `new-${index}`}
                                    className="grid grid-cols-[1fr_120px_auto] items-start gap-2"
                                >
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`services-${index}-label`}
                                        >
                                            Service
                                        </Label>
                                        <Input
                                            id={`services-${index}-label`}
                                            type="text"
                                            required
                                            name={`services[${index}][label]`}
                                            value={service.label}
                                            onChange={(event) =>
                                                updateService(
                                                    index,
                                                    'label',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Déneigement entrée"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`services-${index}-amount`}
                                        >
                                            Montant
                                        </Label>
                                        <Input
                                            id={`services-${index}-amount`}
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            name={`services[${index}][amount]`}
                                            value={service.amount}
                                            onChange={(event) =>
                                                updateService(
                                                    index,
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="425.00"
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="mt-8 size-9 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => removeService(index)}
                                        disabled={services.length <= 1}
                                        aria-label={`Retirer le service ${index + 1}`}
                                    >
                                        <Trash2
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </Button>
                                </div>
                            ))}

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="self-start"
                                onClick={addService}
                                data-test="add-service"
                            >
                                <Plus className="size-4" /> Ajouter un service
                            </Button>
                        </div>
                        <InputError message={errors['services.0.label']} />
                    </FormSection>

                    <FormSection
                        title="Versements"
                        description="Le total des versements doit correspondre au total des services"
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            {slots.map((slot) => {
                                const payment = payments[slot.value] ?? {
                                    amount: '',
                                };
                                const locked = payment.paid === true;

                                return (
                                    <div
                                        key={slot.value}
                                        className="grid gap-2"
                                    >
                                        <Label
                                            htmlFor={`payments-${slot.value}`}
                                            className="flex items-center gap-2"
                                        >
                                            {slot.label}
                                            {locked && (
                                                <Badge
                                                    variant="outline"
                                                    className="border-success/25 bg-success/10 text-success"
                                                >
                                                    Encaissé
                                                </Badge>
                                            )}
                                        </Label>
                                        <Input
                                            id={`payments-${slot.value}`}
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            disabled={locked}
                                            name={`payments[${slot.value}]`}
                                            value={payment.amount}
                                            onChange={(event) =>
                                                updatePayment(
                                                    slot.value,
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="150.00"
                                        />
                                        <InputError
                                            message={
                                                errors[`payments.${slot.value}`]
                                            }
                                        />
                                    </div>
                                );
                            })}
                        </div>

                        {/* Live totals: versements vs services */}
                        <div
                            className={`flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 ${
                                balanced
                                    ? 'border-success/25 bg-success/5'
                                    : 'border-ember-500/30 bg-ember-500/5'
                            }`}
                            aria-live="polite"
                        >
                            <div className="flex items-center gap-6">
                                <p className="text-xs text-muted-foreground">
                                    Services{' '}
                                    <span className="font-mono text-sm font-medium text-foreground tabular-nums">
                                        {formatCurrency(servicesTotal)}
                                    </span>
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Versements{' '}
                                    <span className="font-mono text-sm font-medium text-foreground tabular-nums">
                                        {formatCurrency(paymentsTotal)}
                                    </span>
                                </p>
                            </div>
                            {!balanced && (
                                <p
                                    className="text-xs font-medium text-ember-700 dark:text-ember-400"
                                    role="alert"
                                    data-test="totals-warning"
                                >
                                    Les totaux ne correspondent pas
                                </p>
                            )}
                        </div>
                        <InputError message={errors.payments} />
                    </FormSection>

                    <FormFooter>
                        <CancelButton href={cancelHref} />
                        <SubmitButton processing={processing}>
                            {submitLabel}
                        </SubmitButton>
                    </FormFooter>
                </>
            )}
        </Form>
    );
}
