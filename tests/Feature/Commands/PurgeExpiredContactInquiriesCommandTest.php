<?php

declare(strict_types=1);

use App\Models\ContactInquiry;

it('purges only contact inquiries beyond the configured retention period', function (): void {
    config(['privacy.contact_inquiry_retention_months' => 12]);

    $expired = ContactInquiry::factory()->create(['created_at' => now()->subMonths(13)]);
    $retained = ContactInquiry::factory()->create(['created_at' => now()->subMonths(11)]);

    $this->artisan('contact-inquiries:purge-expired')
        ->expectsOutputToContain('Deleted 1 expired contact inquiries.')
        ->assertSuccessful();

    $this->assertModelMissing($expired);
    $this->assertModelExists($retained);
});
