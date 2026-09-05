<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AnonymizeClient;
use App\Actions\DeleteClient;
use App\Models\Client;
use App\Models\Contract;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled personal-data retention (Quebec Law 25 s. 22): once the
 * retention window has passed a client's last season, their identity is
 * irreversibly anonymized while the financial skeleton stays for
 * accountability. Clients without any contract are erased entirely —
 * there is nothing to account for.
 */
#[Signature('clients:anonymize-expired {--dry-run : Report what would happen without erasing anything}')]
#[Description('Anonymize clients whose personal data retention window has expired')]
final class AnonymizeExpiredClientsCommand extends Command
{
    public function handle(AnonymizeClient $anonymizeClient, DeleteClient $deleteClient): int
    {
        $retentionYears = config()->integer('privacy.client_retention_years', 6);

        $clients = Client::query()
            ->whereNull('anonymized_at')
            ->with(['contracts:id,client_id,season,created_at'])
            ->lazyById();

        $anonymized = 0;
        $deleted = 0;
        $failed = 0;

        foreach ($clients as $client) {
            if (! $this->isExpired($client, $retentionYears)) {
                continue;
            }

            // One broken client must not stop the retention sweep for every
            // other client: failures are recorded and the loop continues...
            try {
                // Contractless clients carry no accountable record: erase the
                // row entirely. The action re-checks the contract guard...
                $eraseEntirely = $client->contracts->isEmpty();

                if ($this->option('dry-run')) {
                    $this->line($eraseEntirely
                        ? sprintf('Would erase client [%s] (no contract history).', $client->clientNumber)
                        : sprintf('Would anonymize client [%s] (last season ended too long ago).', $client->clientNumber));
                } elseif ($eraseEntirely) {
                    $deleteClient->handle($client);
                } else {
                    $anonymizeClient->handle($client);
                }

                if ($eraseEntirely) {
                    $deleted++;
                } else {
                    $anonymized++;
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->error(sprintf('Failed to process client [%s]: %s', $client->clientNumber, $exception->getMessage()));
                Log::error('Expired-client retention failed for a client.', [
                    'client_id' => $client->id,
                    'client_number' => $client->clientNumber,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info($this->option('dry-run')
            ? sprintf('Dry run: %d would be anonymized, %d would be erased.', $anonymized, $deleted)
            : sprintf('Retention complete: %d anonymized, %d erased, %d failed.', $anonymized, $deleted, $failed));

        return self::SUCCESS;
    }

    /**
     * A client is expired once the retention window has fully passed since
     * their last activity: the latest contract season's end for contracted
     * clients, the record creation date otherwise.
     */
    private function isExpired(Client $client, int $retentionYears): bool
    {
        /** @var CarbonInterface $anchor */
        $anchor = $client->contracts->isEmpty()
            ? $client->created_at
            : $client->contracts
                ->map(fn (Contract $contract): CarbonInterface => $contract->seasonEndDate())
                ->max();

        return $anchor->addYears($retentionYears)->endOfDay()->isPast();
    }
}
