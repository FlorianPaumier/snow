import type { FormComponentRef } from '@inertiajs/core';
import { Form, Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useRef, useState } from 'react';
// Components
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AuthLayout from '@/layouts/auth-layout';
import { interpolate } from '@/lib/utils';
import { login } from '@/routes';
import { resend, store } from '@/routes/login-challenge';

type Props = {
    email: string;
    status?: string;
};

export default function LoginChallenge({ email, status }: Props) {
    const { t } = useLocale();

    return (
        <AuthLayout
            title={t.auth.challengeTitle}
            description={interpolate(t.auth.challengeSubtitle, { email })}
        >
            <Head title={t.auth.challengeTitle} />

            <div className="space-y-6">
                {status === 'login-challenge-code-sent' && (
                    <div
                        className="rounded-lg border border-info/25 bg-info/10 px-4 py-3 text-center text-sm font-medium text-info"
                        data-test="login-challenge-code-sent"
                    >
                        {t.auth.challengeCodeSent}
                    </div>
                )}

                <CodeForm />

                <Form {...resend.form()}>
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="secondary"
                            className="w-full"
                            disabled={processing}
                            data-test="resend-login-code-button"
                        >
                            {processing && <Spinner />}
                            {t.auth.challengeResend}
                        </Button>
                    )}
                </Form>

                <p className="text-center text-sm text-muted-foreground">
                    {t.auth.challengeNotYou}{' '}
                    <TextLink
                        href={login()}
                        className="text-sm"
                        data-test="back-to-login-link"
                    >
                        {t.auth.challengeBackToLogin}
                    </TextLink>
                </p>
            </div>
        </AuthLayout>
    );
}

function CodeForm() {
    const { t } = useLocale();
    const [code, setCode] = useState('');
    const formRef = useRef<FormComponentRef>(null);

    return (
        <Form {...store.form()} ref={formRef}>
            {({ processing, errors }) => (
                <div className="grid gap-6">
                    <div className="flex flex-col items-center gap-3">
                        <div className="flex size-11 items-center justify-center rounded-full border border-glacier-400/30 bg-glacier-500/10 text-glacier-600 dark:text-glacier-400">
                            <ShieldCheck className="size-5" />
                        </div>

                        <InputOTP
                            id="code"
                            name="code"
                            maxLength={6}
                            autoFocus
                            autoComplete="one-time-code"
                            inputMode="numeric"
                            aria-label={t.auth.loginCodeAria}
                            value={code}
                            onChange={(value) => {
                                setCode(value);

                                // Auto-submit once the code is complete...
                                if (value.length === 6) {
                                    formRef.current?.submit();
                                }
                            }}
                            disabled={processing}
                            containerClassName="justify-center"
                            data-test="login-challenge-input"
                        >
                            <InputOTPGroup>
                                {[0, 1, 2].map((index) => (
                                    <InputOTPSlot
                                        key={index}
                                        index={index}
                                        className="size-11 text-base font-medium"
                                    />
                                ))}
                            </InputOTPGroup>
                            <InputOTPSeparator />
                            <InputOTPGroup>
                                {[3, 4, 5].map((index) => (
                                    <InputOTPSlot
                                        key={index}
                                        index={index}
                                        className="size-11 text-base font-medium"
                                    />
                                ))}
                            </InputOTPGroup>
                        </InputOTP>

                        <InputError
                            message={errors.code}
                            className="text-center"
                        />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing || code.length !== 6}
                        data-test="confirm-login-button"
                    >
                        {processing && <Spinner />}
                        {t.auth.challengeConfirmButton}
                    </Button>
                </div>
            )}
        </Form>
    );
}
