import { Form } from '@inertiajs/react';
import { useState } from 'react';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useLocale } from '@/hooks/use-locale';
import AppFormLayout, {
    CancelButton,
    FormFooter,
    FormSection,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { interpolate } from '@/lib/utils';
import { index, show } from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';

type EditableClient = {
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
    addressStreet: string;
    addressCity: string;
    addressPostalCode: string;
    addressCountry: string;
};

export default function Edit({ client }: { client: EditableClient }) {
    const { t } = useLocale();
    const [type, setType] = useState(client.type);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.clients.title, href: index().url },
        {
            title: interpolate(t.clients.editClientTitle, {
                name: `${client.firstName} ${client.lastName}`,
            }),
            href: ClientController.edit.url({ client: client.id }),
        },
    ];

    return (
        <AppFormLayout
            title={interpolate(t.clients.editClientTitle, {
                name: `${client.firstName} ${client.lastName}`,
            })}
            description={t.clients.editClientDescription}
            breadcrumbs={breadcrumbs}
            backHref={show({ client: client.id }).url}
            backLabel={t.clients.clientBreadcrumb}
        >
            <Form
                {...ClientController.update.form({ client: client.id })}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <p className="font-mono text-xs text-muted-foreground">
                            {t.clients.clientNumberLabel}{' '}
                            <span className="font-medium">
                                {client.clientNumber}
                            </span>{' '}
                            {t.clients.clientNumberNote}
                        </p>

                        <FormSection
                            title={t.clients.identitySection}
                            description={t.clients.identityDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="type">{t.common.type}</Label>
                                <Select
                                    name="type"
                                    defaultValue={client.type}
                                    onValueChange={setType}
                                >
                                    <SelectTrigger
                                        aria-invalid={Boolean(errors.type)}
                                        aria-describedby={
                                            errors.type
                                                ? 'type' + '-error'
                                                : undefined
                                        }
                                        id="type"
                                        className="w-full"
                                    >
                                        <SelectValue
                                            placeholder={
                                                t.clients.typePlaceholder
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="individual">
                                            {t.clients.typeIndividual}
                                        </SelectItem>
                                        <SelectItem value="company">
                                            {t.clients.typeCompany}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    id={'type' + '-error'}
                                    message={errors.type}
                                />
                            </div>

                            {type === 'company' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="companyName">
                                        {t.clients.fieldCompanyTypeName}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(
                                            errors.companyName,
                                        )}
                                        aria-describedby={
                                            errors.companyName
                                                ? 'companyName' + '-error'
                                                : undefined
                                        }
                                        id="companyName"
                                        type="text"
                                        name="companyName"
                                        defaultValue={
                                            client.companyName ?? undefined
                                        }
                                        placeholder={
                                            t.clients.fieldCompanyTypeName
                                        }
                                    />
                                    <InputError
                                        id={'companyName' + '-error'}
                                        message={errors.companyName}
                                    />
                                </div>
                            )}
                        </FormSection>

                        <FormSection
                            title={t.clients.contactSection}
                            description={t.clients.contactDescription}
                        >
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="firstName">
                                        {t.clients.fieldFirstName}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(errors.firstName)}
                                        aria-describedby={
                                            errors.firstName
                                                ? 'firstName' + '-error'
                                                : undefined
                                        }
                                        id="firstName"
                                        type="text"
                                        required
                                        autoFocus
                                        name="firstName"
                                        defaultValue={client.firstName}
                                    />
                                    <InputError
                                        id={'firstName' + '-error'}
                                        message={errors.firstName}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="lastName">
                                        {t.clients.fieldLastName}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(errors.lastName)}
                                        aria-describedby={
                                            errors.lastName
                                                ? 'lastName' + '-error'
                                                : undefined
                                        }
                                        id="lastName"
                                        type="text"
                                        required
                                        name="lastName"
                                        defaultValue={client.lastName}
                                    />
                                    <InputError
                                        id={'lastName' + '-error'}
                                        message={errors.lastName}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t.clients.fieldEmail}
                                </Label>
                                <Input
                                    aria-invalid={Boolean(errors.email)}
                                    aria-describedby={
                                        errors.email
                                            ? 'email' + '-error'
                                            : undefined
                                    }
                                    id="email"
                                    type="email"
                                    required
                                    name="email"
                                    defaultValue={client.email}
                                />
                                <InputError
                                    id={'email' + '-error'}
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phoneDay">
                                        {t.clients.fieldPhoneDay}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(errors.phoneDay)}
                                        aria-describedby={
                                            errors.phoneDay
                                                ? 'phoneDay' + '-error'
                                                : undefined
                                        }
                                        id="phoneDay"
                                        type="tel"
                                        name="phoneDay"
                                        defaultValue={
                                            client.phoneDay ?? undefined
                                        }
                                        placeholder="514 555-0100"
                                    />
                                    <InputError
                                        id={'phoneDay' + '-error'}
                                        message={errors.phoneDay}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cellPhone">
                                        {t.clients.fieldCellPhone}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(errors.cellPhone)}
                                        aria-describedby={
                                            errors.cellPhone
                                                ? 'cellPhone' + '-error'
                                                : undefined
                                        }
                                        id="cellPhone"
                                        type="tel"
                                        name="cellPhone"
                                        defaultValue={
                                            client.cellPhone ?? undefined
                                        }
                                        placeholder="438 555-0100"
                                    />
                                    <InputError
                                        id={'cellPhone' + '-error'}
                                        message={errors.cellPhone}
                                    />
                                </div>
                            </div>
                        </FormSection>

                        <FormSection
                            title={t.clients.addressSection}
                            description={t.clients.addressDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="addressStreet">
                                    {t.clients.fieldStreet}
                                </Label>
                                <Input
                                    aria-invalid={Boolean(errors.addressStreet)}
                                    aria-describedby={
                                        errors.addressStreet
                                            ? 'addressStreet' + '-error'
                                            : undefined
                                    }
                                    id="addressStreet"
                                    type="text"
                                    required
                                    name="addressStreet"
                                    defaultValue={client.addressStreet}
                                />
                                <InputError
                                    id={'addressStreet' + '-error'}
                                    message={errors.addressStreet}
                                />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="addressCity">
                                        {t.clients.fieldCity}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(
                                            errors.addressCity,
                                        )}
                                        aria-describedby={
                                            errors.addressCity
                                                ? 'addressCity' + '-error'
                                                : undefined
                                        }
                                        id="addressCity"
                                        type="text"
                                        required
                                        name="addressCity"
                                        defaultValue={client.addressCity}
                                    />
                                    <InputError
                                        id={'addressCity' + '-error'}
                                        message={errors.addressCity}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="addressPostalCode">
                                        {t.clients.fieldPostalCode}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(
                                            errors.addressPostalCode,
                                        )}
                                        aria-describedby={
                                            errors.addressPostalCode
                                                ? 'addressPostalCode' + '-error'
                                                : undefined
                                        }
                                        id="addressPostalCode"
                                        type="text"
                                        required
                                        name="addressPostalCode"
                                        defaultValue={client.addressPostalCode}
                                    />
                                    <InputError
                                        id={'addressPostalCode' + '-error'}
                                        message={errors.addressPostalCode}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="addressCountry">
                                        {t.clients.fieldCountry}
                                    </Label>
                                    <Input
                                        aria-invalid={Boolean(
                                            errors.addressCountry,
                                        )}
                                        aria-describedby={
                                            errors.addressCountry
                                                ? 'addressCountry' + '-error'
                                                : undefined
                                        }
                                        id="addressCountry"
                                        type="text"
                                        required
                                        name="addressCountry"
                                        defaultValue={client.addressCountry}
                                    />
                                    <InputError
                                        id={'addressCountry' + '-error'}
                                        message={errors.addressCountry}
                                    />
                                </div>
                            </div>
                        </FormSection>

                        <FormSection
                            title={t.clients.notesSection}
                            description={t.clients.notesDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="remark">
                                    {t.clients.fieldRemark}
                                </Label>
                                <Textarea
                                    aria-invalid={Boolean(errors.remark)}
                                    aria-describedby={
                                        errors.remark
                                            ? 'remark' + '-error'
                                            : undefined
                                    }
                                    id="remark"
                                    name="remark"
                                    defaultValue={client.remark ?? undefined}
                                    placeholder={t.clients.remarkPlaceholder}
                                />
                                <InputError
                                    id={'remark' + '-error'}
                                    message={errors.remark}
                                />
                            </div>
                        </FormSection>

                        <FormFooter>
                            <CancelButton
                                href={show({ client: client.id }).url}
                            />
                            <SubmitButton processing={processing}>
                                {t.clients.buttonSaveClient}
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
