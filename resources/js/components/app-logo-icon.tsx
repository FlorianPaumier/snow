import { Snowflake } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'flex aspect-square size-9 items-center justify-center rounded-xl bg-linear-to-br from-glacier-600 to-glacier-400 text-white shadow-frost-sm dark:from-glacier-500 dark:to-glacier-300 dark:text-glacier-950',
                className,
            )}
        >
            <Snowflake className="size-5" />
        </span>
    );
}
