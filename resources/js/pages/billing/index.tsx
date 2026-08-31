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
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { contact } from '@/routes';
import { checkout, index, portal } from '@/routes/billing';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: index(),
    },
];

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
    const subscription = auth.subscription;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />

            <h1 className="sr-only">Billing</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Subscription"
                        description={`Plan and billing for ${organization.name}`}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                Current plan
                                {subscription?.onTrial && (
                                    <Badge variant="secondary">Trial</Badge>
                                )}
                            </CardTitle>
                            <CardDescription>
                                {subscription?.plan
                                    ? `You are subscribed to the ${subscription.plan} plan (${subscription.status}).`
                                    : subscription?.onTrial
                                      ? `Your free trial runs until ${subscription.trialEndsAt ?? 'its end'}. Pick a plan below to keep going.`
                                      : 'Pick a plan below to get started.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form {...portal.form()}>
                                <Button variant="outline" type="submit">
                                    Manage billing in Stripe
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
                                            ? 'Unlimited users'
                                            : `${plan.maxUsers} users`}
                                        {' · '}
                                        {plan.maxClients === null
                                            ? 'unlimited clients'
                                            : `${plan.maxClients} clients`}
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
                                                Subscribe monthly
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
                                                Subscribe annually
                                            </Button>
                                        </Form>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Prices are managed in Stripe. Need a custom arrangement?{' '}
                        <TextLink href={contact()}>Contact us</TextLink>.
                    </p>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
