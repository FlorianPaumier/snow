import { Form, Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import ImportClientsController from '@/actions/App/Http/Controllers/ImportClientsController';
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
import { index } from '@/routes/clients';
import { template } from '@/routes/clients/import/index';
import type { BreadcrumbItem } from '@/types';

type CsvPreview = {
    headers: string[];
    mapping: Record<string, string>;
    rows: { row: number; values: Record<string, string>; errors: string[] }[];
    csv: string;
};

const FIELDS = [
    'type',
    'clientNumber',
    'companyName',
    'firstName',
    'lastName',
    'email',
    'phoneDay',
    'cellPhone',
    'addressStreet',
    'addressCity',
    'addressPostalCode',
    'addressCountry',
    'remark',
] as const;

export default function ImportClients({
    importPreview,
}: {
    importPreview?: CsvPreview;
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
        { title: t.clients.title, href: index().url },
        { title: t.clients.importTitle, href: '/clients/import' },
    ];

    return (
        <AppFormLayout
            title={t.clients.importTitle}
            description={t.clients.importDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.clients.title}
        >
            <Head title={t.clients.importTitle} />
            <Form
                {...ImportClientsController.store.form()}
                transform={(data) => data}
                className="flex flex-col gap-6"
            >
                {({ errors, processing }) => (
                    <>
                        <FormSection
                            title={t.clients.importUploadTitle}
                            description={t.clients.importUploadDescription}
                        >
                            <p className="text-sm">
                                <a className="underline" href={template().url}>
                                    {t.clients.downloadTemplate}
                                </a>
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="file">
                                    {t.clients.importFileLabel}
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
                            <Button
                                type="submit"
                                name="mode"
                                value="preview"
                                disabled={processing}
                            >
                                {t.clients.previewImport}
                            </Button>
                        </FormSection>
                        {(headers.length > 0 || importPreview) && (
                            <FormSection
                                title={t.clients.mappingTitle}
                                description={t.clients.mappingDescription}
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {FIELDS.map((field) => (
                                        <div key={field} className="grid gap-2">
                                            <Label htmlFor={`mapping-${field}`}>
                                                {t.clients.importFields[field]}
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
                                                    id={`mapping-${field}`}
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="__skip">
                                                        {t.clients.skipColumn}
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
                                {importPreview && (
                                    <>
                                        <p className="text-sm text-muted-foreground">
                                            {t.clients.previewRows.replace(
                                                ':count',
                                                String(
                                                    importPreview.rows.length,
                                                ),
                                            )}
                                        </p>
                                        <div className="max-h-72 overflow-auto rounded-md border">
                                            <table className="w-full text-left text-sm">
                                                <thead className="sticky top-0 bg-muted">
                                                    <tr>
                                                        <th className="p-2">
                                                            {t.clients.csvRow}
                                                        </th>
                                                        <th className="p-2">
                                                            {
                                                                t.clients
                                                                    .fieldFirstName
                                                            }{' '}
                                                            /{' '}
                                                            {
                                                                t.clients
                                                                    .fieldLastName
                                                            }
                                                        </th>
                                                        <th className="p-2">
                                                            {
                                                                t.clients
                                                                    .fieldEmail
                                                            }
                                                        </th>
                                                        <th className="p-2">
                                                            {
                                                                t.clients
                                                                    .validation
                                                            }
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
                                                                        row
                                                                            .values
                                                                            .firstName
                                                                    }{' '}
                                                                    {
                                                                        row
                                                                            .values
                                                                            .lastName
                                                                    }
                                                                </td>
                                                                <td className="p-2">
                                                                    {
                                                                        row
                                                                            .values
                                                                            .email
                                                                    }
                                                                </td>
                                                                <td className="p-2 text-destructive">
                                                                    {row.errors.join(
                                                                        ', ',
                                                                    ) ||
                                                                        t
                                                                            .clients
                                                                            .rowValid}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        <InputError message={errors.file} />
                                        <Button
                                            type="submit"
                                            name="mode"
                                            value="import"
                                            disabled={
                                                processing ||
                                                importPreview.rows.length ===
                                                    0 ||
                                                importPreview.rows.some(
                                                    (row) =>
                                                        row.errors.length > 0,
                                                )
                                            }
                                        >
                                            {t.clients.importSubmit}
                                        </Button>
                                    </>
                                )}
                            </FormSection>
                        )}
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
