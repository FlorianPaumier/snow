import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

type LinkHref = NonNullable<InertiaLinkProps['href']>;

/**
 * Shared layout for app-side forms.
 *
 * Every form rendered inside the app shell uses this layout so headers,
 * back links, panels, and action footers stay consistent.
 *
 * Usage:
 *
 * <AppFormLayout
 *     title="New user"
 *     description="Add a new user by providing a name, email, and role"
 *     breadcrumbs={[...]}
 *     backHref={index()}
 *     backLabel="Users"
 * >
 *     <Form>...</Form>
 * </AppFormLayout>
 */
export default function AppFormLayout({
    title,
    description,
    breadcrumbs,
    backHref,
    backLabel,
    headTitle,
    children,
}: PropsWithChildren<{
    title: string;
    description?: string;
    breadcrumbs: BreadcrumbItem[];
    backHref?: LinkHref;
    backLabel?: string;
    headTitle?: string;
}>) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={headTitle ?? title} />

            <div className="mx-auto flex h-full w-full max-w-2xl flex-1 flex-col gap-6 p-4">
                {backHref && (
                    <Button
                        asChild
                        variant="ghost"
                        size="sm"
                        className="-ml-2 self-start text-muted-foreground"
                    >
                        <Link href={backHref} prefetch>
                            <ArrowLeft className="size-4" />
                            {backLabel ?? 'Back'}
                        </Link>
                    </Button>
                )}

                <Heading title={title} description={description} />

                <div className="rounded-xl border p-4 sm:p-6">{children}</div>
            </div>
        </AppLayout>
    );
}

/**
 * Consistent action footer for forms: separated by a border, actions
 * right-aligned, cancel always to the left of the primary action.
 */
export function FormFooter({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div
            className={cn(
                'mt-6 flex flex-col-reverse gap-3 border-t pt-4 sm:flex-row sm:justify-end',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** Secondary cancel action rendered inside a FormFooter. */
export function CancelButton({ href }: { href: LinkHref }) {
    return (
        <Button asChild variant="outline" type="button">
            <Link href={href} prefetch>
                Cancel
            </Link>
        </Button>
    );
}

/** Primary submit action; shows a spinner and disables while processing. */
export function SubmitButton({
    processing,
    children,
}: PropsWithChildren<{ processing?: boolean }>) {
    return (
        <Button type="submit" disabled={processing}>
            {processing && <Spinner aria-hidden="true" />}
            {children}
        </Button>
    );
}

/**
 * Optional titled section for grouping related fields within a form.
 */
export function FormSection({
    title,
    description,
    children,
}: PropsWithChildren<{ title?: string; description?: string }>) {
    return (
        <section className="space-y-4">
            {title && (
                <div className="space-y-0.5">
                    <h3 className="text-sm font-medium">{title}</h3>
                    {description && (
                        <p className="text-xs text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
            )}
            <div className="grid gap-6">{children}</div>
        </section>
    );
}
