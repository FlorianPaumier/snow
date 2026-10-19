import { Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useLocale } from '@/hooks/use-locale';

export default function PdfDownloadButton({
    generateUrl,
    downloadUrl,
}: {
    generateUrl: string;
    downloadUrl: string;
}) {
    const { t } = useLocale();
    const [busy, setBusy] = useState(false);
    const request = useRef<AbortController | null>(null);
    useEffect(() => () => request.current?.abort(), []);

    const download = async () => {
        if (request.current) {
            return;
        }
        const controller = new AbortController();
        request.current = controller;
        const signal = AbortSignal.any([
            controller.signal,
            AbortSignal.timeout(120_000),
        ]);
        setBusy(true);
        const notification = toast.loading(t.contracts.pdfGenerating);
        try {
            const cookie = document.cookie
                .split('; ')
                .find((value) => value.startsWith('XSRF-TOKEN='));
            const response = await fetch(generateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                signal,
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': cookie
                        ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length))
                        : '',
                },
            });
            if (!response.ok) {
                throw new Error(t.contracts.pdfFailed);
            }
            let result: { ready: boolean } = await response.json();
            while (result.ready === false) {
                await new Promise((resolve) => setTimeout(resolve, 1500));
                const status = await fetch(downloadUrl, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    signal,
                });
                if (!status.ok) {
                    throw new Error(t.contracts.pdfFailed);
                }
                result = await status.json();
            }
            if (result.ready !== true) {
                throw new Error(t.contracts.pdfFailed);
            }
            window.location.assign(downloadUrl);
            toast.success(t.contracts.pdfDownloaded, { id: notification });
        } catch {
            if (controller.signal.aborted) {
                toast.dismiss(notification);
            } else {
                toast.error(t.contracts.pdfFailed, { id: notification });
            }
        } finally {
            request.current = null;
            if (!controller.signal.aborted) {
                setBusy(false);
            }
        }
    };

    return (
        <Button
            type="button"
            variant="outline"
            onClick={() => void download()}
            disabled={busy}
            aria-busy={busy}
            data-test="download-pdf"
        >
            {busy ? <Spinner /> : <Download aria-hidden="true" />}
            {busy ? t.common.loading : t.contracts.downloadPdf}
        </Button>
    );
}
