import { Head, Form, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { interpolate } from '@/lib/utils';
import { contact } from '@/routes';
import { checkout, index, portal } from '@/routes/billing';
import type { BreadcrumbItem } from '@/types';

type Plan = {
    key: string;
    name: string;
    maxUsers: number | null;
    maxClients: number | null;
};

export default function BillingIndex({
    organization,
    plan,
}: {
    organization: { name: string };
    plan: Plan;
}) {
    const { auth } = usePage().props;
    const { t } = useLocale();
    const subscription = auth.subscription;

    const isPro = subscription?.plan === plan.key && !subscription?.onTrial;
    const isTrial = subscription?.status === 'trialing';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.billing.title,
            href: index(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.billing.title} />

            <h1 className="sr-only">{t.billing.title}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t.billing.subscriptionTitle}
                        description={interpolate(
                            t.billing.subscriptionDescription,
                            { name: organization.name },
                        )}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {t.billing.currentPlan}
                                {subscription?.onTrial && (
                                    <Badge variant="secondary">
                                        {t.billing.trial}
                                    </Badge>
                                )}
                            </CardTitle>
                            <CardDescription>
                                {subscription?.plan
                                    ? interpolate(t.billing.subscribedPlan, {
                                          plan: subscription.plan,
                                          status: subscription.status,
                                      })
                                    : t.billing.pickPlan}
                            </CardDescription>
                        </CardHeader>
                        {isPro && (
                            <CardContent>
                                <Form {...portal.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            type="submit"
                                            disabled={processing}
                                            aria-busy={processing}
                                        >
                                            {processing && (
                                                <Spinner
                                                    aria-label={
                                                        t.common.loading
                                                    }
                                                />
                                            )}
                                            {processing
                                                ? t.common.loading
                                                : t.billing.manageInStripe}
                                        </Button>
                                    )}
                                </Form>
                            </CardContent>
                        )}
                        {isTrial && (
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {subscription?.trialEndsAt
                                        ? interpolate(t.billing.trialUntil, {
                                              date: subscription.trialEndsAt,
                                          })
                                        : t.billing.trialUntilUnknown}
                                </p>
                            </CardContent>
                        )}
                    </Card>

                    <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t.billing.freePlanName}</CardTitle>
                                <CardDescription>
                                    {t.billing.freePlanLimits}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    {isPro
                                        ? t.billing.freeAfterDowngrade
                                        : t.billing.freeCurrentNote}
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border-glacier-500/50 shadow-glow-cyan">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {plan.name}
                                    <Badge className="bg-glacier-600 text-white dark:bg-glacier-500 dark:text-glacier-950">
                                        {t.billing.proBadge}
                                    </Badge>
                                </CardTitle>
                                <CardDescription>
                                    {plan.maxUsers === null
                                        ? t.billing.unlimitedUsers
                                        : interpolate(t.billing.userCount, {
                                              count: plan.maxUsers,
                                          })}
                                    {' · '}
                                    {plan.maxClients === null
                                        ? t.billing.unlimitedClients
                                        : interpolate(t.billing.clientCount, {
                                              count: plan.maxClients,
                                          })}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-col gap-2">
                                    <Form {...checkout.form()}>
                                        {({ processing }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="plan"
                                                    value={plan.key}
                                                />
                                                <input
                                                    type="hidden"
                                                    name="interval"
                                                    value="annual"
                                                />
                                                <Button
                                                    type="submit"
                                                    className="w-full"
                                                    disabled={
                                                        processing || isPro
                                                    }
                                                    aria-busy={processing}
                                                >
                                                    {processing && (
                                                        <Spinner
                                                            aria-label={
                                                                t.common.loading
                                                            }
                                                        />
                                                    )}
                                                    {processing
                                                        ? t.common.loading
                                                        : isPro
                                                          ? t.billing
                                                                .currentPlan
                                                          : t.billing
                                                                .subscribeAnnually}
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        {t.billing.pricesNote}{' '}
                        <TextLink href={contact()}>
                            {t.billing.contactUs}
                        </TextLink>
                        .
                    </p>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
