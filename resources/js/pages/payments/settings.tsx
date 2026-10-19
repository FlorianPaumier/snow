import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { show, store } from '@/routes/stripe-connect';

export default function Settings({
    configured,
    account,
    ready,
}: {
    configured: boolean;
    account: string | null;
    ready: boolean;
}) {
    const { t } = useLocale();
    return (
        <AppLayout breadcrumbs={[{ title: t.connect.title, href: show() }]}>
            <Head title={t.connect.title} />
            <SettingsLayout>
                <div className="space-y-6">
                    <h1 className="text-xl font-semibold">{t.connect.title}</h1>
                    <p>{t.connect.description}</p>
                    <p className="text-sm text-muted-foreground">
                        {t.connect.responsibilities}
                    </p>
                    <p role="status">
                        {!configured
                            ? t.connect.unconfigured
                            : ready
                              ? t.connect.ready
                              : t.connect.pending}
                    </p>
                    {configured && (
                        <Form {...store.form()}>
                            {({ processing, errors }) => (
                                <div className="space-y-2">
                                    <Button disabled={processing}>
                                        {t.connect.connect}
                                    </Button>
                                    <InputError message={errors.payment} />
                                </div>
                            )}
                        </Form>
                    )}
                    {account && (
                        <div className="flex flex-wrap gap-3">
                            <Button asChild variant="outline">
                                <a
                                    href="https://dashboard.stripe.com/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {t.connect.dashboard}
                                </a>
                            </Button>
                            <Button asChild variant="ghost">
                                <Link href={show()}>{t.connect.refresh}</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
