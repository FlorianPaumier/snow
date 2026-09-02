import type { InertiaLinkProps } from '@inertiajs/react';
import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { useLocale } from '@/hooks/use-locale';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Replace `:placeholder` tokens in a translation string.
 */
export function interpolate(
    template: string,
    params: Record<string, string | number>,
): string {
    return template.replace(/:(\w+)/g, (match, key: string) =>
        key in params ? String(params[key]) : match,
    );
}

/**
 * Format an amount stored in cents as Canadian dollars, using the
 * number-formatting conventions of the active locale.
 */
export function useCurrencyFormatter(): (cents: number) => string {
    const { locale } = useLocale();

    return (cents: number): string => {
        const formatter = new Intl.NumberFormat(
            locale === 'en' ? 'en-CA' : 'fr-CA',
            { style: 'currency', currency: 'CAD' },
        );

        return formatter.format(cents / 100);
    };
}

/**
 * Format an amount stored in cents as Canadian dollars in the French
 * (Québec) convention. Prefer `useCurrencyFormatter` in components so
 * the formatting follows the active locale; this plain helper remains
 * for module-level contexts like aria-label templates outside React.
 */
export function formatCurrency(cents: number): string {
    return new Intl.NumberFormat('fr-CA', {
        style: 'currency',
        currency: 'CAD',
    }).format(cents / 100);
}
