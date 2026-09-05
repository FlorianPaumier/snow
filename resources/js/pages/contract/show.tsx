import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarRange,
    Download,
    FileText,
    Pencil,
    Printer,
    ReceiptText,
    Trash2,
    User,
    Undo2,
} from 'lucide-react';
import { useState } from 'react';
import ContractController from '@/actions/App/Http/Controllers/ContractController';
import GenerateContractPdfController from '@/actions/App/Http/Controllers/GenerateContractPdfController';
import MarkPaymentPaidController from '@/actions/App/Http/Controllers/MarkPaymentPaidController';
import PrintContractDocumentController from '@/actions/App/Http/Controllers/PrintContractDocumentController';
import PrintPaymentReceiptController from '@/actions/App/Http/Controllers/PrintPaymentReceiptController';
import UnmarkPaymentPaidController from '@/actions/App/Http/Controllers/UnmarkPaymentPaidController';
import Heading from '@/components/heading';
import PlanCapAlert from '@/components/plan-cap-alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import type { BreadcrumbItem } from '@/types';

type ContractRecord = {
    id: string;
    season: string;
    client: {
        id: string;
        clientNumber: string;
        displayName: string;
    };
    services: Array<{
        id: number;
        label: string;
        amount: number;
        position: number;
    }>;
    payments: Array<{
        id: number;
        slot: string;
        slotLabel: string;
        amount: number;
        paid: boolean;
        method: string | null;
        methodLabel: string | null;
        paidAt: string | null;
    }>;
    total: number;
    collected: number;
    outstanding: number;
    created_at: string;
};

type MethodOption = {
    value: string;
    label: string;
};

export default function Show({
    contract,
    methods,
}: {
    contract: ContractRecord;
    methods: MethodOption[];
}) {
    const { t } = useLocale();
    const formatCurrency = useCurrencyFormatter();
    const { status, errors, auth } = usePage().props;
    const canManageContracts = auth.can?.manageContracts ?? false;
    const [markPaidTarget, setMarkPaidTarget] = useState<
        ContractRecord['payments'][number] | null
    >(null);
    const [method, setMethod] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: `${contract.client.displayName} · ${contract.season}`,
            href: ContractController.show.url({ contract: contract.id }),
        },
    ];

    const confirmMarkPaid = () => {
        if (markPaidTarget === null || method === '') {
            return;
        }

        router.post(
            MarkPaymentPaidController.url({ payment: markPaidTarget.id }),
            { method },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMarkPaidTarget(null);
                    setMethod('');
                },
            },
        );
    };

    const unmarkPaid = (payment: ContractRecord['payments'][number]) => {
        router.post(
            UnmarkPaymentPaidController.url({ payment: payment.id }),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={interpolate(t.contracts.pageHeadTitle, {
                    client: contract.client.displayName,
                    season: contract.season,
                })}
            />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {typeof status === 'string' && (
                    <p className="text-sm text-success">{status}</p>
                )}

                <PlanCapAlert />

                {typeof errors.contract === 'string' && (
                    <p
                        role="alert"
                        className="text-sm font-medium text-destructive"
                        data-test="contract-error"
                    >
                        {errors.contract}
                    </p>
                )}

                <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
                    {/* Record header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-glacier-500/10 text-glacier-700 dark:text-glacier-400">
                                <FileText
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            </span>
                            <div className="flex flex-col gap-1.5">
                                <p className="font-mono text-xs tracking-[0.18em] text-muted-foreground uppercase">
                                    {contract.client.clientNumber}
                                </p>
                                <Heading
                                    title={interpolate(t.contracts.showTitle, {
                                        season: contract.season,
                                    })}
                                    description={interpolate(
                                        t.contracts.signedWith,
                                        { client: contract.client.displayName },
                                    )}
                                />
                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={ContractController.show.url({
                                            contract: contract.id,
                                        })}
                                        className="text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        {t.contracts.viewClientRecord}
                                    </Link>
                                    <Badge
                                        variant="outline"
                                        className="border-glacier-500/20 bg-glacier-500/10 text-glacier-700 dark:text-glacier-400"
                                    >
                                        {contract.season}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Document actions */}
                        <div className="flex shrink-0 items-center gap-2">
                            <Button asChild variant="outline">
                                <a
                                    href={PrintContractDocumentController.url({
                                        contract: contract.id,
                                    })}
                                    target="_blank"
                                    rel="noopener"
                                    data-test="print-document"
                                >
                                    <Printer aria-hidden="true" />{' '}
                                    {t.contracts.printDocument}
                                </a>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    router.post(
                                        GenerateContractPdfController.url({
                                            contract: contract.id,
                                        }),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                data-test="download-pdf"
                            >
                                <Download aria-hidden="true" />{' '}
                                {t.contracts.downloadPdf}
                            </Button>
                        </div>
                    </div>

                    <div className="rounded-xl border">
                        {/* Services section */}
                        <div className="p-5">
                            <h3 className="mb-3 flex items-center gap-2 text-sm font-medium">
                                <User aria-hidden="true" className="size-4" />
                                {t.contracts.servicesSection}
                            </h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            {t.contracts.fieldService}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            {t.contracts.fieldAmount}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {contract.services.map((service) => (
                                        <TableRow key={service.id}>
                                            <TableCell className="text-sm">
                                                {service.label}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(service.amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-muted/30">
                                        <TableCell className="text-sm font-medium">
                                            {t.contracts.totalServices}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-xs font-medium tabular-nums">
                                            {formatCurrency(contract.total)}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <Separator />

                        {/* Payments section */}
                        <div className="p-5">
                            <h3 className="mb-3 flex items-center gap-2 text-sm font-medium">
                                <CalendarRange
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {t.contracts.installments}
                            </h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            {t.contracts.installmentColumn}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            {t.contracts.fieldAmount}
                                        </TableHead>
                                        <TableHead>
                                            {t.contracts.columnStatus}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            {t.common.actions}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {contract.payments.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="text-sm">
                                                {payment.slotLabel}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-xs tabular-nums">
                                                {formatCurrency(payment.amount)}
                                            </TableCell>
                                            <TableCell>
                                                {payment.paid ? (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-success/25 bg-success/10 text-success"
                                                    >
                                                        {payment.methodLabel} ·{' '}
                                                        {payment.paidAt}
                                                    </Badge>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-border bg-muted text-muted-foreground"
                                                    >
                                                        {
                                                            t.contracts
                                                                .statusPending
                                                        }
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {canManageContracts && (
                                                    <div className="flex items-center justify-end gap-1">
                                                        {payment.paid ? (
                                                            <>
                                                                <Button
                                                                    asChild
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="text-muted-foreground hover:text-foreground"
                                                                >
                                                                    <a
                                                                        href={PrintPaymentReceiptController.url(
                                                                            {
                                                                                payment:
                                                                                    payment.id,
                                                                            },
                                                                        )}
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        aria-label={interpolate(
                                                                            t
                                                                                .contracts
                                                                                .receiptAria,
                                                                            {
                                                                                slot: payment.slotLabel,
                                                                            },
                                                                        )}
                                                                        data-test={`receipt-${payment.slot}`}
                                                                    >
                                                                        <ReceiptText
                                                                            aria-hidden="true"
                                                                            className="size-4"
                                                                        />{' '}
                                                                        {
                                                                            t
                                                                                .contracts
                                                                                .receipt
                                                                        }
                                                                    </a>
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    className="text-muted-foreground hover:text-foreground"
                                                                    onClick={() =>
                                                                        unmarkPaid(
                                                                            payment,
                                                                        )
                                                                    }
                                                                    aria-label={interpolate(
                                                                        t
                                                                            .contracts
                                                                            .unmarkPaidAria,
                                                                        {
                                                                            slot: payment.slotLabel,
                                                                        },
                                                                    )}
                                                                >
                                                                    <Undo2
                                                                        aria-hidden="true"
                                                                        className="size-4"
                                                                    />
                                                                    {
                                                                        t
                                                                            .contracts
                                                                            .confirmUnmark
                                                                    }
                                                                </Button>
                                                            </>
                                                        ) : (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => {
                                                                    setMarkPaidTarget(
                                                                        payment,
                                                                    );
                                                                    setMethod(
                                                                        '',
                                                                    );
                                                                }}
                                                                aria-label={interpolate(
                                                                    t.contracts
                                                                        .markPaid,
                                                                    {
                                                                        slot: payment.slotLabel,
                                                                    },
                                                                )}
                                                                data-test={`mark-paid-${payment.slot}`}
                                                            >
                                                                {
                                                                    t.contracts
                                                                        .confirmCollect
                                                                }
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-muted/30">
                                        <TableCell className="text-sm font-medium">
                                            {t.contracts.collectedSummary}
                                        </TableCell>
                                        <TableCell
                                            colSpan={3}
                                            className="text-right font-mono text-xs font-medium text-success tabular-nums"
                                        >
                                            {interpolate(
                                                t.contracts
                                                    .collectedOverBalance,
                                                {
                                                    collected: formatCurrency(
                                                        contract.collected,
                                                    ),
                                                    outstanding: formatCurrency(
                                                        contract.outstanding,
                                                    ),
                                                },
                                            )}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        {canManageContracts && (
                            <>
                                <Separator />
                                {/* Record footer */}
                                <div className="flex flex-col-reverse gap-3 p-5 sm:flex-row sm:justify-end">
                                    <Button asChild variant="outline">
                                        <Link
                                            href={ContractController.edit.url({
                                                contract: contract.id,
                                            })}
                                            prefetch
                                        >
                                            <Pencil aria-hidden="true" />{' '}
                                            {t.contracts.editContractAction}
                                        </Link>
                                    </Button>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button
                                                variant="destructive"
                                                data-test="delete-contract"
                                            >
                                                <Trash2 aria-hidden="true" />{' '}
                                                {
                                                    t.contracts
                                                        .deleteContractAction
                                                }
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogTitle>
                                                {t.contracts.deleteConfirmTitle}
                                            </DialogTitle>
                                            <DialogDescription>
                                                {
                                                    t.contracts
                                                        .deleteConfirmDescription
                                                }
                                            </DialogDescription>

                                            <Form
                                                {...ContractController.destroy.form(
                                                    {
                                                        contract: contract.id,
                                                    },
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                resetOnSuccess
                                                className="contents"
                                            >
                                                {({ processing }) => (
                                                    <DialogFooter className="gap-2">
                                                        <DialogClose asChild>
                                                            <Button variant="secondary">
                                                                {
                                                                    t.common
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
                                                                    t.contracts
                                                                        .deleteContractAction
                                                                }
                                                            </button>
                                                        </Button>
                                                    </DialogFooter>
                                                )}
                                            </Form>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* Mark-paid dialog: choose the collection method, paid_at is set server-side */}
            <Dialog
                open={markPaidTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setMarkPaidTarget(null);
                        setMethod('');
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {markPaidTarget !== null &&
                                interpolate(t.contracts.markPaidDialogTitle, {
                                    slot: markPaidTarget.slotLabel,
                                    amount: formatCurrency(
                                        markPaidTarget.amount,
                                    ),
                                })}
                        </DialogTitle>
                        <DialogDescription>
                            {t.contracts.markPaidDialogDescription}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Select
                            name="method"
                            value={method}
                            onValueChange={setMethod}
                        >
                            <SelectTrigger id="method" className="w-full">
                                <SelectValue
                                    placeholder={t.contracts.paymentMethod}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {methods.map((methodOption) => (
                                    <SelectItem
                                        key={methodOption.value}
                                        value={methodOption.value}
                                    >
                                        {methodOption.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {typeof errors.method === 'string' && (
                            <p className="text-xs font-medium text-destructive">
                                {errors.method}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                setMarkPaidTarget(null);
                                setMethod('');
                            }}
                        >
                            {t.common.cancel}
                        </Button>
                        <Button
                            type="button"
                            disabled={method === ''}
                            onClick={confirmMarkPaid}
                            data-test="confirm-mark-paid"
                        >
                            {t.contracts.confirmCollect}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
