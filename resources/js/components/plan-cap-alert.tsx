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
 */
export default function PlanCapAlert() {
    const { errors } = usePage().props;
    const { t } = useLocale();

    const message =
        (typeof errors.planLimit === 'string' && errors.planLimit) ||
        (typeof errors.featureLocked === 'string' && errors.featureLocked) ||
        (typeof errors.ownerProtected === 'string' && errors.ownerProtected);

    if (!message) {
        return null;
    }

    return (
        <Alert variant="destructive" data-test="plan-cap-alert">
            <CircleAlert />
            <AlertTitle>{t.common.planCapTitle}</AlertTitle>
            <AlertDescription>
                <p>{message}</p>
                <div className="mt-2">
                    <Button asChild size="sm">
                        <Link href={index()}>{t.common.planCapUpgrade}</Link>
                    </Button>
                </div>
            </AlertDescription>
        </Alert>
    );
}
