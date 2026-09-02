import { Form, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/hooks/use-locale';

export default function DeleteUser() {
    const { t } = useLocale();
    const { auth } = usePage().props;
    const passwordInput = useRef<HTMLInputElement>(null);

    // The owner keeps the organization alive; ownership must be
    // transferred before the account can go...
    if (auth.organization?.ownerId === auth.user.id) {
        return (
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t.account.deleteAccountTitle}
                    description={t.account.deleteAccountResources}
                />
                <div className="rounded-lg border border-border bg-muted/40 p-4">
                    <p className="text-sm text-muted-foreground">
                        {t.account.deleteOwnerNote}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title={t.account.deleteAccountTitle}
                description={t.account.deleteAccountResources}
            />
            <div className="space-y-4 rounded-lg border border-destructive/20 bg-destructive/5 p-4">
                <div className="relative space-y-0.5 text-destructive">
                    <p className="font-medium">
                        {t.account.deleteWarningTitle}
                    </p>
                    <p className="text-sm">{t.account.deleteWarningBody}</p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            {t.account.deleteAccountButton}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            {t.account.deleteConfirmTitle}
                        </DialogTitle>
                        <DialogDescription>
                            {t.account.deleteConfirmDescription}
                        </DialogDescription>

                        <Form
                            {...UserController.destroy.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            onError={() => passwordInput.current?.focus()}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="sr-only"
                                        >
                                            {t.account.deletePasswordField}
                                        </Label>

                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder={
                                                t.account
                                                    .deletePasswordPlaceholder
                                            }
                                            autoComplete="current-password"
                                        />

                                        <InputError message={errors.password} />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                variant="secondary"
                                                onClick={() =>
                                                    resetAndClearErrors()
                                                }
                                            >
                                                {t.common.cancel}
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-delete-user-button"
                                            >
                                                {t.account.deleteAccountButton}
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
