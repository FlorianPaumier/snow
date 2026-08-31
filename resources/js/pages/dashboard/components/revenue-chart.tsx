import { formatCurrency } from '@/lib/utils';

export type RevenuePoint = {
    season: string;
    revenue: number;
    collected: number;
    outstanding: number;
};

const WIDTH = 560;
const HEIGHT = 240;
const PADDING = { top: 24, right: 16, bottom: 32, left: 64 };
const BAR_WIDTH = 26;
const GAP = 14;

/**
 * Grouped bar chart of revenue vs collected per season, drawn as inline SVG
 * so no chart dependency is needed. Scales to its container via viewBox.
 */
export default function RevenueChart({
    data,
    currentSeason,
}: {
    data: RevenuePoint[];
    currentSeason: string;
}) {
    if (data.length === 0) {
        return (
            <p className="flex h-40 items-center justify-center text-sm text-muted-foreground">
                Aucun revenu à afficher pour le moment.
            </p>
        );
    }

    const innerWidth = WIDTH - PADDING.left - PADDING.right;
    const innerHeight = HEIGHT - PADDING.top - PADDING.bottom;

    const maxValue = Math.max(...data.map((point) => point.revenue), 1);
    const niceMax = niceCeiling(maxValue);
    const scale = (value: number): number => (value / niceMax) * innerHeight;

    const groupWidth = BAR_WIDTH * 2 + GAP;
    const step = innerWidth / data.length;

    const summary = data
        .map(
            (point) =>
                `${point.season} : ${formatCurrency(point.revenue)} dont ${formatCurrency(point.collected)} encaissés`,
        )
        .join(' ; ');

    return (
        <svg
            viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
            className="h-auto w-full"
            role="img"
            aria-label={`Évolution des revenus par saison — ${summary}`}
        >
            {/* Horizontal gridlines with axis labels... */}
            {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
                const y = PADDING.top + innerHeight - ratio * innerHeight;

                return (
                    <g key={ratio}>
                        <line
                            x1={PADDING.left}
                            x2={WIDTH - PADDING.right}
                            y1={y}
                            y2={y}
                            className="stroke-border"
                            strokeWidth={1}
                            strokeDasharray={ratio === 0 ? undefined : '3 3'}
                        />
                        <text
                            x={PADDING.left - 8}
                            y={y + 4}
                            textAnchor="end"
                            className="fill-muted-foreground text-[10px] tabular-nums"
                        >
                            {formatCurrency(niceMax * ratio)}
                        </text>
                    </g>
                );
            })}

            {/* Grouped bars: revenue (glacier) then collected (green)... */}
            {data.map((point, index) => {
                const groupX = PADDING.left + index * step + (step - groupWidth) / 2;
                const isCurrent = point.season === currentSeason;

                return (
                    <g key={point.season}>
                        <rect
                            x={groupX}
                            y={PADDING.top + innerHeight - scale(point.revenue)}
                            width={BAR_WIDTH}
                            height={scale(point.revenue)}
                            className={isCurrent ? 'fill-chart-1' : 'fill-chart-1/60'}
                            rx={3}
                        >
                            <title>
                                {`${point.season} — Revenus : ${formatCurrency(point.revenue)}`}
                            </title>
                        </rect>
                        <rect
                            x={groupX + BAR_WIDTH + GAP / 2}
                            y={PADDING.top + innerHeight - scale(point.collected)}
                            width={BAR_WIDTH}
                            height={scale(point.collected)}
                            className={isCurrent ? 'fill-chart-3' : 'fill-chart-3/60'}
                            rx={3}
                        >
                            <title>
                                {`${point.season} — Encaissé : ${formatCurrency(point.collected)}`}
                            </title>
                        </rect>
                        <text
                            x={groupX + groupWidth / 2}
                            y={HEIGHT - PADDING.bottom + 16}
                            textAnchor="middle"
                            className={
                                isCurrent
                                    ? 'fill-foreground text-[10px] font-medium'
                                    : 'fill-muted-foreground text-[10px]'
                            }
                        >
                            {point.season}
                        </text>
                    </g>
                );
            })}
        </svg>
    );
}

/**
 * Round up to a clean axis maximum (e.g. 575 000 → 600 000).
 */
function niceCeiling(value: number): number {
    const magnitude = 10 ** Math.floor(Math.log10(value));
    const scaled = value / magnitude;

    const nice = scaled <= 1
        ? 1
        : scaled <= 1.5
          ? 1.5
          : Math.ceil(scaled);

    return nice * magnitude;
}
