import { Form, Head } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
// Components
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const { t } = useLocale();

    return (
        <AuthLayout
            title={t.auth.forgotTitle}
            description={t.auth.forgotDescription}
        >
            <Head title={t.auth.forgotTitle} />

            <div className="space-y-6">
                {status && (
                    <div className="flex items-start gap-3 rounded-lg border border-success/25 bg-success/10 px-4 py-3 text-sm font-medium text-success">
                        <MailCheck className="mt-0.5 size-4 shrink-0" />
                        <span data-test="forgot-password-status">{status}</span>
                    </div>
                )}

                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t.auth.emailLabel}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        autoComplete="off"
                                        autoFocus
                                        placeholder="email@example.com"
                                        className="h-10"
                                    />

                                    <InputError message={errors.email} />
                                </div>

                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <Spinner className="size-4" />
                                    )}
                                    {t.auth.sendResetLinkButton}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>{t.auth.orReturnTo}</span>
                    <TextLink href={login()}>{t.auth.logInButton}</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
