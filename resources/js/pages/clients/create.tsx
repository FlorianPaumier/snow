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
import { index } from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';

export default function Create() {
    const { t } = useLocale();
    const [type, setType] = useState('individual');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.clients.title, href: index().url },
        { title: t.clients.newClient, href: '/clients/create' },
    ];

    return (
        <AppFormLayout
            title={t.clients.newClient}
            description={t.clients.newClientDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.clients.title}
        >
            <Form
                {...ClientController.store.form()}
                resetOnSuccess={[
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
                ]}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSection
                            title={t.clients.identitySection}
                            description={t.clients.identityDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="type">{t.common.type}</Label>
                                <Select
                                    name="type"
                                    defaultValue="individual"
                                    onValueChange={setType}
                                >
                                    <SelectTrigger id="type" className="w-full">
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
                                <InputError message={errors.type} />
                            </div>

                            {type === 'company' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="companyName">
                                        {t.clients.fieldCompanyTypeName}
                                    </Label>
                                    <Input
                                        id="companyName"
                                        type="text"
                                        name="companyName"
                                        placeholder={
                                            t.clients.fieldCompanyTypeName
                                        }
                                    />
                                    <InputError message={errors.companyName} />
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
                                        id="firstName"
                                        type="text"
                                        required
                                        name="firstName"
                                        placeholder={t.clients.fieldFirstName}
                                    />
                                    <InputError message={errors.firstName} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="lastName">
                                        {t.clients.fieldLastName}
                                    </Label>
                                    <Input
                                        id="lastName"
                                        type="text"
                                        required
                                        name="lastName"
                                        placeholder={t.clients.fieldLastName}
                                    />
                                    <InputError message={errors.lastName} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t.clients.fieldEmail}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phoneDay">
                                        {t.clients.fieldPhoneDay}
                                    </Label>
                                    <Input
                                        id="phoneDay"
                                        type="tel"
                                        name="phoneDay"
                                        placeholder="514 555-0100"
                                    />
                                    <InputError message={errors.phoneDay} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cellPhone">
                                        {t.clients.fieldCellPhone}
                                    </Label>
                                    <Input
                                        id="cellPhone"
                                        type="tel"
                                        name="cellPhone"
                                        placeholder="438 555-0100"
                                    />
                                    <InputError message={errors.cellPhone} />
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
                                    id="addressStreet"
                                    type="text"
                                    required
                                    name="addressStreet"
                                    placeholder={t.clients.streetPlaceholder}
                                />
                                <InputError message={errors.addressStreet} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="addressCity">
                                        {t.clients.fieldCity}
                                    </Label>
                                    <Input
                                        id="addressCity"
                                        type="text"
                                        required
                                        name="addressCity"
                                        placeholder={t.clients.cityPlaceholder}
                                    />
                                    <InputError message={errors.addressCity} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="addressPostalCode">
                                        {t.clients.fieldPostalCode}
                                    </Label>
                                    <Input
                                        id="addressPostalCode"
                                        type="text"
                                        required
                                        name="addressPostalCode"
                                        placeholder="H3G 1M7"
                                    />
                                    <InputError
                                        message={errors.addressPostalCode}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="addressCountry">
                                        {t.clients.fieldCountry}
                                    </Label>
                                    <Input
                                        id="addressCountry"
                                        type="text"
                                        required
                                        name="addressCountry"
                                        placeholder="Canada"
                                    />
                                    <InputError
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
                                    id="remark"
                                    name="remark"
                                    placeholder={t.clients.remarkPlaceholder}
                                />
                                <InputError message={errors.remark} />
                            </div>
                        </FormSection>

                        <FormFooter>
                            <CancelButton href={index().url} />
                            <SubmitButton processing={processing}>
                                {t.clients.buttonCreateClient}
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
