import { useLocale } from '@/hooks/use-locale';
import publicFrench from '@/lang/public-fr';

export function usePublicCopy() {
    const { locale } = useLocale();

    function copy<T>(value: T): T {
        if (locale !== 'fr' || typeof value !== 'string') {
            return value;
        }
        const translated = publicFrench[value.trim()];
        return (
            translated ? value.replace(value.trim(), translated) : value
        ) as T;
    }

    return { copy, locale };
}
