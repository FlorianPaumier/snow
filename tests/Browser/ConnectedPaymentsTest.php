<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\User;

it('shows merchant payment settings with a clear configuration state', function (): void {
    config(['services.stripe_connect.key' => '']);
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);
    $page->navigate('/settings/payments')->assertSee('Paiements clients')
        ->assertSee('Frostline ne prend aucune commission')->assertSee('Les paiements en ligne ne sont pas configurés')
        ->assertNoJavascriptErrors();
});

it('shows a private customer payment page without confirming payment from a browser visit', function (): void {
    $payment = Payment::factory()->create(['amount' => 25000]);
    $page = visit($payment->paymentUrl());
    $page->assertSee($payment->organization->name)->assertSee('Le paiement en ligne est indisponible')
        ->assertSee('Ce lien privé expire après 30 jours')->assertNoJavascriptErrors();
    expect($payment->refresh()->paid)->toBeFalse();
});
