import { Form, Head } from '@inertiajs/react';
// Components
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useLocale();

    return (
        <AuthLayout
            title={t.auth.verificationTitle}
            description={t.auth.verificationSentDescription}
        >
            <Head title={t.auth.verificationTitle} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    {t.auth.verificationLinkSent}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t.auth.resendVerification}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t.auth.logout}
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
