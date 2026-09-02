<?php

declare(strict_types=1);

use App\Actions\SubmitContactInquiry;
use App\Mail\ContactInquiryMail;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Mail;

it('persists a contact inquiry and still emails it', function (): void {
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => 'Twin Cities Snow Co.',
        'fleetSize' => '4-10',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    $inquiry = ContactInquiry::query()->sole();

    expect($inquiry->name)->toBe('Dana Kowalski')
        ->and($inquiry->email)->toBe('dana@example.com')
        ->and($inquiry->company)->toBe('Twin Cities Snow Co.')
        ->and($inquiry->fleetSize)->toBe('4-10')
        ->and($inquiry->message)->toBe('We run six trucks across the metro and need dispatch.');

    Mail::assertQueued(ContactInquiryMail::class, 1);
    Mail::assertQueued(ContactInquiryMail::class, function (ContactInquiryMail $mail): bool {
        if ($mail->envelope()->replyTo[0]->address !== 'dana@example.com') {
            return false;
        }

        return str_contains($mail->render(), 'We run six trucks across the metro');
    });
});

it('persists nothing when the contact inquiry payload is invalid', function (): void {
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'D',
        'email' => 'not-an-email',
        'message' => 'short',
    ]);

    $response->assertUnprocessable();

    $this->assertDatabaseCount(ContactInquiry::class, 0);

    Mail::assertNothingSent();
});

it('keeps the inquiry row even when the mail send fails', function (): void {
    // Simulate a dead SMTP connection: the mailer throws after the action
    // has persisted the lead, and the row must survive.
    Mail::swap(Mockery::mock(\Illuminate\Mail\Mailer::class)->makePartial());
    Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP connection failed'));

    $action = resolve(SubmitContactInquiry::class);

    expect(fn (): \App\Models\ContactInquiry => $action->handle([
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => null,
        'fleetSize' => null,
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]))->toThrow(RuntimeException::class, 'SMTP connection failed');

    $this->assertDatabaseCount(ContactInquiry::class, 1);
});
