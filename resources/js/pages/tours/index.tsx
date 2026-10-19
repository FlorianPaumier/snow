import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import TourSummary from '@/components/tour-summary';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { index as templatesIndex } from '@/routes/tour-templates';
import { create, index, show } from '@/routes/tours';
import type { TourPagination } from '@/types/tour';

export default function Index({
    tours,
    counts,
    date,
}: {
    tours: TourPagination;
    counts: Record<'assigned' | 'planned' | 'active' | 'finished', number>;
    date: string;
}) {
    const { t } = useLocale();
    const filter = useForm({ date });
    const labels = {
        assigned: t.tourTemplates.assigned,
        planned: t.tours.planned,
        active: t.tours.active,
        finished: t.tours.finished,
    };
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
                    <div className="flex flex-wrap gap-3">
                        <Button asChild variant="outline">
                            <Link href={templatesIndex()}>
                                {t.tourTemplates.title}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>{t.tours.newTour}</Link>
                        </Button>
                    </div>
                </div>
                <form
                    className="flex flex-wrap items-end gap-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        filter.get(index().url);
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="date">{t.tours.date}</Label>
                        <Input
                            id="date"
                            type="date"
                            value={filter.data.date}
                            onChange={(event) =>
                                filter.setData('date', event.target.value)
                            }
                        />
                    </div>
                    <Button variant="outline" disabled={filter.processing}>
                        {t.tourTemplates.filter}
                    </Button>
                    <Link href={index()} className="p-2 underline">
                        {t.tourTemplates.clear}
                    </Link>
                </form>
                <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {(Object.keys(labels) as (keyof typeof labels)[]).map(
                        (status) => (
                            <div key={status} className="rounded-lg border p-4">
                                <dt>{labels[status]}</dt>
                                <dd className="text-2xl font-semibold">
                                    {counts[status]}
                                </dd>
                            </div>
                        ),
                    )}
                </dl>
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
