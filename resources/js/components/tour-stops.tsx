import { ArrowDown, ArrowUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import type { TourStop } from '@/types/tour';

export function moveStop<T>(items: T[], index: number, offset: number): T[] {
    const moved = [...items];
    [moved[index], moved[index + offset]] = [
        moved[index + offset],
        moved[index],
    ];
    return moved;
}

export default function TourStops({
    stops,
    onChange,
}: {
    stops: TourStop[];
    onChange?: (stops: TourStop[]) => void;
}) {
    const { t } = useLocale();
    return (
        <ol className="divide-y rounded-xl border bg-card">
            {stops.map((stop, index) => (
                <li key={stop.id} className="flex items-start gap-3 p-4">
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {index + 1}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="font-medium">{stop.name}</p>
                        <p className="text-sm text-muted-foreground">
                            {stop.address}
                        </p>
                        {stop.remark && (
                            <p className="mt-2 text-sm">{stop.remark}</p>
                        )}
                    </div>
                    {onChange && (
                        <div className="flex gap-1">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={`${t.tours.up} ${stop.name}`}
                                disabled={index === 0}
                                onClick={() =>
                                    onChange(moveStop(stops, index, -1))
                                }
                            >
                                <ArrowUp className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={`${t.tours.down} ${stop.name}`}
                                disabled={index === stops.length - 1}
                                onClick={() =>
                                    onChange(moveStop(stops, index, 1))
                                }
                            >
                                <ArrowDown className="size-4" />
                            </Button>
                        </div>
                    )}
                </li>
            ))}
        </ol>
    );
}
