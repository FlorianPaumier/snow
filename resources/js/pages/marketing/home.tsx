import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    Download,
    FileText,
    Snowflake,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import ContractDemo from '@/components/marketing/contract-demo';
import { pricingTiers } from '@/components/marketing/data';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import { ROUTES } from '@/components/marketing/routes';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { usePublicCopy } from '@/hooks/use-public-copy';
import { cn } from '@/lib/utils';
import { register } from '@/routes';
import '../../../css/marketing-home.css';

const questions = [
    {
        q: 'What happens after the trial?',
        a: 'New organizations get 14 days of Pro without a credit card. After that, Free limits apply unless you subscribe. Your existing records remain available.',
    },
    {
        q: 'Can I bring my existing client list?',
        a: 'Yes. Import your clients from a CSV file, or add them individually. You can also export your season’s payment records for your accountant.',
    },
    {
        q: 'Can I send contracts to my clients?',
        a: 'Yes. Generate a contract PDF and queue an email from the contract detail page, or select contracts and send them in a batch. Each client needs a valid email address.',
    },
    {
        q: 'Does it work in French?',
        a: 'Yes. The app supports English and French, including validation messages and emails. Each user can switch languages.',
    },
    {
        q: 'How do I get started?',
        a: 'Request a trial through our contact page. Registration requires an invitation code. If you already have one, you can create your organization and begin your trial.',
    },
];

export default function Home() {
    const { copy, locale } = usePublicCopy();

    const [driveway, setDriveway] = useState(450);
    const { appUrl } = usePage().props;
    return (
        <MarketingLayout
            title={copy(
                'Frostline OPS — Snow Removal Contract & Payment Software',
            )}
            route={ROUTES.home}
            structuredData={{
                '@context': 'https://schema.org',
                '@type': 'SoftwareApplication',
                name: 'Frostline OPS',
                applicationCategory: 'BusinessApplication',
                url: new URL('/', `${appUrl}/`).toString(),
            }}
        >
            <div className="landing-next">
                <section className="landing-hero">
                    <div className="preview-container grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                        <div>
                            <h1 className="landing-display">
                                {copy('Less paperwork.')}
                                <br />
                                <span className="text-primary">
                                    {copy('A smoother season.')}
                                </span>
                            </h1>
                            <p className="mt-6 max-w-lg text-lg leading-relaxed text-muted-foreground">
                                {copy(
                                    'Run your snow-removal business with clients, seasonal contracts and payments in one clear place.',
                                )}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild size="lg">
                                    <Link
                                        href={ROUTES.contact}
                                        data-test="landing-trial"
                                    >
                                        {copy('Request a free trial')}
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" size="lg">
                                    <a href="#contract-demo">
                                        {copy('Try the contract demo')}
                                    </a>
                                </Button>
                            </div>
                            <ul className="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground">
                                {[
                                    '14 days of Pro',
                                    'No card required',
                                    'By invitation',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-center gap-2"
                                    >
                                        <Check
                                            aria-hidden="true"
                                            className="size-4 text-primary"
                                        />
                                        {copy(item)}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div id="contract-demo" className="scroll-mt-24">
                            <ContractDemo
                                driveway={driveway}
                                onPriceChange={setDriveway}
                            />
                        </div>
                    </div>
                    <div className="preview-container mt-14 md:mt-20">
                        <div className="landing-note flex flex-wrap items-center justify-between gap-5">
                            <p className="font-medium">
                                {copy('Made for the rhythm of snow season.')}
                            </p>
                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                <CheckCircle2
                                    aria-hidden="true"
                                    className="size-4 text-primary"
                                />
                                {copy(
                                    'English & French. Your records, your season.',
                                )}
                            </p>
                        </div>
                    </div>
                </section>

                <section id="workflow" className="landing-section">
                    <div className="preview-container">
                        <div className="max-w-2xl">
                            <h2 className="landing-title">
                                {copy('From the first contract')}
                                <br />
                                {copy('to the final payment.')}
                            </h2>
                            <p className="preview-intro text-lg">
                                {copy(
                                    'A simple flow for the work behind the work. Prepare in the fall, keep up through winter, and close the season with clear records.',
                                )}
                            </p>
                        </div>
                        <div className="mt-10 grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                            <div className="landing-client-scene">
                                <div className="flex items-center justify-between gap-4">
                                    <p className="font-semibold">
                                        {copy('Your client book')}
                                    </p>
                                    <span className="text-sm text-muted-foreground">
                                        {copy('Demo records')}
                                    </span>
                                </div>
                                {[
                                    {
                                        name: 'Camille Tremblay',
                                        note: 'Driveway & walkway',
                                        initials: 'CT',
                                    },
                                    {
                                        name: 'Alex Martin',
                                        note: 'Seasonal snow removal',
                                        initials: 'AM',
                                    },
                                    {
                                        name: 'Les Pins Demo',
                                        note: 'Parking & salting',
                                        initials: 'LP',
                                    },
                                ].map((client) => (
                                    <div
                                        key={client.name}
                                        className="landing-client-row"
                                    >
                                        <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">
                                            {copy(client.initials)}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">
                                                {copy(client.name)}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {copy(client.note)}
                                            </p>
                                        </div>
                                        <CheckCircle2
                                            aria-label={copy(
                                                'Included in the example',
                                            )}
                                            className="size-5 shrink-0 text-primary"
                                        />
                                    </div>
                                ))}
                                <div className="mt-5 flex items-center gap-2 text-sm text-muted-foreground">
                                    <Users
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    {copy(
                                        'Client details stay with the contract.',
                                    )}
                                </div>
                            </div>
                            <div className="space-y-8">
                                <div className="flex gap-4">
                                    <span className="preview-icon">
                                        <Users aria-hidden="true" />
                                    </span>
                                    <div>
                                        <h3 className="text-xl font-semibold">
                                            {copy(
                                                'Bring your clients together.',
                                            )}
                                        </h3>
                                        <p className="mt-2 leading-relaxed text-muted-foreground">
                                            {copy(
                                                'Import a CSV or add clients as you go. Find contact details, addresses and notes without searching through spreadsheets.',
                                            )}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-4">
                                    <span className="preview-icon">
                                        <FileText aria-hidden="true" />
                                    </span>
                                    <div>
                                        <h3 className="text-xl font-semibold">
                                            {copy('Make every contract clear.')}
                                        </h3>
                                        <p className="mt-2 leading-relaxed text-muted-foreground">
                                            {copy(
                                                'Choose your services, adjust each property’s price, and set the payment schedule. Generate a PDF and send it to your client.',
                                            )}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-4">
                                    <span className="preview-icon">
                                        <CheckCircle2 aria-hidden="true" />
                                    </span>
                                    <div>
                                        <h3 className="text-xl font-semibold">
                                            {copy('Follow the money.')}
                                        </h3>
                                        <p className="mt-2 leading-relaxed text-muted-foreground">
                                            {copy(
                                                'Record deposits and installments, see what remains outstanding, and keep payment dates and methods with the contract.',
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="landing-section bg-secondary/50">
                    <div className="preview-container grid items-center gap-10 lg:grid-cols-2 lg:gap-20">
                        <div>
                            <h2 className="landing-title">
                                {copy('Close the season.')}
                                <br />
                                {copy('Keep the full picture.')}
                            </h2>
                            <p className="mt-5 max-w-lg text-lg leading-relaxed text-muted-foreground">
                                {copy(
                                    'Your payment history should be useful after the snow melts. Download invoices and receipts, and export the season’s payments for your accountant.',
                                )}
                            </p>
                            <Link
                                href={ROUTES.features}
                                className="mt-6 inline-flex items-center gap-2 font-semibold text-primary"
                            >
                                {copy('Explore all features')}
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                        </div>
                        <div className="landing-record-scene">
                            <div className="flex items-center gap-3">
                                <span className="preview-icon">
                                    <Download aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 className="font-semibold">
                                        {copy('Season records')}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {copy('Everything in its place.')}
                                    </p>
                                </div>
                            </div>
                            <ul className="mt-7 space-y-5">
                                {[
                                    'Itemized contracts in PDF',
                                    'Invoices & payment receipts',
                                    'Season payment export in CSV',
                                ].map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-center justify-between gap-4"
                                    >
                                        <span>{copy(item)}</span>
                                        <Check
                                            aria-hidden="true"
                                            className="size-5 shrink-0 text-primary"
                                        />
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-7 rounded-xl bg-secondary p-4 text-sm leading-relaxed text-secondary-foreground">
                                {copy(
                                    'Clear records for the office. Less retyping at season’s end.',
                                )}
                            </p>
                        </div>
                    </div>
                </section>

                <section id="pricing" className="landing-section">
                    <div className="preview-container">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="landing-title">
                                {copy('Room to start.')}
                                <br />
                                {copy('Space to grow.')}
                            </h2>
                            <p className="preview-intro text-lg">
                                {copy(
                                    'Begin with 14 days of Pro. Then stay on Free or choose the annual plan that fits your business.',
                                )}
                            </p>
                        </div>
                        <div className="mx-auto mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
                            {pricingTiers.map((tier) => (
                                <div
                                    key={tier.name}
                                    className={cn(
                                        'landing-plan flex flex-col',
                                        tier.featured &&
                                            'landing-plan-featured',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="text-2xl font-semibold">
                                            {copy(tier.name)}
                                        </h3>
                                        {tier.featured && (
                                            <span className="rounded-full bg-secondary px-3 py-1 text-sm font-medium text-secondary-foreground">
                                                {copy('For your growing team')}
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-5 text-4xl font-semibold tracking-tight">
                                        {new Intl.NumberFormat(
                                            locale === 'fr' ? 'fr-CA' : 'en-CA',
                                            {
                                                style: 'currency',
                                                currency: 'CAD',
                                                maximumFractionDigits: 0,
                                            },
                                        ).format(tier.annual ?? 0)}
                                        <span className="ml-2 text-base font-normal text-muted-foreground">
                                            {copy(
                                                tier.annual === null
                                                    ? 'forever'
                                                    : '/ year',
                                            )}
                                        </span>
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {copy(
                                            tier.annual === null
                                                ? 'After your trial, within Free limits.'
                                                : 'Canadian dollars, before tax.',
                                        )}
                                    </p>
                                    <p className="mt-5 font-medium">
                                        {copy(tier.limits)}
                                    </p>
                                    <ul className="my-7 space-y-3">
                                        {tier.features.map((feature) => (
                                            <li
                                                key={feature}
                                                className="flex items-start gap-3 text-sm"
                                            >
                                                <Check
                                                    aria-hidden="true"
                                                    className="mt-0.5 size-4 shrink-0 text-primary"
                                                />
                                                {copy(feature)}
                                            </li>
                                        ))}
                                    </ul>
                                    <Button
                                        asChild
                                        size="lg"
                                        variant={
                                            tier.featured
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="mt-auto w-full"
                                    >
                                        <Link href={ROUTES.contact}>
                                            {copy('Request a trial')}
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </div>
                            ))}
                        </div>
                        <p className="mt-7 text-center text-sm text-muted-foreground">
                            {copy(
                                'No automatic paid subscription. Free limits apply after the trial unless you subscribe.',
                            )}
                        </p>
                    </div>
                </section>

                <section id="questions" className="landing-section bg-card">
                    <div className="preview-container grid gap-8 lg:grid-cols-5 lg:gap-20">
                        <div className="lg:col-span-2">
                            <h2 className="landing-title">
                                {copy('A few things')}
                                <br />
                                {copy('you might wonder.')}
                            </h2>
                            <p className="preview-intro">
                                {copy('Have a different question?')}
                                {copy(' ')}
                                <Link
                                    href={ROUTES.contact}
                                    className="font-medium text-primary underline underline-offset-4"
                                >
                                    {copy('Let’s talk.')}
                                </Link>
                            </p>
                        </div>
                        <Accordion
                            type="single"
                            collapsible
                            className="lg:col-span-3"
                        >
                            {questions.map((question, index) => (
                                <AccordionItem
                                    key={question.q}
                                    value={'question-' + index}
                                >
                                    <AccordionTrigger className="text-base">
                                        {copy(question.q)}
                                    </AccordionTrigger>
                                    <AccordionContent className="text-base leading-relaxed text-muted-foreground">
                                        {copy(question.a)}
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>
                    </div>
                </section>

                <section className="landing-section">
                    <div className="preview-container">
                        <div className="landing-close text-center">
                            <Snowflake
                                aria-hidden="true"
                                className="mx-auto mb-6 size-9 text-primary"
                            />
                            <h2 className="landing-title">
                                {copy('Ready for a smoother season?')}
                            </h2>
                            <p className="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                                {copy(
                                    'Get your clients, contracts and payments ready for winter. We’ll help you take the first step.',
                                )}
                            </p>
                            <Button asChild size="lg" className="mt-7">
                                <Link href={ROUTES.contact}>
                                    {copy('Let’s get you started')}
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                            <p className="mt-5 text-sm text-muted-foreground">
                                {copy('Already have an invitation?')}
                                {copy(' ')}
                                <Link
                                    href={register()}
                                    className="font-medium text-primary underline underline-offset-4"
                                >
                                    {copy('Create your account.')}
                                </Link>
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </MarketingLayout>
    );
}
