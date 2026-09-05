import { Link, usePage } from '@inertiajs/react';
import { CircleAlert } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { index } from '@/routes/billing';

/**
 * Bag-level feedback for business-rule blocks (plan caps, Pro-only
 * features, ownership protections). The backend renders these as
 * `back()->withErrors(...)` under the `planLimit`, `featureLocked`, and
 * `ownerProtected` keys; without this component the redirect is silent.
 *
 * The upgrade CTA only makes sense where money lifts the block — plan
 * caps and locked features. Ownership protections are solved by
 * transferring ownership, so the alert there carries no CTA.
 */
const BILLING_REMEDY_KEYS = ['planLimit', 'featureLocked'] as const;

export default function PlanCapAlert() {
    const { errors } = usePage().props;
    const { t } = useLocale();

    const activeKey = BILLING_REMEDY_KEYS.find(
        (key) => typeof errors[key] === 'string',
    );
    const ownerProtected =
        activeKey === undefined && typeof errors.ownerProtected === 'string';
    const message = activeKey
        ? errors[activeKey]
        : ownerProtected
          ? errors.ownerProtected
          : undefined;

    if (typeof message !== 'string' || message === '') {
        return null;
    }

    return (
        <Alert variant="destructive" data-test="plan-cap-alert">
            <CircleAlert />
            <AlertTitle>{t.common.planCapTitle}</AlertTitle>
            <AlertDescription>
                <p>{message}</p>
                {activeKey !== undefined && (
                    <div className="mt-2">
                        <Button asChild size="sm">
                            <Link href={index()}>
                                {t.billing.bannerUpgrade}
                            </Link>
                        </Button>
                    </div>
                )}
            </AlertDescription>
        </Alert>
    );
}
