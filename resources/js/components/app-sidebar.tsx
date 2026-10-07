import { Link, usePage } from '@inertiajs/react';
import { Building2, FileText, LayoutGrid, Route, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useLocale } from '@/hooks/use-locale';
import { dashboard } from '@/routes';
import { index as clientsIndex } from '@/routes/clients';
import { index as contractsIndex } from '@/routes/contracts';
import { index as toursIndex, worker as workerTours } from '@/routes/tours';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const { t } = useLocale();

    const mainNavItems: NavItem[] = [
        {
            title: t.nav.dashboard,
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t.nav.clients,
            href: clientsIndex(),
            icon: Building2,
        },
        {
            title: t.nav.contracts,
            href: contractsIndex(),
            icon: FileText,
        },
    ];

    const userManagementNavItems: NavItem[] = [
        {
            title: t.nav.users,
            href: usersIndex(),
            icon: Users,
        },
    ];

    const footerNavItems: NavItem[] = [];

    const items =
        auth.user.role === 'worker'
            ? [{ title: t.tours.myTours, href: workerTours(), icon: Route }]
            : auth.can?.manageUsers
              ? [
                    ...mainNavItems,
                    { title: t.tours.title, href: toursIndex(), icon: Route },
                    ...userManagementNavItems,
                ]
              : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
