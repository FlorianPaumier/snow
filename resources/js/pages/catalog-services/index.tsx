import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { destroy, index, store, update } from '@/routes/catalog-services';
import type { CatalogService } from '@/types/catalog-service';

function ServiceForm({ service }: { service?: CatalogService }) {
    const { t } = useLocale();
    const prefix = service ? `service-${service.id}` : 'new-service';
    return (
        <Form
            {...(service ? update.form(service.id) : store.form())}
            errorBag={prefix}
            resetOnSuccess={service ? [] : ['label', 'amount']}
            options={{ preserveScroll: true }}
            transform={(data) => ({
                ...data,
                amount: Math.round(Number(data.amount) * 100),
            })}
        >
            {({ errors, processing }) => (
                <div className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-[1fr_160px]">
                        <div className="grid gap-2">
                            <Label htmlFor={`${prefix}-label`}>
                                {t.catalog.name}
                            </Label>
                            <Input
                                aria-invalid={Boolean(errors.label)}
                                aria-describedby={
                                    errors.label
                                        ? `${prefix}-label` + '-error'
                                        : undefined
                                }
                                id={`${prefix}-label`}
                                name="label"
                                defaultValue={service?.label}
                                required
                                maxLength={255}
                            />
                            <InputError
                                id={`${prefix}-label` + '-error'}
                                message={errors.label}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`${prefix}-amount`}>
                                {t.catalog.price}
                            </Label>
                            <Input
                                aria-invalid={Boolean(errors.amount)}
                                aria-describedby={
                                    errors.amount
                                        ? `${prefix}-amount` + '-error'
                                        : undefined
                                }
                                id={`${prefix}-amount`}
                                name="amount"
                                type="number"
                                min="0"
                                max="100000"
                                step="0.01"
                                required
                                defaultValue={
                                    service
                                        ? (service.amount / 100).toFixed(2)
                                        : undefined
                                }
                            />
                            <InputError
                                id={`${prefix}-amount` + '-error'}
                                message={errors.amount}
                            />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        aria-busy={processing}
                    >
                        {processing && <Spinner />}
                        {service ? t.common.save : t.catalog.add}
                    </Button>
                </div>
            )}
        </Form>
    );
}

export default function ServiceCatalog({
    services,
}: {
    services: CatalogService[];
}) {
    const { t } = useLocale();
    return (
        <AppLayout breadcrumbs={[{ title: t.catalog.title, href: index() }]}>
            <Head title={t.catalog.title} />
            <h1 className="sr-only">{t.catalog.title}</h1>
            <SettingsLayout>
                <div className="space-y-8">
                    <Heading
                        variant="small"
                        title={t.catalog.title}
                        description={t.catalog.description}
                    />
                    <section
                        className="space-y-4"
                        aria-labelledby="new-service-title"
                    >
                        <h2 id="new-service-title" className="font-medium">
                            {t.catalog.newService}
                        </h2>
                        <ServiceForm />
                    </section>
                    <section
                        className="space-y-4"
                        aria-labelledby="catalog-title"
                    >
                        <h2 id="catalog-title" className="font-medium">
                            {t.catalog.available}
                        </h2>
                        {services.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                {t.catalog.empty}
                            </p>
                        )}
                        {services.map((service) => (
                            <div
                                key={service.id}
                                className="space-y-4 rounded-lg border p-4"
                                data-test={`catalog-service-${service.id}`}
                            >
                                <h3 className="font-medium">{service.label}</h3>
                                <ServiceForm service={service} />
                                <Form
                                    {...destroy.form(service.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                            aria-busy={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t.catalog.archive}
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ))}
                    </section>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
