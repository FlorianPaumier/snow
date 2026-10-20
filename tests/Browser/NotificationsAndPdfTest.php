<?php

declare(strict_types=1);

use App\Actions\GenerateContractPdf;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('shows PDF progress then downloads automatically or allows a retry after failure', function (bool $fails): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    Storage::fake(config()->string('filesystems.default'))->put(GenerateContractPdf::pdfPath($contract->id), '%PDF-1.4 test');

    $page = loginViaUi($admin)->navigate('/contracts/'.$contract->id);
    $failure = $fails ? 'true' : 'false';
    $page->script(<<<JS
        () => {
            const originalFetch = window.fetch;
            window.pdfRequests = 0;
            window.fetch = async (url, options) => {
                if (String(url).endsWith('/pdf')) {
                    window.pdfRequests++;
                    await new Promise(resolve => setTimeout(resolve, 500));
                    if ({$failure}) return new Response('{}', { status: 500 });
                    return new Response(JSON.stringify({ ready: window.pdfRequests >= 3 }), {
                        status: 200, headers: { 'Content-Type': 'application/json' }
                    });
                }
                return originalFetch(url, options);
            };
        }
    JS);
    $page->click('@download-pdf')->assertButtonDisabled('@download-pdf')
        ->assertVisible('[data-test="download-pdf"] [role="status"]');
    $page->script('document.querySelector("[data-test=download-pdf]").click()');
    if ($fails) {
        $page->assertSeeIn('[data-sonner-toast]', 'Impossible de générer le PDF')
            ->assertButtonEnabled('@download-pdf')->assertScript('window.pdfRequests', 1);
    } else {
        $page->assertSeeIn('[data-sonner-toast]', 'Le téléchargement du PDF a commencé.')
            ->assertButtonEnabled('@download-pdf')->assertScript('window.pdfRequests', 3);
    }

    $page->assertNoJavascriptErrors();
    Storage::disk(config()->string('filesystems.default'))->delete(GenerateContractPdf::pdfPath($contract->id));
})->with(['generated' => false, 'failed' => true]);

it('shows a saved client status in a floating alert', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $page = loginViaUi($admin)->navigate('/clients/'.$client->id.'/edit');
    $page->press('Enregistrer le client')
        ->assertSeeIn('[data-sonner-toast]', 'Client mis à jour.')
        ->assertNoJavascriptErrors();
});
