import { Head, Link, router, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TourStops from '@/components/tour-stops';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { store as dispatch } from '@/routes/tour-dispatches';
import { create, destroy, edit, index } from '@/routes/tour-templates';
import { index as tours } from '@/routes/tours';
import type { TourStop } from '@/types/tour';

type Template = {
    id: string;
    name: string;
    worker: string | null;
    valid: boolean;
    missingClients: number;
    stops: TourStop[];
    departure_address: string | null;
    arrival_address: string | null;
};
export default function Templates({
    templates,
    requestKey,
    today,
}: {
    templates: {
        data: Template[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    requestKey: string;
    today: string;
}) {
    const { t } = useLocale();
    const form = useForm({
        request_key: requestKey,
        scheduled_date: today,
        template_ids: [] as string[],
    });
    const eligible = templates.data
        .filter((template) => template.valid)
        .map((template) => template.id);
    return (
        <AppLayout
            breadcrumbs={[
                { title: t.tours.title, href: tours() },
                { title: t.tourTemplates.title, href: index() },
            ]}
        >
            <Head title={t.tourTemplates.title} />
            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-semibold">
                        {t.tourTemplates.title}
                    </h1>
                    <Button asChild>
                        <Link href={create()}>
                            {t.tourTemplates.newTemplate}
                        </Link>
                    </Button>
                </div>
                <p>{t.tourTemplates.description}</p>
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(dispatch());
                    }}
                >
                    <div className="max-w-sm space-y-2">
                        <Label htmlFor="scheduled_date">
                            {t.tourTemplates.date}
                        </Label>
                        <Input
                            aria-invalid={Boolean(form.errors.scheduled_date)}
                            aria-describedby={
                                form.errors.scheduled_date
                                    ? 'scheduled_date' + '-error'
                                    : undefined
                            }
                            id="scheduled_date"
                            type="date"
                            required
                            min={today}
                            value={form.data.scheduled_date}
                            onChange={(event) =>
                                form.setData(
                                    'scheduled_date',
                                    event.target.value,
                                )
                            }
                        />
                        <InputError
                            id={'scheduled_date' + '-error'}
                            message={form.errors.scheduled_date}
                        />
                    </div>
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="all-templates"
                            checked={
                                eligible.length > 0 &&
                                eligible.every((id) =>
                                    form.data.template_ids.includes(id),
                                )
                            }
                            disabled={!eligible.length || form.processing}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'template_ids',
                                    checked === true ? eligible : [],
                                )
                            }
                        />
                        <Label htmlFor="all-templates">
                            {t.tourTemplates.selectAll}
                        </Label>
                    </div>
                    {!templates.data.length && <p>{t.tourTemplates.empty}</p>}
                    {templates.data.map((template) => (
                        <article
                            key={template.id}
                            className="space-y-3 rounded-xl border p-5"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id={template.id}
                                        aria-label={template.name}
                                        checked={form.data.template_ids.includes(
                                            template.id,
                                        )}
                                        disabled={
                                            !template.valid || form.processing
                                        }
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'template_ids',
                                                checked === true
                                                    ? [
                                                          ...form.data
                                                              .template_ids,
                                                          template.id,
                                                      ]
                                                    : form.data.template_ids.filter(
                                                          (id) =>
                                                              id !==
                                                              template.id,
                                                      ),
                                            )
                                        }
                                    />
                                    <Label htmlFor={template.id}>
                                        {template.name}
                                    </Label>
                                </div>
                                <div className="flex gap-4">
                                    <Link
                                        href={edit(template.id)}
                                        className="underline"
                                    >
                                        {t.tourTemplates.edit}
                                    </Link>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        disabled={form.processing}
                                        onClick={() =>
                                            router.delete(
                                                destroy(template.id).url,
                                            )
                                        }
                                    >
                                        {t.tourTemplates.delete}
                                    </Button>
                                </div>
                            </div>
                            <p>
                                {template.worker ??
                                    t.tourTemplates.missingWorker}
                            </p>
                            {(template.missingClients > 0 ||
                                !template.stops.length) && (
                                <p className="text-destructive">
                                    {t.tourTemplates.missingClients}
                                </p>
                            )}
                            <details>
                                <summary className="cursor-pointer">
                                    {t.tourTemplates.preview}
                                </summary>
                                <div className="space-y-3 pt-3">
                                    <p>
                                        {t.tours.departure}:{' '}
                                        {template.departure_address || '—'} ·{' '}
                                        {t.tours.arrival}:{' '}
                                        {template.arrival_address ||
                                            template.departure_address ||
                                            '—'}
                                    </p>
                                    <TourStops stops={template.stops} />
                                </div>
                            </details>
                        </article>
                    ))}
                    <InputError
                        message={
                            form.errors.template_ids ?? form.errors.request_key
                        }
                    />
                    {Object.entries(form.errors)
                        .filter(([key]) => key.startsWith('template_ids.'))
                        .map(([key, error]) => (
                            <InputError key={key} message={error} />
                        ))}
                    <Button
                        disabled={
                            form.processing || !form.data.template_ids.length
                        }
                    >
                        {t.tourTemplates.dispatch}
                    </Button>
                </form>
                <div className="flex gap-4">
                    {templates.prev_page_url && (
                        <Link href={templates.prev_page_url}>
                            {t.tours.previous}
                        </Link>
                    )}
                    {templates.next_page_url && (
                        <Link href={templates.next_page_url}>
                            {t.tours.next}
                        </Link>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
