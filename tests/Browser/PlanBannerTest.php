<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

/**
 * The plan banner appears on every app page while the organization is on
 * its signup trial or on the Free plan. Its "View Pro" button targets the
 * billing page, which is admin-only (can:manage-billing) — a worker
 * following it from the banner would land on a 403. The button is
 * therefore rendered for admins only, while the banner text alone informs
 * everyone else.
 *
 * The browser has no locale cookie, so the French default applies (see
 * SetLocale) — the assertions pin the French banner strings.
 */
it('shows the upgrade button to an admin on a capped plan', function (): void {
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();

    loginViaUi($admin)
        ->navigate('/clients')
        ->wait(2)
        ->assertSee('Voir Pro');
});

it('hides the upgrade button from a worker on a capped plan', function (): void {
    $organization = Organization::factory()->expired()->create();
    User::factory()->admin()->forOrganization($organization)->create();
    $worker = User::factory()->worker()->forOrganization($organization)->create();

    $page = loginViaUi($worker);

    $page->navigate('/clients');
    $page->wait(2);

    // The Free-plan banner text still informs the worker...
    $page->assertSee('Vous êtes sur le forfait gratuit')
        ->assertDontSee('Voir Pro');
});

it('hides the upgrade button from a worker during the trial', function (): void {
    $organization = Organization::factory()->create(); // trial runs
    User::factory()->admin()->forOrganization($organization)->create();
    $worker = User::factory()->worker()->forOrganization($organization)->create();

    $page = loginViaUi($worker);

    $page->navigate('/clients');
    $page->wait(2);

    $page->assertSee('Votre essai gratuit est en cours')
        ->assertDontSee('Voir Pro');
});

it('shows pending billing feedback and prevents duplicate submissions', function (bool $subscribed): void {
    config()->set('plans.pro.prices.annual');
    $organization = Organization::factory()->expired()->create();
    $admin = User::factory()->admin()->forOrganization($organization)->create();
    if ($subscribed) {
        $organization->subscriptions()->create(['type' => 'pro', 'stripe_id' => 'sub_test', 'stripe_status' => 'active',
            'stripe_price' => 'price_test', 'quantity' => 1]);
    }

    $page = loginViaUi($admin)->navigate('/settings/billing');
    if ($subscribed) {
        $page->assertButtonDisabled('button[type="submit"][aria-busy="false"]:disabled')
            ->assertDontSee("S'abonner à l'annuel (300 \$CA)");
    } else {
        $page->assertButtonEnabled('button[type="submit"][aria-busy="false"]');
    }
    $label = $subscribed ? 'Gérer la facturation dans Stripe' : "S'abonner à l'annuel (300 \$CA)";
    $page->assertSee($label);
    $page->script(<<<'JS'
        () => {
            window.billingSubmissionCount = 0;
            const originalOpen = XMLHttpRequest.prototype.open;
            const originalSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.open = function (method, url, ...args) {
                this.isBillingRequest = method.toUpperCase() !== 'GET' && new URL(url, location.href).pathname === location.pathname;
                return originalOpen.call(this, method, url, ...args);
            };
            XMLHttpRequest.prototype.send = function (body) {
                if (!this.isBillingRequest) {
                    return originalSend.call(this, body);
                }
                window.billingSubmissionCount++;
                window.resumeBillingRequest = () => originalSend.call(this, body);
            };
        }
    JS);
    $page->page()->getByRole('button', ['name' => $label, 'exact' => true])->click();
    $page->assertSee('Chargement…')
        ->assertButtonDisabled('button[aria-busy="true"]')
        ->assertVisible('button[aria-busy="true"] [role="status"]');
    $page->script('document.querySelector("button[aria-busy=true]").click()');
    $page->assertScript('window.billingSubmissionCount', 1)->assertNoJavascriptErrors();
    if (! $subscribed) {
        $page->script('window.resumeBillingRequest()');
        $page->assertButtonEnabled('button[aria-busy="false"]')->assertSee($label)->assertDontSee('Chargement…');
    }
})->with(['Pro checkout' => false, 'billing portal' => true]);
