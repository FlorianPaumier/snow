<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Contract;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

final readonly class ValidateContractCsvAction
{
    /** @var list<string> */
    public const array FIELDS = [
        'clientNumber', 'season', 'serviceLabel', 'serviceAmount',
        'depositAmount', 'depositDueDate', 'firstAmount', 'firstDueDate',
        'secondAmount', 'secondDueDate', 'balanceAmount', 'balanceDueDate',
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
        while (($columns = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            if ($columns === [null]) {
                continue;
            }

            $values = [];
            foreach (self::FIELDS as $field) {
                $header = $mapping[$field] ?? '';
                $position = $header === '' ? false : array_search($header, $headers, true);
                $values[$field] = $position === false ? '' : mb_trim($columns[$position] ?? '');
            }

            $validator = Validator::make($values, [
                'clientNumber' => ['required', 'string', 'max:255', Rule::exists(Client::class, 'clientNumber')->where('organization_id', Auth::user()?->organization_id)],
                'season' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
                'serviceLabel' => ['required', 'string', 'max:255'],
                'serviceAmount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'depositAmount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'depositDueDate' => ['nullable', 'date'],
                'firstAmount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'firstDueDate' => ['nullable', 'date'],
                'secondAmount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'secondDueDate' => ['nullable', 'date'],
                'balanceAmount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'balanceDueDate' => ['nullable', 'date'],
            ]);

            try {
                $season = Season::fromString($values['season']);
                if (! in_array($season->label, [Season::current()->previous()->label, Season::current()->label, Season::current()->next()->label], true)) {
                    $validator->errors()->add('season', __('validation.in', ['attribute' => 'season']));
                }
            } catch (InvalidArgumentException) {
                $validator->errors()->add('season', __('validation.regex', ['attribute' => 'season']));
            }

            $client = $values['clientNumber'] === '' ? null : Client::query()->where('clientNumber', $values['clientNumber'])->first();
            if ($client?->isAnonymized()) {
                $validator->errors()->add('clientNumber', __('clients.anonymized_no_new_contract'));
            }

            if ($client !== null && $values['season'] !== '' && Contract::query()->where('client_id', $client->id)->where('season', $values['season'])->exists()) {
                $validator->errors()->add('season', __('contracts.duplicate_season'));
            }

            if ($validator->errors()->isEmpty() && array_sum(array_map(fn (string $key): int => (int) $values[$key], ['depositAmount', 'firstAmount', 'secondAmount', 'balanceAmount'])) !== (int) $values['serviceAmount']) {
                $validator->errors()->add('serviceAmount', __('contracts.totals_mismatch'));
            }

            $errors = [];
            foreach ($validator->errors()->messages() as $messages) {
                array_push($errors, ...$messages);
            }

            $rows[] = ['row' => $line, 'values' => $values, 'errors' => $errors];
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
        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[$this->normalizeHeader($header)] = $header;
        }

        foreach (self::FIELDS as $field) {
            if (($mapping[$field] ?? '') === '' && isset($normalizedHeaders[$this->normalizeHeader($this->headerFor($field))])) {
                $mapping[$field] = $normalizedHeaders[$this->normalizeHeader($this->headerFor($field))];
            }
        }

        return $mapping;
    }

    private function headerFor(string $field): string
    {
        return match ($field) {
            'clientNumber' => 'client_number', 'serviceLabel' => 'service_label', 'serviceAmount' => 'service_amount',
            'depositAmount' => 'deposit_amount', 'depositDueDate' => 'deposit_due_date', 'firstAmount' => 'first_amount',
            'firstDueDate' => 'first_due_date', 'secondAmount' => 'second_amount', 'secondDueDate' => 'second_due_date',
            'balanceAmount' => 'balance_amount', 'balanceDueDate' => 'balance_due_date', default => 'season',
        };
    }

    private function normalizeHeader(string $header): string
    {
        return mb_trim(preg_replace('/[^\pL\pN]+/u', '_', mb_strtolower(mb_trim($header))) ?? '', '_');
    }

    /** @param resource $stream */
    private function detectDelimiter(mixed $stream): string
    {
        $sample = (string) fgets($stream);
        $counts = array_map(fn (string $delimiter): int => mb_substr_count($sample, $delimiter), [',', ';', "\t"]);

        return [',', ';', "\t"][array_search(max($counts), $counts, true)];
    }
}
