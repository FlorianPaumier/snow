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
import AppFormLayout, {
    CancelButton,
    FormFooter,
    FormSection,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { index } from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: index().url },
    { title: 'New client', href: '/clients/create' },
];

export default function Create() {
    const [type, setType] = useState('individual');

    return (
        <AppFormLayout
            title="New client"
            description="Add a new client by providing their identity, contact details, and address"
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel="Clients"
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
                            title="Identity"
                            description="Who this client is"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    name="type"
                                    defaultValue="individual"
                                    onValueChange={setType}
                                >
                                    <SelectTrigger id="type" className="w-full">
                                        <SelectValue placeholder="Select a type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="individual">
                                            Individual
                                        </SelectItem>
                                        <SelectItem value="company">
                                            Company
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            {type === 'company' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="companyName">
                                        Company name
                                    </Label>
                                    <Input
                                        id="companyName"
                                        type="text"
                                        name="companyName"
                                        placeholder="Company name"
                                    />
                                    <InputError message={errors.companyName} />
                                </div>
                            )}
                        </FormSection>

                        <FormSection
                            title="Contact"
                            description="How to reach this client — at least one phone number is required"
                        >
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="firstName">
                                        First name
                                    </Label>
                                    <Input
                                        id="firstName"
                                        type="text"
                                        required
                                        name="firstName"
                                        placeholder="First name"
                                    />
                                    <InputError message={errors.firstName} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="lastName">Last name</Label>
                                    <Input
                                        id="lastName"
                                        type="text"
                                        required
                                        name="lastName"
                                        placeholder="Last name"
                                    />
                                    <InputError message={errors.lastName} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
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
                                        Daytime phone
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
                                        Cell phone
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
                            title="Address"
                            description="Where services are delivered"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="addressStreet">Street</Label>
                                <Input
                                    id="addressStreet"
                                    type="text"
                                    required
                                    name="addressStreet"
                                    placeholder="1234 Rue Sainte-Catherine"
                                />
                                <InputError message={errors.addressStreet} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="addressCity">City</Label>
                                    <Input
                                        id="addressCity"
                                        type="text"
                                        required
                                        name="addressCity"
                                        placeholder="Montréal"
                                    />
                                    <InputError message={errors.addressCity} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="addressPostalCode">
                                        Postal code
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
                                        Country
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
                            title="Notes"
                            description="Internal remarks about this client (optional)"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="remark">Remark</Label>
                                <Textarea
                                    id="remark"
                                    name="remark"
                                    placeholder="Access instructions, preferences, anything the crew should know…"
                                />
                                <InputError message={errors.remark} />
                            </div>
                        </FormSection>

                        <FormFooter>
                            <CancelButton href={index().url} />
                            <SubmitButton processing={processing}>
                                Create client
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
