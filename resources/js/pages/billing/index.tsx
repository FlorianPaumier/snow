import { Head, usePage } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
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
    plans,
}: {
    organization: { name: string };
    plans: Plan[];
}) {
    const { auth } = usePage().props;
    const { t } = useLocale();
    const subscription = auth.subscription;

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
                                    : subscription?.onTrial
                                      ? subscription.trialEndsAt
                                          ? interpolate(t.billing.trialUntil, {
                                                date: subscription.trialEndsAt,
                                            })
                                          : t.billing.trialUntilUnknown
                                      : t.billing.pickPlan}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...portal.form()}>
                                <Button variant="outline" type="submit">
                                    {t.billing.manageInStripe}
                                </Button>
                            </Form>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 md:grid-cols-3">
                        {plans.map((plan) => (
                            <Card key={plan.key}>
                                <CardHeader>
                                    <CardTitle>{plan.name}</CardTitle>
                                    <CardDescription>
                                        {plan.maxUsers === null
                                            ? t.billing.unlimitedUsers
                                            : interpolate(t.billing.userCount, {
                                                  count: plan.maxUsers,
                                              })}
                                        {' · '}
                                        {plan.maxClients === null
                                            ? t.billing.unlimitedClients
                                            : interpolate(
                                                  t.billing.clientCount,
                                                  { count: plan.maxClients },
                                              )}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-col gap-2">
                                        <Form {...checkout.form()}>
                                            <input
                                                type="hidden"
                                                name="plan"
                                                value={plan.key}
                                            />
                                            <input
                                                type="hidden"
                                                name="interval"
                                                value="monthly"
                                            />
                                            <Button
                                                type="submit"
                                                className="w-full"
                                            >
                                                {t.billing.subscribeMonthly}
                                            </Button>
                                        </Form>
                                        <Form {...checkout.form()}>
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
                                                variant="outline"
                                                className="w-full"
                                            >
                                                {t.billing.subscribeAnnually}
                                            </Button>
                                        </Form>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
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
