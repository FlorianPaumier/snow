<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

final readonly class ValidateClientCsv
{
    /** @var list<string> */
    public const array FIELDS = [
        'type', 'clientNumber', 'companyName', 'firstName', 'lastName', 'email', 'phoneDay', 'cellPhone',
        'addressStreet', 'addressCity', 'addressPostalCode', 'addressCountry', 'remark',
    ];

    /**
     * @param  array<string, string|null>  $mapping
     * @return array{headers: list<string>, mapping: array<string, string|null>, rows: list<array{row: int, values: array<string, string>, errors: list<string>}>}
     */
    public function handle(string $csv, array $mapping, bool $applyDefaults = false): array
    {
        $stream = fopen('php://temp', 'r+');
        throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv);
        rewind($stream);
        $delimiter = $this->detectDelimiter($stream);
        rewind($stream);
        $headers = fgetcsv($stream, 0, $delimiter, '"', '\\');

        if (! is_array($headers)) {
            fclose($stream);

            return ['headers' => [], 'mapping' => $mapping, 'rows' => []];
        }

        $headers = array_map(fn (mixed $header): string => mb_trim((string) $header), $headers);
        if ($applyDefaults) {
            $mapping = $this->applyDefaultMapping($headers, $mapping);
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            if ($values === [null]) {
                continue;
            }

            $attributes = [];
            foreach (self::FIELDS as $field) {
                $header = $mapping[$field] ?? '';
                $position = $header === '' ? false : array_search($header, $headers, true);
                $attributes[$field] = $position === false ? '' : mb_trim($values[$position] ?? '');
            }

            $type = $attributes['type'] !== '' ? mb_strtolower($attributes['type']) : ClientType::CLIENT_INDIVIDUAL->value;
            $validator = Validator::make([...$attributes, 'type' => $type], [
                'type' => ['required', 'in:individual,company'],
                'clientNumber' => ['required', 'string', 'max:255', Rule::unique(Client::class, 'clientNumber')],
                'companyName' => ['nullable', 'string', 'max:255', 'required_if:type,company'],
                'firstName' => ['required', 'string', 'max:255'],
                'lastName' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
                'phoneDay' => ['nullable', 'string', 'max:255', 'required_without:cellPhone'],
                'cellPhone' => ['nullable', 'string', 'max:255', 'required_without:phoneDay'],
                'addressStreet' => ['required', 'string', 'max:255'],
                'addressCity' => ['required', 'string', 'max:255'],
                'addressPostalCode' => ['required', 'string', 'max:255'],
                'addressCountry' => ['required', 'string', 'max:255'],
                'remark' => ['nullable', 'string', 'max:1000'],
            ]);

            $errors = [];
            foreach ($validator->errors()->messages() as $messages) {
                array_push($errors, ...$messages);
            }

            $rows[] = ['row' => $line, 'values' => [...$attributes, 'type' => $type], 'errors' => $errors];
        }

        fclose($stream);

        return ['headers' => $headers, 'mapping' => $mapping, 'rows' => $rows];
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string|null>  $mapping
     * @return array<string, string|null>
     */
    private function applyDefaultMapping(array $headers, array $mapping): array
    {
        $defaults = [
            'type' => ['type'],
            'clientNumber' => ['client_number', 'client number', 'client #', 'nº client', 'no client', 'client no'],
            'companyName' => ['company_name', 'company name', 'business_name', 'business name', 'nom de compagnie', 'nom compagnie'],
            'firstName' => ['first_name', 'first name', 'firstname', 'prénom', 'prenom'],
            'lastName' => ['last_name', 'last name', 'lastname', 'surname', 'nom'],
            'email' => ['email', 'email_address', 'email address', 'courriel', 'adresse courriel'],
            'phoneDay' => ['daytime_phone', 'daytime phone', 'phone', 'telephone', 'téléphone', 'téléphone de jour'],
            'cellPhone' => ['cell_phone', 'cell phone', 'mobile', 'mobile_phone', 'cellulaire'],
            'addressStreet' => ['street', 'address', 'address_street', 'street address', 'adresse', 'rue'],
            'addressCity' => ['city', 'address_city', 'ville'],
            'addressPostalCode' => ['postal_code', 'postal code', 'zip', 'zip_code', 'code postal'],
            'addressCountry' => ['country', 'address_country', 'pays'],
            'remark' => ['remark', 'notes', 'note', 'comment', 'commentaire'],
        ];
        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[$this->normalizeHeader($header)] = $header;
        }

        foreach ($defaults as $field => $aliases) {
            if (($mapping[$field] ?? '') !== '') {
                continue;
            }

            foreach ($aliases as $alias) {
                $normalizedHeader = $this->normalizeHeader($alias);
                if (isset($normalizedHeaders[$normalizedHeader])) {
                    $mapping[$field] = $normalizedHeaders[$normalizedHeader];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(mb_trim($header, " \t\n\r\0\x0B\xEF\xBB\xBF"));

        return mb_trim(preg_replace('/[^\pL\pN]+/u', '_', $header) ?? '', '_');
    }

    /** @param resource $stream */
    private function detectDelimiter(mixed $stream): string
    {
        $sample = (string) fgets($stream);
        $counts = array_map(fn (string $delimiter): int => mb_substr_count($sample, $delimiter), [',', ';', "\t"]);

        return [',', ';', "\t"][array_search(max($counts), $counts, true)];
    }
}
