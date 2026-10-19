import { useId } from 'react';
import type { HTMLAttributes } from 'react';
import { useLocale, type Locale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';

/**
 * FR/EN pill toggle for the application locale, following the same
 * visual pattern as the appearance tabs. The active locale is shown
 * with the full name, the inactive one with its short code.
 */
export default function LocaleTabs({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { locale, updateLocale } = useLocale();
    const groupId = useId();

    const options: { value: Locale; short: string; full: string }[] = [
        { value: 'fr', short: 'FR', full: 'Français' },
        { value: 'en', short: 'EN', full: 'English' },
    ];

    return (
        <div
            role="group"
            aria-labelledby={groupId}
            className={cn(
                'inline-flex gap-1 rounded-lg bg-muted p-1',
                className,
            )}
            {...props}
        >
            <span id={groupId} className="sr-only">
                {locale === 'fr' ? 'Langue' : 'Language'}
            </span>
            {options.map(({ value, short, full }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateLocale(value)}
                    aria-pressed={locale === value}
                    className={cn(
                        'flex min-h-11 items-center rounded-md px-3.5 py-1.5 transition-colors',
                        locale === value
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    )}
                >
                    <span className="text-sm">
                        {locale === value ? full : short}
                    </span>
                </button>
            ))}
        </div>
    );
}
