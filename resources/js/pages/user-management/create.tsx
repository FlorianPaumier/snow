import { Form } from '@inertiajs/react';
import UserManagementController from '@/actions/App/Http/Controllers/UserManagementController';
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
import { useLocale } from '@/hooks/use-locale';
import AppFormLayout, {
    CancelButton,
    FormFooter,
    SubmitButton,
} from '@/layouts/app-form/layout';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';

export default function Create() {
    const { t } = useLocale();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t.users.title, href: index() },
        { title: t.users.newUserTitle, href: '/users/create' },
    ];

    return (
        <AppFormLayout
            title={t.users.newUserTitle}
            description={t.users.newUserDescription}
            breadcrumbs={breadcrumbs}
            backHref={index().url}
            backLabel={t.users.title}
        >
            <Form
                {...UserManagementController.store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">{t.users.nameField}</Label>
                            <Input
                                aria-invalid={Boolean(errors.name)}
                                aria-describedby={
                                    errors.name ? 'name' + '-error' : undefined
                                }
                                id="name"
                                type="text"
                                required
                                autoFocus
                                name="name"
                                placeholder={t.auth.fullNamePlaceholder}
                            />
                            <InputError
                                id={'name' + '-error'}
                                message={errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">{t.users.emailField}</Label>
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
                                placeholder="email@example.com"
                            />
                            <InputError
                                id={'email' + '-error'}
                                message={errors.email}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="role">{t.users.roleField}</Label>
                            <Select name="role" defaultValue="user">
                                <SelectTrigger
                                    aria-invalid={Boolean(errors.role)}
                                    aria-describedby={
                                        errors.role
                                            ? 'role' + '-error'
                                            : undefined
                                    }
                                    id="role"
                                    className="w-full"
                                >
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
                            <InputError
                                id={'role' + '-error'}
                                message={errors.role}
                            />
                        </div>

                        <FormFooter>
                            <CancelButton href={index().url} />
                            <SubmitButton processing={processing}>
                                {t.users.createUserButton}
                            </SubmitButton>
                        </FormFooter>
                    </>
                )}
            </Form>
        </AppFormLayout>
    );
}
