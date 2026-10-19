<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('renders the isolated design preview in development', function (string $environment): void {
    $this->app->instance('env', $environment);
    $this->get('/design-system')->assertOk()->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page->component('design-system'),
    );
})->with(['local', 'testing']);

it('hides the temporary preview outside development', function (string $environment): void {
    $this->app->instance('env', $environment);
    $this->get('/design-system')->assertNotFound();
})->with(['production', 'staging']);
