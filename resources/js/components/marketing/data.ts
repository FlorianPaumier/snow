import {
    Camera,
    CloudSnow,
    Receipt,
    Route,
    Truck,
    Zap,
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

/* ── Hero stats ─────────────────────────────────────────────────── */
export const heroStats = [
    { value: '1,200+', label: 'crews dispatched' },
    { value: '4.2M', label: 'properties cleared' },
    { value: '11 min', label: 'median dispatch time' },
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
        icon: Zap,
        title: 'Trigger-based auto-dispatch',
        copy: 'Stop running the 3 a.m. phone tree. Set a trigger depth per property and crews are dispatched, routed, and rolling the moment snow crosses the line.',
    },
    {
        icon: Route,
        title: 'Smart route optimization',
        copy: 'Routes shouldn\u2019t live in one dispatcher\u2019s head. Multi-stop sequencing respects priority, fuel, and shift hours — and re-routes in seconds when a truck goes down mid-storm.',
    },
    {
        icon: CloudSnow,
        title: 'Storm mode',
        copy: 'The night every route changes at once needs one switch, not a whiteboard. Priority sorting, escalation alerts, and a dashboard built for 3 a.m. decisions.',
    },
    {
        icon: Truck,
        title: 'Driver-first mobile app',
        copy: 'Gloves, glare, and dead zones shouldn\u2019t stop work. Offline-first with giant touch targets — stops, photos, and ETAs in three taps or fewer.',
    },
    {
        icon: Camera,
        title: 'Proof of service',
        copy: 'When a property manager asks whether lot B was cleared, the answer should be a photo — not a phone call. Geo-stamped, timestamped proof on every pass.',
    },
    {
        icon: Receipt,
        title: 'Billing that keeps up',
        copy: 'Storms end at dawn; invoices shouldn\u2019t take a week. Per-storm, seasonal, or per-push billing compiled within 24 hours of the final pass. NET-14 as standard.',
    },
];

/* ── Pricing ────────────────────────────────────────────────────── */
export interface PricingTier {
    name: string;
    tagline: string;
    monthly: number;
    annual: number; // per year, 2 months free
    featured?: boolean;
    limits: string;
    cta: string;
    ctaTo: string;
    features: string[];
}

export const pricingTiers: PricingTier[] = [
    {
        name: 'Starter',
        tagline: 'For solo operators proving the model',
        monthly: 99,
        annual: 990,
        limits: 'Up to 25 properties · 3 crews · 2 users',
        cta: 'Start free trial',
        ctaTo: ROUTES.contact,
        features: [
            'Auto-dispatch at trigger depth',
            'Driver app for iOS & Android',
            'Live storm dashboard',
            'Photo proof capture',
            'Email support',
        ],
    },
    {
        name: 'Pro',
        tagline: 'For growing companies running multiple crews',
        monthly: 249,
        annual: 2490,
        featured: true,
        limits: 'Up to 150 properties · 10 crews · 15 users',
        cta: 'Start free trial',
        ctaTo: ROUTES.contact,
        features: [
            'Everything in Starter',
            'Route optimization',
            'Seasonal & per-storm billing',
            'Client portal & notifications',
            'Weather intelligence feeds',
            'Priority support',
        ],
    },
    {
        name: 'Fleet',
        tagline: 'For municipalities and multi-region operators',
        monthly: 599,
        annual: 5990,
        limits: 'Unlimited properties · unlimited crews · unlimited users',
        cta: 'Talk to sales',
        ctaTo: ROUTES.contact,
        features: [
            'Everything in Pro',
            'Multi-region operations',
            'API access & webhooks',
            'SSO & audit logs',
            'Dedicated success manager',
            '99.9% uptime SLA',
        ],
    },
];

/* ── Home FAQ ───────────────────────────────────────────────────── */
export const homeFaqs = [
    {
        q: 'How does auto-dispatch decide when to send crews?',
        a: 'Every property carries a trigger depth — 2.5 cm for priority-1 sites, 5 cm for standard contracts. Weather feeds and pavement sensors watch each site; the moment accumulation crosses the line, the closest crew is dispatched with an optimized route. You keep full override control from the dashboard.',
    },
    {
        q: 'Does the driver app work without cell coverage?',
        a: 'Yes. The app is offline-first: routes, stop details, and photo capture all work in dead zones. Everything syncs the moment signal returns, with timestamps preserved from the field — never from the upload.',
    },
    {
        q: 'Can I import my existing property list?',
        a: 'Bring a CSV from any system — spreadsheets, property management software, or lists you already keep. The importer maps addresses, surface types, and trigger depths in one pass, and guided setup walks you through it.',
    },
    {
        q: 'How does billing handle seasonal contracts versus per-storm?',
        a: 'Both, simultaneously. Seasonal contracts bill on their schedule; per-storm and per-push properties compile automatically from completed jobs — photos, timestamps, and salt usage included. Invoices go out within 24 hours of the final pass.',
    },
    {
        q: 'What hardware do crews need?',
        a: 'Any modern smartphone — nothing else. The app is built for gloves, glare, and cold mornings. If you run mounted tablets, the driver app scales up to those too.',
    },
    {
        q: 'Is there a contract, or can I cancel anytime?',
        a: 'Monthly plans cancel anytime. Annual plans save you two months, and every plan includes a seasonal pause that keeps your data warm over the summer for a nominal fee.',
    },
];

/* ── Pricing FAQ ────────────────────────────────────────────────── */
export const pricingFaqs = [
    {
        q: 'What happens if I exceed my property limit mid-season?',
        a: 'Nothing breaks. You can exceed the limit for the rest of the billing cycle, and we\u2019ll invite you to the right tier when it renews — prorated, never back-charged.',
    },
    {
        q: 'Do you offer off-season pricing?',
        a: 'Yes — the seasonal pause is $19/month from May through September. Your properties, routes, and history stay intact so October setup takes minutes.',
    },
    {
        q: 'Is onboarding included?',
        a: 'On every plan. Pro and Fleet get guided onboarding: we help you import your properties, configure triggers, and run your first routed event. Starter includes self-serve setup with full documentation.',
    },
    {
        q: 'Can I pay annually?',
        a: 'Annual billing takes two months off the price — that\u2019s the toggle at the top of the plans. Municipal customers can request net-30 invoicing and a signed SLA.',
    },
];

/* ── Company stats (about + home band) ──────────────────────────── */
export const companyStats = [
    { value: '2021', label: 'founded' },
    { value: '1,200+', label: 'crews dispatched' },
    { value: '11 min', label: 'median dispatch time' },
    { value: '4.2M', label: 'properties cleared' },
] as const;
