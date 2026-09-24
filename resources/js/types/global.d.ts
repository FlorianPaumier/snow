import type { Auth } from '@/types/auth';
import type { FlashToast } from '@/types/ui';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            toast?: FlashToast;
        };
        sharedPageProps: {
            name: string;
            appUrl: string;
            legal: {
                operatorName: string | null;
                mailingAddress: string | null;
                privacyOfficerTitle: string | null;
                privacyEmail: string | null;
            };
            locale: 'fr' | 'en';
            /** Flash success message shared by HandleInertiaRequests. */
            status?: string;
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
