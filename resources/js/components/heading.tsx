export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    // Rendered as a plain block, not a heading element: the app shell
    // already exposes each page's title as a screen-reader-only h1, so a
    // second semantic heading here would duplicate it for assistive tech.
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <div
                role="heading"
                aria-level={2}
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                }
            >
                {title}
            </div>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </header>
    );
}
