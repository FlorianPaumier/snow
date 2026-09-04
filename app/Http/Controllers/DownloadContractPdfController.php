<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateContractPdf;
use App\Models\Contract;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadContractPdfController
{
    /**
     * Stream the stored PDF as a download.
     */
    public function __invoke(Contract $contract): StreamedResponse
    {
        $disk = Storage::disk(config()->string('filesystems.default'));
        $path = GenerateContractPdf::pdfPath($contract->id);

        abort_unless($disk->exists($path), 404);

        $contract->loadMissing('client');

        return $disk->download($path, sprintf('Contrat-%s-%s.pdf', $contract->season, $contract->client->clientNumber));
    }
}
