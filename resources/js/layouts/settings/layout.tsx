import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useLocale } from '@/hooks/use-locale';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { index as billingIndex } from '@/routes/billing';
import { index as catalogIndex } from '@/routes/catalog-services';
import { edit as editLocale } from '@/routes/locale';
import { edit as editOrganization } from '@/routes/organization';
import { edit as editPassword } from '@/routes/password';
import { show as paymentsSettings } from '@/routes/stripe-connect';
import { edit } from '@/routes/user-profile';
import type { NavItem } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { auth } = usePage().props;
    const { t } = useLocale();

    const sidebarNavItems: NavItem[] = [
        {
            title: t.settings.profile,
            href: edit(),
            icon: null,
        },
        {
            title: t.settings.password,
            href: editPassword(),
            icon: null,
        },
        {
            title: t.settings.appearance,
            href: editAppearance(),
            icon: null,
        },
        {
            title: t.settings.language,
            href: editLocale(),
            icon: null,
        },
    ];

    // Organization and billing are only visible to organization admins...
    const adminNavItems: NavItem[] = [];

    if (auth.can?.manageOrganization) {
        adminNavItems.push({
            title: t.settings.organization,
            href: editOrganization(),
            icon: null,
        });
    }

    if (auth.can?.manageContracts) {
        adminNavItems.push({
            title: t.catalog.title,
            href: catalogIndex(),
            icon: null,
        });
    }

    if (auth.can?.manageBilling) {
        adminNavItems.push({
            title: t.connect.title,
            href: paymentsSettings(),
            icon: null,
        });
        adminNavItems.push({
            title: t.settings.billing,
            href: billingIndex(),
            icon: null,
        });
    }

    const items = [...sidebarNavItems, ...adminNavItems];

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title={t.settings.title}
                description={t.settings.description}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside
                    className="w-full max-w-xl lg:w-48"
                    aria-label={t.settings.title}
                >
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label={t.settings.title}
                    >
                        {items.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
