import { router } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';
import en from '@/lang/en';
import fr from '@/lang/fr';
import type { Translations } from '@/lang/fr';

export type Locale = 'fr' | 'en';

export type UseLocaleReturn = {
    readonly locale: Locale;
    readonly t: Translations;
    readonly updateLocale: (locale: Locale) => void;
};

const SUPPORTED: Locale[] = ['fr', 'en'];
const TRANSLATIONS: Record<Locale, Translations> = { fr, en };

const listeners = new Set<() => void>();
let currentLocale: Locale = 'fr';

const setCookie = (value: Locale, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `locale=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

const isLocale = (value: unknown): value is Locale =>
    typeof value === 'string' && (SUPPORTED as string[]).includes(value);

/**
 * Seeded by the server on first load (the `locale` shared prop is the
 * source of truth — the SetLocale middleware reads the cookie), then
 * kept in a local store so switching re-renders immediately while the
 * reload picks up server-translated messages.
 */
export function useLocale(): UseLocaleReturn {
    const pageLocale = usePage().props.locale;

    if (isLocale(pageLocale) && pageLocale !== currentLocale) {
        currentLocale = pageLocale;
    }

    const locale: Locale = useSyncExternalStore(
        subscribe,
        () => currentLocale,
        () => 'fr' as const,
    );

    const updateLocale = (next: Locale): void => {
        currentLocale = next;

        // Cookie for the server-side SetLocale middleware...
        setCookie(next);

        notify();

        // Re-fetch props so validation errors, flash statuses, and enum
        // labels provided by the server follow the new locale...
        router.reload();
    };

    return { locale, t: TRANSLATIONS[locale], updateLocale } as const;
}
