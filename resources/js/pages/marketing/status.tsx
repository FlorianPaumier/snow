import { MarketingLayout } from '@/components/marketing/marketing-layout';
import {
    CTABand,
    MarketingSection,
    PageHero,
} from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { usePublicCopy } from '@/hooks/use-public-copy';

type StatusComponent = {
    name: string;
    status: 'operational' | 'degraded';
};

type Props = {
    components: StatusComponent[];
};

export default function Status({ components }: Props) {
    const { copy } = usePublicCopy();

    const allOperational = components.every(
        (component) => component.status === 'operational',
    );

    return (
        <MarketingLayout
            title={copy('System status — Frostline OPS')}
            index={false}
            route={ROUTES.status}
        >
            <PageHero
                eyebrow={copy('Status')}
                title={copy('System status')}
                description={copy(
                    'Live health of the services that keep your back office running.',
                )}
                compact
            />

            <MarketingSection
                eyebrow={copy(
                    allOperational ? 'All systems clearing' : 'Partly degraded',
                )}
                title={copy(
                    allOperational
                        ? 'Everything is operational'
                        : 'Some systems are degraded',
                )}
                description={copy(
                    allOperational
                        ? 'Every component below is answering normally. This page checks the core services in real time on every view.'
                        : 'One or more components are not answering normally. Our team is on it — no action is needed on your side.',
                )}
            >
                <ul className="mx-auto flex max-w-2xl flex-col gap-3">
                    {components.map((component) => {
                        const operational = component.status === 'operational';

                        return (
                            <li
                                key={component.name}
                                className="flex items-center justify-between gap-4 rounded-xl border border-border bg-card px-5 py-4 shadow-frost-xs"
                            >
                                <span className="text-sm font-medium">
                                    {copy(component.name)}
                                </span>
                                <span
                                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm tracking-[0.14em] ${
                                        operational
                                            ? 'border-border bg-success/10 text-success'
                                            : 'border-destructive/30 bg-destructive/10 text-destructive'
                                    }`}
                                >
                                    <span
                                        aria-hidden="true"
                                        className="relative flex size-2"
                                    >
                                        {operational ? (
                                            <span className="relative inline-flex size-2 rounded-full bg-success" />
                                        ) : (
                                            <span className="relative inline-flex size-2 rounded-full bg-destructive" />
                                        )}
                                    </span>
                                    {copy(
                                        operational
                                            ? 'Operational'
                                            : 'Degraded',
                                    )}
                                </span>
                            </li>
                        );
                    })}
                </ul>
            </MarketingSection>

            <CTABand
                title={copy('Run your season on a console you can trust')}
                description={copy(
                    'Clients, contracts, payments, and receipts in one place — with the receipts to prove it.',
                )}
                primaryLabel={copy('Start free trial')}
                primaryTo={ROUTES.contact}
                secondaryLabel={copy('See pricing')}
                secondaryTo={ROUTES.pricing}
            />
        </MarketingLayout>
    );
}
