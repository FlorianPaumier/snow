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
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    role: string;
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

const SORTABLE_COLUMNS = [
    { value: 'name', label: 'Name' },
    { value: 'email', label: 'Email' },
    { value: 'role', label: 'Role' },
    { value: 'created_at', label: 'Created' },
] as const;

/* ── Role vocabulary: tinted renderings of one state ── */
const ROLE_BADGES: Record<string, string> = {
    admin: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    worker: 'border-sky-500/25 bg-sky-500/10 text-sky-600 dark:text-sky-400',
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

export default function Index({
    users,
    filters,
}: {
    users: Pagination;
    filters: Filters;
}) {
    const { status } = usePage().props;
    const [search, setSearch] = useState(filters.search);

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
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Users"
                        description="Manage the people on your team"
                    />

                    <Button asChild>
                        <Link href={create()} prefetch>
                            <Plus /> New user
                        </Link>
                    </Button>
                </div>

                {typeof status === 'string' && (
                    <p className="text-sm text-green-600 dark:text-green-400">
                        {status}
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
                                placeholder="Filter names, emails…"
                                aria-label="Filter users"
                                className="h-9 pl-8"
                            />
                        </div>
                        <p
                            className="font-mono text-xs text-muted-foreground sm:ml-auto"
                            aria-live="polite"
                        >
                            {users.total} user{users.total === 1 ? '' : 's'}
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
                                        Actions
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
                                                        <AvatarFallback className="bg-primary/15 text-[10px] font-medium text-primary">
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
                                                <StatusBadge
                                                    label={user.role}
                                                    className={
                                                        ROLE_BADGES[
                                                            user.role
                                                        ] ?? ROLE_BADGES.user
                                                    }
                                                />
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
                                                        aria-label={`Edit ${user.name}`}
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
                                                    <Form
                                                        {...UserManagementController.destroy.form(
                                                            { user: user.id },
                                                        )}
                                                        className="contents"
                                                    >
                                                        <Button
                                                            type="submit"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                            aria-label={`Delete ${user.name}`}
                                                        >
                                                            <Trash2
                                                                aria-hidden="true"
                                                                className="size-4"
                                                            />
                                                        </Button>
                                                    </Form>
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
                                                    No matching users
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Try a different name, email,
                                                    or role.
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
                                Showing {users.from}–{users.to} of {users.total}
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
                                    Previous
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
                                    Next
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
