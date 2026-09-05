import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/heading';
import PlanCapAlert from '@/components/plan-cap-alert';
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
import { create, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    role: string;
    status: string;
    is_owner: boolean;
    created_at: string;
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    data: ManagedUser[];
};

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

/* ── Role vocabulary: tinted renderings of one state ── */
const ROLE_BADGES: Record<string, string> = {
    admin: 'border-ember-500/25 bg-ember-500/10 text-ember-700 dark:text-ember-400',
    worker: 'border-glacier-500/25 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400',
    user: 'border-border bg-muted text-muted-foreground',
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

const initialsOf = (user: ManagedUser) =>
    user.name
        .split(/\s+/)
        .map((part) => part[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();

export default function Index({
    users,
    filters,
}: {
    users: Pagination;
    filters: Filters;
}) {
    const { t } = useLocale();
    const { status } = usePage().props;
    const [search, setSearch] = useState(filters.search);

    const SORTABLE_COLUMNS = [
        { value: 'name', label: t.users.nameField },
        { value: 'email', label: t.users.emailField },
        { value: 'role', label: t.users.columnRole },
        { value: 'created_at', label: t.users.columnCreated },
    ] as const;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.users.title,
            href: '/users',
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
                { search, sort: filters.sort, direction: filters.direction },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.search, filters.sort, filters.direction]);

    const sortBy = (column: string) => {
        router.get(
            index(),
            {
                ...(filters.search ? { search: filters.search } : {}),
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
            <Head title={t.users.title} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title={t.users.title}
                        description={t.users.descriptionTeam}
                    />

                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus /> {t.users.newUser}
                        </Link>
                    </Button>
                </div>

                {typeof status === 'string' && (
                    <p className="text-sm text-success">{status}</p>
                )}

                <PlanCapAlert />

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
                                placeholder={t.users.searchPlaceholderUsers}
                                aria-label={t.users.searchAriaLabel}
                                className="h-9 pl-8"
                            />
                        </div>
                        <p
                            className="font-mono text-xs text-muted-foreground sm:ml-auto"
                            aria-live="polite"
                        >
                            {interpolate(t.users.userCount, {
                                count: users.total,
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
                                {users.data.length > 0 ? (
                                    users.data.map((user) => (
                                        <TableRow
                                            key={user.id}
                                            className="border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15"
                                        >
                                            <TableCell>
                                                <span className="flex items-center gap-2">
                                                    <Avatar className="size-7">
                                                        <AvatarFallback className="bg-primary/15 text-[10px] font-medium text-glacier-800 dark:text-glacier-300">
                                                            {initialsOf(user)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <span className="text-sm">
                                                        {user.name}
                                                    </span>
                                                </span>
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {user.email}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    <StatusBadge
                                                        label={user.role}
                                                        className={
                                                            ROLE_BADGES[
                                                                user.role
                                                            ] ??
                                                            ROLE_BADGES.user
                                                        }
                                                    />
                                                    {user.status ===
                                                        'pending' && (
                                                        <StatusBadge
                                                            label={
                                                                t.users
                                                                    .pendingInvitation
                                                            }
                                                            className="border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-400"
                                                        />
                                                    )}
                                                    {user.is_owner && (
                                                        <StatusBadge
                                                            label={
                                                                t.users.owner
                                                            }
                                                            className="border-primary/25 bg-primary/10 text-primary"
                                                        />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right text-xs text-muted-foreground">
                                                {user.created_at}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center justify-end gap-0.5">
                                                    <Button
                                                        asChild
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-muted-foreground hover:text-foreground"
                                                        aria-label={interpolate(
                                                            t.users
                                                                .editUserAria,
                                                            { name: user.name },
                                                        )}
                                                    >
                                                        <Link
                                                            href={UserManagementController.edit.url(
                                                                {
                                                                    user: user.id,
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
                                                    {!user.is_owner && (
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
                                                                        t.users
                                                                            .deleteUserAria,
                                                                        {
                                                                            name: user.name,
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
                                                                        t.users
                                                                            .deleteConfirmTitle
                                                                    }
                                                                </DialogTitle>
                                                                <DialogDescription>
                                                                    {
                                                                        t.users
                                                                            .deleteConfirmDescription
                                                                    }
                                                                </DialogDescription>

                                                                <Form
                                                                    {...UserManagementController.destroy.form(
                                                                        {
                                                                            user: user.id,
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
                                                                                    data-test="confirm-delete-user"
                                                                                >
                                                                                    {
                                                                                        t
                                                                                            .users
                                                                                            .deleteUserButton
                                                                                    }
                                                                                </button>
                                                                            </Button>
                                                                        </DialogFooter>
                                                                    )}
                                                                </Form>
                                                            </DialogContent>
                                                        </Dialog>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-28 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-1.5">
                                                <p className="text-sm font-medium">
                                                    {t.users.emptyTitle}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {t.users.emptyHint}
                                                </p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination footer */}
                    {users.total > users.per_page && (
                        <div className="flex flex-wrap items-center justify-between gap-4 border-t px-4 py-3">
                            <p className="text-xs text-muted-foreground">
                                {interpolate(t.common.paginationSummary, {
                                    from: users.from ?? 0,
                                    to: users.to ?? 0,
                                    total: users.total,
                                })}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={users.current_page <= 1}
                                    onClick={() =>
                                        goToPage(users.current_page - 1)
                                    }
                                >
                                    {t.common.previous}
                                </Button>
                                <span className="font-mono text-xs text-muted-foreground">
                                    {users.current_page}/{users.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={
                                        users.current_page >= users.last_page
                                    }
                                    onClick={() =>
                                        goToPage(users.current_page + 1)
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
