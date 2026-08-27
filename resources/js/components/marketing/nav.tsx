import { Link } from '@inertiajs/react';
import { ArrowRight, Menu, Moon, Snowflake, Sun } from 'lucide-react';
import * as React from 'react';
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
import { cn } from '@/lib/utils';

export function MarketingNav({ route }: { route: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [menuOpen, setMenuOpen] = React.useState(false);

    return (
        <header className="sticky top-0 z-50 w-full border-b border-border/80 bg-background/85 backdrop-blur-md supports-[backdrop-filter]:bg-background/70">
            <div className="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <Link
                    href={ROUTES.home}
                    aria-label="Frostline OPS home"
                    className="flex shrink-0 items-center gap-2.5 rounded-lg outline-offset-4 focus-visible:outline-2 focus-visible:outline-ring"
                >
                    <span
                        aria-hidden="true"
                        className="flex size-9 items-center justify-center rounded-xl bg-linear-to-br from-glacier-600 to-glacier-400 text-white shadow-frost-sm dark:from-glacier-500 dark:to-glacier-300 dark:text-glacier-950"
                    >
                        <Snowflake className="size-5" />
                    </span>
                    <span className="flex flex-col leading-none">
                        <span className="text-base font-semibold tracking-tight">
                            Frostline{' '}
                            <span className="text-glacier-600 dark:text-glacier-400">
                                OPS
                            </span>
                        </span>
                        <span className="mt-1 font-mono text-[10px] tracking-[0.18em] text-muted-foreground uppercase">
                            Snow operations
                        </span>
                    </span>
                </Link>

                <nav aria-label="Main" className="ml-auto hidden lg:block">
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
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                <div className="ml-auto flex items-center gap-1.5 lg:ml-0">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Toggle color theme"
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
                        <Link href="/login">Sign in</Link>
                    </Button>

                    <Button asChild className="hidden sm:inline-flex">
                        <Link href={ROUTES.contact}>
                            Get started
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>

                    <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Open navigation menu"
                                className="lg:hidden"
                            >
                                <Menu className="size-5" aria-hidden="true" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="w-72 gap-0 p-0">
                            <SheetHeader className="space-y-1 border-b border-border p-4 text-left">
                                <SheetTitle className="flex items-center gap-2 text-base font-semibold tracking-tight">
                                    <span
                                        aria-hidden="true"
                                        className="flex size-7 items-center justify-center rounded-lg bg-linear-to-br from-glacier-600 to-glacier-400 text-white dark:from-glacier-500 dark:to-glacier-300 dark:text-glacier-950"
                                    >
                                        <Snowflake className="size-4" />
                                    </span>
                                    Frostline OPS
                                </SheetTitle>
                                <SheetDescription className="font-mono text-[10px] tracking-[0.18em] text-muted-foreground uppercase">
                                    Snow operations
                                </SheetDescription>
                            </SheetHeader>
                            <nav aria-label="Mobile" className="p-3">
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
                                                {item.label}
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
                                            Get started
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link
                                            href="/login"
                                            onClick={() => setMenuOpen(false)}
                                        >
                                            Sign in
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
