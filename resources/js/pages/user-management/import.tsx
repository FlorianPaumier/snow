import { Form, Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ImportUsersController from '@/actions/App/Http/Controllers/ImportUsersController';
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
import { index } from '@/routes/users';
import { template } from '@/routes/users/import/index';
import type { BreadcrumbItem } from '@/types';

type Preview = {
    headers: string[];
    mapping: Record<string, string>;
    rows: { row: number; values: Record<string, string>; errors: string[] }[];
    csv: string;
};
const FIELDS = ['name', 'email', 'role'] as const;

export default function ImportUsers({
    importPreview,
}: {
    importPreview?: Preview;
}) {
    const { t } = useLocale();
    const [mapping, setMapping] = useState<Record<string, string>>(
        importPreview?.mapping ?? {},
    );
    useEffect(
        () => setMapping(importPreview?.mapping ?? {}),
        [importPreview?.mapping],
    );
    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.users.title, href: index().url },
        { title: t.users.importTitle, href: '/users/import' },
    ];

    return (
        <AppFormLayout
            title={t.users.importTitle}
            description={t.users.importDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.users.title}
        >
            <Head title={t.users.importTitle} />
            <Form
                {...ImportUsersController.store.form()}
                transform={(data) => data}
                className="flex flex-col gap-6"
            >
                {({ errors, processing }) => (
                    <>
                        <FormSection
                            title={t.users.importUploadTitle}
                            description={t.users.importUploadDescription}
                        >
                            <p className="text-sm">
                                <a className="underline" href={template().url}>
                                    {t.users.downloadTemplate}
                                </a>
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="file">
                                    {t.users.importFileLabel}
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
                                {t.users.previewImport}
                            </Button>
                        </FormSection>
                        {importPreview && (
                            <FormSection
                                title={t.users.mappingTitle}
                                description={t.users.mappingDescription}
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {FIELDS.map((field) => (
                                        <div key={field} className="grid gap-2">
                                            <Label htmlFor={`mapping-${field}`}>
                                                {t.users.importFields[field]}
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
                                                        {t.users.skipColumn}
                                                    </SelectItem>
                                                    {importPreview.headers.map(
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
                                    {t.users.previewRows.replace(
                                        ':count',
                                        String(importPreview.rows.length),
                                    )}
                                </p>
                                <div className="max-h-72 overflow-auto rounded-md border">
                                    <table className="w-full text-left text-sm">
                                        <thead className="sticky top-0 bg-muted">
                                            <tr>
                                                <th className="p-2">
                                                    {t.users.csvRow}
                                                </th>
                                                <th className="p-2">
                                                    {t.users.nameField}
                                                </th>
                                                <th className="p-2">
                                                    {t.users.emailField}
                                                </th>
                                                <th className="p-2">
                                                    {t.users.validation}
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
                                                            {row.values.name}
                                                        </td>
                                                        <td className="p-2">
                                                            {row.values.email}
                                                        </td>
                                                        <td className="p-2 text-destructive">
                                                            {row.errors.join(
                                                                ', ',
                                                            ) ||
                                                                t.users
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
                                    {t.users.importSubmit}
                                </Button>
                            </FormSection>
                        )}
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
