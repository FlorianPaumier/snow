import { Form } from '@inertiajs/react';
import SendUserPasswordResetController from '@/actions/App/Http/Controllers/SendUserPasswordResetController';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import AppFormLayout, {
    CancelButton,
    FormFooter,
    FormSection,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { interpolate } from '@/lib/utils';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

type EditableUser = {
    id: string;
    name: string;
    email: string;
    role: string;
    status: string;
    is_owner: boolean;
};

export default function Edit({ user }: { user: EditableUser }) {
    const { t } = useLocale();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.users.title, href: index() },
        {
            title: interpolate(t.users.editUserBreadcrumbs, {
                name: user.name,
            }),
            href: `/users/${user.id}/edit`,
        },
    ];

    return (
        <AppFormLayout
            title={interpolate(t.users.editUserTitle, { name: user.name })}
            description={t.users.editUserDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.users.title}
        >
            <Form
                {...UserManagementController.update.form({ user: user.id })}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSection
                            title={t.users.detailsSection}
                            description={t.users.detailsSectionDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {t.users.nameField}
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
                                    required
                                    autoFocus
                                    name="name"
                                    defaultValue={user.name}
                                />
                                <InputError
                                    id={'name' + '-error'}
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t.users.emailField}
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
                                    defaultValue={user.email}
                                />
                                <InputError
                                    id={'email' + '-error'}
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">
                                    {t.users.roleField}
                                </Label>
                                {user.is_owner ? (
                                    <>
                                        <Input
                                            id="role"
                                            type="text"
                                            value={t.users.roleAdmin}
                                            readOnly
                                            disabled
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {t.users.ownerRoleLocked}
                                        </p>
                                    </>
                                ) : (
                                    <Select
                                        name="role"
                                        defaultValue={user.role}
                                    >
                                        <SelectTrigger
                                            id="role"
                                            className="w-full"
                                        >
                                            <SelectValue
                                                placeholder={
                                                    t.users
                                                        .selectRolePlaceholder
                                                }
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="admin">
                                                {t.users.roleAdmin}
                                            </SelectItem>
                                            <SelectItem value="worker">
                                                {t.users.roleWorker}
                                            </SelectItem>
                                            <SelectItem value="user">
                                                {t.users.roleUser}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                                <InputError message={errors.role} />
                            </div>
                        </FormSection>

                        <FormFooter>
                            <CancelButton href={index().url} />
                            <SubmitButton processing={processing}>
                                {t.users.saveChangesButton}
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>

            <FormSection
                title={t.users.accessSection}
                description={t.users.accessSectionDescription}
            >
                {user.status === 'pending' && (
                    <div>
                        <Badge
                            variant="outline"
                            className="gap-1.5 border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-400"
                        >
                            <span
                                aria-hidden="true"
                                className="size-1.5 shrink-0 rounded-full bg-current"
                            />
                            {t.users.pendingInvitation}
                        </Badge>
                    </div>
                )}

                <Form
                    {...SendUserPasswordResetController.form({
                        user: user.id,
                    })}
                    options={{
                        preserveScroll: true,
                    }}
                    disableWhileProcessing
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            disabled={processing}
                        >
                            {user.status === 'pending'
                                ? t.users.resendInvitation
                                : t.users.sendPasswordResetEmail}
                        </Button>
                    )}
                </Form>
            </FormSection>
        </AppFormLayout>
    );
}
