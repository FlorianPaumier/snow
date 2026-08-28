import { Link, usePage } from '@inertiajs/react';
import { Snowflake } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid min-h-svh lg:grid-cols-2">
            {/* Brand panel */}
            <div className="relative hidden h-full flex-col justify-between overflow-hidden bg-glacier-950 p-10 text-white lg:flex">
                <div className="bg-grid-frost absolute inset-0 opacity-60" />
                <div className="absolute -top-32 -left-32 size-96 rounded-full bg-glacier-500/20 blur-3xl" />
                <div className="absolute -right-24 bottom-1/4 size-80 animate-drift rounded-full bg-glacier-400/15 blur-3xl" />

                <Link
                    href={home()}
                    className="relative z-20 flex items-center gap-2 text-lg font-medium"
                >
                    <AppLogoIcon />
                    {name}
                </Link>

                <div className="relative z-20 max-w-md space-y-6">
                    <div className="flex size-12 items-center justify-center rounded-xl border border-glacier-400/30 bg-glacier-500/10 text-glacier-300 shadow-glow-cyan">
                        <Snowflake className="size-6" />
                    </div>
                    <div className="space-y-3">
                        <h2 className="text-3xl font-semibold tracking-tight text-balance text-white">
                            Snow operations, coordinated with cold precision.
                        </h2>
                        <p className="text-sm leading-relaxed text-balance text-glacier-200/80">
                            Dispatch crews, track properties, and storm-mode
                            your workflow — all from one clear console.
                        </p>
                    </div>
                </div>

                <p className="relative z-20 text-xs text-glacier-300/60">
                    © {new Date().getFullYear()} {name}. All rights reserved.
                </p>
            </div>

            {/* Form panel */}
            <div className="flex flex-col items-center justify-center bg-background p-6 md:p-10">
                <div className="w-full max-w-sm space-y-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 font-medium lg:hidden"
                        >
                            <div className="flex size-9 items-center justify-center rounded-md">
                                <AppLogoIcon />
                            </div>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-xl border border-border/60 bg-card p-6 shadow-frost-md sm:p-8">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
