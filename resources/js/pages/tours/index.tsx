import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import TourSummary from '@/components/tour-summary';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { create, index, show } from '@/routes/tours';
import type { TourPagination } from '@/types/tour';

export default function Index({ tours }: { tours: TourPagination }) {
    const { t } = useLocale();
    return (
        <AppLayout breadcrumbs={[{ title: t.tours.title, href: index() }]}>
            <Head title={t.tours.title} />
            <h1 className="sr-only">{t.tours.title}</h1>
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={t.tours.title}
                        description={t.tours.description}
                    />
                    <Button asChild>
                        <Link href={create()}>{t.tours.newTour}</Link>
                    </Button>
                </div>
                {!tours.data.length && (
                    <p className="text-muted-foreground">{t.tours.empty}</p>
                )}
                <div className="grid gap-4 md:grid-cols-2">
                    {tours.data.map((tour) => (
                        <Link
                            key={tour.id}
                            href={show(tour.id)}
                            className="space-y-3 rounded-xl border bg-card p-5 hover:border-primary"
                        >
                            <h2 className="font-semibold">{tour.name}</h2>
                            <TourSummary tour={tour} />
                        </Link>
                    ))}
                </div>
                <div className="flex gap-3">
                    {tours.prev_page_url && (
                        <Button asChild variant="outline">
                            <Link href={tours.prev_page_url}>
                                {t.tours.previous}
                            </Link>
                        </Button>
                    )}
                    {tours.next_page_url && (
                        <Button asChild variant="outline">
                            <Link href={tours.next_page_url}>
                                {t.tours.next}
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
