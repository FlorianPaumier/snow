import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { ROUTES } from '@/components/marketing/routes';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const { t } = useLocale();

    return (
        <AuthLayout
            title={t.auth.registerTitle}
            description={t.auth.registerDescription}
        >
            <Head title={t.auth.registerTitle} />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t.auth.nameField}</Label>
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
                                    autoComplete="name"
                                    name="name"
                                    placeholder={t.auth.fullNamePlaceholder}
                                />
                                <InputError
                                    id={'name' + '-error'}
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="organization">
                                    {t.auth.organizationField}
                                </Label>
                                <Input
                                    aria-invalid={Boolean(errors.organization)}
                                    aria-describedby={
                                        errors.organization
                                            ? 'organization' + '-error'
                                            : undefined
                                    }
                                    id="organization"
                                    type="text"
                                    required
                                    autoComplete="organization"
                                    name="organization"
                                    placeholder={t.auth.organizationPlaceholder}
                                />
                                <InputError
                                    id={'organization' + '-error'}
                                    message={errors.organization}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t.auth.emailLabel}
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
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError
                                    id={'email' + '-error'}
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="invite_code">
                                    {t.auth.inviteCodeField}
                                </Label>
                                <Input
                                    aria-invalid={Boolean(errors.invite_code)}
                                    aria-describedby={
                                        errors.invite_code
                                            ? 'invite_code' + '-error'
                                            : undefined
                                    }
                                    id="invite_code"
                                    type="text"
                                    required
                                    name="invite_code"
                                    autoComplete="off"
                                    autoCapitalize="characters"
                                    spellCheck={false}
                                    className="font-mono text-sm tracking-widest"
                                    placeholder={t.auth.inviteCodePlaceholder}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t.auth.inviteCodeHint}
                                </p>
                                <InputError
                                    id={'invite_code' + '-error'}
                                    message={errors.invite_code}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t.auth.passwordLabel}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    autoComplete="new-password"
                                    name="password"
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
                                    required
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t.users.confirmPasswordField}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        aria-invalid={Boolean(errors.terms)}
                                        aria-describedby={
                                            errors.terms
                                                ? 'terms' + '-error'
                                                : undefined
                                        }
                                        id="terms"
                                        name="terms"
                                        className="mt-0.5"
                                        required
                                    />
                                    <Label
                                        htmlFor="terms"
                                        className="leading-relaxed font-normal text-muted-foreground"
                                    >
                                        {t.auth.acceptTermsPrefix}{' '}
                                        <TextLink href={ROUTES.terms}>
                                            {t.auth.termsOfService}
                                        </TextLink>{' '}
                                        {t.auth.acceptTermsConnector}{' '}
                                        <TextLink href={ROUTES.privacy}>
                                            {t.auth.privacyPolicy}
                                        </TextLink>
                                    </Label>
                                </div>
                                <InputError
                                    id={'terms' + '-error'}
                                    message={errors.terms}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t.auth.createAccountButton}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t.auth.haveAccount}{' '}
                            <TextLink href={login()}>
                                {t.auth.logInButton}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
