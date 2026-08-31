import type { InertiaLinkProps } from '@inertiajs/react';
import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

const cadCurrency = new Intl.NumberFormat('fr-CA', {
    style: 'currency',
    currency: 'CAD',
});

/**
 * Format an amount stored in cents as Canadian dollars.
 */
export function formatCurrency(cents: number): string {
    return cadCurrency.format(cents / 100);
}
