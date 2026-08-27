<?php

declare(strict_types=1);

it('has marketing home page', function (): void {
    $page = visit('/');

    $page->assertSee('Frostline OPS');
});
