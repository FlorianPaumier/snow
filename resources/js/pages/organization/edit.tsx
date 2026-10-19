import { Form, Head, usePage } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/OrganizationController';
import TransferOrganizationOwnershipController from '@/actions/App/Http/Controllers/TransferOrganizationOwnershipController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
import SettingsLayout from '@/layouts/settings/layout';
import { interpolate } from '@/lib/utils';
import { edit } from '@/routes/organization';
import type { BreadcrumbItem } from '@/types';

type OrganizationInfo = {
    id: string;
    name: string;
    ownerId: string | null;
    ownerName: string | null;
};

type Member = {
    id: string;
    name: string;
};

export default function Edit({
    organization,
    members,
}: {
    organization: OrganizationInfo;
    members: Member[];
}) {
    const { auth } = usePage().props;
    const { t } = useLocale();

    const isOwner = auth.user.id === organization.ownerId;
    const otherMembers = members.filter((member) => member.id !== auth.user.id);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.settings.organization,
            href: edit(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.settings.organization} />

            <h1 className="sr-only">{t.settings.organization}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t.settings.organizationTitle}
                        description={t.settings.organizationDescription}
                    />

                    <Form
                        {...OrganizationController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        {t.settings.nameField}
                                    </Label>

                                    <Input
                                        aria-invalid={Boolean(errors.name)}
                                        aria-describedby={
                                            errors.name
                                                ? 'name' + '-error'
                                                : undefined
                                        }
                                        id="name"
                                        type="text"
                                        name="name"
                                        required
                                        autoFocus
                                        defaultValue={organization.name}
                                    />

                                    <InputError
                                        id={'name' + '-error'}
                                        message={errors.name}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        {t.common.save}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t.settings.ownershipTitle}
                        description={t.settings.ownershipDescription}
                    />

                    <p className="text-sm text-muted-foreground">
                        {interpolate(t.settings.ownerLabel, {
                            name: organization.ownerName ?? '',
                        })}
                    </p>

                    {isOwner &&
                        (otherMembers.length > 0 ? (
                            <Form
                                {...TransferOrganizationOwnershipController.form()}
                                id="transfer-ownership-form"
                                options={{
                                    preserveScroll: true,
                                }}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="user_id">
                                                {t.settings.transferSelect}
                                            </Label>

                                            <Select name="user_id">
                                                <SelectTrigger
                                                    aria-invalid={Boolean(
                                                        errors.user_id,
                                                    )}
                                                    aria-describedby={
                                                        errors.user_id
                                                            ? 'user_id' +
                                                              '-error'
                                                            : undefined
                                                    }
                                                    id="user_id"
                                                    className="w-full"
                                                >
                                                    <SelectValue
                                                        placeholder={
                                                            t.settings
                                                                .transferSelectPlaceholder
                                                        }
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {otherMembers.map(
                                                        (member) => (
                                                            <SelectItem
                                                                key={member.id}
                                                                value={
                                                                    member.id
                                                                }
                                                            >
                                                                {member.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>

                                            <InputError
                                                id={'user_id' + '-error'}
                                                message={errors.user_id}
                                            />
                                        </div>

                                        <p className="text-sm text-muted-foreground">
                                            {t.settings.transferDescription}
                                        </p>

                                        <p className="text-sm text-destructive">
                                            {t.settings.transferWarning}
                                        </p>

                                        <div className="flex items-center gap-4">
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        data-test="transfer-ownership"
                                                    >
                                                        {
                                                            t.settings
                                                                .transferButton
                                                        }
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogTitle>
                                                        {
                                                            t.settings
                                                                .transferConfirmTitle
                                                        }
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        {
                                                            t.settings
                                                                .transferConfirmDescription
                                                        }
                                                    </DialogDescription>

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
                                                                form="transfer-ownership-form"
                                                                data-test="confirm-transfer-ownership"
                                                            >
                                                                {
                                                                    t.settings
                                                                        .transferButton
                                                                }
                                                            </button>
                                                        </Button>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </>
                                )}
                            </Form>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t.settings.noOtherMembers}
                            </p>
                        ))}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
