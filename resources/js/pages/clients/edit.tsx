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
    const [type, setType] = useState(client.type);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clients', href: index().url },
        {
            title: `Edit ${client.firstName} ${client.lastName}`,
            href: ClientController.edit.url({ client: client.id }),
        },
    ];

    return (
        <AppFormLayout
            title={`Edit ${client.firstName} ${client.lastName}`}
            description="Update the client's identity, contact details, and address"
            breadcrumbs={breadcrumbs}
            backHref={show({ client: client.id }).url}
            backLabel="Client"
        >
            <Form
                {...ClientController.update.form({ client: client.id })}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <p className="font-mono text-xs text-muted-foreground">
                            Client number:{' '}
                            <span className="font-medium">
                                {client.clientNumber}
                            </span>{' '}
                            — assigned at creation and never renumbered.
                        </p>

                        <FormSection
                            title="Identity"
                            description="Who this client is"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    name="type"
                                    defaultValue={client.type}
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
                                        defaultValue={
                                            client.companyName ?? undefined
                                        }
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
                                        autoFocus
                                        name="firstName"
                                        defaultValue={client.firstName}
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
                                        defaultValue={client.lastName}
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
                                    defaultValue={client.email}
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
                                        defaultValue={
                                            client.phoneDay ?? undefined
                                        }
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
                                        defaultValue={
                                            client.cellPhone ?? undefined
                                        }
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
                                    defaultValue={client.addressStreet}
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
                                        defaultValue={client.addressCity}
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
                                        defaultValue={client.addressPostalCode}
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
                                        defaultValue={client.addressCountry}
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
                                    defaultValue={client.remark ?? undefined}
                                    placeholder="Access instructions, preferences, anything the crew should know…"
                                />
                                <InputError message={errors.remark} />
                            </div>
                        </FormSection>

                        <FormFooter>
                            <CancelButton
                                href={show({ client: client.id }).url}
                            />
                            <SubmitButton processing={processing}>
                                Save changes
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
