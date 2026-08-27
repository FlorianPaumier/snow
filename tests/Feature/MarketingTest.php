<?php

declare(strict_types=1);

use App\Mail\ContactInquiryMail;
use Illuminate\Support\Facades\Mail;

it('renders all public marketing pages', function (string $path, string $component): void {
    $this->get($path)->assertOk()->assertInertia(
        fn ($page) => $page->component($component),
    );
})->with([
    '/' => ['/', 'marketing/home'],
    'features' => ['/features', 'marketing/features'],
    'pricing' => ['/pricing', 'marketing/pricing'],
    'about' => ['/about', 'marketing/about'],
    'contact' => ['/contact', 'marketing/contact'],
]);

it('sends a contact inquiry email', function (): void {
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => 'Twin Cities Snow Co.',
        'fleetSize' => '4-10',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    Mail::assertQueued(ContactInquiryMail::class, 1);
    Mail::assertQueued(ContactInquiryMail::class, function (ContactInquiryMail $mail): bool {
        if ($mail->envelope()->replyTo[0]->address !== 'dana@example.com') {
            return false;
        }

        return str_contains($mail->render(), 'We run six trucks across the metro');
    });
});

it('validates the contact inquiry payload', function (): void {
    Mail::fake();

    $response = $this->postJson('/contact', [
        'name' => 'D',
        'email' => 'not-an-email',
        'message' => 'short',
    ]);

    $response->assertUnprocessable()->assertInvalid(['name', 'email', 'message']);

    Mail::assertNothingSent();
});
