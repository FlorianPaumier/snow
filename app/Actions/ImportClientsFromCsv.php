<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ImportClientsFromCsv
{
    public function __construct(private ValidateClientCsv $validateClientCsv, private CreateClient $createClient) {}

    /** @param array<string, string|null> $mapping */
    public function handle(string $csv, array $mapping): int
    {
        $result = $this->validateClientCsv->handle($csv, $mapping);
        $rows = $result['rows'];
        $invalidRows = array_filter($rows, fn (array $row): bool => $row['errors'] !== []);

        if ($rows === [] || $invalidRows !== []) {
            throw ValidationException::withMessages([
                'file' => $rows === [] ? __('statuses.clients_import_empty') : __('statuses.clients_import_invalid'),
            ]);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $this->createClient->handle([
                    'type' => $row['values']['type'],
                    'clientNumber' => $row['values']['clientNumber'],
                    'companyName' => $row['values']['companyName'],
                    'firstName' => $row['values']['firstName'],
                    'lastName' => $row['values']['lastName'],
                    'email' => $row['values']['email'],
                    'phoneDay' => $row['values']['phoneDay'],
                    'cellPhone' => $row['values']['cellPhone'],
                    'addressStreet' => $row['values']['addressStreet'],
                    'addressCity' => $row['values']['addressCity'],
                    'addressPostalCode' => $row['values']['addressPostalCode'],
                    'addressCountry' => $row['values']['addressCountry'],
                    'remark' => $row['values']['remark'],
                ]);
            }
        });

        return count($rows);
    }
}
