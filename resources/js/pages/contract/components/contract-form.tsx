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
import { useLocale } from '@/hooks/use-locale';
import {
    CancelButton,
    FormFooter,
    FormSection,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { interpolate, useCurrencyFormatter } from '@/lib/utils';

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
    /** Optional reminder due date, as a `YYYY-MM-DD` input value. */
    dueDate?: string;
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
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();
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

    const updatePayment = (
        slot: string,
        field: 'amount' | 'dueDate',
        value: string,
    ) =>
        setPayments((current) => ({
            ...current,
            [slot]: { ...current[slot], [field]: value },
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
                        {
                            amount: Math.round(
                                (Number(payments[slot.value]?.amount) || 0) *
                                    100,
                            ),
                            dueDate: payments[slot.value]?.dueDate || null,
                        },
                    ]),
                ),
            })}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <FormSection
                        title={t.contracts.clientAndSeasonSection}
                        description={t.contracts.clientAndSeasonDescription}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="clientId">
                                {t.contracts.fieldClient}
                            </Label>
                            <SearchableClientSelect
                                name="clientId"
                                id="clientId"
                                clients={clients}
                                defaultValue={initialClientId}
                                placeholder={t.clients.chooseClient}
                                searchPlaceholder={t.clients.noMatchingClient}
                            />
                            <InputError message={errors.clientId} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="season">
                                {t.contracts.fieldSeason}
                            </Label>
                            <Select name="season" defaultValue={initialSeason}>
                                <SelectTrigger id="season" className="w-full">
                                    <SelectValue
                                        placeholder={
                                            t.contracts.seasonPlaceholder
                                        }
                                    />
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
                        title={t.contracts.servicesSection}
                        description={t.contracts.servicesDescription}
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
                                            {t.contracts.fieldService}
                                        </Label>
                                        <Input
                                            id={`services-${index}-label`}
                                            type="text"
                                            name={`services[${index}][label]`}
                                            value={service.label}
                                            onChange={(event) =>
                                                updateService(
                                                    index,
                                                    'label',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={
                                                t.contracts
                                                    .serviceLabelPlaceholder
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `services.${index}.label`
                                                ]
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`services-${index}-amount`}
                                        >
                                            {t.contracts.fieldAmount}
                                        </Label>
                                        <Input
                                            id={`services-${index}-amount`}
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            name={`services[${index}][amount]`}
                                            value={service.amount}
                                            onChange={(event) =>
                                                updateService(
                                                    index,
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={
                                                t.contracts
                                                    .serviceAmountPlaceholder
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `services.${index}.amount`
                                                ]
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="mt-8 size-9 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => removeService(index)}
                                        disabled={services.length <= 1}
                                        aria-label={interpolate(
                                            t.contracts.removeService,
                                            { index: index + 1 },
                                        )}
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
                                <Plus className="size-4" />{' '}
                                {t.contracts.addService}
                            </Button>
                        </div>
                        <InputError message={errors.services} />
                    </FormSection>

                    <FormSection
                        title={t.contracts.paymentsSection}
                        description={t.contracts.paymentsDescription}
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            {slots.map((slot) => {
                                const payment = payments[slot.value] ?? {
                                    amount: '',
                                };
                                const locked = payment.paid === true;
                                const slotLabel =
                                    t.paymentSlots[
                                        slot.value as keyof typeof t.paymentSlots
                                    ] ?? slot.label;

                                return (
                                    <div
                                        key={slot.value}
                                        className="grid gap-2"
                                    >
                                        <Label
                                            htmlFor={`payments-${slot.value}`}
                                            className="flex items-center gap-2"
                                        >
                                            {slotLabel}
                                            {locked && (
                                                <Badge
                                                    variant="outline"
                                                    className="border-success/25 bg-success/10 text-success"
                                                >
                                                    {
                                                        t.contracts
                                                            .statusCollected
                                                    }
                                                </Badge>
                                            )}
                                        </Label>
                                        <div className="grid grid-cols-[1fr_140px] gap-2">
                                            <Input
                                                id={`payments-${slot.value}`}
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                disabled={locked}
                                                name={`payments[${slot.value}][amount]`}
                                                value={payment.amount}
                                                onChange={(event) =>
                                                    updatePayment(
                                                        slot.value,
                                                        'amount',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={
                                                    t.contracts
                                                        .paymentAmountPlaceholder
                                                }
                                            />
                                            <Input
                                                id={`payments-${slot.value}-dueDate`}
                                                type="date"
                                                disabled={locked}
                                                name={`payments[${slot.value}][dueDate]`}
                                                value={payment.dueDate ?? ''}
                                                onChange={(event) =>
                                                    updatePayment(
                                                        slot.value,
                                                        'dueDate',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-label={
                                                    t.contracts.paymentDueDate
                                                }
                                                title={
                                                    t.contracts.paymentDueDate
                                                }
                                                data-test={`payments-${slot.value}-dueDate`}
                                            />
                                        </div>
                                        <InputError
                                            message={
                                                errors[
                                                    `payments.${slot.value}.amount`
                                                ] ??
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
                                    {t.contracts.totalsServices}{' '}
                                    <span className="font-mono text-sm font-medium text-foreground tabular-nums">
                                        {formatCurrency(servicesTotal)}
                                    </span>
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {t.contracts.totalsPayments}{' '}
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
                                    {t.contracts.totalsMismatch}
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
