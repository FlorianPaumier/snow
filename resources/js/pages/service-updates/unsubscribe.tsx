import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import PublicLayout from '@/layouts/public-layout';
import { store } from '@/routes/service-updates/unsubscribe';

export default function Unsubscribe({
    organization,
    client,
    organizationName,
    enabled,
    signature,
}: {
    organization: string;
    client: string;
    organizationName: string;
    enabled: boolean;
    signature: string;
}) {
    const { t } = useLocale();
    return (
        <PublicLayout>
            <main className="mx-auto max-w-xl space-y-6 px-6 py-12">
                <Head title={t.serviceUpdates.unsubscribe}>
                    <meta name="robots" content="noindex, nofollow" />
                    <meta name="referrer" content="no-referrer" />
                </Head>
                <h1 className="text-2xl font-semibold">{organizationName}</h1>
                {enabled ? (
                    <Form
                        {...store.form(
                            { organization, client },
                            { query: { signature } },
                        )}
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-4">
                                <p>{t.serviceUpdates.unsubscribeConfirm}</p>
                                <Button disabled={processing}>
                                    {t.serviceUpdates.unsubscribe}
                                </Button>
                                <InputError message={errors.client} />
                            </div>
                        )}
                    </Form>
                ) : (
                    <p role="status">{t.serviceUpdates.unsubscribed}</p>
                )}
            </main>
        </PublicLayout>
    );
}
