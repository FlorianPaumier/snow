import { usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ArrowRight, LifeBuoy, Loader2, MessageSquare } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';
import { MarketingLayout } from '@/components/marketing/marketing-layout';
import { PageHero } from '@/components/marketing/page-hero';
import { ROUTES } from '@/components/marketing/routes';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { usePublicCopy } from '@/hooks/use-public-copy';

/* ── Types & helpers ─────────────────────────────────────────────── */

interface ContactFormValues {
    name: string;
    email: string;
    company: string;
    message: string;
}

type ValidatedField = 'name' | 'email' | 'message';
type FieldErrors = Partial<Record<ValidatedField, string>>;

const EMPTY_FORM: ContactFormValues = {
    name: '',
    email: '',
    company: '',
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

const QUICK_FACTS = [
    { value: '2h', label: 'median first reply' },
    { value: 'EN/FR', label: 'bilingual support' },
    { value: '14-day', label: 'free trial, no card' },
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
    const { copy } = usePublicCopy();

    if (!message) {
        return null;
    }
    return (
        <p
            id={id}
            role="alert"
            className="text-sm font-medium text-destructive"
        >
            {copy(message)}
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
    const { copy } = usePublicCopy();

    return (
        <div className="rounded-2xl border border-border bg-card p-5 shadow-frost-xs">
            <div className="flex items-center gap-3">
                <span
                    aria-hidden="true"
                    className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${iconClass}`}
                >
                    <Icon className="size-5" />
                </span>
                <p className="font-medium">{copy(title)}</p>
            </div>
            <div className="mt-4 grid gap-1.5">{children}</div>
        </div>
    );
}

/* ── Page ────────────────────────────────────────────────────────── */

export default function Contact() {
    const { copy } = usePublicCopy();

    const locale = usePage().props.locale === 'en' ? 'en' : 'fr';
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
            toast.error(
                Object.values(nextErrors).filter(Boolean).map(copy).join('\n'),
            );
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
                    message: form.message.trim(),
                }),
            });

            if (response.ok) {
                toast.success(copy('Message received'), {
                    description: copy(
                        "We'll reply within two business hours — watch your inbox.",
                    ),
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
                toast.error(copy("We couldn't send that"), {
                    description: copy(
                        issues.name ??
                            issues.email ??
                            issues.message ??
                            'Please check your details and try again.',
                    ),
                });
            } else {
                toast.error(copy("We couldn't send that"), {
                    description: copy(
                        'Please check your details and try again.',
                    ),
                });
            }
        } catch {
            toast.error(copy('Something went wrong'), {
                description: copy(
                    "Try again in a moment — your message didn't go out.",
                ),
            });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <MarketingLayout
            title={copy('Contact — Frostline OPS')}
            route={ROUTES.contact}
        >
            <PageHero
                compact
                eyebrow={copy('Contact')}
                title={copy('Get your season under control')}
                description={copy(
                    'Questions, a tricky client book to untangle, or ready to start — tell us where it hurts. Median first reply: 2 business hours.',
                )}
            />

            {/* Form + info column */}
            <section className="py-12 md:py-20">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-8 lg:grid-cols-5">
                        <div className="rounded-2xl border border-border bg-card p-6 shadow-[var(--preview-elevation)] md:p-8 lg:col-span-3">
                            <h2 className="text-xl font-semibold tracking-tight">
                                {copy('Start your trial')}
                            </h2>
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {copy(
                                    "Tell us about your business — we'll set you up and import your client list before the next system moves through.",
                                )}
                            </p>

                            {submitted ? (
                                <div className="mt-6">
                                    <Button
                                        variant="outline"
                                        className="mt-4"
                                        onClick={handleReset}
                                    >
                                        {copy('Send another message')}
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
                                                {copy('Name')}
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
                                                placeholder={copy('Your name')}
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
                                            {copy(
                                                touched.name ? (
                                                    <FieldError
                                                        id="contact-name-error"
                                                        message={errors.name}
                                                    />
                                                ) : null,
                                            )}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="contact-email">
                                                {copy('Work email')}
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
                                                placeholder={copy(
                                                    'you@yourcompany.com',
                                                )}
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
                                            {copy(
                                                touched.email ? (
                                                    <FieldError
                                                        id="contact-email-error"
                                                        message={errors.email}
                                                    />
                                                ) : null,
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="contact-company">
                                            {copy('Company')}
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
                                            placeholder={copy('Your company')}
                                            autoComplete="organization"
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="contact-message">
                                            {copy(
                                                'What does your season look like?',
                                            )}
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
                                            placeholder={copy(
                                                'Clients under contract, how payments are tracked today, biggest headache at season-end…',
                                            )}
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
                                        {copy(
                                            touched.message ? (
                                                <FieldError
                                                    id="contact-message-error"
                                                    message={errors.message}
                                                />
                                            ) : null,
                                        )}
                                    </div>

                                    <div className="grid gap-3">
                                        <Button
                                            type="submit"
                                            size="lg"
                                            disabled={submitting}
                                            className="w-full sm:w-auto"
                                        >
                                            {copy(
                                                submitting ? (
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
                                                ),
                                            )}
                                        </Button>
                                        <p className="text-sm leading-relaxed tracking-[0.08em] text-muted-foreground">
                                            {copy(
                                                '14-day trial · no card · we reply in ~2 business hours',
                                            )}
                                        </p>
                                        <p className="text-sm leading-relaxed text-muted-foreground">
                                            {copy(
                                                locale === 'fr' ? (
                                                    <>
                                                        Nous utilisons votre
                                                        nom, courriel,
                                                        entreprise et message
                                                        uniquement pour répondre
                                                        à votre demande et en
                                                        assurer le suivi. Ils
                                                        sont supprimés selon
                                                        notre calendrier de
                                                        conservation. Consultez
                                                        notre{' '}
                                                        <TextLink
                                                            href={
                                                                ROUTES.privacy
                                                            }
                                                        >
                                                            politique de
                                                            confidentialité
                                                        </TextLink>{' '}
                                                        ou écrivez-nous pour
                                                        exercer vos droits.
                                                    </>
                                                ) : (
                                                    <>
                                                        We use your name, email,
                                                        company, and message
                                                        only to answer and
                                                        follow up on your
                                                        request. They are
                                                        deleted under our
                                                        retention schedule. Read
                                                        our{' '}
                                                        <TextLink
                                                            href={
                                                                ROUTES.privacy
                                                            }
                                                        >
                                                            Privacy Policy
                                                        </TextLink>{' '}
                                                        or contact us to
                                                        exercise your rights.
                                                    </>
                                                ),
                                            )}
                                        </p>
                                    </div>
                                </form>
                            )}
                        </div>

                        <div className="flex flex-col gap-4 lg:col-span-2">
                            <div className="rounded-2xl bg-secondary p-8">
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    {copy('A smoother season starts here.')}
                                </h2>
                                <p className="mt-4 leading-relaxed text-muted-foreground">
                                    {copy(
                                        'Tell us how you manage your clients, contracts and payments. We’ll help you find your next step.',
                                    )}
                                </p>
                            </div>

                            <ContactInfoCard
                                icon={MessageSquare}
                                iconClass="bg-glacier-500/10 text-glacier-700 dark:bg-glacier-400/10 dark:text-glacier-400"
                                title={copy('Talk to sales')}
                            >
                                <p className="text-sm">
                                    {copy(
                                        'Tell us about your business — clients, contracts, and what breaks at season-end.',
                                    )}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {copy(
                                        'Use the form · median first reply 2h',
                                    )}
                                </p>
                            </ContactInfoCard>

                            <ContactInfoCard
                                icon={LifeBuoy}
                                iconClass="bg-ember-500/10 text-ember-700 dark:bg-ember-400/10 dark:text-ember-400"
                                title={copy('Product support')}
                            >
                                <p className="text-sm">
                                    {copy(
                                        'Email support for every plan, with priority handling on Pro and a dedicated manager on Fleet.',
                                    )}
                                </p>
                                <Badge className="mt-1 w-fit border-success/25 bg-success/10 text-success">
                                    {copy('Median reply 12h')}
                                </Badge>
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
                                <dt className="order-2 text-sm font-medium tracking-[0.14em] text-muted-foreground">
                                    {copy(fact.label)}
                                </dt>
                                <dd className="order-1 text-xl font-semibold tabular-nums">
                                    {copy(fact.value)}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>
        </MarketingLayout>
    );
}
