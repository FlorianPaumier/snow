import { Link, usePage } from '@inertiajs/react';
import { Snowflake } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useLocale } from '@/hooks/use-locale';
import PublicLayout from '@/layouts/public-layout';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;
    const { t } = useLocale();

    return (
        <PublicLayout className="relative grid min-h-svh lg:grid-cols-2">
            {/* Brand panel */}
            <aside className="relative hidden h-full flex-col justify-between overflow-hidden bg-secondary p-10 text-foreground lg:flex">
                <Link
                    href={home()}
                    className="relative z-20 flex items-center gap-2 text-lg font-medium"
                >
                    <AppLogoIcon />
                    {name}
                </Link>

                <div className="relative z-20 max-w-md space-y-6">
                    <div className="flex size-12 items-center justify-center rounded-xl border border-border bg-card text-primary">
                        <Snowflake className="size-6" />
                    </div>
                    <div className="space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight text-balance text-foreground">
                            {t.auth.brandHeadline}
                        </h2>
                        <p className="text-sm leading-relaxed text-balance text-muted-foreground">
                            {t.auth.brandSubline}
                        </p>
                    </div>
                </div>

                <p className="relative z-20 text-sm text-muted-foreground">
                    © {new Date().getFullYear()} {name}.{' '}
                    {t.auth.allRightsReserved}
                </p>
            </aside>

            {/* Form panel */}
            <main className="flex flex-col items-center justify-center bg-background p-6 md:p-10">
                <div className="w-full max-w-sm space-y-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            aria-label={name}
                            className="flex items-center gap-2 font-medium lg:hidden"
                        >
                            <div className="flex size-9 items-center justify-center rounded-md">
                                <AppLogoIcon />
                            </div>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-3xl font-semibold">{title}</h1>
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-xl border border-border/60 bg-card p-6 shadow-[var(--preview-elevation)] sm:p-8">
                        {children}
                    </div>
                </div>
            </main>
        </PublicLayout>
    );
}
