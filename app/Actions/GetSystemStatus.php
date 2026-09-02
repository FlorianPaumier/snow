<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Health probes behind the public status page. Every probe resolves to
 * 'operational' or 'degraded' and never throws, so a broken dependency
 * shows up as a red row instead of taking the status page down with it.
 */
final readonly class GetSystemStatus
{
    /**
     * @return list<array{name: string, status: 'operational'|'degraded'}>
     */
    public function handle(): array
    {
        return [
            $this->probe('Database', fn (): bool => DB::select('select 1') !== []),
            $this->probe('Cache store', fn (): bool => $this->cacheResponds()),
            $this->probe('Job queue', fn (): bool => Queue::size() >= 0),
        ];
    }

    /**
     * @param  callable(): bool  $probe
     * @return array{name: string, status: 'operational'|'degraded'}
     */
    private function probe(string $name, callable $probe): array
    {
        try {
            return [
                'name' => $name,
                'status' => $probe() ? 'operational' : 'degraded',
            ];
        } catch (Throwable) {
            return ['name' => $name, 'status' => 'degraded'];
        }
    }

    private function cacheResponds(): bool
    {
        Cache::store()->put('status:probe', true, now()->addSeconds(10));

        return Cache::store()->get('status:probe') === true;
    }
}
