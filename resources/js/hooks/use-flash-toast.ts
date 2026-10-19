import type { Page } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import en from '@/lang/en';
import fr from '@/lang/fr';
import { index } from '@/routes/billing';
import type { Auth } from '@/types/auth';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(initialPage: Page): void {
    useEffect(() => {
        let currentPage = initialPage;
        const showErrors = (errors: Record<string, unknown>) => {
            const messages = [
                ...new Set(
                    Object.values(errors)
                        .flat()
                        .filter(
                            (message): message is string =>
                                typeof message === 'string',
                        ),
                ),
            ];
            if (messages.length) {
                requestAnimationFrame(() => {
                    document
                        .querySelector<HTMLElement>(
                            'main [aria-invalid="true"]',
                        )
                        ?.focus();
                });
                const t = currentPage.props.locale === 'en' ? en : fr;
                const needsUpgrade =
                    typeof errors.planLimit === 'string' ||
                    typeof errors.featureLocked === 'string';
                toast.error(messages.join('\n'), {
                    id: 'request-errors',
                    action:
                        needsUpgrade &&
                        (currentPage.props.auth as Auth | undefined)?.can
                            ?.manageBilling
                            ? {
                                  label: t.billing.bannerUpgrade,
                                  onClick: () => router.visit(index()),
                              }
                            : undefined,
                });
            }
        };
        const showStatus = (page: Page) => {
            currentPage = page;
            const status = page.props.status;
            const t = page.props.locale === 'en' ? en : fr;
            if (typeof status === 'string' && status) {
                const message =
                    status === 'verification-link-sent'
                        ? t.auth.verificationLinkSent
                        : status === 'login-challenge-code-sent'
                          ? t.auth.challengeCodeSent
                          : status;
                toast.success(message, { id: 'request-status' });
            }
        };
        showStatus(initialPage);
        showErrors(initialPage.props.errors);
        const removeSuccess = router.on('success', (event) =>
            showStatus(event.detail.page),
        );
        const removeError = router.on('error', (event) =>
            showErrors(event.detail.errors),
        );
        const showFlash = (data?: FlashToast) => {
            if (data) {
                toast[data.type](data.message, { id: 'flash-toast' });
            }
        };
        showFlash(initialPage.flash?.toast);
        const removeFlash = router.on('flash', (event) =>
            showFlash(event.detail.flash.toast),
        );
        return () => {
            removeSuccess();
            removeError();
            removeFlash();
        };
    }, [initialPage]);
}
