<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportClientsFromCsv;
use App\Actions\ValidateClientCsv;
use App\Http\Requests\ImportClientsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportClientsController
{
    public function index(Request $request): Response
    {
        return Inertia::render('clients/import', [
            'importPreview' => $request->session()->get('importPreview'),
        ]);
    }

    public function store(ImportClientsRequest $request, ValidateClientCsv $validateClientCsv, ImportClientsFromCsv $importClients): RedirectResponse
    {
        $file = $request->file('file');
        $csv = $file !== null ? file_get_contents($file->getRealPath()) : base64_decode($request->string('csv')->toString(), true);
        abort_if($csv === false, 422);
        /** @var array<string, string|null> $mapping */
        $mapping = $request->validated('mapping', []);

        if ($request->input('mode') === 'preview') {
            $preview = $validateClientCsv->handle($csv, $mapping, ! $request->has('csv'));

            $request->session()->flash('importPreview', [
                ...$preview,
                'csv' => base64_encode($csv),
            ]);

            return to_route('clients.import');
        }

        $count = $importClients->handle($csv, $mapping);

        return to_route('clients.index')->with('status', __('statuses.clients_import_success', ['count' => $count]));
    }

    public function show(): StreamedResponse
    {
        $fields = ValidateClientCsv::FIELDS;
        $headers = ['type', 'client_number', 'company_name', 'first_name', 'last_name', 'email', 'daytime_phone', 'cell_phone', 'street', 'city', 'postal_code', 'country', 'remark'];
        $row = array_fill_keys($fields, '');
        $row['type'] = 'individual';
        $row['clientNumber'] = '';
        $row['firstName'] = 'Jean';
        $row['lastName'] = 'Tremblay';
        $row['email'] = 'jean@example.com';
        $row['phoneDay'] = '514 555-0100';
        $row['addressStreet'] = '1234 Sainte-Catherine';
        $row['addressCity'] = 'Montréal';
        $row['addressPostalCode'] = 'H3G 1M7';
        $row['addressCountry'] = 'Canada';

        return response()->streamDownload(function () use ($headers, $row): void {
            echo "\xEF\xBB\xBF";
            $stream = fopen('php://output', 'w');
            throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

            fputcsv($stream, $headers, ';', escape: '\\');
            fputcsv($stream, array_values($row), ';', escape: '\\');
            fclose($stream);
        }, 'clients-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
