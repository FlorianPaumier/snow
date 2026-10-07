import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TourStops from '@/components/tour-stops';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { create, index, store } from '@/routes/tours';
import type { TourStop } from '@/types/tour';

type ClientOption = Omit<TourStop, 'remark'>;
export default function Create({
    workers,
    clients,
    today,
}: {
    workers: { id: string; name: string }[];
    clients: ClientOption[];
    today: string;
}) {
    const { t } = useLocale();
    const [search, setSearch] = useState('');
    const form = useForm({
        name: '',
        worker_id: '',
        scheduled_date: today,
        departure_address: '',
        arrival_address: '',
        client_ids: [] as string[],
    });
    const stops = form.data.client_ids.map((id) => ({
        ...clients.find((client) => client.id === id)!,
        remark: null,
    }));
    const available = clients.filter(
        (client) =>
            !form.data.client_ids.includes(client.id) &&
            `${client.name} ${client.address}`
                .toLocaleLowerCase()
                .includes(search.toLocaleLowerCase()),
    );
    return (
        <AppLayout
            breadcrumbs={[
                { title: t.tours.title, href: index() },
                { title: t.tours.newTour, href: create() },
            ]}
        >
            <Head title={t.tours.newTour} />
            <h1 className="sr-only">{t.tours.newTour}</h1>
            <form
                className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(store());
                }}
            >
                <h2 className="text-xl font-semibold">{t.tours.newTour}</h2>
                {!workers.length && <p>{t.tours.noWorkers}</p>}
                {!clients.length && <p>{t.tours.noClients}</p>}
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">{t.tours.name}</Label>
                        <Input
                            id="name"
                            required
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="worker_id">{t.tours.worker}</Label>
                        <select
                            id="worker_id"
                            required
                            className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                            value={form.data.worker_id}
                            onChange={(e) =>
                                form.setData('worker_id', e.target.value)
                            }
                        >
                            <option value="">{t.tours.selectWorker}</option>
                            {workers.map((worker) => (
                                <option key={worker.id} value={worker.id}>
                                    {worker.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.worker_id} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="scheduled_date">{t.tours.date}</Label>
                        <Input
                            id="scheduled_date"
                            type="date"
                            required
                            min={today}
                            value={form.data.scheduled_date}
                            onChange={(e) =>
                                form.setData('scheduled_date', e.target.value)
                            }
                        />
                        <InputError message={form.errors.scheduled_date} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="departure_address">
                            {t.tours.departure}
                        </Label>
                        <Input
                            id="departure_address"
                            value={form.data.departure_address}
                            onChange={(e) =>
                                form.setData(
                                    'departure_address',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={form.errors.departure_address} />
                    </div>
                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="arrival_address">
                            {t.tours.arrival}
                        </Label>
                        <Input
                            id="arrival_address"
                            value={form.data.arrival_address}
                            onChange={(e) =>
                                form.setData('arrival_address', e.target.value)
                            }
                        />
                        <p className="text-sm text-muted-foreground">
                            {t.tours.arrivalHint}
                        </p>
                        <InputError message={form.errors.arrival_address} />
                    </div>
                </div>
                <div className="space-y-3">
                    <Label htmlFor="client-search">{t.tours.clients}</Label>
                    <Input
                        id="client-search"
                        placeholder={t.tours.search}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    <div className="max-h-64 overflow-auto rounded-lg border">
                        {available.map((client) => (
                            <div
                                key={client.id}
                                className="flex items-center justify-between gap-3 border-b p-3 last:border-0"
                            >
                                <div>
                                    <p className="font-medium">{client.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {client.address}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        form.setData('client_ids', [
                                            ...form.data.client_ids,
                                            client.id,
                                        ])
                                    }
                                >
                                    {t.tours.add}
                                </Button>
                            </div>
                        ))}
                    </div>
                    <InputError message={form.errors.client_ids} />
                    {stops.length > 0 && (
                        <TourStops
                            stops={stops}
                            onChange={(moved) =>
                                form.setData(
                                    'client_ids',
                                    moved.map((stop) => stop.id),
                                )
                            }
                        />
                    )}
                    <div className="flex flex-wrap gap-2">
                        {stops.map((stop) => (
                            <Button
                                key={stop.id}
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    form.setData(
                                        'client_ids',
                                        form.data.client_ids.filter(
                                            (id) => id !== stop.id,
                                        ),
                                    )
                                }
                            >
                                {t.tours.remove} · {stop.name}
                            </Button>
                        ))}
                    </div>
                </div>
                {Object.entries(form.errors)
                    .filter(([key]) => key.startsWith('client_ids.'))
                    .map(([key, error]) => (
                        <InputError key={key} message={error} />
                    ))}
                <Button
                    disabled={
                        form.processing || !workers.length || !stops.length
                    }
                >
                    {t.tours.create}
                </Button>
            </form>
        </AppLayout>
    );
}
