<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * @property-read Client $client
 * @property string $id
 * @property string $client_id
 * @property string|null $contract_id
 * @property string|null $source_contract_id
 * @property CarbonInterface|null $renewal_reviewed_at
 * @property string $season
 * @property string $status
 * @property list<array{label: string, amount: int}> $services
 * @property array<string, array{amount: int, dueDate: string|null}> $payments
 * @property array{name: string, address: string, email: string}|null $customer
 * @property string|null $organization_name
 * @property CarbonInterface|null $sent_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $accepted_at
 */
#[Fillable(['organization_id', 'client_id', 'season', 'services', 'payments'])]
final class Quote extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    use HasUuids;

    protected $attributes = ['status' => 'draft'];

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewUrl(): string
    {
        return URL::temporarySignedRoute('quotes.review', $this->expires_at ?? abort(410), ['organization' => $this->organization_id, 'quote' => $this->id]);
    }

    public function casts(): array
    {
        return ['services' => 'array', 'payments' => 'array', 'customer' => 'array',
            'sent_at' => 'datetime', 'expires_at' => 'datetime', 'accepted_at' => 'datetime', 'renewal_reviewed_at' => 'datetime'];
    }
}
