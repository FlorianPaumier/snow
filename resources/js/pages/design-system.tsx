import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    CheckCircle2,
    Moon,
    Send,
    Snowflake,
    Sun,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import ContractDemo from '@/components/marketing/contract-demo';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { home } from '@/routes';
import '../../css/public-design.css';

const money = (value: number) =>
    new Intl.NumberFormat('en-CA', {
        style: 'currency',
        currency: 'CAD',
        maximumFractionDigits: 0,
    }).format(value);
const colors = [
    { name: 'Canvas', value: 'var(--color-background)' },
    { name: 'Surface', value: 'var(--color-card)' },
    { name: 'Ink', value: 'var(--color-foreground)' },
    { name: 'Glacier', value: 'var(--color-primary)' },
    { name: 'Soft accent', value: 'var(--color-accent)' },
    { name: 'Attention', value: 'var(--color-destructive)' },
];

export default function DesignSystem() {
    const [dark, setDark] = useState(false);
    const [driveway, setDriveway] = useState(450);
    const [sending, setSending] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);
    const total = driveway + 150;

    useEffect(() => {
        if (!sending) return;
        const timer = window.setTimeout(() => {
            setSending(false);
            setDialogOpen(false);
            toast.success('Preview complete. No email was sent.');
        }, 900);
        return () => window.clearTimeout(timer);
    }, [sending]);

    return (
        <div
            className="frostline-public design-preview min-h-svh bg-background text-foreground"
            data-theme={dark ? 'dark' : 'light'}
        >
            <Head title="Design system preview — Frostline OPS">
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <header className="preview-nav">
                <div className="preview-container flex flex-wrap items-center justify-between gap-4 py-5">
                    <Link
                        href={home()}
                        className="flex items-center gap-3 font-semibold"
                        aria-label="Frostline OPS home"
                    >
                        <span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <Snowflake aria-hidden="true" className="size-6" />
                        </span>
                        <span>
                            Frostline <span className="text-primary">OPS</span>
                        </span>
                    </Link>
                    <nav
                        aria-label="Design system sections"
                        className="flex flex-wrap items-center gap-5 text-sm font-medium"
                    >
                        <a href="#foundations">Foundations</a>
                        <a href="#components">Components</a>
                        <a href="#application">App pattern</a>
                        <Button
                            variant="secondary"
                            size="icon"
                            aria-label={
                                dark
                                    ? 'Preview light theme'
                                    : 'Preview dark theme'
                            }
                            onClick={() => setDark(!dark)}
                        >
                            {dark ? (
                                <Sun aria-hidden="true" />
                            ) : (
                                <Moon aria-hidden="true" />
                            )}
                        </Button>
                    </nav>
                </div>
            </header>
            <main>
                <section className="preview-hero">
                    <div className="preview-container">
                        <p className="mb-8 text-sm text-muted-foreground">
                            Temporary design-system preview · Proposed direction
                            · Demo data only
                        </p>
                        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                            <div>
                                <h1 className="preview-display">
                                    A clearer season.
                                    <br />
                                    <span className="text-primary">
                                        A calmer office.
                                    </span>
                                </h1>
                                <p className="mt-6 max-w-lg text-lg leading-relaxed">
                                    A friendlier Frostline: light surfaces,
                                    confident colour and useful details.
                                    Contracts, clients and payments feel like
                                    parts of the same system.
                                </p>
                                <div className="mt-8 flex flex-wrap items-center gap-3">
                                    <Button asChild size="lg">
                                        <a href="#components">
                                            Explore the components
                                            <ArrowRight aria-hidden="true" />
                                        </a>
                                    </Button>
                                    <Button asChild variant="outline" size="lg">
                                        <a href="#application">
                                            See the app pattern
                                        </a>
                                    </Button>
                                </div>
                                <p className="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
                                    <CheckCircle2
                                        aria-hidden="true"
                                        className="size-4 text-primary"
                                    />
                                    Inspired by Simple AI Chatbot. Made for
                                    Frostline.
                                </p>
                            </div>
                            <ContractDemo
                                driveway={driveway}
                                onPriceChange={setDriveway}
                            />
                        </div>
                    </div>
                </section>

                <section id="foundations" className="preview-section">
                    <div className="preview-container">
                        <h2 className="preview-title">
                            Foundations with a little warmth.
                        </h2>
                        <p className="preview-intro">
                            Cool neutrals and Glacier cyan keep the identity.
                            Softer surfaces and a clearer type scale change how
                            it feels.
                        </p>
                        <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                            {colors.map((color) => (
                                <div key={color.name}>
                                    <div
                                        className="preview-swatch"
                                        style={{ background: color.value }}
                                    />
                                    <p className="mt-3 text-sm font-medium">
                                        {color.name}
                                    </p>
                                </div>
                            ))}
                        </div>
                        <div className="mt-12 grid gap-8 md:grid-cols-2">
                            <div className="preview-surface">
                                <h3 className="text-2xl font-semibold tracking-tight">
                                    One familiar voice.
                                </h3>
                                <p className="mt-4 text-base leading-relaxed">
                                    Instrument Sans, with readable body text and
                                    tabular numbers. Bigger headlines belong to
                                    marketing; clear titles and compact controls
                                    belong to the app.
                                </p>
                                <p className="mt-5 text-sm text-muted-foreground">
                                    Body 16px · Labels 14px · Page titles 32px
                                </p>
                            </div>
                            <div className="preview-surface">
                                <h3 className="text-2xl font-semibold tracking-tight">
                                    Space to see what matters.
                                </h3>
                                <p className="mt-4 text-base leading-relaxed">
                                    A 4px spacing rhythm. Rounded 16px panels.
                                    Soft elevation on raised surfaces, with
                                    stronger focus rings for keyboard
                                    navigation.
                                </p>
                                <div
                                    className="mt-6 flex items-end gap-3"
                                    role="img"
                                    aria-label="Spacing samples: 8, 16, 24 and 32 pixels"
                                >
                                    {[8, 16, 24, 32].map((size) => (
                                        <span
                                            key={size}
                                            className="inline-block rounded bg-primary/20"
                                            style={{
                                                width: size,
                                                height: size,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="components"
                    className="preview-section bg-muted/40"
                >
                    <div className="preview-container">
                        <h2 className="preview-title">
                            Small parts. One language.
                        </h2>
                        <p className="preview-intro">
                            Existing accessible components, wearing proposed
                            tokens. Try the controls; nothing is saved or sent.
                        </p>
                        <div className="mt-8 grid items-start gap-6 lg:grid-cols-2">
                            <div className="preview-surface space-y-6">
                                <h3 className="text-xl font-semibold">
                                    Actions & feedback
                                </h3>
                                <div className="flex flex-wrap gap-3">
                                    <Button
                                        onClick={() =>
                                            toast.success(
                                                'This is a preview success message.',
                                            )
                                        }
                                    >
                                        Primary action
                                        <ArrowRight aria-hidden="true" />
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() =>
                                            toast.info(
                                                'This is a preview information message.',
                                            )
                                        }
                                    >
                                        Secondary
                                    </Button>
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            toast.error(
                                                'Preview error. Your records have not changed.',
                                            )
                                        }
                                    >
                                        Show an error
                                    </Button>
                                    <Button disabled>Unavailable</Button>
                                </div>
                                <div className="flex flex-wrap items-center gap-3">
                                    <Badge>Active</Badge>
                                    <Badge variant="secondary">Draft</Badge>
                                    <Badge variant="outline">Pending</Badge>
                                    <Badge
                                        variant="destructive"
                                        className="bg-destructive/10 text-destructive dark:bg-destructive/10"
                                    >
                                        Needs attention
                                    </Badge>
                                </div>
                                <Alert>
                                    <CheckCircle2 aria-hidden="true" />
                                    <AlertTitle>
                                        Clear, useful feedback.
                                    </AlertTitle>
                                    <AlertDescription>
                                        Success confirms the action. Errors
                                        explain what to do next.
                                    </AlertDescription>
                                </Alert>
                            </div>
                            <div className="preview-surface space-y-5">
                                <h3 className="text-xl font-semibold">
                                    Forms & validation
                                </h3>
                                <div className="space-y-2">
                                    <Label htmlFor="preview-name">
                                        Client name
                                    </Label>
                                    <Input
                                        id="preview-name"
                                        defaultValue="Camille Tremblay"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="preview-email">
                                        Email address
                                    </Label>
                                    <Input
                                        id="preview-email"
                                        type="email"
                                        defaultValue="camille@"
                                        aria-invalid="true"
                                        aria-describedby="preview-email-error"
                                    />
                                    <p
                                        id="preview-email-error"
                                        className="text-sm text-destructive"
                                    >
                                        Example error: enter a complete email
                                        address.
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="preview-reminder"
                                        defaultChecked
                                    />
                                    <Label htmlFor="preview-reminder">
                                        Include a payment reminder
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    These fields demonstrate states only.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="application" className="preview-section">
                    <div className="preview-container">
                        <h2 className="preview-title">
                            Built for the work, too.
                        </h2>
                        <p className="preview-intro">
                            The same system at an operational density. Status
                            stays visible and actions wrap comfortably on
                            smaller screens.
                        </p>
                        <div className="preview-surface mt-8">
                            <div className="flex flex-wrap items-start justify-between gap-5">
                                <div>
                                    <h3 className="text-2xl font-semibold tracking-tight">
                                        Camille Tremblay
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Winter contract · 2026–2027 · Example
                                        record
                                    </p>
                                </div>
                                <Dialog
                                    open={dialogOpen}
                                    onOpenChange={(open) => {
                                        if (!sending) setDialogOpen(open);
                                    }}
                                >
                                    <DialogTrigger asChild>
                                        <Button>
                                            <Send aria-hidden="true" />
                                            Preview contract email
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent
                                        className="frostline-public design-preview"
                                        data-theme={dark ? 'dark' : 'light'}
                                    >
                                        <DialogHeader>
                                            <DialogTitle>
                                                Review before sending
                                            </DialogTitle>
                                            <DialogDescription>
                                                This is a design preview. No
                                                email will be sent.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <p className="text-sm">
                                            Camille Tremblay ·
                                            camille@example.com
                                            <br />
                                            Contract total: {money(total)}
                                        </p>
                                        <DialogFooter>
                                            <Button
                                                variant="secondary"
                                                disabled={sending}
                                                onClick={() =>
                                                    setDialogOpen(false)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                disabled={sending}
                                                aria-busy={sending}
                                                onClick={() => setSending(true)}
                                            >
                                                {sending ? (
                                                    <Spinner />
                                                ) : (
                                                    <Check aria-hidden="true" />
                                                )}
                                                {sending
                                                    ? 'Previewing…'
                                                    : 'Run preview'}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                            <Table className="mt-7">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Payment</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell>Deposit</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                Collected
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {money(200)}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell>Balance</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                Outstanding
                                            </Badge>
                                        </TableCell>
                                        <TableCell
                                            data-test="remaining-balance"
                                            className="text-right font-semibold tabular-nums"
                                        >
                                            {money(total - 200)}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </section>
            </main>
            <footer className="preview-container py-8 text-sm text-muted-foreground">
                Frostline OPS · Temporary preview · Production pages remain
                unchanged.
            </footer>
        </div>
    );
}
