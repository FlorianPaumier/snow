import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarRange,
    Download,
    Eye,
    FileText,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
import { create, importMethod, index } from '@/routes/contracts';
import { exportMethod } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';

type ContractRow = {
    id: string;
    season: string;
    client: {
        id: string;
        clientNumber: string;
        displayName: string;
    };
    total: number;
    collected: number;
    outstanding: number;
    created_at: string;
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    data: ContractRow[];
};

type Filters = {
    season: string;
    client: string;
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const initialsOf = (name: string) =>
    name
        .split(/\s+/)
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();

export default function Index({
    contracts,
    filters,
    seasons,
}: {
    contracts: Pagination;
    filters: Filters;
    seasons: string[];
}) {
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();
    const { status, errors, auth } = usePage().props;
    const canManageContracts = auth.can?.manageContracts ?? false;
    const [search, setSearch] = useState(filters.search);

    // Server-side CSV download; follows the season filter when one is set.
    const exportHref = exportMethod.url(
        filters.season ? { query: { season: filters.season } } : undefined,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.contracts.title,
            href: '/contracts',
        },
    ];

    // Debounced search: reload the page with the updated query string...
    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                index(),
                {
                    ...(search ? { search } : {}),
                    ...(filters.season ? { season: filters.season } : {}),
                    ...(filters.client ? { client: filters.client } : {}),
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.search, filters.season, filters.client]);

    const selectSeason = (nextSeason: string) => {
        router.get(
            index(),
            {
                ...(nextSeason ? { season: nextSeason } : {}),
                ...(filters.client ? { client: filters.client } : {}),
                ...(filters.search ? { search: filters.search } : {}),
            },
            { preserveState: true, replace: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            index(),
            {
                ...(filters.search ? { search: filters.search } : {}),
                ...(filters.season ? { season: filters.season } : {}),
                ...(filters.client ? { client: filters.client } : {}),
                page,
            },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.contracts.title} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t.contracts.title}
                        description={t.contracts.description}
                    />

                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            name="season"
                            value={filters.season || 'all'}
                            onValueChange={(value) =>
                                selectSeason(value === 'all' ? '' : value)
                            }
                        >
                            <SelectTrigger
                                id="season"
                                aria-label={t.common.season}
                                className="w-[160px]"
                                data-test="season-filter"
                            >
                                <CalendarRange
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                <SelectValue placeholder={t.common.season} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t.contracts.allSeasons}
                                </SelectItem>
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

                        {canManageContracts && (
                            <Button
                                asChild
                                variant="outline"
                                data-test="export-payments"
                            >
                                <a href={exportHref} download>
                                    <Download
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    {t.contracts.exportPayments}
                                </a>
                            </Button>
                        )}

                        {canManageContracts && (
                            <>
                                <Button asChild variant="outline">
                                    <Link href={importMethod()}>
                                        {t.contracts.importButton}
                                    </Link>
                                </Button>
                                <Button asChild data-test="new-contract">
                                    <Link href={create()} prefetch>
                                        <Plus /> {t.contracts.newContract}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {typeof status === 'string' && (
                    <p className="text-sm text-success">{status}</p>
                )}

                {typeof errors.contract === 'string' && (
                    <p
                        role="alert"
                        className="text-sm font-medium text-destructive"
                        data-test="contract-error"
                    >
                        {errors.contract}
                    </p>
                )}

                <div className="overflow-hidden rounded-xl border">
                    {/* Toolbar: live search + mono count (design system data display pattern) */}
                    <div className="flex flex-col gap-3 border-b bg-muted/40 px-4 py-3 sm:flex-row sm:items-center">
                        <div className="relative w-full sm:max-w-xs">
                            <Search
                                aria-hidden="true"
                                className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') setSearch('');
                                }}
                                placeholder={t.contracts.filterPlaceholder}
                                aria-label={t.contracts.filterAriaLabel}
                                className="h-9 pl-8"
                                data-test="contract-search"
                            />
                        </div>
                        <p
                            className="font-mono text-xs text-muted-foreground sm:ml-auto"
                            aria-live="polite"
                        >
                            {interpolate(t.contracts.contractCount, {
                                count: contracts.total,
                                plural:
                                    contracts.total === 1
                                        ? ''
                                        : t.contracts.contractCountPlural,
                            })}
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        {t.contracts.columnClient}
                                    </TableHead>
                                    <TableHead>
                                        {t.contracts.columnSeason}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.contracts.columnTotal}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.contracts.columnCollected}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t.contracts.columnBalance}
                                    </TableHead>
                                    <TableHead>{t.common.createdAt}</TableHead>
                                    <TableHead className="text-right">
                                        {t.common.actions}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {contracts.data.length > 0 ? (
                                    contracts.data.map((contract) => (
                                        <TableRow
                                            key={contract.id}
                                            className="border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15"
                                        >
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <Avatar className="size-7">
                                                        <AvatarFallback className="bg-glacier-500/15 text-[10px] font-medium text-glacier-700 dark:text-glacier-400">
                                                            {initialsOf(
                                                                contract.client
                                                                    .displayName,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <span className="flex flex-col">
                                                        <span className="text-sm">
                                                            {
                                                                contract.client
                                                                    .displayName
                                                            }
                                                        </span>
                                                        <span className="font-mono text-xs text-muted-foreground">
                                                            {
                                                                contract.client
                                                                    .clientNumber
                                                            }
                                                        </span>
                                                    </span>
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className="border-glacier-500/20 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400"
                                                >
                                                    {contract.season}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(contract.total)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs text-success tabular-nums">
                                                {formatCurrency(
                                                    contract.collected,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(
                                                    contract.outstanding,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {contract.created_at}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center justify-end gap-0.5">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-muted-foreground hover:text-foreground"
                                                        aria-label={interpolate(
                                                            t.contracts
                                                                .viewContract,
                                                            {
                                                                client: contract
                                                                    .client
                                                                    .displayName,
                                                            },
                                                        )}
                                                    >
                                                        <Link
                                                            href={ContractController.show.url(
                                                                {
                                                                    contract:
                                                                        contract.id,
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
                                                    {canManageContracts && (
                                                        <>
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-8 text-muted-foreground hover:text-foreground"
                                                                aria-label={interpolate(
                                                                    t.contracts
                                                                        .editContractOf,
                                                                    {
                                                                        client: contract
                                                                            .client
                                                                            .displayName,
                                                                    },
                                                                )}
                                                            >
                                                                <Link
                                                                    href={ContractController.edit.url(
                                                                        {
                                                                            contract:
                                                                                contract.id,
                                                                        },
                                                                    )}
                                                                    prefetch
                                                                >
                                                                    <Pencil
                                                                        aria-hidden="true"
                                                                        className="size-4"
                                                                    />
                                                                </Link>
                                                            </Button>
                                                            <Dialog>
                                                                <DialogTrigger
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                                        aria-label={interpolate(
                                                                            t
                                                                                .contracts
                                                                                .deleteContractOf,
                                                                            {
                                                                                client: contract
                                                                                    .client
                                                                                    .displayName,
                                                                            },
                                                                        )}
                                                                    >
                                                                        <Trash2
                                                                            aria-hidden="true"
                                                                            className="size-4"
                                                                        />
                                                                    </Button>
                                                                </DialogTrigger>
                                                                <DialogContent>
                                                                    <DialogTitle>
                                                                        {
                                                                            t
                                                                                .contracts
                                                                                .deleteConfirmTitle
                                                                        }
                                                                    </DialogTitle>
                                                                    <DialogDescription>
                                                                        {
                                                                            t
                                                                                .contracts
                                                                                .deleteConfirmDescription
                                                                        }
                                                                    </DialogDescription>

                                                                    <Form
                                                                        {...ContractController.destroy.form(
                                                                            {
                                                                                contract:
                                                                                    contract.id,
                                                                            },
                                                                        )}
                                                                        className="contents"
                                                                    >
                                                                        {({
                                                                            processing,
                                                                        }) => (
                                                                            <DialogFooter className="gap-2">
                                                                                <DialogClose
                                                                                    asChild
                                                                                >
                                                                                    <Button variant="secondary">
                                                                                        {
                                                                                            t
                                                                                                .common
                                                                                                .cancel
                                                                                        }
                                                                                    </Button>
                                                                                </DialogClose>

                                                                                <Button
                                                                                    variant="destructive"
                                                                                    disabled={
                                                                                        processing
                                                                                    }
                                                                                    asChild
                                                                                >
                                                                                    <button
                                                                                        type="submit"
                                                                                        data-test="confirm-delete-contract"
                                                                                    >
                                                                                        {
                                                                                            t
                                                                                                .contracts
                                                                                                .deleteContractAction
                                                                                        }
                                                                                    </button>
                                                                                </Button>
                                                                            </DialogFooter>
                                                                        )}
                                                                    </Form>
                                                                </DialogContent>
                                                            </Dialog>
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-28 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-1.5">
                                                <FileText
                                                    aria-hidden="true"
                                                    className="size-6 text-muted-foreground"
                                                />
                                                <p className="text-sm font-medium">
                                                    {t.contracts.emptyState}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {t.contracts.emptyStateHint}
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination footer */}
                    {contracts.total > contracts.per_page && (
                        <div className="flex flex-wrap items-center justify-between gap-4 border-t px-4 py-3">
                            <p className="text-xs text-muted-foreground">
                                {interpolate(t.contracts.paginationSummary, {
                                    from: contracts.from ?? 0,
                                    to: contracts.to ?? 0,
                                    total: contracts.total,
                                })}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={contracts.current_page <= 1}
                                    onClick={() =>
                                        goToPage(contracts.current_page - 1)
                                    }
                                >
                                    {t.common.previous}
                                </Button>
                                <span className="font-mono text-xs text-muted-foreground">
                                    {contracts.current_page}/
                                    {contracts.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        contracts.current_page >=
                                        contracts.last_page
                                    }
                                    onClick={() =>
                                        goToPage(contracts.current_page + 1)
                                    }
                                >
                                    {t.common.next}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
