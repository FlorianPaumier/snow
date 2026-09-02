<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetContractDocument;
use App\Models\Contract;
use Illuminate\Http\Response;

final readonly class PrintContractDocumentController
{
    /**
     * The printable document, rendered as a standalone HTML page the
     * browser can print or save as PDF.
     */
    public function __invoke(Contract $contract, GetContractDocument $document): Response
    {
        return response()->view('documents.contract-summary', [
            ...$document->handle($contract),
            'toolbar' => true,
        ]);
    }
}
