import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    CalendarRange,
    Eye,
    FileText,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Smartphone,
    Trash2,
    User,
} from 'lucide-react';
import type { ReactNode } from 'react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
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
import { index, show } from '@/routes/clients';
import { index as contractsIndex } from '@/routes/contracts';
import type { BreadcrumbItem } from '@/types';

type ClientRecord = {
    id: string;
    clientNumber: string;
    type: string;
    companyName: string | null;
    firstName: string;
    lastName: string;
    email: string;
    phoneDay: string | null;
    cellPhone: string | null;
    remark: string | null;
    address: {
        street: string;
        city: string;
        postalCode: string;
        country: string;
    };
    contracts: ContractSummary[];
    contractsCount: number;
    created_at: string;
};

type ContractSummary = {
    id: string;
    season: string;
    total: number;
    collected: number;
    outstanding: number;
    created_at: string;
};

/* ── Type vocabulary: tinted renderings of one state ── */
const TYPE_META: Record<
    string,
    {
        labelKey: 'typeIndividual' | 'typeCompany';
        badge: string;
        iconSquare: string;
    }
> = {
    individual: {
        labelKey: 'typeIndividual',
        badge: 'border-glacier-500/20 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400',
        iconSquare: 'bg-glacier-500/10 text-glacier-700 dark:text-glacier-400',
    },
    company: {
        labelKey: 'typeCompany',
        badge: 'border-ember-500/25 bg-ember-500/10 text-ember-700 dark:text-ember-400',
        iconSquare: 'bg-ember-500/10 text-ember-700 dark:text-ember-400',
    },
};

function InfoRow({
    icon,
    label,
    children,
    mono = false,
}: {
    icon: ReactNode;
    label: string;
    children: ReactNode;
    mono?: boolean;
}) {
    return (
        <div className="flex items-center gap-3 py-2">
            <span className="flex size-7 shrink-0 items-center justify-center rounded-md text-muted-foreground [&>svg]:size-4">
                {icon}
            </span>
            <div className="flex min-w-0 flex-col">
                <span className="text-xs text-muted-foreground">{label}</span>
                <span className={`text-sm ${mono ? 'font-mono text-xs' : ''}`}>
                    {children}
                </span>
            </div>
        </div>
    );
}

export default function Show({ client }: { client: ClientRecord }) {
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();
    const { status, errors, auth } = usePage().props;
    const canManageClients = auth.can?.manageClients ?? false;
    const typeMeta = TYPE_META[client.type] ?? TYPE_META.individual;
    const displayName =
        client.type === 'company' && client.companyName
            ? client.companyName
            : `${client.firstName} ${client.lastName}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.clients.title, href: index().url },
        { title: displayName, href: show.url({ client: client.id }) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={displayName} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {typeof status === 'string' && (
                    <p className="text-sm text-success">{status}</p>
                )}

                <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                    {/* Record header: tinted icon square + mono eyebrow + type badge */}
                    <div className="flex items-start gap-4">
                        <span
                            className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${typeMeta.iconSquare}`}
                        >
                            {client.type === 'company' ? (
                                <Building2
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            ) : (
                                <User aria-hidden="true" className="size-5" />
                            )}
                        </span>
                        <div className="flex flex-col gap-1.5">
                            <p className="font-mono text-xs tracking-[0.18em] text-muted-foreground uppercase">
                                {client.clientNumber}
                            </p>
                            <Heading
                                title={displayName}
                                description={
                                    client.type === 'company' &&
                                    client.companyName
                                        ? interpolate(
                                              t.clients.companyContact,
                                              {
                                                  name: `${client.firstName} ${client.lastName}`,
                                              },
                                          )
                                        : undefined
                                }
                            />
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    className={`gap-1.5 ${typeMeta.badge}`}
                                >
                                    <span
                                        aria-hidden="true"
                                        className="size-1.5 shrink-0 rounded-full bg-current"
                                    />
                                    {t.clients[typeMeta.labelKey]}
                                </Badge>
                                <Link
                                    href={contractsIndex.url({
                                        query: { client: client.id },
                                    })}
                                    className="inline-flex"
                                >
                                    <Badge
                                        variant="outline"
                                        className="border-border bg-muted text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >
                                        {interpolate(t.clients.contractCount, {
                                            count: client.contractsCount,
                                            plural:
                                                client.contractsCount === 1
                                                    ? ''
                                                    : t.clients
                                                          .contractCountPlural,
                                        })}
                                    </Badge>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border">
                        {/* Contact section */}
                        <div className="grid gap-1 p-5 sm:grid-cols-2">
                            <InfoRow
                                icon={<Mail aria-hidden="true" />}
                                label={t.clients.emailLabel}
                            >
                                {client.email}
                            </InfoRow>
                            {client.phoneDay && (
                                <InfoRow
                                    icon={<Phone aria-hidden="true" />}
                                    label={t.clients.fieldPhoneDay}
                                >
                                    {client.phoneDay}
                                </InfoRow>
                            )}
                            {client.cellPhone && (
                                <InfoRow
                                    icon={<Smartphone aria-hidden="true" />}
                                    label={t.clients.fieldCellPhone}
                                >
                                    {client.cellPhone}
                                </InfoRow>
                            )}
                            <InfoRow
                                icon={<CalendarDays aria-hidden="true" />}
                                label={t.clients.clientSince}
                            >
                                {client.created_at}
                            </InfoRow>
                        </div>

                        <Separator />

                        {/* Address section — data/measurements in mono per the design system */}
                        <div className="p-5">
                            <InfoRow
                                icon={<MapPin aria-hidden="true" />}
                                label={t.clients.serviceAddress}
                                mono
                            >
                                {client.address.street}, {client.address.city}{' '}
                                {client.address.postalCode},{' '}
                                {client.address.country}
                            </InfoRow>
                        </div>

                        {client.remark && (
                            <>
                                <Separator />
                                <div className="p-5">
                                    <InfoRow
                                        icon={<FileText aria-hidden="true" />}
                                        label={t.clients.remarks}
                                    >
                                        {client.remark}
                                    </InfoRow>
                                </div>
                            </>
                        )}

                        {canManageClients && (
                            <>
                                <Separator />
                                {/* Record footer: separated actions, destructive last */}
                                <div className="flex flex-col-reverse gap-3 p-5 sm:flex-row sm:justify-end">
                                    <Button asChild variant="outline">
                                        <Link
                                            href={ClientController.edit.url({
                                                client: client.id,
                                            })}
                                            prefetch
                                        >
                                            <Pencil aria-hidden="true" />{' '}
                                            {t.clients.editClient}
                                        </Link>
                                    </Button>
                                    <Form
                                        {...ClientController.destroy.form({
                                            client: client.id,
                                        })}
                                        className="contents"
                                    >
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            data-test="delete-client"
                                        >
                                            <Trash2 aria-hidden="true" />{' '}
                                            {t.clients.buttonDeleteClient}
                                        </Button>
                                    </Form>
                                </div>
                            </>
                        )}
                    </div>

                    {typeof errors.client === 'string' && (
                        <p
                            role="alert"
                            className="text-sm font-medium text-destructive"
                            data-test="client-error"
                        >
                            {errors.client}
                        </p>
                    )}

                    {/* Contracts section: one row per season, mirroring the contracts index */}
                    <section
                        aria-labelledby="contracts-heading"
                        className="rounded-xl border"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/40 px-5 py-4">
                            <h2
                                id="contracts-heading"
                                className="flex items-center gap-2 text-sm font-medium"
                            >
                                <CalendarRange
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {t.clients.contractsTitle}
                                <span className="font-mono text-xs text-muted-foreground">
                                    {client.contractsCount}
                                </span>
                            </h2>
                            <Link
                                href={contractsIndex.url({
                                    query: { client: client.id },
                                })}
                                className="text-xs text-muted-foreground hover:text-foreground"
                            >
                                {t.clients.viewAllContracts}
                            </Link>
                        </div>

                        {client.contracts.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>
                                                {t.common.season}
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
                                            <TableHead>
                                                {t.common.createdAt}
                                            </TableHead>
                                            <TableHead className="text-right">
                                                {t.common.actions}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {client.contracts.map((contract) => (
                                            <TableRow
                                                key={contract.id}
                                                className="border-border/50 bg-muted/10 hover:bg-muted/25 dark:bg-muted/5 dark:hover:bg-muted/15"
                                            >
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className="border-glacier-500/20 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400"
                                                    >
                                                        {contract.season}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right font-mono text-xs tabular-nums">
                                                    {formatCurrency(
                                                        contract.total,
                                                    )}
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
                                                    <div className="flex items-center justify-end">
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-muted-foreground hover:text-foreground"
                                                            aria-label={interpolate(
                                                                t.clients
                                                                    .viewContractOf,
                                                                {
                                                                    season: contract.season,
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
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-1.5 px-5 py-8 text-center">
                                <FileText
                                    aria-hidden="true"
                                    className="size-6 text-muted-foreground"
                                />
                                <p className="text-sm font-medium">
                                    {t.clients.noContractsForClient}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {t.clients.contractsEmpty}
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
