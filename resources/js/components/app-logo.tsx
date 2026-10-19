import AppLogoIcon from '@/components/app-logo-icon';
import { useLocale } from '@/hooks/use-locale';

export default function AppLogo() {
    const { locale } = useLocale();
    return (
        <>
            <AppLogoIcon />
            <div className="ml-1 grid flex-1 text-left text-sm leading-none">
                <span className="font-semibold tracking-tight">
                    Frostline{' '}
                    <span className="text-glacier-600 dark:text-glacier-400">
                        OPS
                    </span>
                </span>
                <span className="mt-1 text-xs leading-snug text-muted-foreground">
                    {locale === 'fr'
                        ? 'Gestion du déneigement'
                        : 'Snow operations'}
                </span>
            </div>
        </>
    );
}
