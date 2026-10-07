import { Badge } from '@/components/ui/badge';
import { useLocale } from '@/hooks/use-locale';
import type { Tour } from '@/types/tour';

export default function TourSummary({ tour }: { tour: Tour }) {
    const { t, locale } = useLocale();
    const dateTime = (value: string) =>
        new Date(value).toLocaleString(locale, {
            timeZone: 'America/Toronto',
            dateStyle: 'short',
            timeStyle: 'short',
        });
    const minutes =
        tour.started_at && tour.finished_at
            ? Math.floor(
                  (Date.parse(tour.finished_at) - Date.parse(tour.started_at)) /
                      60000,
              )
            : null;
    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <Badge
                    variant={
                        tour.started_at && !tour.finished_at
                            ? 'default'
                            : 'secondary'
                    }
                >
                    {tour.finished_at
                        ? t.tours.finished
                        : tour.started_at
                          ? t.tours.active
                          : t.tours.planned}
                </Badge>
                <span className="text-sm text-muted-foreground">
                    {tour.scheduled_date.slice(0, 10)}
                    {tour.worker ? ` · ${tour.worker.name}` : ''}
                </span>
                {tour.optimized_at && (
                    <Badge variant="outline">{t.tours.optimized}</Badge>
                )}
            </div>
            <dl className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                {tour.started_at && (
                    <div>
                        <dt className="text-muted-foreground">
                            {t.tours.startedAt}
                        </dt>
                        <dd>{dateTime(tour.started_at)}</dd>
                    </div>
                )}
                {tour.finished_at && (
                    <div>
                        <dt className="text-muted-foreground">
                            {t.tours.finishedAt}
                        </dt>
                        <dd>{dateTime(tour.finished_at)}</dd>
                    </div>
                )}
                {minutes !== null && (
                    <div>
                        <dt className="text-muted-foreground">
                            {t.tours.duration}
                        </dt>
                        <dd>
                            {Math.floor(minutes / 60)} h {minutes % 60}{' '}
                            {t.tours.minutes}
                        </dd>
                    </div>
                )}
                <div>
                    <dt className="text-muted-foreground">
                        {t.tours.plannedClients}
                    </dt>
                    <dd>{tour.stops.length}</dd>
                </div>
            </dl>
        </div>
    );
}
