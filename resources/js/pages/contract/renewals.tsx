import { Head, Link, router, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import { useCurrencyFormatter } from '@/lib/utils';
import { index as quotes, show } from '@/routes/quotes';
import { index, store } from '@/routes/renewals';

type RenewalStatus = 'draft' | 'sent' | 'accepted' | 'declined' | 'expired';
type ContractRow = {
    id: string;
    name: string;
    total: number;
    anonymized: boolean;
    blocked: boolean;
    quoteId: string | null;
    status: RenewalStatus | null;
};
export default function Renewals({
    sourceSeason,
    targetSeason,
    seasons,
    contracts,
    counts,
    result,
}: {
    sourceSeason: string;
    targetSeason: string;
    seasons: string[];
    contracts: {
        data: ContractRow[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    counts: Record<RenewalStatus, number>;
    result: { created: number; existing: number; anonymized: number } | null;
}) {
    const { t } = useLocale();
    const currency = useCurrencyFormatter();
    const form = useForm({
        sourceSeason,
        targetSeason,
        contractIds: [] as string[],
    });
    const eligible = contracts.data
        .filter((contract) => !contract.blocked && !contract.anonymized)
        .map((contract) => contract.id);
    const allSelected =
        eligible.length > 0 &&
        eligible.every((id) => form.data.contractIds.includes(id));
    return (
        <AppLayout
            breadcrumbs={[
                { title: t.quotes.title, href: quotes() },
                { title: t.renewals.title, href: index() },
            ]}
        >
            <Head title={t.renewals.title} />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">{t.renewals.title}</h1>
                <p>{t.renewals.description}</p>
                <div className="max-w-sm space-y-2">
                    <Label htmlFor="sourceSeason">{t.renewals.source}</Label>
                    <Select
                        value={sourceSeason}
                        onValueChange={(value) =>
                            router.get(index().url, { sourceSeason: value })
                        }
                    >
                        <SelectTrigger
                            aria-invalid={Boolean(
                                form.errors.sourceSeason ??
                                form.errors.targetSeason,
                            )}
                            aria-describedby={
                                (form.errors.sourceSeason ??
                                form.errors.targetSeason)
                                    ? 'sourceSeason' + '-error'
                                    : undefined
                            }
                            id="sourceSeason"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {seasons.map((season) => (
                                <SelectItem key={season} value={season}>
                                    {season}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p>
                        {t.renewals.target}: {targetSeason}
                    </p>
                    <InputError
                        id={'sourceSeason' + '-error'}
                        message={
                            form.errors.sourceSeason ?? form.errors.targetSeason
                        }
                    />
                </div>
                <dl className="grid grid-cols-2 gap-3 md:grid-cols-5">
                    {(Object.keys(counts) as RenewalStatus[]).map((status) => (
                        <div key={status} className="rounded-lg border p-4">
                            <dt>{t.renewals[status]}</dt>
                            <dd className="text-2xl font-semibold">
                                {counts[status]}
                            </dd>
                        </div>
                    ))}
                </dl>
                {result && (
                    <p role="status">
                        {t.renewals.created}: {result.created} ·{' '}
                        {t.renewals.existing}: {result.existing} ·{' '}
                        {t.renewals.skipped}: {result.anonymized}
                    </p>
                )}
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(store().url);
                    }}
                >
                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="selectAll"
                            checked={allSelected}
                            disabled={!eligible.length || form.processing}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'contractIds',
                                    checked === true ? eligible : [],
                                )
                            }
                        />
                        <Label htmlFor="selectAll">
                            {t.renewals.selectAll}
                        </Label>
                    </div>
                    {!contracts.data.length && <p>{t.renewals.empty}</p>}
                    {contracts.data.map((contract) => (
                        <article
                            key={contract.id}
                            className="flex flex-wrap items-center justify-between gap-4 rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={contract.id}
                                    aria-label={contract.name}
                                    checked={form.data.contractIds.includes(
                                        contract.id,
                                    )}
                                    disabled={
                                        contract.blocked ||
                                        contract.anonymized ||
                                        form.processing
                                    }
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'contractIds',
                                            checked === true
                                                ? [
                                                      ...form.data.contractIds,
                                                      contract.id,
                                                  ]
                                                : form.data.contractIds.filter(
                                                      (id) =>
                                                          id !== contract.id,
                                                  ),
                                        )
                                    }
                                />
                                <Label htmlFor={contract.id}>
                                    {contract.name}
                                </Label>
                                <span>{currency(contract.total)}</span>
                            </div>
                            {contract.anonymized ? (
                                <p>{t.renewals.anonymized}</p>
                            ) : contract.quoteId ? (
                                <div className="flex gap-3">
                                    <span>
                                        {contract.status &&
                                            t.renewals[contract.status]}
                                    </span>
                                    <Link
                                        className="underline"
                                        href={show(contract.quoteId)}
                                    >
                                        {t.renewals.review}
                                    </Link>
                                </div>
                            ) : (
                                contract.blocked && <p>{t.renewals.blocked}</p>
                            )}
                        </article>
                    ))}
                    <InputError message={form.errors.contractIds} />
                    <Button
                        disabled={
                            form.processing || !form.data.contractIds.length
                        }
                    >
                        {t.renewals.prepare}
                    </Button>
                </form>
                <div className="flex gap-4">
                    {contracts.prev_page_url && (
                        <Link href={contracts.prev_page_url}>
                            {t.quotes.previous}
                        </Link>
                    )}
                    {contracts.next_page_url && (
                        <Link href={contracts.next_page_url}>
                            {t.quotes.next}
                        </Link>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
