import { Form, Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import ImportContractsController from '@/actions/App/Http/Controllers/ImportContractsController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/hooks/use-locale';
import AppFormLayout, { FormSection } from '@/layouts/app-form/layout';
import { index } from '@/routes/contracts';
import { template } from '@/routes/contracts/import/index';
import type { BreadcrumbItem } from '@/types';

type ImportPreview = {
    headers: string[];
    mapping: Record<string, string>;
    rows: { row: number; values: Record<string, string>; errors: string[] }[];
    csv: string;
};

const FIELDS = [
    'clientNumber',
    'season',
    'serviceLabel',
    'serviceAmount',
    'depositAmount',
    'depositDueDate',
    'firstAmount',
    'firstDueDate',
    'secondAmount',
    'secondDueDate',
    'balanceAmount',
    'balanceDueDate',
] as const;

export default function ImportContracts({
    importPreview,
}: {
    importPreview?: ImportPreview;
}) {
    const { t } = useLocale();
    const [mapping, setMapping] = useState<Record<string, string>>(
        importPreview?.mapping ?? {},
    );
    const headers = useMemo(
        () => importPreview?.headers ?? [],
        [importPreview?.headers],
    );
    useEffect(
        () => setMapping(importPreview?.mapping ?? {}),
        [importPreview?.mapping],
    );
    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.contracts.title, href: index().url },
        { title: t.contracts.importTitle, href: '/contracts/import' },
    ];

    return (
        <AppFormLayout
            title={t.contracts.importTitle}
            description={t.contracts.importDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.contracts.title}
        >
            <Head title={t.contracts.importTitle} />
            <Form
                {...ImportContractsController.store.form()}
                transform={(data) => data}
                className="flex flex-col gap-6"
            >
                {({ errors, processing }) => (
                    <>
                        <FormSection
                            title={t.contracts.importUploadTitle}
                            description={t.contracts.importUploadDescription}
                        >
                            <p className="text-sm">
                                <a className="underline" href={template().url}>
                                    {t.contracts.downloadTemplate}
                                </a>
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="file">
                                    {t.contracts.importFileLabel}
                                </Label>
                                <input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    required={!importPreview}
                                    className="text-sm file:mr-3 file:rounded-md file:border file:bg-muted file:px-3 file:py-2"
                                />
                                {importPreview?.csv && (
                                    <input
                                        type="hidden"
                                        name="csv"
                                        value={importPreview.csv}
                                    />
                                )}
                                <InputError
                                    message={errors.file ?? errors.csv}
                                />
                            </div>
                            {!importPreview &&
                                FIELDS.map((field) => (
                                    <input
                                        key={field}
                                        type="hidden"
                                        name={`mapping[${field}]`}
                                        value=""
                                    />
                                ))}
                            <Button
                                type="submit"
                                name="mode"
                                value="preview"
                                disabled={processing}
                            >
                                {t.contracts.previewImport}
                            </Button>
                        </FormSection>
                        {importPreview && (
                            <FormSection
                                title={t.contracts.mappingTitle}
                                description={t.contracts.mappingDescription}
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {FIELDS.map((field) => (
                                        <div key={field} className="grid gap-2">
                                            <Label htmlFor={`mapping-${field}`}>
                                                {
                                                    t.contracts.importFields[
                                                        field
                                                    ]
                                                }
                                            </Label>
                                            <Select
                                                name={`mapping[${field}]`}
                                                value={
                                                    mapping[field] || '__skip'
                                                }
                                                onValueChange={(value) =>
                                                    setMapping((previous) => ({
                                                        ...previous,
                                                        [field]:
                                                            value === '__skip'
                                                                ? ''
                                                                : value,
                                                    }))
                                                }
                                            >
                                                <SelectTrigger
                                                    aria-invalid={Boolean(
                                                        errors.file ??
                                                        errors.csv,
                                                    )}
                                                    aria-describedby={
                                                        (errors.file ??
                                                        errors.csv)
                                                            ? `mapping-${field}` +
                                                              '-error'
                                                            : undefined
                                                    }
                                                    id={`mapping-${field}`}
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="__skip">
                                                        {t.contracts.skipColumn}
                                                    </SelectItem>
                                                    {headers.map(
                                                        (header, index) => (
                                                            <SelectItem
                                                                key={`${header}-${index}`}
                                                                value={header}
                                                            >
                                                                {header}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    ))}
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {t.contracts.previewRows.replace(
                                        ':count',
                                        String(importPreview.rows.length),
                                    )}
                                </p>
                                <div className="max-h-72 overflow-auto rounded-md border">
                                    <table className="w-full text-left text-sm">
                                        <thead className="sticky top-0 bg-muted">
                                            <tr>
                                                <th className="p-2">
                                                    {t.contracts.csvRow}
                                                </th>
                                                <th className="p-2">
                                                    {
                                                        t.contracts.importFields
                                                            .clientNumber
                                                    }
                                                </th>
                                                <th className="p-2">
                                                    {
                                                        t.contracts.importFields
                                                            .season
                                                    }
                                                </th>
                                                <th className="p-2">
                                                    {
                                                        t.contracts.importFields
                                                            .serviceAmount
                                                    }
                                                </th>
                                                <th className="p-2">
                                                    {t.contracts.validation}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {importPreview.rows
                                                .slice(0, 50)
                                                .map((row) => (
                                                    <tr
                                                        key={row.row}
                                                        className="border-t"
                                                    >
                                                        <td className="p-2">
                                                            {row.row}
                                                        </td>
                                                        <td className="p-2">
                                                            {
                                                                row.values
                                                                    .clientNumber
                                                            }
                                                        </td>
                                                        <td className="p-2">
                                                            {row.values.season}
                                                        </td>
                                                        <td className="p-2">
                                                            {
                                                                row.values
                                                                    .serviceAmount
                                                            }
                                                        </td>
                                                        <td className="p-2 text-destructive">
                                                            {row.errors.join(
                                                                ', ',
                                                            ) ||
                                                                t.contracts
                                                                    .rowValid}
                                                        </td>
                                                    </tr>
                                                ))}
                                        </tbody>
                                    </table>
                                </div>
                                <InputError
                                    message={errors.file ?? errors.csv}
                                />
                                <Button
                                    type="submit"
                                    name="mode"
                                    value="import"
                                    disabled={
                                        processing ||
                                        importPreview.rows.length === 0 ||
                                        importPreview.rows.some(
                                            (row) => row.errors.length > 0,
                                        )
                                    }
                                >
                                    {t.contracts.importSubmit}
                                </Button>
                            </FormSection>
                        )}
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
