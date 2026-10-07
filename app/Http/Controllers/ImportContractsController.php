<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportContractsFromCsvAction;
use App\Actions\ValidateContractCsvAction;
use App\Http\Requests\ImportContractsRequest;
use App\ValueObjects\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportContractsController
{
    public function index(Request $request): Response
    {
        return Inertia::render('contract/import', ['importPreview' => $request->session()->get('contractImportPreview')]);
    }

    public function show(): StreamedResponse
    {
        $headers = ['client_number', 'season', 'service_label', 'service_amount', 'deposit_amount', 'deposit_due_date', 'first_amount', 'first_due_date', 'second_amount', 'second_due_date', 'balance_amount', 'balance_due_date'];
        $row = ['individual-0001', Season::current()->label, 'Snow removal', '10000', '2500', '', '2500', '', '2500', '', '2500', ''];

        return response()->streamDownload(function () use ($headers, $row): void {
            echo "\xEF\xBB\xBF";
            $stream = fopen('php://output', 'w');
            throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

            fputcsv($stream, $headers, ';', escape: '\\');
            fputcsv($stream, $row, ';', escape: '\\');
            fclose($stream);
        }, 'contracts-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(ImportContractsRequest $request, ValidateContractCsvAction $validateCsv, ImportContractsFromCsvAction $importContracts): RedirectResponse
    {
        $file = $request->file('file');
        $csv = $file !== null ? file_get_contents($file->getRealPath()) : base64_decode($request->string('csv')->toString(), true);
        abort_if($csv === false, 422);
        /** @var array<string, string|null> $mapping */
        $mapping = $request->validated('mapping', []);

        if ($request->input('mode') === 'preview') {
            $preview = $validateCsv->handle($csv, $mapping, ! $request->has('csv'));
            $request->session()->flash('contractImportPreview', [...$preview, 'csv' => base64_encode($csv)]);

            return to_route('contracts.import');
        }

        $count = $importContracts->handle($csv, $mapping);

        return to_route('contracts.index')->with('status', __('statuses.contracts_import_success', ['count' => $count]));
    }
}
