import { Link } from '@inertiajs/react';
import { CheckCircle2, Circle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { useLocale } from '@/hooks/use-locale';
import { interpolate } from '@/lib/utils';

export type OnboardingStep = {
    key: string;
    href: string;
    completed: boolean;
};

export type OnboardingChecklist = {
    steps: OnboardingStep[];
    incompleteCount: number;
};

/**
 * The didactic "get started" checklist: each step links to the real page
 * where the action is performed, and ticks itself off when the backend
 * detects the action has happened. Server-side derived, so it never
 * drifts — and the dashboard omits the prop once everything is done.
 */
export default function OnboardingChecklistCard({
    checklist,
}: {
    checklist: OnboardingChecklist;
}) {
    const { t } = useLocale();

    const total = checklist.steps.length;
    const done = total - checklist.incompleteCount;
    const stepLabel = (key: string) =>
        t.onboarding.steps[key as keyof typeof t.onboarding.steps] ?? key;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t.onboarding.title}</CardTitle>
                <CardDescription>{t.onboarding.description}</CardDescription>
                <p className="text-xs text-muted-foreground">
                    {interpolate(t.onboarding.progress, {
                        done: String(done),
                        total: String(total),
                    })}
                </p>
                <Progress value={total === 0 ? 100 : (done / total) * 100} />
            </CardHeader>
            <CardContent>
                <ul className="flex flex-col gap-2">
                    {checklist.steps.map((step) => (
                        <li key={step.key} className="flex items-center gap-2">
                            {step.completed ? (
                                <CheckCircle2
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-success"
                                />
                            ) : (
                                <Circle
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-muted-foreground"
                                />
                            )}
                            {step.completed ? (
                                <span className="text-muted-foreground line-through">
                                    {stepLabel(step.key)}
                                </span>
                            ) : (
                                <Button
                                    asChild
                                    variant="link"
                                    className="h-auto p-0 text-base font-normal"
                                >
                                    <Link href={step.href} prefetch>
                                        {stepLabel(step.key)}
                                    </Link>
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}
