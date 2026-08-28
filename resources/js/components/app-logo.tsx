import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
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
                <span className="mt-1 font-mono text-[10px] tracking-[0.18em] text-muted-foreground uppercase">
                    Snow operations
                </span>
            </div>
        </>
    );
}
