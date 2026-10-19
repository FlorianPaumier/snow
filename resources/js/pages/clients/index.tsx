import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Building2,
    Eye,
    FileUp,
    Pencil,
    Plus,
    Search,
    ShieldOff,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import { store as importClients } from '@/actions/App/Http/Controllers/ImportClientsController';
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
import { interpolate } from '@/lib/utils';
import { create, index } from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';

type ManagedClient = {
    id: string;
    clientNumber: string;
    type: string;
    companyName: string | null;
    firstName: string;
    lastName: string;
    email: string;
    anonymized_at: string | null;
    created_at: string;
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    data: ManagedClient[];
};

type ClientView = 'active' | 'anonymized' | 'all';

type Filters = {
    search: string;
    view: ClientView;
    sort: string;
    direction: 'asc' | 'desc';
};

/* ── Type vocabulary: tinted renderings of one state ── */
const TYPE_BADGES: Record<
    string,
    { labelKey: 'typeIndividual' | 'typeCompany'; className: string }
> = {
    individual: {
        labelKey: 'typeIndividual',
        className:
            'border-glacier-500/20 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400',
    },
    company: {
        labelKey: 'typeCompany',
        className:
            'border-ember-500/25 bg-ember-500/10 text-ember-700 dark:text-ember-400',
    },
};

function StatusBadge({
    label,
    className,
}: {
    label: string;
    className: string;
}) {
    return (
        <Badge variant="outline" className={`gap-1.5 ${className}`}>
            <span
                aria-hidden="true"
                className="size-1.5 shrink-0 rounded-full bg-current"
            />
            {label}
        </Badge>
    );
}

const displayName = (client: ManagedClient) =>
    client.type === 'company' && client.companyName
        ? client.companyName
        : `${client.firstName} ${client.lastName}`;

const initialsOf = (client: ManagedClient) => {
    if (client.anonymized_at) {
        return '·';
    }

    if (client.type === 'company' && client.companyName) {
        return client.companyName
            .split(/\s+/)
            .map((part) => part[0])
            .filter(Boolean)
            .slice(0, 2)
            .join('')
            .toUpperCase();
    }

    return `${client.firstName[0] ?? ''}${client.lastName[0] ?? ''}`.toUpperCase();
};

export default function Index({
    clients,
    filters,
}: {
    clients: Pagination;
    filters: Filters;
}) {
    const { t } = useLocale();
    const [search, setSearch] = useState(filters.search);
    const canManageClients = usePage().props.auth.can?.manageClients ?? false;

    const SORTABLE_COLUMNS = [
        { value: 'clientNumber', label: t.clients.columnNumber },
        { value: 'firstName', label: t.clients.columnClient },
        { value: 'type', label: t.common.type },
        { value: 'created_at', label: t.clients.columnCreated },
    ] as const;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.clients.title,
            href: '/clients',
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
                    search,
                    view: filters.view,
                    sort: filters.sort,
                    direction: filters.direction,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.search, filters.view, filters.sort, filters.direction]);

    const selectView = (nextView: ClientView) => {
        router.get(
            index(),
            {
                ...(filters.search ? { search: filters.search } : {}),
                view: nextView,
                sort: filters.sort,
                direction: filters.direction,
            },
            { preserveState: true, replace: true },
        );
    };

    const sortBy = (column: string) => {
        router.get(
            index(),
            {
                ...(filters.search ? { search: filters.search } : {}),
                view: filters.view,
                sort: column,
                direction:
                    filters.sort === column && filters.direction === 'asc'
                        ? 'desc'
                        : 'asc',
            },
            { preserveState: true },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            index(),
            {
                ...(filters.search ? { search: filters.search } : {}),
                view: filters.view,
                sort: filters.sort,
                direction: filters.direction,
                page,
            },
            { preserveState: true },
        );
    };

    const sortIcon = (column: string) => {
        if (filters.sort !== column) {
            return <ArrowUpDown className="size-3.5 opacity-40" />;
        }

        return filters.direction === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.clients.title} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t.clients.title}
                        description={t.clients.description}
                    />

                    {canManageClients && (
                        <div className="flex gap-2">
                            <Button asChild variant="outline">
                                <Link href={importClients.url()} prefetch>
                                    <FileUp /> {t.clients.importButton}
                                </Link>
                            </Button>
                            <Button asChild data-test="new-client">
                                <Link href={create()} prefetch>
                                    <Plus /> {t.clients.newClient}
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

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
                                placeholder={t.clients.searchPlaceholder}
                                aria-label={t.clients.filterAriaLabel}
                                className="h-9 pl-8"
                                data-test="client-search"
                            />
                        </div>
                        <Select
                            name="view"
                            value={filters.view}
                            onValueChange={(value) =>
                                selectView(value as ClientView)
                            }
                        >
                            <SelectTrigger
                                id="view"
                                aria-label={t.clients.filterViewAriaLabel}
                                className="w-[170px]"
                                data-test="client-view-filter"
                            >
                                <ShieldOff
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">
                                    {t.clients.filterActive}
                                </SelectItem>
                                <SelectItem value="anonymized">
                                    {t.clients.filterAnonymized}
                                </SelectItem>
                                <SelectItem value="all">
                                    {t.clients.filterAll}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            className="font-mono text-xs text-muted-foreground sm:ml-auto"
                            aria-live="polite"
                        >
                            {interpolate(t.clients.clientCount, {
                                count: clients.total,
                                plural:
                                    clients.total === 1
                                        ? ''
                                        : t.clients.clientCountPlural,
                            })}
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {SORTABLE_COLUMNS.map((column) => (
                                        <TableHead key={column.value}>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    sortBy(column.value)
                                                }
                                                className="inline-flex items-center gap-1.5 hover:text-foreground"
                                            >
                                                {column.label}
                                                {sortIcon(column.value)}
                                            </button>
                                        </TableHead>
                                    ))}
                                    <TableHead className="text-right">
                                        {t.common.actions}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {clients.data.length > 0 ? (
                                    clients.data.map((client) => {
                                        const typeBadge =
                                            TYPE_BADGES[client.type];
                                        const label = client.anonymized_at
                                            ? t.clients.anonymizedName
                                            : displayName(client);

                                        return (
                                            <TableRow
                                                key={client.id}
                                                className="border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15"
                                            >
                                                <TableCell className="font-mono text-xs font-medium">
                                                    {client.clientNumber}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="flex items-center gap-2">
                                                        <Avatar className="size-7">
                                                            <AvatarFallback
                                                                className={
                                                                    client.type ===
                                                                    'company'
                                                                        ? 'bg-ember-500/15 text-[10px] font-medium text-ember-700 dark:text-ember-400'
                                                                        : 'bg-glacier-500/15 text-[10px] font-medium text-glacier-700 dark:text-glacier-400'
                                                                }
                                                            >
                                                                {initialsOf(
                                                                    client,
                                                                )}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span className="flex flex-col">
                                                            <span
                                                                className={
                                                                    client.anonymized_at
                                                                        ? 'text-sm text-muted-foreground italic'
                                                                        : 'text-sm'
                                                                }
                                                            >
                                                                {label}
                                                            </span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {client.anonymized_at
                                                                    ? '—'
                                                                    : client.email}
                                                            </span>
                                                        </span>
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    {client.anonymized_at ? (
                                                        <StatusBadge
                                                            label={
                                                                t.clients
                                                                    .anonymizedBadge
                                                            }
                                                            className="border-border bg-muted text-muted-foreground"
                                                        />
                                                    ) : (
                                                        <StatusBadge
                                                            label={
                                                                typeBadge
                                                                    ? t.clients[
                                                                          typeBadge
                                                                              .labelKey
                                                                      ]
                                                                    : client.type
                                                            }
                                                            className={
                                                                typeBadge?.className ??
                                                                'border-border bg-muted text-muted-foreground'
                                                            }
                                                        />
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right text-xs text-muted-foreground">
                                                    {client.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center justify-end gap-0.5">
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-muted-foreground hover:text-foreground"
                                                            aria-label={interpolate(
                                                                t.clients
                                                                    .viewClient,
                                                                {
                                                                    client: label,
                                                                },
                                                            )}
                                                        >
                                                            <Link
                                                                href={ClientController.show.url(
                                                                    {
                                                                        client: client.id,
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
                                                        {canManageClients &&
                                                            !client.anonymized_at && (
                                                                <>
                                                                    <Button
                                                                        asChild
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="size-8 text-muted-foreground hover:text-foreground"
                                                                        aria-label={interpolate(
                                                                            t
                                                                                .clients
                                                                                .editClientOf,
                                                                            {
                                                                                client: label,
                                                                            },
                                                                        )}
                                                                    >
                                                                        <Link
                                                                            href={ClientController.edit.url(
                                                                                {
                                                                                    client: client.id,
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
                                                                                        .clients
                                                                                        .deleteClientOf,
                                                                                    {
                                                                                        client: label,
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
                                                                                        .clients
                                                                                        .deleteConfirmTitle
                                                                                }
                                                                            </DialogTitle>
                                                                            <DialogDescription>
                                                                                {
                                                                                    t
                                                                                        .clients
                                                                                        .deleteConfirmDescription
                                                                                }
                                                                            </DialogDescription>

                                                                            <Form
                                                                                {...ClientController.destroy.form(
                                                                                    {
                                                                                        client: client.id,
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
                                                                                                data-test="confirm-delete-client"
                                                                                            >
                                                                                                {
                                                                                                    t
                                                                                                        .clients
                                                                                                        .buttonDeleteClient
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
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-28 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-1.5">
                                                <Building2
                                                    aria-hidden="true"
                                                    className="size-6 text-muted-foreground"
                                                />
                                                <p className="text-sm font-medium">
                                                    {filters.view ===
                                                    'anonymized'
                                                        ? t.clients
                                                              .emptyStateAnonymized
                                                        : t.clients.emptyState}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {filters.view ===
                                                    'anonymized'
                                                        ? t.clients
                                                              .emptyStateAnonymizedHint
                                                        : t.clients
                                                              .emptyStateHint}
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination footer */}
                    {clients.total > clients.per_page && (
                        <div className="flex flex-wrap items-center justify-between gap-4 border-t px-4 py-3">
                            <p className="text-xs text-muted-foreground">
                                {interpolate(t.clients.paginationSummary, {
                                    from: clients.from ?? 0,
                                    to: clients.to ?? 0,
                                    total: clients.total,
                                })}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={clients.current_page <= 1}
                                    onClick={() =>
                                        goToPage(clients.current_page - 1)
                                    }
                                >
                                    {t.common.previous}
                                </Button>
                                <span className="font-mono text-xs text-muted-foreground">
                                    {clients.current_page}/{clients.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        clients.current_page >=
                                        clients.last_page
                                    }
                                    onClick={() =>
                                        goToPage(clients.current_page + 1)
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
