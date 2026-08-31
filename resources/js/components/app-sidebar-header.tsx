import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    // The last breadcrumb names the current page; exposing it as a
    // screen-reader-only h1 gives every page a level-one heading without
    // changing the visual hierarchy (each page's visible Heading stays
    // the page's styled title)...
    const pageTitle = breadcrumbs.at(-1)?.title;

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            {pageTitle && (
                <h1 className="sr-only">{pageTitle}</h1>
            )}
        </header>
    );
}
