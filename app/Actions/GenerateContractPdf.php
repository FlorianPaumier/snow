<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final readonly class GenerateContractPdf
{
    public function __construct(private GetContractDocument $document) {}

    /**
     * The path where a contract's PDF is stored, shared with the
     * controller so downloads and regeneration checks agree.
     */
    public static function pdfPath(string $contractId): string
    {
        return sprintf('documents/contracts/%s/contract-summary.pdf', $contractId);
    }

    /**
     * Render the contract document and convert it to PDF through
     * Gotenberg, storing the result on the default disk.
     *
     * @return string The stored PDF path
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function handle(Contract $contract): string
    {
        $html = view('documents.contract-summary', $this->document->handle($contract))->render();

        $response = Http::asMultipart()
            ->attach('files', $html, 'index.html')
            ->timeout(30)
            ->post($this->endpoint(), ['preferCssPageSize' => 'true']);

        $response->throw();

        $path = self::pdfPath($contract->id);
        Storage::disk(config()->string('filesystems.default'))->put($path, $response->body());

        return $path;
    }

    private function endpoint(): string
    {
        return sprintf('%s/forms/chromium/convert/html', mb_rtrim(config()->string('services.gotenberg.url'), '/'));
    }
}
