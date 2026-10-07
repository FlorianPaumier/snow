import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TourStops from '@/components/tour-stops';
import TourSummary from '@/components/tour-summary';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { finish, show, start, worker } from '@/routes/tours';
import type { Tour, TourPagination } from '@/types/tour';

export default function Worker({
    tours,
    history,
    today,
}: {
    tours: Tour[];
    history: TourPagination;
    today: string;
}) {
    const { t } = useLocale();
    const hasActive = tours.some((tour) => tour.started_at !== null);
    return (
        <AppLayout breadcrumbs={[{ title: t.tours.myTours, href: worker() }]}>
            <Head title={t.tours.myTours} />
            <h1 className="sr-only">{t.tours.myTours}</h1>
            <div className="mx-auto w-full max-w-4xl space-y-8 p-4 md:p-6">
                <header className="space-y-2">
                    <h2 className="text-2xl font-semibold">
                        {t.tours.myTours}
                    </h2>
                    <p className="text-muted-foreground">
                        {t.tours.workerDescription}
                    </p>
                </header>
                {!tours.length && (
                    <p className="text-muted-foreground">{t.tours.empty}</p>
                )}
                {tours.map((tour) => (
                    <section
                        key={tour.id}
                        className="space-y-4 rounded-xl border bg-card p-4 md:p-6"
                    >
                        <h2 className="text-lg font-semibold">{tour.name}</h2>
                        <TourSummary tour={tour} />
                        <Form
                            {...(tour.started_at
                                ? finish.form(tour.id)
                                : start.form(tour.id))}
                            disableWhileProcessing
                        >
                            {({ processing, errors }) => (
                                <div className="space-y-2">
                                    <Button
                                        className="min-h-14 w-full text-lg"
                                        disabled={
                                            processing ||
                                            (!tour.started_at &&
                                                (hasActive ||
                                                    tour.scheduled_date.slice(
                                                        0,
                                                        10,
                                                    ) > today))
                                        }
                                    >
                                        {tour.started_at
                                            ? t.tours.finish
                                            : t.tours.start}
                                    </Button>
                                    {Object.entries(errors).map(
                                        ([key, error]) => (
                                            <InputError
                                                key={key}
                                                message={error}
                                            />
                                        ),
                                    )}
                                </div>
                            )}
                        </Form>
                        <TourStops stops={tour.stops} />
                    </section>
                ))}
                <section className="space-y-4">
                    <h2 className="text-xl font-semibold">{t.tours.history}</h2>
                    {!history.data.length && (
                        <p className="text-muted-foreground">
                            {t.tours.noHistory}
                        </p>
                    )}
                    {history.data.map((tour) => (
                        <Link
                            key={tour.id}
                            href={show(tour.id)}
                            className="block space-y-3 rounded-xl border p-4 hover:border-primary"
                        >
                            <h3 className="font-semibold">{tour.name}</h3>
                            <TourSummary tour={tour} />
                        </Link>
                    ))}
                    <div className="flex gap-3">
                        {history.prev_page_url && (
                            <Button asChild variant="outline">
                                <Link href={history.prev_page_url}>
                                    {t.tours.previous}
                                </Link>
                            </Button>
                        )}
                        {history.next_page_url && (
                            <Button asChild variant="outline">
                                <Link href={history.next_page_url}>
                                    {t.tours.next}
                                </Link>
                            </Button>
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
