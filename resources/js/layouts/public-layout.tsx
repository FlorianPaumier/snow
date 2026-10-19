import type { ReactNode } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import '../../css/public-design.css';

export default function PublicLayout({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const { resolvedAppearance } = useAppearance();
    return (
        <div
            className={cn(
                'frostline-public min-h-svh bg-background text-foreground',
                className,
            )}
            data-theme={resolvedAppearance}
        >
            {children}
        </div>
    );
}
