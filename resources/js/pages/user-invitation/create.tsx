import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AuthLayout from '@/layouts/auth-layout';
import { interpolate } from '@/lib/utils';
import { store } from '@/routes/user-invitation';

type Props = {
    token: string;
    email: string;
    organizationName: string | null;
};

export default function AcceptInvitation({
    token,
    email,
    organizationName,
}: Props) {
    const { t } = useLocale();

    const description = organizationName
        ? interpolate(t.auth.invitationDescription, {
              organization: organizationName,
          })
        : t.auth.invitationGenericDescription;

    return (
        <AuthLayout title={t.auth.invitationTitle} description={description}>
            <Head title={t.auth.invitationTitle} />

            <Form
                {...store.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">{t.auth.emailLabel}</Label>
                            <Input
                                aria-invalid={Boolean(errors.email)}
                                aria-describedby={
                                    errors.email
                                        ? 'email' + '-error'
                                        : undefined
                                }
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                className="mt-1 block w-full"
                                readOnly
                            />
                            <InputError
                                id={'email' + '-error'}
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t.auth.passwordLabel}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder={t.auth.passwordLabel}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t.users.confirmPasswordField}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder={t.users.confirmPasswordField}
                            />
                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            disabled={processing}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            {t.auth.invitationButton}
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
