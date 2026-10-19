import { FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { usePublicCopy } from '@/hooks/use-public-copy';

const money = (value: number, locale: string) =>
    new Intl.NumberFormat(locale === 'fr' ? 'fr-CA' : 'en-CA', {
        style: 'currency',
        currency: 'CAD',
        maximumFractionDigits: 0,
    }).format(value);

export default function ContractDemo({
    driveway,
    onPriceChange,
}: {
    driveway: number;
    onPriceChange: (price: number) => void;
}) {
    const { copy, locale } = usePublicCopy();

    return (
        <div
            className="preview-product"
            role="group"
            aria-label={copy('Interactive contract example')}
        >
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="preview-icon">
                        <FileText aria-hidden="true" />
                    </span>
                    <div>
                        <p className="font-semibold">
                            {copy('Camille’s winter contract')}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {copy('2026–2027 · Fictional example')}
                        </p>
                    </div>
                </div>
                <Badge variant="secondary">{copy('Draft')}</Badge>
            </div>
            <div className="mt-8 space-y-5">
                <div className="flex items-center justify-between gap-4">
                    <Label htmlFor="driveway-price">
                        {copy('Driveway snow removal')}
                    </Label>
                    <output
                        htmlFor="driveway-price"
                        className="font-semibold tabular-nums"
                    >
                        {money(driveway, locale)}
                    </output>
                </div>
                <input
                    id="driveway-price"
                    aria-label={copy('Driveway price')}
                    type="range"
                    min="250"
                    max="850"
                    step="25"
                    value={driveway}
                    onChange={(event) =>
                        onPriceChange(Number(event.target.value))
                    }
                    className="w-full accent-primary"
                />
                <div className="flex justify-between text-sm">
                    <span>{copy('Walkway & salting')}</span>
                    <span className="tabular-nums">{money(150, locale)}</span>
                </div>
            </div>
            <div className="preview-total mt-7 flex items-center justify-between gap-4">
                <span>{copy('Season total')}</span>
                <output
                    data-test="season-total"
                    className="text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {money(driveway + 150, locale)}
                </output>
            </div>
            <p className="mt-4 text-sm text-muted-foreground">
                {copy('Adjust the service price. The total updates instantly.')}
            </p>
        </div>
    );
}
