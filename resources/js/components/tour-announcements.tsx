import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { store } from '@/routes/tour-notifications';
import type { Tour } from '@/types/tour';

export type Announcement = {
    event: string;
    status: string;
    reason: string | null;
};

export default function TourAnnouncements({
    tour,
    announcements,
}: {
    tour: Tour;
    announcements: Announcement[];
}) {
    const { t } = useLocale();
    const [event, setEvent] = useState(
        tour.finished_at ? 'finished' : 'started',
    );
    return (
        <section
            className="space-y-4 rounded-xl border p-5"
            aria-labelledby="service-updates-title"
        >
            <h2 id="service-updates-title" className="font-semibold">
                {t.serviceUpdates.title}
            </h2>
            <p className="text-sm text-muted-foreground">
                {t.serviceUpdates.privacy}
            </p>
            <Form {...store.form(tour.id)}>
                {({ processing, errors }) => (
                    <div className="space-y-3">
                        <label className="flex flex-col gap-2">
                            {t.serviceUpdates.event}
                            <select
                                name="event"
                                value={event}
                                onChange={(e) => setEvent(e.target.value)}
                                className="rounded-md border bg-background p-2"
                            >
                                <option
                                    value="started"
                                    disabled={
                                        !tour.started_at || !!tour.finished_at
                                    }
                                >
                                    {t.serviceUpdates.started}
                                </option>
                                <option
                                    value="finished"
                                    disabled={!tour.finished_at}
                                >
                                    {t.serviceUpdates.finished}
                                </option>
                            </select>
                        </label>
                        <blockquote className="space-y-2 rounded-md bg-muted p-4 text-sm">
                            <p>
                                {event === 'finished'
                                    ? t.serviceUpdates.finishedText
                                    : t.serviceUpdates.startedText}
                            </p>
                            <p>{t.serviceUpdates.disclaimer}</p>
                        </blockquote>
                        <label className="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="confirmed"
                                value="1"
                                required
                                className="mt-1 size-4"
                            />
                            <span>{t.serviceUpdates.confirm}</span>
                        </label>
                        <InputError
                            message={errors.event ?? errors.confirmed}
                        />
                        <Button disabled={processing || !tour.started_at}>
                            {t.serviceUpdates.send}
                        </Button>
                    </div>
                )}
            </Form>
            {(['started', 'finished'] as const).map((kind) => {
                const rows = announcements.filter(
                    (notice) => notice.event === kind,
                );
                return (
                    rows.length > 0 && (
                        <div
                            key={kind}
                            className="space-y-1 text-sm"
                            role="status"
                        >
                            <h3 className="font-medium">
                                {t.serviceUpdates[kind]}
                            </h3>
                            <p>
                                {(
                                    [
                                        'pending',
                                        'sending',
                                        'sent',
                                        'skipped',
                                        'uncertain',
                                    ] as const
                                )
                                    .map(
                                        (state) =>
                                            `${t.serviceUpdates[state]}: ${rows.filter((notice) => notice.status === state).length}`,
                                    )
                                    .join(' · ')}
                            </p>
                            <p>
                                {(
                                    [
                                        ...new Set(
                                            rows
                                                .map((notice) => notice.reason)
                                                .filter(
                                                    (reason) =>
                                                        reason &&
                                                        reason in
                                                            t.serviceUpdates,
                                                ),
                                        ),
                                    ] as (keyof typeof t.serviceUpdates)[]
                                )
                                    .map(
                                        (reason) =>
                                            `${t.serviceUpdates[reason]}: ${rows.filter((notice) => notice.reason === reason).length}`,
                                    )
                                    .join(' · ')}
                            </p>
                        </div>
                    )
                );
            })}
            <p className="text-xs text-muted-foreground">
                {t.serviceUpdates.failureHint}
            </p>
        </section>
    );
}
