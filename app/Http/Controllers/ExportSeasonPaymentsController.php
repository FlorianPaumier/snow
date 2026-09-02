<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ExportSeasonPayments;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportSeasonPaymentsController
{
    /**
     * Stream the season's installments — with their client and contract
     * columns — as a CSV download for the accountant.
     */
    public function __invoke(Request $request, ExportSeasonPayments $export): StreamedResponse
    {
        /** @var array{season?: string} $validated */
        $validated = $request->validate([
            'season' => ['nullable', 'string', Rule::in($this->availableSeasons())],
        ]);

        $season = $validated['season'] ?? null;

        return response()->streamDownload(
            function () use ($export, $season): void {
                $output = fopen('php://output', 'w');

                if ($output === false) {
                    throw new RuntimeException('Unable to open the CSV output stream.');
                }

                // UTF-8 BOM so accented headers survive Excel's default encoding.
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, ExportSeasonPayments::headers());

                foreach ($export->handle($season) as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            },
            sprintf('Paiements-%s.csv', $season ?? 'toutes-saisons'),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Seasons that have contracts, same list as the contracts index filter.
     *
     * @return list<string>
     */
    private function availableSeasons(): array
    {
        /** @var list<string> */
        return Contract::query()
            ->select('season')
            ->distinct()
            ->orderByDesc('season')
            ->pluck('season')
            ->all();
    }
}
