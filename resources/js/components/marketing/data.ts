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

/* ── Client wordmarks (styled text logos) ───────────────────────── */
export const clientLogos = [
    'NorthPeak Property Group',
    'Twin Cities Snow Co.',
    'IronRange Municipal',
    'Lakeshore Facilities',
    'BearPaw Contracting',
    'Silver Birch HOA',
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
        copy: 'Set a depth per property. When snow crosses the line, crews are dispatched, routed, and rolling — no phone calls required.',
    },
    {
        icon: Route,
        title: 'Smart route optimization',
        copy: 'Multi-stop routes that respect priorities, fuel, and shift hours. Re-route mid-storm the moment a truck goes down.',
    },
    {
        icon: CloudSnow,
        title: 'Storm mode',
        copy: 'One switch escalates everything: priority sorting, alerting, and an ember-lit dashboard built for 3 a.m. decisions.',
    },
    {
        icon: Truck,
        title: 'Driver-first mobile app',
        copy: 'Offline-first with giant touch targets that work in gloves. Stops, photos, and ETAs in three taps or fewer.',
    },
    {
        icon: Camera,
        title: 'Proof of service',
        copy: 'Geo-stamped, timestamped photos on every pass. Invoice disputes end before they start.',
    },
    {
        icon: Receipt,
        title: 'Billing that keeps up',
        copy: 'Per-storm, seasonal, or per-push invoicing compiled within 24 hours of the final pass. NET-14 as standard.',
    },
];

/* ── Testimonials ───────────────────────────────────────────────── */
export interface Testimonial {
    quote: string;
    name: string;
    role: string;
    initials: string;
}

export const testimonials: Testimonial[] = [
    {
        quote: 'We used to rebuild routes on a whiteboard at 4 a.m. Frostline arms the crews before the first flake lands.',
        name: 'Elena Marsh',
        role: 'Owner · Twin Cities Snow Co.',
        initials: 'EM',
    },
    {
        quote: 'Proof-of-service photos cut our invoice disputes to zero. Property managers sign off the same day.',
        name: 'Priya Natarajan',
        role: 'Ops Director · NorthPeak Property Group',
        initials: 'PN',
    },
    {
        quote: 'Storm mode is the feature I didn\u2019t know I needed — one toggle and the whole night reorganizes itself.',
        name: 'Sam Okafor',
        role: 'Streets Superintendent · IronRange Municipal',
        initials: 'SO',
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
        a: 'Every property carries a trigger depth — 2.5 cm for priority-1 sites, 5 cm for standard contracts. Our weather feeds and pavement sensors watch each site; the moment accumulation crosses the line, Frostline dispatches the right crew with an optimized route. You keep full override control from the dashboard.',
    },
    {
        q: 'Does the driver app work without cell coverage?',
        a: 'Yes. The app is offline-first: routes, stop details, and photo capture all work in dead zones. Everything syncs the moment signal returns, with timestamps preserved from the field — never from the upload.',
    },
    {
        q: 'Can I import my existing property list?',
        a: 'Bring a CSV from any system — Service Autopilot, Yardbook, or plain spreadsheets. Our importer maps addresses, surface types, and trigger depths in one pass, and your onboarding specialist will run it with you.',
    },
    {
        q: 'How does billing handle seasonal contracts versus per-storm?',
        a: 'Both, simultaneously. Seasonal contracts bill on their schedule; per-storm and per-push properties compile automatically from completed jobs — photos, timestamps, and salt usage included. Invoices go out within 24 hours of the final pass.',
    },
    {
        q: 'What hardware do crews need?',
        a: 'Any modern iPhone or Android phone — nothing else. The app is built for gloves, glare, and -30° mornings. If you run mounted tablets, the driver app scales up to those too.',
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
        a: 'On every plan. Pro and Fleet get white-glove storm-season onboarding: we import your properties, configure triggers, and ride along on your first dispatched storm.',
    },
    {
        q: 'Can I pay annually?',
        a: 'Annual billing takes two months off the price — that\u2019s the toggle at the top of the plans. Municipal customers can request net-30 invoicing and a signed SLA.',
    },
];

/* ── Company stats (about + home band) ──────────────────────────── */
export const companyStats = [
    { value: '2021', label: 'founded in Minneapolis' },
    { value: '1,200+', label: 'crews on Frostline' },
    { value: '38', label: 'states & provinces' },
    { value: '4.2M', label: 'properties cleared' },
] as const;

/* ── Team (about page) ──────────────────────────────────────────── */
export const teamMembers = [
    {
        name: 'Mika Halttunen',
        role: 'Co-founder & CEO',
        fact: 'Third-generation plow operator',
        initials: 'MH',
    },
    {
        name: 'Jordan Reyes',
        role: 'Co-founder & CTO',
        fact: 'Wrote the first route optimizer in a truck cab',
        initials: 'JR',
    },
    {
        name: 'Aisha Bello',
        role: 'Head of Design',
        fact: 'Owns the Frostline design system',
        initials: 'AB',
    },
    {
        name: 'Theo Lindqvist',
        role: 'Head of Meteorology',
        fact: 'Reads radar the way others read mail',
        initials: 'TL',
    },
    {
        name: 'Grace Oduya',
        role: 'Customer Success Lead',
        fact: 'Onboards every fleet personally',
        initials: 'GO',
    },
    {
        name: 'Marcus Webb',
        role: 'Engineering',
        fact: 'Keeps the driver app alive at -30°',
        initials: 'MW',
    },
] as const;
