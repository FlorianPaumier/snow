import { Form } from '@inertiajs/react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    name="name"
                                    defaultValue={user.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t.users.emailField}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    name="email"
                                    defaultValue={user.email}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">
                                    {t.users.roleField}
                                </Label>
                                <Select name="role" defaultValue={user.role}>
                                    <SelectTrigger id="role" className="w-full">
                                        <SelectValue
                                            placeholder={
                                                t.users.selectRolePlaceholder
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
                                <InputError message={errors.role} />
                            </div>
                        </FormSection>

                        <FormSection
                            title={t.users.passwordSection}
                            description={t.users.passwordSectionDescription}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t.settings.passwordField}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    placeholder={t.settings.passwordField}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t.users.confirmNewPasswordField}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    placeholder={
                                        t.users.confirmNewPasswordField
                                    }
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
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
        </AppFormLayout>
    );
}
