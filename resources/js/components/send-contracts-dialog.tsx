import { Form } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { useState } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import { interpolate } from '@/lib/utils';
import { sendEmail } from '@/routes/contracts';
import type { ContractMail, ContractRecipient } from '@/types/contract-mail';

export function ContractMailStatus({ mail }: { mail: ContractMail }) {
    const { t, locale } = useLocale();
    return (
        <div className="text-xs text-muted-foreground" aria-live="polite">
            {mail.status && <p>{t.contractMail.statuses[mail.status]}</p>}
            {mail.sentAt && (
                <p>
                    {interpolate(t.contractMail.lastSent, {
                        date: new Date(mail.sentAt).toLocaleString(locale),
                        sender: mail.sentBy ?? '—',
                    })}
                </p>
            )}
        </div>
    );
}

export default function SendContractsDialog({
    contracts,
    label,
    onQueued,
}: {
    contracts: ContractRecipient[];
    label?: string;
    onQueued?: () => void;
}) {
    const { t } = useLocale();
    const [open, setOpen] = useState(false);
    const blocked = contracts.some(
        (contract) =>
            !contract.client.email ||
            ['queued', 'sending'].includes(contract.mail.status ?? ''),
    );
    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    disabled={contracts.length === 0}
                    data-test="send-contracts"
                >
                    <Mail aria-hidden="true" />
                    {label ?? t.contractMail.send}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t.contractMail.confirmTitle}</DialogTitle>
                    <DialogDescription>
                        {t.contractMail.confirmDescription}
                    </DialogDescription>
                </DialogHeader>
                <ul className="max-h-64 space-y-3 overflow-auto text-sm">
                    {contracts.map((contract) => (
                        <li key={contract.id} className="space-y-1">
                            <p className="font-medium">
                                {contract.client.displayName} ·{' '}
                                {contract.season}
                            </p>
                            <p>
                                {contract.client.email ??
                                    t.contractMail.noEmail}
                            </p>
                            <ContractMailStatus mail={contract.mail} />
                        </li>
                    ))}
                </ul>
                {blocked && (
                    <p className="text-sm text-destructive">
                        {t.contractMail.blocked}
                    </p>
                )}
                <Form
                    {...sendEmail.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => {
                        setOpen(false);
                        onQueued?.();
                    }}
                >
                    {({ processing }) => (
                        <>
                            {contracts.map((contract) => (
                                <input
                                    key={contract.id}
                                    type="hidden"
                                    name="contract_ids[]"
                                    value={contract.id}
                                />
                            ))}
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        {t.common.cancel}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        blocked ||
                                        contracts.length === 0
                                    }
                                    aria-busy={processing}
                                    data-test="confirm-send-contracts"
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? t.common.loading
                                        : t.contractMail.confirm}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
