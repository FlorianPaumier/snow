<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a stored document PDF from the default disk, warning instead of
 * throwing when the delete fails.
 *
 * The generate flows short-circuit while a stored file exists, so a
 * silently-failed delete keeps serving a stale document (or, after
 * anonymization, keeps a recoverable copy of the identity). The mutation
 * itself already succeeded at this point, so the operator is warned to
 * purge the orphan rather than the write being rolled back.
 */
final readonly class DeleteStoredDocument
{
    public function handle(string $path): void
    {
        $disk = Storage::disk(config()->string('filesystems.default'));

        if (! $disk->delete($path)) {
            Log::warning('A stored document PDF could not be deleted.', [
                'path' => $path,
            ]);
        }
    }
}
