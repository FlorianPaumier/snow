<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ImportUsersFromCsvAction;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImportUsersController
{
    public function index(Request $request): Response
    {
        return Inertia::render('user-management/import', ['importPreview' => $request->session()->get('userImportPreview')]);
    }

    public function show(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            throw_if($stream === false, RuntimeException::class, 'Unable to open the CSV stream.');

            echo "\xEF\xBB\xBF";
            fputcsv($stream, ['name', 'email', 'role'], ';', escape: '\\');
            fputcsv($stream, ['Marie Tremblay', 'marie@example.com', UserRole::ROLE_WORKER->value], ';', escape: '\\');
            fclose($stream);
        }, 'users-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, ImportUsersFromCsvAction $importUsers): RedirectResponse
    {
        abort_unless($request->user()?->can('manage-users') ?? false, 403);
        $request->validate([
            'file' => ['required_without:csv', 'file', 'mimes:csv,txt', 'max:5120'],
            'csv' => ['required_without:file', 'nullable', 'string', 'max:6990507'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['preview', 'import'])],
        ]);

        $file = $request->file('file');
        $csv = $file !== null ? file_get_contents($file->getRealPath()) : base64_decode($request->string('csv')->toString(), true);
        abort_if($csv === false, 422);
        /** @var array<string, string|null> $mapping */
        $mapping = $request->input('mapping', []);
        $result = $importUsers->handle(
            $csv,
            $mapping,
            $request->input('mode') === 'import',
            $request->input('mode') === 'preview' && ! $request->has('csv'),
        );

        if ($request->input('mode') === 'preview') {
            $request->session()->flash('userImportPreview', [...$result, 'csv' => base64_encode($csv)]);

            return to_route('users.import');
        }

        $invalid = array_filter($result['rows'], fn (array $row): bool => $row['errors'] !== []);
        if ($result['rows'] === [] || $invalid !== []) {
            return back()->withErrors(['file' => $result['rows'] === [] ? __('statuses.users_import_empty') : __('statuses.users_import_invalid')])->withInput();
        }

        return to_route('users.index')->with('status', __('statuses.users_import_success', ['count' => count($result['rows'])]));
    }
}
