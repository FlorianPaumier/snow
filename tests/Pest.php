<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}

/**
 * Start an authenticated browser session without going through the login
 * form: the login POST would generate (and hash) a fresh random challenge
 * code the test cannot know, so the session is seeded directly instead and
 * the challenge code is pre-hashed with a known value. The mail fake does
 * not apply to the browser's separate HTTP requests either way.
 */
function loginViaUi(User $user, string $code = '123456'): Pest\Browser\Api\AwaitableWebpage
{
    session([
        'login.id' => $user->getKey(),
        'login.remember' => false,
    ]);

    $user->forceFill([
        'login_challenge_code' => Hash::make($code),
        'login_challenge_code_expires_at' => now()->addMinutes(15),
    ])->save();

    return visit('/login-challenge')
        ->assertSee("Enter the 6-digit code we sent to {$user->email}")
        ->typeSlowly('#code', $code, 10)
        ->wait(1)
        ->assertPathIs('/dashboard')
        // Waiting for the app shell (and a beat) guarantees the redirect
        // chain settled before the caller navigates on...
        ->assertVisible('main[data-slot="sidebar-inset"]')
        ->wait(1);
}

/**
 * Make the page ready for an accessibility scan: the document-level axe
 * rules (one main landmark, level-one heading) evaluate against the
 * Inertia-rendered shell, so the shell must exist before axe samples the
 * page — otherwise the scan can race the first paint.
 *
 * @param  Pest\Browser\Api\PendingAwaitablePage|Pest\Browser\Api\AwaitableWebpage  $page
 */
function settleForAccessibilityScan($page): void
{
    $page->assertScript('document.querySelector("main") !== null && document.querySelector("h1") !== null');
}
