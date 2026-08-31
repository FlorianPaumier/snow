<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create(['name' => 'Billing Org']);
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('renders the billing page for admins', function (): void {
    $this->actingAs($this->admin)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(function ($page): void {
            $page->component('billing/index')
                ->where('organization.name', 'Billing Org')
                ->has('plans', 3);
        });
});

it('forbids billing for non-admin users', function (): void {
    $worker = User::factory()->worker()->forOrganization($this->organization)->create();

    $this->actingAs($worker)
        ->get(route('billing.index'))
        ->assertForbidden();
});

it('requires authentication', function (): void {
    $this->get(route('billing.index'))
        ->assertRedirectToRoute('login');
});

it('rejects an unknown plan at checkout', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'enterprise', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors('plan');
});

it('rejects an invalid billing interval at checkout', function (string $interval): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => $interval])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors('interval');
})->with([
    'quarterly' => ['quarterly'],
    'weekly' => ['weekly'],
    'missing' => [''],
]);

it('requires a plan and an interval at checkout', function (): void {
    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), [])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHasErrors(['plan', 'interval']);
});

it('redirects to billing with a message when the plan has no price configured', function (): void {
    config()->set('plans.starter.prices.monthly', null);

    $this->actingAs($this->admin)
        ->fromRoute('billing.index')
        ->post(route('billing.checkout'), ['plan' => 'starter', 'interval' => 'monthly'])
        ->assertRedirectToRoute('billing.index')
        ->assertSessionHas('status', 'This plan is not configured yet. Please contact support.');
});
