<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateContractPdf;
use App\Jobs\GenerateContractDocumentPdf;
use App\Models\Contract;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class GenerateContractPdfController
{
    /**
     * Ensure a PDF exists for the contract: send the browser to the
     * download when one is already stored, otherwise queue its generation.
     */
    public function __invoke(Contract $contract): SymfonyResponse
    {
        if (Storage::disk((string) config('filesystems.default'))->exists(GenerateContractPdf::pdfPath($contract->id))) {
            // Inertia answers a 409 + X-Inertia-Location header so the
            // client performs a full-page navigation and the PDF streams...
            return Inertia::location(route('contracts.pdf.download', $contract));
        }

        GenerateContractDocumentPdf::dispatch($contract->id);

        return back()->with('status', __('statuses.document_pdf_generating'));
    }
}
