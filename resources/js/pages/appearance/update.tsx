import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { useLocale } from '@/hooks/use-locale';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editAppearance } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';

export default function Update() {
    const { t } = useLocale();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t.settings.appearanceTitle,
            href: editAppearance(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t.settings.appearanceTitle} />

            <h1 className="sr-only">{t.settings.appearanceTitle}</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title={t.settings.appearanceTitle}
                        description={t.settings.appearanceDescription}
                    />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
