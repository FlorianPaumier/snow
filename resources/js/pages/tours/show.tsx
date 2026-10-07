import { Form, Head, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TourStops from '@/components/tour-stops';
import TourSummary from '@/components/tour-summary';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { index, optimize, show, update, worker } from '@/routes/tours';
import type { Tour } from '@/types/tour';

function OrderEditor({ tour }: { tour: Tour }) {
    const { t } = useLocale();
    const form = useForm({ client_ids: tour.stops.map((stop) => stop.id) });
    const stops = form.data.client_ids.map(
        (id) => tour.stops.find((stop) => stop.id === id)!,
    );
    return (
        <form
            className="space-y-3"
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(update(tour.id));
            }}
        >
            <TourStops
                stops={stops}
                onChange={
                    form.processing
                        ? undefined
                        : (moved) =>
                              form.setData(
                                  'client_ids',
                                  moved.map((stop) => stop.id),
                              )
                }
            />
            {Object.entries(form.errors).map(([key, error]) => (
                <InputError key={key} message={error} />
            ))}
            <Button
                variant="outline"
                disabled={
                    form.processing ||
                    JSON.stringify(form.data.client_ids) ===
                        JSON.stringify(tour.stops.map((stop) => stop.id))
                }
            >
                {t.tours.saveOrder}
            </Button>
        </form>
    );
}

export default function Show({
    tour,
    optimizationAvailable,
}: {
    tour: Tour;
    optimizationAvailable: boolean;
}) {
    const { t } = useLocale();
    const { auth } = usePage().props;
    const canManage = auth.can?.manageUsers ?? false;
    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: canManage ? t.tours.title : t.tours.myTours,
                    href: canManage ? index() : worker(),
                },
                { title: tour.name, href: show(tour.id) },
            ]}
        >
            <Head title={tour.name} />
            <h1 className="sr-only">{tour.name}</h1>
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <h2 className="text-xl font-semibold">{tour.name}</h2>
                <TourSummary tour={tour} />
                {tour.departure_address && (
                    <p className="text-sm">
                        <span className="font-medium">
                            {t.tours.departure} :{' '}
                        </span>
                        {tour.departure_address}
                        <br />
                        <span className="font-medium">
                            {t.tours.arrival} :{' '}
                        </span>
                        {tour.arrival_address || tour.departure_address}
                    </p>
                )}
                {canManage && !tour.started_at && (
                    <Form {...optimize.form(tour.id)} disableWhileProcessing>
                        {({ processing, errors }) => (
                            <div className="space-y-3">
                                <Button
                                    disabled={
                                        processing || !optimizationAvailable
                                    }
                                >
                                    {t.tours.optimize}
                                </Button>
                                {!optimizationAvailable && (
                                    <p className="text-sm text-muted-foreground">
                                        {t.tours.optimizationMissing}
                                    </p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    {t.tours.optimizationHint}
                                </p>
                                {Object.entries(errors).map(([key, error]) => (
                                    <InputError key={key} message={error} />
                                ))}
                            </div>
                        )}
                    </Form>
                )}
                {tour.started_at && canManage && (
                    <p className="text-sm text-muted-foreground">
                        {t.tours.locked}
                    </p>
                )}
                {canManage && !tour.started_at ? (
                    <OrderEditor key={JSON.stringify(tour.stops)} tour={tour} />
                ) : (
                    <TourStops stops={tour.stops} />
                )}
            </div>
        </AppLayout>
    );
}
