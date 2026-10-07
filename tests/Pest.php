<?php

declare(strict_types=1);

use App\Actions\GenerateOrganizationInviteCode;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

pest()->use(LazilyRefreshDatabase::class)->in('Unit');
pest()->use(RefreshDatabase::class)->in('Browser', 'Feature');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Issue a real organization invite for the email and return its
 * plaintext code, the way the artisan command delivers it. Shared by
 * the invite and registration test files.
 */
function issueInvite(string $email = 'prospect@example.com'): string
{
    return resolve(GenerateOrganizationInviteCode::class)->handle($email);
}

/**
 * A complete, valid registration payload for the given email. When no
 * invite code is given, one is issued for the email first.
 */
function registrationPayload(string $email = 'prospect@example.com', ?string $inviteCode = null): array
{
    return [
        'name' => 'Test User',
        'email' => $email,
        'organization' => 'Test Company',
        'invite_code' => $inviteCode ?? issueInvite($email),
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'terms' => true,
    ];
}

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
function loginViaUi(User $user, string $code = '123456'): AwaitableWebpage
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
        ->assertSee('Entrez le code à 6 chiffres envoyé à '.$user->email)
        ->typeSlowly('#code', $code, 10)
        ->wait(1)
        ->assertPathIs($user->role === UserRole::ROLE_WORKER ? '/my-tours' : '/dashboard')
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
 * @param  PendingAwaitablePage|AwaitableWebpage  $page
 */
function settleForAccessibilityScan($page): void
{
    $page->assertScript('document.querySelector("main") !== null && document.querySelector("h1") !== null');
}
