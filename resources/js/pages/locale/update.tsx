import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import LocaleTabs from '@/components/locale-tabs';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editLocale } from '@/routes/locale';
import type { BreadcrumbItem } from '@/types';

export default function Update() {
    const { t } = useLocale();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.settings.languageTitle,
            href: editLocale(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.settings.languageTitle} />

            <h1 className="sr-only">{t.settings.languageTitle}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t.settings.languageTitle}
                        description={t.settings.languageDescription}
                    />
                    <LocaleTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
