import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    LifeBuoy,
    Loader2,
    Phone,
} from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import { PageHero } from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

/* ── Types & helpers ─────────────────────────────────────────────── */

interface ContactFormValues {
    name: string;
    email: string;
    company: string;
    fleetSize: string;
    message: string;
}

type ValidatedField = 'name' | 'email' | 'message';
type FieldErrors = Partial<Record<ValidatedField, string>>;

const EMPTY_FORM: ContactFormValues = {
    name: '',
    email: '',
    company: '',
    fleetSize: '',
    message: '',
};

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** Inertia's axios client echoes the XSRF-TOKEN cookie as a header; raw fetch must too. */
function xsrfToken(): string | null {
    const match = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : null;
}

const FLEET_OPTIONS = [
    { value: '1-3', label: '1–3 trucks' },
    { value: '4-10', label: '4–10 trucks' },
    { value: '11-50', label: '11–50 trucks' },
    { value: '50+', label: '50+ trucks' },
] as const;

const QUICK_FACTS = [
    { value: '2h', label: 'median first reply' },
    { value: '24/7', label: 'storm-season support' },
    { value: '38', label: 'states & provinces served' },
] as const;

function validateField(
    field: ValidatedField,
    value: string,
): string | undefined {
    const trimmed = value.trim();
    if (field === 'name') {
        return trimmed.length >= 2
            ? undefined
            : 'Please enter your name — at least 2 characters.';
    }
    if (field === 'email') {
        return EMAIL_PATTERN.test(trimmed)
            ? undefined
            : 'Please enter a valid email address.';
    }
    return trimmed.length >= 10
        ? undefined
        : 'Tell us a little more — at least 10 characters.';
}

/** Map a Laravel 422 response body ({ errors: { field: [messages] } }) to field errors. */
function extractIssues(data: unknown): Partial<Record<string, string>> {
    if (
        typeof data !== 'object' ||
        data === null ||
        !('errors' in data) ||
        typeof (data as { errors?: unknown }).errors !== 'object'
    ) {
        return {};
    }

    const errors = (data as { errors: Record<string, unknown> }).errors;
    const issues: Partial<Record<string, string>> = {};

    for (const [field, messages] of Object.entries(errors)) {
        if (Array.isArray(messages) && typeof messages[0] === 'string') {
            issues[field] = messages[0];
        }
    }

    return issues;
}

/* ── Small presentational helpers ────────────────────────────────── */

function FieldError({ id, message }: { id: string; message?: string }) {
    if (!message) {
        return null;
    }
    return (
        <p
            id={id}
            role="alert"
            className="text-xs font-medium text-destructive"
        >
            {message}
        </p>
    );
}

function ContactInfoCard({
    icon: Icon,
    iconClass,
    title,
    children,
}: {
    icon: LucideIcon;
    iconClass: string;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-xs">
            <div className="flex items-center gap-3">
                <span
                    aria-hidden="true"
                    className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${iconClass}`}
                >
                    <Icon className="size-5" />
                </span>
                <p className="font-medium">{title}</p>
            </div>
            <div className="mt-4 grid gap-1.5">{children}</div>
        </div>
    );
}

/* ── Page ────────────────────────────────────────────────────────── */

export default function Contact() {
    const [form, setForm] = React.useState<ContactFormValues>(EMPTY_FORM);
    const [errors, setErrors] = React.useState<FieldErrors>({});
    const [touched, setTouched] = React.useState<
        Partial<Record<ValidatedField, boolean>>
    >({});
    const [submitting, setSubmitting] = React.useState(false);
    const [submitted, setSubmitted] = React.useState(false);

    const setField = (field: keyof ContactFormValues, value: string) => {
        setForm((prev) => {
            const next = { ...prev };
            next[field] = value;
            return next;
        });
        if (field === 'name' || field === 'email' || field === 'message') {
            if (touched[field]) {
                const error = validateField(field, value);
                setErrors((prev) => {
                    const next = { ...prev };
                    if (error) {
                        next[field] = error;
                    } else {
                        delete next[field];
                    }
                    return next;
                });
            }
        }
    };

    const handleBlur = (field: ValidatedField) => {
        setTouched((prev) => {
            const next = { ...prev };
            next[field] = true;
            return next;
        });
        const error = validateField(field, form[field]);
        setErrors((prev) => {
            const next = { ...prev };
            if (error) {
                next[field] = error;
            } else {
                delete next[field];
            }
            return next;
        });
    };

    const handleReset = () => {
        setSubmitted(false);
        setForm(EMPTY_FORM);
        setErrors({});
        setTouched({});
    };

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const nextErrors: FieldErrors = {
            name: validateField('name', form.name),
            email: validateField('email', form.email),
            message: validateField('message', form.message),
        };
        setTouched({ name: true, email: true, message: true });
        setErrors(nextErrors);
        if (nextErrors.name || nextErrors.email || nextErrors.message) {
            return;
        }

        setSubmitting(true);
        try {
            const token = xsrfToken();
            const response = await fetch(ROUTES.contact, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(token ? { 'X-XSRF-TOKEN': token } : {}),
                },
                body: JSON.stringify({
                    name: form.name.trim(),
                    email: form.email.trim(),
                    company: form.company.trim() || undefined,
                    fleetSize: form.fleetSize || undefined,
                    message: form.message.trim(),
                }),
            });

            if (response.ok) {
                toast('Message received', {
                    description:
                        "We'll reply within two business hours — watch your inbox.",
                });
                setForm(EMPTY_FORM);
                setErrors({});
                setTouched({});
                setSubmitted(true);
                return;
            }

            const data: unknown = await response.json().catch(() => null);
            const issues = extractIssues(data);
            const fieldIssues = issues as FieldErrors;
            if (Object.keys(issues).length > 0) {
                setErrors((prev) => ({ ...prev, ...fieldIssues }));
                toast.error("We couldn't send that", {
                    description:
                        issues.name ??
                        issues.email ??
                        issues.message ??
                        'Please check your details and try again.',
                });
            } else {
                toast.error("We couldn't send that", {
                    description:
                        'Please check your details and try again, or email hello@frostline.ops.',
                });
            }
        } catch {
            toast.error('Something went wrong', {
                description:
                    "Try again or email hello@frostline.ops — your message didn't go out.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <MarketingLayout title="Contact — Frostline OPS" route={ROUTES.contact}>
            <PageHero
                compact
                eyebrow="Contact"
                title="Talk snow with us"
                description="Sales, support, or a story from last night's storm — we answer fast. Median first reply: 2 business hours."
            />

            {/* Form + info column */}
            <section className="py-12 md:py-20">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-8 lg:grid-cols-5">
                        <div className="rounded-2xl border border-border bg-card p-6 shadow-frost-sm md:p-8 lg:col-span-3">
                            <h2 className="text-xl font-semibold tracking-tight">
                                Start your trial
                            </h2>
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                Tell us about your operation — we'll set you up
                                before the next system moves through.
                            </p>

                            {submitted ? (
                                <div className="mt-6">
                                    <Alert className="border-success/25 bg-success/10 text-success">
                                        <CheckCircle2 aria-hidden="true" />
                                        <AlertTitle>
                                            Thanks — you're on the list.
                                        </AlertTitle>
                                        <AlertDescription className="text-success/90">
                                            A Frostline crew member (the desk
                                            kind) will reach out shortly.
                                        </AlertDescription>
                                    </Alert>
                                    <Button
                                        variant="outline"
                                        className="mt-4"
                                        onClick={handleReset}
                                    >
                                        Send another message
                                    </Button>
                                </div>
                            ) : (
                                <form
                                    onSubmit={handleSubmit}
                                    noValidate
                                    className="mt-6 grid gap-5"
                                >
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="contact-name">
                                                Name
                                            </Label>
                                            <Input
                                                id="contact-name"
                                                value={form.name}
                                                onChange={(event) =>
                                                    setField(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                onBlur={() =>
                                                    handleBlur('name')
                                                }
                                                placeholder="Dana Kowalski"
                                                autoComplete="name"
                                                aria-invalid={
                                                    touched.name && errors.name
                                                        ? true
                                                        : undefined
                                                }
                                                aria-describedby={
                                                    touched.name && errors.name
                                                        ? 'contact-name-error'
                                                        : undefined
                                                }
                                            />
                                            {touched.name ? (
                                                <FieldError
                                                    id="contact-name-error"
                                                    message={errors.name}
                                                />
                                            ) : null}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="contact-email">
                                                Work email
                                            </Label>
                                            <Input
                                                id="contact-email"
                                                type="email"
                                                value={form.email}
                                                onChange={(event) =>
                                                    setField(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                                onBlur={() =>
                                                    handleBlur('email')
                                                }
                                                placeholder="dana@yourcompany.com"
                                                autoComplete="email"
                                                aria-invalid={
                                                    touched.email &&
                                                    errors.email
                                                        ? true
                                                        : undefined
                                                }
                                                aria-describedby={
                                                    touched.email &&
                                                    errors.email
                                                        ? 'contact-email-error'
                                                        : undefined
                                                }
                                            />
                                            {touched.email ? (
                                                <FieldError
                                                    id="contact-email-error"
                                                    message={errors.email}
                                                />
                                            ) : null}
                                        </div>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="contact-company">
                                                Company
                                            </Label>
                                            <Input
                                                id="contact-company"
                                                value={form.company}
                                                onChange={(event) =>
                                                    setField(
                                                        'company',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Twin Cities Snow Co."
                                                autoComplete="organization"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="contact-fleet">
                                                Fleet size
                                            </Label>
                                            <Select
                                                value={form.fleetSize}
                                                onValueChange={(value) =>
                                                    setField('fleetSize', value)
                                                }
                                            >
                                                <SelectTrigger
                                                    id="contact-fleet"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="Select fleet size" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {FLEET_OPTIONS.map(
                                                        (option) => (
                                                            <SelectItem
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="contact-message">
                                            What does your season look like?
                                        </Label>
                                        <Textarea
                                            id="contact-message"
                                            rows={5}
                                            value={form.message}
                                            onChange={(event) =>
                                                setField(
                                                    'message',
                                                    event.target.value,
                                                )
                                            }
                                            onBlur={() => handleBlur('message')}
                                            placeholder="Properties under contract, crews, biggest headache on storm nights…"
                                            aria-invalid={
                                                touched.message &&
                                                errors.message
                                                    ? true
                                                    : undefined
                                            }
                                            aria-describedby={
                                                touched.message &&
                                                errors.message
                                                    ? 'contact-message-error'
                                                    : undefined
                                            }
                                        />
                                        {touched.message ? (
                                            <FieldError
                                                id="contact-message-error"
                                                message={errors.message}
                                            />
                                        ) : null}
                                    </div>

                                    <div className="grid gap-3">
                                        <Button
                                            type="submit"
                                            size="lg"
                                            disabled={submitting}
                                            className="w-full sm:w-auto"
                                        >
                                            {submitting ? (
                                                <>
                                                    <Loader2
                                                        aria-hidden="true"
                                                        className="animate-spin"
                                                    />
                                                    Sending…
                                                </>
                                            ) : (
                                                <>
                                                    Start your trial
                                                    <ArrowRight aria-hidden="true" />
                                                </>
                                            )}
                                        </Button>
                                        <p className="font-mono text-[11px] leading-relaxed tracking-[0.08em] text-muted-foreground">
                                            14-day trial · no card · we reply in
                                            ~2 business hours
                                        </p>
                                    </div>
                                </form>
                            )}
                        </div>

                        <div className="flex flex-col gap-4 lg:col-span-2">
                            <div
                                role="img"
                                aria-label="Plow truck clearing a snowy street at night, amber beacon reflecting on fresh snow"
                                className="relative aspect-[16/10] overflow-hidden rounded-2xl border border-border shadow-frost-sm"
                                style={{
                                    backgroundImage:
                                        'url(/images/plow-night.jpg)',
                                    backgroundSize: 'cover',
                                    backgroundPosition: 'center',
                                }}
                            >
                                <div
                                    aria-hidden="true"
                                    className="absolute inset-0 bg-linear-to-t from-black/70 via-black/20 to-transparent"
                                />
                                <div className="absolute bottom-4 left-4">
                                    <p className="flex items-center gap-2 font-semibold text-white">
                                        <span
                                            aria-hidden="true"
                                            className="size-2 animate-pulse-soft rounded-full bg-ember-400 shadow-glow-ember"
                                        />
                                        Storm line open
                                    </p>
                                    <p className="mt-1 font-mono text-xs text-white/80">
                                        24/7 · Nov–Apr
                                    </p>
                                </div>
                            </div>

                            <ContactInfoCard
                                icon={Phone}
                                iconClass="bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400"
                                title="Talk to sales"
                            >
                                <a
                                    href="tel:+16125550147"
                                    className="w-fit font-mono text-sm tracking-tight underline-offset-4 hover:underline"
                                >
                                    (612) 555-0147
                                </a>
                                <a
                                    href="mailto:sales@frostline.ops"
                                    className="w-fit text-sm underline-offset-4 hover:underline"
                                >
                                    sales@frostline.ops
                                </a>
                                <p className="text-xs text-muted-foreground">
                                    Mon–Fri · 7am–6pm CT
                                </p>
                            </ContactInfoCard>

                            <ContactInfoCard
                                icon={LifeBuoy}
                                iconClass="bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400"
                                title="Crew support"
                            >
                                <a
                                    href="mailto:support@frostline.ops"
                                    className="w-fit text-sm underline-offset-4 hover:underline"
                                >
                                    support@frostline.ops
                                </a>
                                <p className="text-sm">
                                    In-app chat · 24/7 storm line
                                </p>
                                <Badge className="mt-1 w-fit border-success/25 bg-success/10 text-success">
                                    Median reply 12 min
                                </Badge>
                            </ContactInfoCard>

                            <ContactInfoCard
                                icon={Building2}
                                iconClass="bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400"
                                title="Headquarters"
                            >
                                <p className="text-sm">
                                    728 Hennepin Ave, Suite 300
                                </p>
                                <p className="text-sm">Minneapolis, MN 55401</p>
                                <p className="font-mono text-xs text-muted-foreground">
                                    44.978° N, 93.265° W
                                </p>
                            </ContactInfoCard>
                        </div>
                    </div>
                </div>
            </section>

            {/* Quick facts */}
            <section className="pb-16 md:pb-24">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <dl className="grid gap-4 sm:grid-cols-3">
                        {QUICK_FACTS.map((fact) => (
                            <div
                                key={fact.label}
                                className="flex flex-col items-center gap-1 rounded-xl border border-border bg-card/60 p-4 text-center"
                            >
                                <dt className="order-2 font-mono text-[11px] font-medium tracking-[0.14em] text-muted-foreground uppercase">
                                    {fact.label}
                                </dt>
                                <dd className="order-1 text-xl font-semibold tabular-nums">
                                    {fact.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>
        </MarketingLayout>
    );
}
