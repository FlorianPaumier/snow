<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

/**
 * Browser coverage for the organization settings page: the ownership
 * transfer posts through a confirmation dialog whose confirm button lives
 * in a Radix portal (outside the <form>), so the submit must stay wired to
 * the form through the button's form attribute.
 */

/**
 * Pick an option in a shadcn/radix Select (not a native <select>). The
 * options render in a portal; Playwright's actionability checks race the
 * portal's animations, so the option is clicked through the DOM directly
 * once the trigger has opened the list.
 */
function pickOrganizationOwner(AwaitableWebpage $page, string $option): void
{
    $page->click('#user_id');
    $page->wait(1);

    $page->script(
        'Array.from(document.querySelectorAll("[role=option]"))'
        .'.find(o => o.innerText.trim() === '.json_encode($option).').click()',
    );
    $page->wait(1);
}

it('transfers ownership through the confirmation dialog', function (): void {
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->forOrganization($organization)->create();
    $organization->update(['owner_id' => $owner->id]);
    $member = User::factory()->forOrganization($organization)->create(['name' => 'Marie Tremblay']);

    $page = loginViaUi($owner);

    $page->navigate('/settings/organization');
    $page->wait(2);

    // Choose the next owner in the radix Select (the input stays on the
    // page; the dialog only confirms)...
    pickOrganizationOwner($page, 'Marie Tremblay');

    // The transfer button opens the confirmation dialog...
    $page->click('@transfer-ownership');
    $page->wait(1);
    $page->assertSee('Transférer la propriété?');

    // The confirm button sits in the portaled DialogContent — it only
    // submits because it is associated with the form via its form attr...
    $page->click('@confirm-transfer-ownership');
    $page->wait(2);

    // The form actually posted (the button reaches its form owner through
    // the DOM portal): ownership moved and both users kept/hold the admin
    // role. Asserting the database here is more robust than following the
    // redirect — the browser server serves each request with a fresh
    // session store, so the post-redirect page view is logged out...
    expect($organization->refresh()->owner_id)->toBe($member->id)
        // The new owner is promoted; the previous owner stays an admin...
        ->and($member->refresh()->role)->toBe(UserRole::ROLE_ADMIN)
        ->and($owner->refresh()->role)->toBe(UserRole::ROLE_ADMIN);
});
