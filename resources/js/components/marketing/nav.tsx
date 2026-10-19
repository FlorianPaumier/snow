import { Link } from '@inertiajs/react';
import { ArrowRight, Menu, Moon, Snowflake, Sun } from 'lucide-react';
import * as React from 'react';
import LocaleTabs from '@/components/locale-tabs';
import { marketingNav } from '@/components/marketing/data';
import { ROUTES } from '@/components/marketing/routes';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAppearance } from '@/hooks/use-appearance';
import { usePublicCopy } from '@/hooks/use-public-copy';
import { cn } from '@/lib/utils';
import { login } from '@/routes';

export function MarketingNav({ route }: { route: string }) {
    const { copy } = usePublicCopy();

    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [menuOpen, setMenuOpen] = React.useState(false);

    return (
        <header className="sticky top-0 z-50 w-full border-b border-border/80 bg-card/95 backdrop-blur-md">
            <div className="mx-auto flex h-20 w-full max-w-[1184px] items-center gap-4 px-4 sm:px-6 lg:px-8">
                <Link
                    href={ROUTES.home}
                    aria-label={copy('Frostline OPS home')}
                    className="flex shrink-0 items-center gap-2.5 rounded-lg outline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
                >
                    <span
                        aria-hidden="true"
                        className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                    >
                        <Snowflake className="size-5" />
                    </span>
                    <span className="flex flex-col leading-none">
                        <span className="text-base font-semibold tracking-tight">
                            {copy('Frostline')}
                            {copy(' ')}
                            <span className="text-glacier-600 dark:text-glacier-400">
                                {copy('OPS')}
                            </span>
                        </span>
                    </span>
                </Link>

                <nav
                    aria-label={copy('Main')}
                    className="ml-auto hidden lg:block"
                >
                    <ul className="flex items-center gap-1">
                        {marketingNav.map((item) => {
                            const active = route === item.to;
                            return (
                                <li key={item.to}>
                                    <Link
                                        href={item.to}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                            active
                                                ? 'text-foreground'
                                                : 'text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {copy(item.label)}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                <div className="ml-auto flex items-center gap-1.5 lg:ml-0">
                    <LocaleTabs className="hidden sm:inline-flex" />
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={copy('Toggle color theme')}
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        <Moon
                            className="size-5 dark:hidden"
                            aria-hidden="true"
                        />
                        <Sun
                            className="hidden size-5 dark:block"
                            aria-hidden="true"
                        />
                    </Button>

                    <Button
                        variant="ghost"
                        className="hidden sm:inline-flex"
                        asChild
                    >
                        <Link href={login()}>{copy('Sign in')}</Link>
                    </Button>

                    <Button asChild className="hidden sm:inline-flex">
                        <Link href={ROUTES.contact}>
                            {copy('Request a trial')}
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>

                    <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={copy('Open navigation menu')}
                                className="lg:hidden"
                            >
                                <Menu className="size-5" aria-hidden="true" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="right"
                            className="frostline-public w-72 gap-0 bg-card p-0"
                            data-theme={resolvedAppearance}
                        >
                            <SheetHeader className="space-y-1 border-b border-border p-4 text-left">
                                <SheetTitle className="flex items-center gap-2 text-base font-semibold tracking-tight">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-7 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                                    >
                                        <Snowflake className="size-4" />
                                    </span>
                                    {copy('Frostline OPS')}
                                </SheetTitle>
                                <SheetDescription className="text-sm text-muted-foreground">
                                    {copy('Snow operations')}
                                </SheetDescription>
                            </SheetHeader>
                            <nav aria-label={copy('Mobile')} className="p-3">
                                <LocaleTabs className="mb-4" />
                                <ul className="flex flex-col gap-0.5">
                                    {marketingNav.map((item) => (
                                        <li key={item.to}>
                                            <Link
                                                href={item.to}
                                                onClick={() =>
                                                    setMenuOpen(false)
                                                }
                                                className={cn(
                                                    'block rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                                    route === item.to
                                                        ? 'bg-accent text-accent-foreground'
                                                        : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                                                )}
                                            >
                                                {copy(item.label)}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-4 flex flex-col gap-2 border-t border-border pt-4">
                                    <Button asChild>
                                        <Link
                                            href={ROUTES.contact}
                                            onClick={() => setMenuOpen(false)}
                                        >
                                            {copy('Request a trial')}
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={login()}
                                            onClick={() => setMenuOpen(false)}
                                        >
                                            {copy('Sign in')}
                                        </Link>
                                    </Button>
                                </div>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
