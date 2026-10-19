import { Link, usePage } from '@inertiajs/react';
import { InfoIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { index } from '@/routes/billing';

/**
 * Persistent plan banner shown above every app page while the organization
 * is on its signup trial (full product, ending soon) or on the Free plan
 * (capped client book and team).
 */
export default function PlanBanner() {
    const { auth } = usePage().props;
    const { t, locale } = useLocale();

    const subscription = auth.subscription;

    if (subscription === null || subscription === undefined) {
        return null;
    }

    // Subscribed organizations not on a trial are settled: no banner...
    if (subscription.status !== 'trialing' && subscription.status !== 'free') {
        return null;
    }

    const isTrial = subscription.onTrial;

    const title = isTrial ? t.billing.trialTitle : t.billing.freeTitle;
    const body = isTrial
        ? subscription.trialEndsAt
            ? interpolateDate(
                  t.billing.trialBody,
                  subscription.trialEndsAt,
                  locale,
              )
            : t.billing.trialBodyUnknown
        : t.billing.freeBody;

    return (
        <Alert className="mx-4 w-auto max-w-5xl sm:mx-auto sm:w-[calc(100%-2rem)]">
            <InfoIcon />
            <AlertTitle>{title}</AlertTitle>
            <AlertDescription>
                <p>{body}</p>
                {/* The billing page is admin-only (can:manage-billing), so
                    only offer the upgrade link to users who can reach it —
                    matching how the settings sidebar gates its Billing
                    item. */}
                {auth.can?.manageBilling ? (
                    <div className="mt-2">
                        <Button asChild size="sm">
                            <Link href={index()}>
                                {t.billing.bannerUpgrade}
                            </Link>
                        </Button>
                    </div>
                ) : null}
            </AlertDescription>
        </Alert>
    );
}

/**
 * Drop a trial end timestamp into a sentence — dates arrive as ISO 8601.
 */
function interpolateDate(
    template: string,
    isoDate: string,
    locale: string,
): string {
    const date = new Date(isoDate);
    const formatted = date.toLocaleDateString(
        locale === 'fr' ? 'fr-CA' : 'en-CA',
        {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        },
    );

    return template.replace(':date', formatted);
}
