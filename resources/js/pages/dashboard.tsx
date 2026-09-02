import { Head, Link, router } from '@inertiajs/react';
import {
    Banknote,
    CalendarRange,
    Eye,
    FileText,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { interpolate, useCurrencyFormatter } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import ClientEvolutionChart, {
    type ClientEvolutionPoint,
} from './dashboard/components/client-evolution-chart';
import RevenueChart, {
    type RevenuePoint,
} from './dashboard/components/revenue-chart';

type SeasonSummary = {
    revenue: number;
    collected: number;
    outstanding: number;
    contracts_count: number;
};

type UnpaidInstallment = {
    id: number;
    amount: number;
    slot: string;
    contract_id: string;
    client: {
        id: string;
        displayName: string;
        clientNumber: string;
        address_street: string;
        address_city: string;
        phone: string | null;
    };
};

type SeasonRecapRow = {
    season: string;
    revenue: number;
    collected: number;
    outstanding: number;
    contracts_count: number;
};

const PAYMENT_SLOT_VALUES = ['deposit', 'first', 'second', 'balance'] as const;

const initialsOf = (name: string) =>
    name
        .split(/\s+/)
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();

function EmptyRow({ colSpan, hint }: { colSpan: number; hint: string }) {
    const { t } = useLocale();

    return (
        <TableRow>
            <TableCell colSpan={colSpan} className="h-28 text-center">
                <div className="flex flex-col items-center gap-1.5">
                    <p className="text-sm font-medium">{t.common.none}</p>
                    <p className="text-xs text-muted-foreground">{hint}</p>
                </div>
            </TableCell>
        </TableRow>
    );
}

function ChartLegend() {
    const { t } = useLocale();

    return (
        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
            <span className="flex items-center gap-1.5">
                <span
                    aria-hidden="true"
                    className="size-2.5 rounded-sm bg-chart-1"
                />
                {t.dashboard.revenueLegendRevenue}
            </span>
            <span className="flex items-center gap-1.5">
                <span
                    aria-hidden="true"
                    className="size-2.5 rounded-sm bg-chart-3"
                />
                {t.dashboard.revenueLegendCollected}
            </span>
        </div>
    );
}

export default function Dashboard({
    season,
    seasons,
    summary,
    unpaidInstallments,
    seasonRecap,
    revenueEvolution,
    clientEvolution,
    slot,
}: {
    season: string;
    seasons: string[];
    summary: SeasonSummary;
    unpaidInstallments: UnpaidInstallment[];
    seasonRecap: SeasonRecapRow[];
    revenueEvolution: RevenuePoint[];
    clientEvolution: ClientEvolutionPoint[];
    slot: string;
}) {
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.dashboard.title,
            href: dashboard(),
        },
    ];

    const slotLabel = (value: string) =>
        t.paymentSlots[value as keyof typeof t.paymentSlots] ?? value;

    const selectSeason = (nextSeason: string) => {
        router.get(
            dashboard(),
            { season: nextSeason, ...(slot !== 'second' ? { slot } : {}) },
            { preserveState: true, replace: true },
        );
    };

    const selectSlot = (nextSlot: string) => {
        router.get(
            dashboard(),
            { season, slot: nextSlot },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.dashboard.title} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t.dashboard.title}
                        description={interpolate(t.dashboard.description, {
                            season,
                        })}
                    />

                    <Select
                        name="season"
                        value={season}
                        onValueChange={selectSeason}
                    >
                        <SelectTrigger
                            id="season"
                            aria-label={t.dashboard.seasonLabel}
                            className="w-[180px]"
                        >
                            <CalendarRange
                                aria-hidden="true"
                                className="size-4 text-muted-foreground"
                            />
                            <SelectValue
                                placeholder={t.dashboard.seasonLabel}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {seasons.map((seasonOption) => (
                                <SelectItem
                                    key={seasonOption}
                                    value={seasonOption}
                                >
                                    {seasonOption}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* KPI cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>
                                {interpolate(t.dashboard.revenue, { season })}
                            </CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {formatCurrency(summary.revenue)}
                            </CardTitle>
                            <CardAction>
                                <Badge variant="outline">
                                    <FileText
                                        aria-hidden="true"
                                        className={''}
                                    />
                                    {interpolate(t.dashboard.contracts, {
                                        count: String(summary.contracts_count),
                                    })}
                                </Badge>
                            </CardAction>
                        </CardHeader>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>
                                {t.dashboard.collected}
                            </CardDescription>
                            <CardTitle className="text-2xl font-semibold text-success tabular-nums @[250px]/card:text-3xl">
                                {formatCurrency(summary.collected)}
                            </CardTitle>
                            <CardAction>
                                <Badge variant="outline">
                                    <Banknote
                                        aria-hidden="true"
                                        className={''}
                                    />
                                    {summary.revenue > 0
                                        ? `${Math.round(
                                              (summary.collected /
                                                  summary.revenue) *
                                                  100,
                                          )}%`
                                        : '0%'}
                                </Badge>
                            </CardAction>
                        </CardHeader>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>
                                {t.dashboard.outstanding}
                            </CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {formatCurrency(summary.outstanding)}
                            </CardTitle>
                            <CardAction>
                                <Badge variant="outline">
                                    <Wallet aria-hidden="true" className={''} />
                                    {summary.outstanding > 0
                                        ? t.dashboard.outstandingFollow
                                        : t.dashboard.upToDate}
                                </Badge>
                            </CardAction>
                        </CardHeader>
                    </Card>
                </div>

                {/* Evolution charts */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <TrendingUp
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                {t.dashboard.revenueChartTitle}
                            </CardTitle>
                            <CardDescription>
                                {t.dashboard.revenueChartDescription}
                            </CardDescription>
                            <CardAction>
                                <ChartLegend />
                            </CardAction>
                        </CardHeader>
                        <div className="px-4 pb-4">
                            <RevenueChart
                                data={revenueEvolution}
                                currentSeason={season}
                            />
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                {t.dashboard.clientChartTitle}
                            </CardTitle>
                            <CardDescription>
                                {t.dashboard.clientChartDescription}
                            </CardDescription>
                            <CardAction>
                                <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                    <span className="flex items-center gap-1.5">
                                        <span
                                            aria-hidden="true"
                                            className="size-2.5 rounded-sm bg-chart-4"
                                        />
                                        {t.dashboard.clientLegendNew}
                                    </span>
                                    <span className="flex items-center gap-1.5">
                                        <span
                                            aria-hidden="true"
                                            className="h-0.5 w-4 rounded-sm bg-chart-1"
                                        />
                                        {t.dashboard.clientLegendTotal}
                                    </span>
                                </div>
                            </CardAction>
                        </CardHeader>
                        <div className="px-4 pb-4">
                            <ClientEvolutionChart
                                data={clientEvolution}
                                currentSeason={season}
                            />
                        </div>
                    </Card>
                </div>

                {/* Unpaid installments report */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Wallet
                                aria-hidden="true"
                                className="size-4 text-muted-foreground"
                            />
                            {t.dashboard.unpaidTitle}
                            <Badge variant="outline" className="font-mono">
                                {unpaidInstallments.length}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            {interpolate(t.dashboard.unpaidDescription, {
                                season,
                            })}
                        </CardDescription>
                        <CardAction>
                            <Select
                                name="slot"
                                value={slot}
                                onValueChange={selectSlot}
                            >
                                <SelectTrigger
                                    id="slot"
                                    size="sm"
                                    aria-label={t.dashboard.paymentSlotLabel}
                                    className="w-[170px]"
                                >
                                    <SelectValue
                                        placeholder={
                                            t.dashboard.paymentSlotLabel
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {PAYMENT_SLOT_VALUES.map((paymentSlot) => (
                                        <SelectItem
                                            key={paymentSlot}
                                            value={paymentSlot}
                                        >
                                            {slotLabel(paymentSlot)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </CardAction>
                    </CardHeader>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/40">
                                    <TableHead>
                                        {t.dashboard.tableClient}
                                    </TableHead>
                                    <TableHead>
                                        {t.dashboard.tableAddress}
                                    </TableHead>
                                    <TableHead>
                                        {t.dashboard.tablePhone}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.dashboard.tableAmountDue}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.common.actions}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {unpaidInstallments.length > 0 ? (
                                    unpaidInstallments.map((installment) => (
                                        <TableRow
                                            key={installment.id}
                                            className="border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15"
                                        >
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <Avatar className="size-7">
                                                        <AvatarFallback className="bg-glacier-500/15 text-[10px] font-medium text-glacier-700 dark:text-glacier-400">
                                                            {initialsOf(
                                                                installment
                                                                    .client
                                                                    .displayName,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <span className="flex flex-col">
                                                        <span className="text-sm">
                                                            {
                                                                installment
                                                                    .client
                                                                    .displayName
                                                            }
                                                        </span>
                                                        <span className="font-mono text-xs text-muted-foreground">
                                                            {
                                                                installment
                                                                    .client
                                                                    .clientNumber
                                                            }
                                                        </span>
                                                    </span>
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {
                                                    installment.client
                                                        .address_street
                                                }
                                                ,{' '}
                                                {
                                                    installment.client
                                                        .address_city
                                                }
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {installment.client.phone ??
                                                    '—'}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(
                                                    installment.amount,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center justify-end">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-muted-foreground hover:text-foreground"
                                                        aria-label={interpolate(
                                                            t.dashboard
                                                                .viewContract,
                                                            {
                                                                client: installment
                                                                    .client
                                                                    .displayName,
                                                            },
                                                        )}
                                                    >
                                                        <Link
                                                            href={ContractController.show.url(
                                                                {
                                                                    contract:
                                                                        installment.contract_id,
                                                                },
                                                            )}
                                                            prefetch
                                                        >
                                                            <Eye
                                                                aria-hidden="true"
                                                                className="size-4"
                                                            />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <EmptyRow
                                        colSpan={5}
                                        hint={interpolate(t.dashboard.allPaid, {
                                            slot: slotLabel(slot),
                                            season,
                                        })}
                                    />
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                {/* Season recap table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CalendarRange
                                aria-hidden="true"
                                className="size-4 text-muted-foreground"
                            />
                            {t.dashboard.recapTitle}
                        </CardTitle>
                        <CardDescription>
                            {t.dashboard.recapDescription}
                        </CardDescription>
                    </CardHeader>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/40">
                                    <TableHead>
                                        {t.dashboard.recapSeason}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.dashboard.recapContracts}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.dashboard.recapRevenue}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.dashboard.recapCollected}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.dashboard.recapOutstanding}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {seasonRecap.length > 0 ? (
                                    seasonRecap.map((row) => (
                                        <TableRow
                                            key={row.season}
                                            className={
                                                row.season === season
                                                    ? 'border-border/50 bg-primary/10 hover:bg-primary/15 dark:bg-primary/5'
                                                    : 'border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15'
                                            }
                                        >
                                            <TableCell>
                                                <span className="flex items-center gap-2 text-sm font-medium">
                                                    {row.season}
                                                    {row.season === season && (
                                                        <Badge
                                                            variant="outline"
                                                            className="border-glacier-500/25 bg-glacier-500/10 text-glacier-600 dark:text-glacier-400"
                                                        >
                                                            {
                                                                t.dashboard
                                                                    .recapCurrent
                                                            }
                                                        </Badge>
                                                    )}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {row.contracts_count}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(row.revenue)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs text-success tabular-nums">
                                                {formatCurrency(row.collected)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(
                                                    row.outstanding,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <EmptyRow
                                        colSpan={5}
                                        hint={t.dashboard.noContractsYet}
                                    />
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
