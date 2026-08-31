export type ClientEvolutionPoint = {
    season: string;
    new_clients: number;
    total_clients: number;
};

const WIDTH = 560;
const HEIGHT = 240;
const PADDING = { top: 24, right: 16, bottom: 32, left: 40 };
const BAR_WIDTH = 32;
const LINE_STROKE = 2;

/**
 * New clients per season as bars, with the cumulative clientele as a line,
 * drawn as inline SVG so no chart dependency is needed. Scales to its
 * container via viewBox.
 */
export default function ClientEvolutionChart({
    data,
    currentSeason,
}: {
    data: ClientEvolutionPoint[];
    currentSeason: string;
}) {
    if (data.length === 0) {
        return (
            <p className="flex h-40 items-center justify-center text-sm text-muted-foreground">
                Aucun client à afficher pour le moment.
            </p>
        );
    }

    const innerWidth = WIDTH - PADDING.left - PADDING.right;
    const innerHeight = HEIGHT - PADDING.top - PADDING.bottom;

    const maxValue = Math.max(...data.map((point) => point.total_clients), 1);
    const scale = (value: number): number => (value / maxValue) * innerHeight;
    const step = innerWidth / data.length;

    const linePoints = data
        .map((point, index) => {
            const x = PADDING.left + index * step + step / 2;
            const y = PADDING.top + innerHeight - scale(point.total_clients);

            return `${x},${y}`;
        })
        .join(' ');

    const summary = data
        .map(
            (point) =>
                `${point.season} : ${point.new_clients} nouveau${point.new_clients === 1 ? '' : 'x'} client${point.new_clients === 1 ? '' : 's'}, ${point.total_clients} au total`,
        )
        .join(' ; ');

    return (
        <svg
            viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
            className="h-auto w-full"
            role="img"
            aria-label={`Évolution de la clientèle par saison — ${summary}`}
        >
            {/* Horizontal gridlines with axis labels... */}
            {gridlineRatios(maxValue).map((ratio) => {
                const y = PADDING.top + innerHeight - ratio.ratio * innerHeight;

                return (
                    <g key={ratio.value}>
                        <line
                            x1={PADDING.left}
                            x2={WIDTH - PADDING.right}
                            y1={y}
                            y2={y}
                            className="stroke-border"
                            strokeWidth={1}
                            strokeDasharray={ratio.ratio === 0 ? undefined : '3 3'}
                        />
                        <text
                            x={PADDING.left - 8}
                            y={y + 4}
                            textAnchor="end"
                            className="fill-muted-foreground text-[10px] tabular-nums"
                        >
                            {ratio.value}
                        </text>
                    </g>
                );
            })}

            {/* New-client bars... */}
            {data.map((point, index) => {
                const barX =
                    PADDING.left + index * step + (step - BAR_WIDTH) / 2;
                const barY = PADDING.top + innerHeight - scale(point.new_clients);
                const isCurrent = point.season === currentSeason;

                return (
                    <rect
                        key={point.season}
                        x={barX}
                        y={barY}
                        width={BAR_WIDTH}
                        height={scale(point.new_clients)}
                        className={isCurrent ? 'fill-chart-4' : 'fill-chart-4/60'}
                        rx={3}
                    >
                        <title>
                            {`${point.season} — ${point.new_clients} nouveau${point.new_clients === 1 ? '' : 'x'} client${point.new_clients === 1 ? '' : 's'}`}
                        </title>
                    </rect>
                );
            })}

            {/* Cumulative clientele line... */}
            <polyline
                points={linePoints}
                fill="none"
                className="stroke-chart-1"
                strokeWidth={LINE_STROKE}
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            {data.map((point, index) => {
                const x = PADDING.left + index * step + step / 2;
                const y = PADDING.top + innerHeight - scale(point.total_clients);

                return (
                    <circle key={point.season} cx={x} cy={y} r={3.5} className="fill-chart-1">
                        <title>
                            {`${point.season} — ${point.total_clients} clients au total`}
                        </title>
                    </circle>
                );
            })}

            {/* Season labels... */}
            {data.map((point, index) => {
                const x = PADDING.left + index * step + step / 2;
                const isCurrent = point.season === currentSeason;

                return (
                    <text
                        key={`label-${point.season}`}
                        x={x}
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
                );
            })}
        </svg>
    );
}

/**
 * Integer gridlines (counts are small): quarter ticks of the axis maximum.
 */
function gridlineRatios(maxValue: number): Array<{ value: number; ratio: number }> {
    const ticks = [0, 1, 2, 3, 4].filter((tick) => tick <= maxValue);

    return ticks.length > 1
        ? ticks.map((tick) => ({ value: tick, ratio: tick / maxValue }))
        : [{ value: 0, ratio: 0 }, { value: 1, ratio: 1 }];
}
