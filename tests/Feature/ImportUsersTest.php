<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the user import page for admins and offers a csv template', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('users.import'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('user-management/import'));
    $template = $this->actingAs($admin)->get(route('users.import.template'));

    expect($template->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent())
        ->toContain('name;email;role')
        ->not->toContain(__('statuses.users_import_fields.name'));
});

it('previews and imports users from mapped csv columns using invitation flow', function (): void {
    Event::fake();
    $admin = User::factory()->admin()->create();
    $csv = file_get_contents(base_path('tests/Fixtures/users-example.csv'));
    expect($csv)->not->toBeFalse();
    $mapping = ['name' => 'name', 'email' => 'email', 'role' => 'role'];
    $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $this->actingAs($admin)->post(route('users.import.store'), ['file' => $file, 'mapping' => $mapping, 'mode' => 'preview'])->assertRedirect(route('users.import'));
    $this->get(route('users.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->component('user-management/import')
        ->where('importPreview.rows.0.values.email', 'marie.tremblay@example.com')
        ->has('importPreview.rows', 5)
        ->where('importPreview.rows.0.errors', []));

    $this->post(route('users.import.store'), ['csv' => base64_encode($csv), 'mapping' => $mapping, 'mode' => 'import'])->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'marie.tremblay@example.com')->value('name'))->toBe('Marie Tremblay')
        ->and(User::query()->whereIn('email', [
            'marie.tremblay@example.com',
            'alex.nguyen@example.com',
            'sophie.bernard@example.com',
            'jordan.lee@example.com',
            'camille.roy@example.com',
        ])->count())->toBe(5);
});

it('automatically maps standard user csv headers on the first preview', function (): void {
    $admin = User::factory()->admin()->create();
    $csv = file_get_contents(base_path('tests/Fixtures/users-example.csv'));
    $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

    $this->actingAs($admin)->post(route('users.import.store'), [
        'file' => $file,
        'mapping' => ['name' => '', 'email' => '', 'role' => ''],
        'mode' => 'preview',
    ])->assertRedirect(route('users.import'));

    $this->get(route('users.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('importPreview.mapping.name', 'name')
        ->where('importPreview.mapping.email', 'email')
        ->where('importPreview.rows.0.values.email', 'marie.tremblay@example.com')
        ->where('importPreview.rows.0.errors', []));
});

it('leaves unrecognized user csv headers ready for manual mapping', function (): void {
    $admin = User::factory()->admin()->create();
    $file = UploadedFile::fake()->createWithContent('users.csv', "Full Name;Email address;Access level\nNew Staff;new.staff@example.com;worker\n");

    $this->actingAs($admin)->post(route('users.import.store'), [
        'file' => $file,
        'mapping' => ['name' => '', 'email' => '', 'role' => ''],
        'mode' => 'preview',
    ])->assertRedirect(route('users.import'));

    $this->get(route('users.import'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('importPreview.mapping.name', null)
        ->where('importPreview.mapping.email', null)
        ->where('importPreview.mapping.role', null)
        ->where('importPreview.headers.0', 'Full Name')
        ->where('importPreview.rows.0.values.email', '')
        ->has('importPreview.rows.0.errors'));
});
