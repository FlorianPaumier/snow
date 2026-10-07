<?php

declare(strict_types=1);

use App\Models\User;

it('recognizes the client csv fixture in the import form', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);

    $page->navigate('/clients/import');
    $page->wait(2);
    $page->assertPathIs('/clients/import');

    $csv = base64_encode(file_get_contents(base_path('tests/Fixtures/clients-example.csv')));
    $page->script(sprintf("const input = document.querySelector('#file'); const binary = atob('%s'); const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0)); const transfer = new DataTransfer(); transfer.items.add(new File([bytes], 'clients-example.csv', { type: 'text/csv' })); input.files = transfer.files; input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true }));", $csv));
    $page->wait(1);

    expect($page->script('document.querySelector("#file")?.files.length'))->toBe(1);
    expect($page->script('new FormData(document.querySelector("form"), document.querySelector("button[type=submit]")).get("file")?.name'))->toBe('clients-example.csv');
});

it('recognizes the user csv fixture in the import form', function (): void {
    $admin = User::factory()->admin()->create();
    $page = loginViaUi($admin);

    $page->navigate('/users/import');
    $page->wait(2);
    $page->assertPathIs('/users/import');

    $csv = base64_encode(file_get_contents(base_path('tests/Fixtures/users-example.csv')));
    $page->script(sprintf("const input = document.querySelector('#file'); const binary = atob('%s'); const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0)); const transfer = new DataTransfer(); transfer.items.add(new File([bytes], 'users-example.csv', { type: 'text/csv' })); input.files = transfer.files; input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true }));", $csv));
    $page->wait(1);

    expect($page->script('document.querySelector("#file")?.files.length'))->toBe(1);
    expect($page->script('new FormData(document.querySelector("form"), document.querySelector("button[type=submit]")).get("file")?.name'))->toBe('users-example.csv');
});
