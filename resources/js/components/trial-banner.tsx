import { Form, Link, usePage } from '@inertiajs/react';
import { LockIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { index, unlock } from '@/routes/billing';

/**
 * Persistent read-only state banner shown above every app page while the
 * organization has no valid access (expired trial, lapsed subscription).
 * The unlock button is the one-time 24h escape hatch for admins.
 */
export default function TrialBanner() {
    const { auth } = usePage().props;
    const { t } = useLocale();

    if (!auth.access?.readOnly) {
        return null;
    }

    // Copy variant rule: any subscription row ever created means the org
    // got here through a payment problem, not an expired trial...
    const hasSubscription =
        auth.subscription !== null && auth.subscription !== undefined;

    const title = hasSubscription
        ? t.billing.readOnlyPaymentTitle
        : t.billing.readOnlyTitle;
    const body = hasSubscription
        ? t.billing.readOnlyPaymentBody
        : t.billing.readOnlyBody;

    return (
        <Alert>
            <LockIcon />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription>
                <p>{body}</p>
                <div className="mt-2 flex flex-wrap gap-2">
                    <Button asChild size="sm">
                        <Link href={index()}>{t.billing.readOnlyUpgrade}</Link>
                    </Button>
                    {auth.can?.manageBilling && auth.access.unlockAvailable && (
                        <Form {...unlock.form()}>
                            <Button type="submit" size="sm" variant="outline">
                                {t.billing.readOnlyUnlock}
                            </Button>
                        </Form>
                    )}
                </div>
            </AlertDescription>
        </Alert>
    );
}
