<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('publishes the new design as the homepage and removes the temporary landing page', function (string $environment): void {
    $this->app->instance('env', $environment);
    $this->get('/')->assertOk()->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page->component('marketing/home'),
    );
    $this->get('/landing')->assertNotFound();
})->with(['local', 'testing', 'production', 'staging']);
