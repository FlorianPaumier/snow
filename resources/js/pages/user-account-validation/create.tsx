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
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { resend, store } from '@/routes/account-validation';

type Props = {
    email: string;
    status?: string;
};

export default function ValidateAccount({ email, status }: Props) {
    return (
        <AuthLayout
            title="Validate your account"
            description={`Enter the 6-digit code we sent to ${email}`}
        >
            <Head title="Validate account" />

            <div className="space-y-6">
                {status === 'validation-code-sent' && (
                    <div
                        className="rounded-lg border border-info/25 bg-info/10 px-4 py-3 text-center text-sm font-medium text-info"
                        data-test="validation-code-sent"
                    >
                        A new validation code has been sent to your email
                        address.
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
                            data-test="resend-validation-code-button"
                        >
                            {processing && <Spinner />}
                            Resend code
                        </Button>
                    )}
                </Form>

                <p className="text-center text-sm text-muted-foreground">
                    Didn&apos;t sign up?{' '}
                    <TextLink
                        href={logout()}
                        method="post"
                        as="button"
                        className="text-sm"
                    >
                        Log out
                    </TextLink>
                </p>
            </div>
        </AuthLayout>
    );
}

function CodeForm() {
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
                            data-test="validation-code-input"
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
                        data-test="validate-account-button"
                    >
                        {processing && <Spinner />}
                        Validate account
                    </Button>
                </div>
            )}
        </Form>
    );
}
