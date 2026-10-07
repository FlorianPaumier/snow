<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

final readonly class ImportUsersFromCsvAction
{
    /** @var list<string> */
    private const array FIELDS = ['name', 'email', 'role'];

    public function __construct(private CreateUser $createUser) {}

    /**
     * @param  array<string, string|null>  $mapping
     * @return array{headers: list<string>, mapping: array<string, string|null>, rows: list<array{row: int, values: array<string, string>, errors: list<string>}>}
     */
    public function handle(string $csv, array $mapping, bool $import = false, bool $applyDefaults = false): array
    {
        $headers = $this->headers($csv);
        if ($applyDefaults) {
            $mapping = $this->applyDefaultMapping($headers, $mapping);
        }

        $rows = $this->parse($csv, $headers, $mapping);

        if ($import && $rows !== [] && array_all($rows, fn (array $row): bool => $row['errors'] === [])) {
            DB::transaction(function () use ($rows): void {
                foreach ($rows as $row) {
                    $this->createUser->handle($row['values']);
                }
            });
        }

        return ['headers' => $headers, 'mapping' => $mapping, 'rows' => $rows];
    }

    /** @return list<string> */
    private function headers(string $csv): array
    {
        $stream = fopen('php://temp', 'r+');
        throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv);
        rewind($stream);
        $sample = (string) fgets($stream);
        $delimiters = [',', ';', "\t"];
        $counts = array_map(fn (string $delimiter): int => mb_substr_count($sample, $delimiter), $delimiters);
        rewind($stream);
        $headers = fgetcsv($stream, 0, $delimiters[array_search(max($counts), $counts, true)], '"', '\\');
        fclose($stream);

        return array_map(fn (mixed $header): string => mb_trim((string) $header), is_array($headers) ? $headers : []);
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
            $normalizedField = $this->normalizeHeader($field);
            if (($mapping[$field] ?? '') === '' && isset($normalizedHeaders[$normalizedField])) {
                $mapping[$field] = $normalizedHeaders[$normalizedField];
            }
        }

        return $mapping;
    }

    private function normalizeHeader(string $header): string
    {
        return mb_trim(preg_replace('/[^\pL\pN]+/u', '_', mb_strtolower(mb_trim($header))) ?? '', '_');
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string|null>  $mapping
     * @return list<array{row: int, values: array<string, string>, errors: list<string>}>
     */
    private function parse(string $csv, array $headers, array $mapping): array
    {
        $stream = fopen('php://temp', 'r+');
        throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv);
        rewind($stream);
        $sample = (string) fgets($stream);
        $delimiters = [',', ';', "\t"];
        $counts = array_map(fn (string $delimiter): int => mb_substr_count($sample, $delimiter), $delimiters);
        $delimiter = $delimiters[array_search(max($counts), $counts, true)];
        rewind($stream);
        fgetcsv($stream, 0, $delimiter, '"', '\\');

        $rows = [];
        $line = 1;
        while (($record = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $line++;
            if ($record === [null]) {
                continue;
            }

            $values = [];
            foreach (self::FIELDS as $field) {
                $position = array_search($mapping[$field] ?? '', $headers, true);
                $values[$field] = $position === false ? '' : mb_trim($record[$position] ?? '');
            }

            $values['role'] = mb_strtolower($values['role'] ?: UserRole::ROLE_WORKER->value);

            $validator = Validator::make($values, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
                'role' => ['required', Rule::enum(UserRole::class)],
            ]);
            $validator->after(function (\Illuminate\Validation\Validator $validator) use ($values): void {
                if (User::query()->where('email', $values['email'])->exists()) {
                    $validator->errors()->add('email', __('validation.unique', ['attribute' => 'email']));
                }
            });

            $errors = [];
            foreach ($validator->errors()->messages() as $messages) {
                array_push($errors, ...$messages);
            }

            $rows[] = ['row' => $line, 'values' => $values, 'errors' => $errors];
        }

        fclose($stream);

        return $rows;
    }
}
