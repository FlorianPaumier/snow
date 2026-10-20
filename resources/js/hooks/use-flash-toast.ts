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
        let errorFocusObserver: MutationObserver | undefined;
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
                errorFocusObserver?.disconnect();
                const main = document.querySelector('main');
                if (main) {
                    const focusFirstInvalid = () => {
                        const field = main.querySelector<HTMLElement>(
                            '[aria-invalid="true"]',
                        );
                        if (
                            field &&
                            !field.closest('[inert]') &&
                            !field.matches(':disabled')
                        ) {
                            field.focus();
                            if (document.activeElement === field) {
                                errorFocusObserver?.disconnect();
                            }
                        }
                    };
                    errorFocusObserver = new MutationObserver(
                        focusFirstInvalid,
                    );
                    errorFocusObserver.observe(main, {
                        childList: true,
                        subtree: true,
                        attributes: true,
                        attributeFilter: ['aria-invalid', 'inert', 'disabled'],
                    });
                    focusFirstInvalid();
                }
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
        const removeStart = router.on('start', () =>
            errorFocusObserver?.disconnect(),
        );
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
            errorFocusObserver?.disconnect();
            removeStart();
            removeSuccess();
            removeError();
            removeFlash();
        };
    }, [initialPage]);
}
