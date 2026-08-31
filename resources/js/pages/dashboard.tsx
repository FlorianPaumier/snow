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
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/utils';
import { dashboard } from '@/routes';
import ClientEvolutionChart, {
    type ClientEvolutionPoint,
} from './dashboard/components/client-evolution-chart';
import RevenueChart, { type RevenuePoint } from './dashboard/components/revenue-chart';
import type { BreadcrumbItem } from '@/types';

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
    },
];

const PAYMENT_SLOTS = [
    { value: 'deposit', label: 'Dépôt' },
    { value: 'first', label: '1er versement' },
    { value: 'second', label: '2e versement' },
    { value: 'balance', label: 'Solde' },
] as const;

const SLOT_LABELS: Record<string, string> = Object.fromEntries(
    PAYMENT_SLOTS.map((slot) => [slot.value, slot.label]),
);

const initialsOf = (name: string) =>
    name
        .split(/\s+/)
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();

function EmptyRow({ colSpan, hint }: { colSpan: number; hint: string }) {
    return (
        <TableRow>
            <TableCell colSpan={colSpan} className="h-28 text-center">
                <div className="flex flex-col items-center gap-1.5">
                    <p className="text-sm font-medium">Aucune donnée</p>
                    <p className="text-xs text-muted-foreground">{hint}</p>
                </div>
            </TableCell>
        </TableRow>
    );
}

function ChartLegend() {
    return (
        <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
            <span className="flex items-center gap-1.5">
                <span
                    aria-hidden="true"
                    className="size-2.5 rounded-sm bg-chart-1"
                />
                Revenus
            </span>
            <span className="flex items-center gap-1.5">
                <span
                    aria-hidden="true"
                    className="size-2.5 rounded-sm bg-chart-3"
                />
                Encaissé
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
            <Head title="Tableau de bord" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Tableau de bord"
                        description={`Suivi de la saison ${season} en temps réel`}
                    />

                    <Select
                        name="season"
                        value={season}
                        onValueChange={selectSeason}
                    >
                        <SelectTrigger
                            id="season"
                            aria-label="Saison"
                            className="w-[180px]"
                        >
                            <CalendarRange
                                aria-hidden="true"
                                className="size-4 text-muted-foreground"
                            />
                            <SelectValue placeholder="Saison" />
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
                                Revenus de la saison {season}
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
                                    {summary.contracts_count} contrat
                                    {summary.contracts_count === 1 ? '' : 's'}
                                </Badge>
                            </CardAction>
                        </CardHeader>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Montant encaissé</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums text-success @[250px]/card:text-3xl">
                                {formatCurrency(summary.collected)}
                            </CardTitle>
                            <CardAction>
                                <Badge variant="outline">
                                    <Banknote
                                        aria-hidden="true"
                                        className={''}
                                    />
                                    {summary.revenue > 0
                                        ? Math.round(
                                              (summary.collected /
                                                  summary.revenue) *
                                                  100,
                                          )
                                        : 0}
                                    %
                                </Badge>
                            </CardAction>
                        </CardHeader>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Solde à percevoir</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {formatCurrency(summary.outstanding)}
                            </CardTitle>
                            <CardAction>
                                <Badge variant="outline">
                                    <Wallet
                                        aria-hidden="true"
                                        className={''}
                                    />
                                    {summary.outstanding > 0
                                        ? 'À suivre'
                                        : 'À jour'}
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
                                Évolution des revenus
                            </CardTitle>
                            <CardDescription>
                                Revenus et encaissements par saison
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
                                Évolution de la clientèle
                            </CardTitle>
                            <CardDescription>
                                Nouveaux clients et clientèle cumulative
                            </CardDescription>
                            <CardAction>
                                <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                    <span className="flex items-center gap-1.5">
                                        <span
                                            aria-hidden="true"
                                            className="size-2.5 rounded-sm bg-chart-4"
                                        />
                                        Nouveaux
                                    </span>
                                    <span className="flex items-center gap-1.5">
                                        <span
                                            aria-hidden="true"
                                            className="h-0.5 w-4 rounded-sm bg-chart-1"
                                        />
                                        Total
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
                            Versements impayés
                            <Badge variant="outline" className="font-mono">
                                {unpaidInstallments.length}
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            Clients à relancer pour la saison {season}
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
                                    aria-label="Versement"
                                    className="w-[170px]"
                                >
                                    <SelectValue placeholder="Versement" />
                                </SelectTrigger>
                                <SelectContent>
                                    {PAYMENT_SLOTS.map((paymentSlot) => (
                                        <SelectItem
                                            key={paymentSlot.value}
                                            value={paymentSlot.value}
                                        >
                                            {paymentSlot.label}
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
                                    <TableHead>Client</TableHead>
                                    <TableHead>Adresse</TableHead>
                                    <TableHead>Téléphone</TableHead>
                                    <TableHead className="text-right">
                                        Montant dû
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
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
                                                        aria-label={`Voir le contrat de ${installment.client.displayName}`}
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
                                        hint={`Tous les ${SLOT_LABELS[slot] ?? 'versements'} de la saison ${season} sont payés.`}
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
                            Revenus par saison
                        </CardTitle>
                        <CardDescription>
                            Récapitulatif de toutes les saisons
                        </CardDescription>
                    </CardHeader>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/40">
                                    <TableHead>Saison</TableHead>
                                    <TableHead className="text-right">
                                        Contrats
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Revenus
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Encaissé
                                    </TableHead>
                                    <TableHead className="text-right">
                                        À percevoir
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
                                                            En cours
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
                                                {formatCurrency(row.outstanding)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <EmptyRow
                                        colSpan={5}
                                        hint="Aucun contrat n'est encore enregistré."
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
