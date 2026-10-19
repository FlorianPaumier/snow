<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateContractPdf;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadContractPdfController
{
    /**
     * Stream the stored PDF as a download.
     */
    public function __invoke(Request $request, Contract $contract): StreamedResponse|JsonResponse
    {
        $disk = Storage::disk(config()->string('filesystems.default'));
        $path = GenerateContractPdf::pdfPath($contract->id);

        if ($request->wantsJson()) {
            return response()->json(['ready' => $disk->exists($path)])->header('Cache-Control', 'no-store');
        }

        abort_unless($disk->exists($path), 404);

        $contract->loadMissing('client');

        return $disk->download($path, sprintf('Contrat-%s-%s.pdf', $contract->season, $contract->client->clientNumber));
    }
}
