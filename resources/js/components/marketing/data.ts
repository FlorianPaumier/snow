import {
    Building2,
    CreditCard,
    FileSpreadsheet,
    LineChart,
    Mail,
    type LucideIcon,
} from 'lucide-react';
import { ROUTES } from '@/components/marketing/routes';

/* ── Primary navigation ─────────────────────────────────────────── */
export const marketingNav: { to: string; label: string }[] = [
    { to: ROUTES.features, label: 'Features' },
    { to: ROUTES.pricing, label: 'Pricing' },
    { to: ROUTES.about, label: 'About' },
    { to: ROUTES.contact, label: 'Contact' },
];

/* ── Hero facts ──────────────────────────────────────────────────── */
/* Factual, checkable product facts — no invented traction numbers. */
export const heroFacts = [
    { value: '14-day', label: 'free trial, no card' },
    { value: 'EN/FR', label: 'bilingual interface' },
    { value: '2 themes', label: 'light & dark' },
] as const;

/* ── Client categories (industries served) ──────────────────────── */
export const clientLogos = [
    'Property Management',
    'Municipal Services',
    'Facilities Management',
    'Commercial Contracting',
    'HOA Management',
    'Landscape & Grounds',
] as const;

/* ── Core product features (home grid) ──────────────────────────── */
export interface MarketingFeature {
    icon: LucideIcon;
    title: string;
    copy: string;
}

export const homeFeatures: MarketingFeature[] = [
    {
        icon: Building2,
        title: 'Client directory',
        copy: 'Every property in one searchable place — commercial or residential, with billing and postal addresses, phone numbers, email, and notes. Stop keeping your client book in one dispatcher\u2019s memory.',
    },
    {
        icon: FileSpreadsheet,
        title: 'Seasonal contracts',
        copy: 'One contract per client per season, with itemized service lines — driveways, walkways, salting, whatever you sell. The price of each service is written down, not remembered.',
    },
    {
        icon: CreditCard,
        title: 'Payment tracking',
        copy: 'Deposit, installments, balance — each contract\u2019s payment schedule tracked slot by slot, with the method and date recorded when you mark it paid. At a glance: who paid, who hasn\u2019t.',
    },
    {
        icon: Mail,
        title: 'Invoices & receipts',
        copy: 'Print or email an invoice or payment receipt straight from the contract. No word-processor templates, no retyping amounts — the numbers are already in the system.',
    },
    {
        icon: FileSpreadsheet,
        title: 'Season CSV export',
        copy: 'Hand your accountant the season in one click: every installment with its client, contract, amount, method and payment date, as a CSV that opens in any spreadsheet or bookkeeping tool. Your records stay yours — no lock-in.',
    },
    {
        icon: LineChart,
        title: 'Season dashboard',
        copy: 'Revenue and client growth, charted as the season unfolds. See where the money actually is before the season-end scramble, not after.',
    },
];

/* ── Pricing ────────────────────────────────────────────────────── */
/* Limits mirror config/plans.php exactly: Free 1 user/15 clients,
   Pro unlimited. Prices are CAD, tax-exclusive (Stripe Tax adds
   GST/QST at checkout). Snow removal only bills for part of the
   year, so Pro is a single annual payment — like the other
   pre-season costs. Features must match what ships — verify every
   item against the app before this site goes live. */
export interface PricingTier {
    name: string;
    tagline: string;
    annual: number | null; // per year
    featured?: boolean;
    limits: string;
    cta: string;
    ctaTo: string;
    features: string[];
}

export const pricingTiers: PricingTier[] = [
    {
        name: 'Free',
        tagline: 'For solo operators getting organized',
        annual: null,
        limits: 'Up to 15 clients · 1 user',
        cta: 'Start for free',
        ctaTo: ROUTES.register,
        features: [
            'Client directory & contracts',
            'Payment tracking with mark-paid',
            'Printable invoices & receipts',
            'Season CSV export',
            'No credit card required',
        ],
    },
    {
        name: 'Pro',
        tagline: 'For growing companies with a real client book',
        annual: 300,
        featured: true,
        limits: 'Unlimited clients · unlimited users',
        cta: 'Start 14-day trial',
        ctaTo: ROUTES.register,
        features: [
            'Everything in Free',
            'Invite your whole team',
            'Revenue & client-growth dashboard',
            'Automated payment reminders',
            'Weekly payment digest',
            'Email support',
        ],
    },
];

/* ── Home FAQ ───────────────────────────────────────────────────── */
export const homeFaqs = [
    {
        q: 'What exactly does Frostline OPS do?',
        a: 'It is the back office for a snow-removal business: your client directory, seasonal contracts with itemized services, and payment tracking — deposit, installments, balance — with dashboards, invoices, and receipts on top. It deliberately does not try to run your trucks or your crews.',
    },
    {
        q: 'Can I get my data out for my accountant?',
        a: 'Yes — one click exports a season as CSV: every installment with its client, contract, amount, payment method and paid date. It opens in any spreadsheet or bookkeeping tool, and your records are never locked in.',
    },
    {
        q: 'How do contracts and payments work?',
        a: 'Each client gets one contract per season, made of itemized service lines. Payments are tracked slot by slot — deposit, first and second installments, balance — and marked paid with the method and date as they come in.',
    },
    {
        q: 'Can I send invoices and receipts?',
        a: 'Yes. Invoices and payment receipts can be printed or emailed directly from the contract — the amounts come straight from the services and payments already recorded, so there is nothing to retype.',
    },
    {
        q: 'What languages does the app support?',
        a: 'English and French, switchable per user. The interface, validation messages, and emails are all bilingual — built for how snow contracts are actually sold in snow country.',
    },
    {
        q: 'Is there a contract, or can I cancel anytime?',
        a: 'Pro is billed once a year — one payment covers the whole season, with no monthly charges to track. Every plan starts with a 14-day free trial, no card required, and you can cancel at any time from your billing settings.',
    },
];

/* ── Pricing FAQ ────────────────────────────────────────────────── */
export const pricingFaqs = [
    {
        q: 'What happens if I exceed the Free plan caps?',
        a: 'Nothing breaks. Your existing clients, contracts, and payment history all stay open — you just can\u2019t add the 16th client or a second user until you upgrade to Pro. No surprises, no lost data.',
    },
    {
        q: 'What counts as a user?',
        a: 'Anyone on your team who signs in — office staff, owners, dispatchers. Client records don\u2019t count, and your clients never need an account: Frostline OPS is for your side of the desk.',
    },
    {
        q: 'Is onboarding included?',
        a: 'Yes. We help you get your client list into the app, set up your first season\u2019s contracts, and get your payment schedules in place.',
    },
    {
        q: 'Why is Pro billed annually?',
        a: 'Snow removal is seasonal — your work and your revenue come in one part of the year. Pro is one annual payment (CA$300, tax-exclusive) that covers the full season, so your software is a single predictable line item alongside your other pre-season costs.',
    },
    {
        q: 'Do prices include taxes?',
        a: 'No — prices are in Canadian dollars before tax. GST/QST (or your local tax) is calculated automatically at checkout based on your billing address.',
    },
];

/* ── Company facts (about + home band) ──────────────────────────── */
/* Facts about the product, not invented traction stats. */
export const companyFacts = [
    { value: '14-day', label: 'free trial, no card' },
    { value: 'EN/FR', label: 'bilingual interface' },
    { value: 'CSV', label: 'season export' },
    { value: 'AA', label: 'accessibility standard' },
] as const;
