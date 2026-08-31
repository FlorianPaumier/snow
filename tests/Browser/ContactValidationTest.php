<?php

declare(strict_types=1);

/**
 * The contact page is the only page with hand-rolled client-side validation
 * (min lengths, email pattern, touched-based re-validation) and it maps
 * Laravel 422 responses back onto its own field errors.
 *
 * Forms are submitted by pressing Enter inside a filled input rather than
 * clicking the submit button: the click is not reliably dispatched to the
 * React handler on this page, while an Enter submission is native and
 * deterministic.
 */
it('shows inline errors when the contact form is submitted empty', function (): void {
    visit('/contact')
        ->fill('#contact-name', ' ')
        ->keys('#contact-name', ['Enter'])
        ->assertSeeIn('#contact-name-error', 'Please enter your name — at least 2 characters.')
        ->assertSeeIn('#contact-email-error', 'Please enter a valid email address.')
        ->assertSeeIn('#contact-message-error', 'Tell us a little more — at least 10 characters.')
        // The form has noValidate: client-side validation blocks the POST, so
        // no success alert may render...
        ->assertDontSee("Thanks — you're on the list.");
});

it('flags invalid fields with aria attributes', function (): void {
    visit('/contact')
        ->fill('#contact-name', ' ')
        ->keys('#contact-name', ['Enter'])
        ->assertAttribute('#contact-name', 'aria-invalid', 'true')
        ->assertAttribute('#contact-name', 'aria-describedby', 'contact-name-error')
        ->assertAttribute('#contact-email', 'aria-invalid', 'true')
        ->assertAttribute('#contact-message', 'aria-invalid', 'true');
});

it('re-validates a field after it has been touched', function (): void {
    $page = visit('/contact');

    // A one-character name alone does not show an error yet (validation
    // runs on blur / submit, not on change)...
    $page->fill('#contact-name', 'J')
        ->assertMissing('#contact-name-error');

    // Submitting touches every field and reveals the name error...
    $page->keys('#contact-name', ['Enter'])
        ->assertSeeIn('#contact-name-error', 'Please enter your name — at least 2 characters.');

    // Correcting the field clears the error again (change re-validates
    // once the field has been touched)...
    $page->fill('#contact-name', 'Jean Tremblay')
        ->assertMissing('#contact-name-error');
});

it('accepts a valid inquiry', function (): void {
    Mail::fake();

    visit('/contact')
        ->fill('#contact-name', 'Jean Tremblay')
        ->fill('#contact-company', 'Tremblay Déneigement')
        ->fill('#contact-message', 'We manage 40 properties and need routes.')
        ->fill('#contact-email', 'jean@example.com')
        // Enter in a text input submits the form (in a textarea it would
        // just add a newline)...
        ->keys('#contact-email', ['Enter'])
        ->assertSee("Thanks — you're on the list.")
        ->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});

it('is accessible on the contact page, including its error state', function (): void {
    $page = visit('/contact');

    settleForAccessibilityScan($page);

    $page->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();

    $errorPage = visit('/contact')
        ->fill('#contact-name', ' ')
        ->keys('#contact-name', ['Enter'])
        ->assertSeeIn('#contact-name-error', 'Please enter your name — at least 2 characters.');

    settleForAccessibilityScan($errorPage);

    $errorPage->assertNoAccessibilityIssues(3)
        ->assertNoSmoke();
});
