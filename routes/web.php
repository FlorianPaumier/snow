<?php

declare(strict_types=1);

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginChallengeController;
use App\Http\Controllers\MarkPaymentPaidController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UnmarkPaymentPaidController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Marketing (public)...
Route::get('/', fn () => Inertia::render('marketing/home'))->name('home');
Route::get('features', fn () => Inertia::render('marketing/features'))->name('features');
Route::get('pricing', fn () => Inertia::render('marketing/pricing'))->name('pricing');
Route::get('about', fn () => Inertia::render('marketing/about'))->name('about');
Route::get('contact', fn () => Inertia::render('marketing/contact'))->name('contact');
Route::post('contact', [ContactInquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');

Route::middleware(['auth', 'verified:login-challenge.show'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Billing (admin-only subscription management)...
    Route::middleware('can:manage-billing')->group(function (): void {
        Route::get('settings/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('settings/billing', [BillingController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('billing.checkout');
        Route::put('settings/billing', [BillingController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('billing.portal');
    });

    // User Management (admin CRUD)...
    Route::middleware('can:manage-users')->group(function (): void {
        Route::resource('users', UserManagementController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('users');
    });

    // Clients (staff can browse; admins manage)...
    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])
        ->middleware('can:manage-clients')
        ->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])
        ->middleware('can:manage-clients')
        ->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])
        ->middleware('can:manage-clients')
        ->name('clients.edit');
    Route::put('clients/{client}', [ClientController::class, 'update'])
        ->middleware('can:manage-clients')
        ->name('clients.update');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])
        ->middleware('can:manage-clients')
        ->name('clients.destroy');

    // Contracts (staff can browse; admins manage)...
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/create', [ContractController::class, 'create'])
        ->middleware('can:manage-contracts')
        ->name('contracts.create');
    Route::post('contracts', [ContractController::class, 'store'])
        ->middleware('can:manage-contracts')
        ->name('contracts.store');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/{contract}/edit', [ContractController::class, 'edit'])
        ->middleware('can:manage-contracts')
        ->name('contracts.edit');
    Route::put('contracts/{contract}', [ContractController::class, 'update'])
        ->middleware('can:manage-contracts')
        ->name('contracts.update');
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])
        ->middleware('can:manage-contracts')
        ->name('contracts.destroy');

    // Payments (admin: mark installments paid / cancel collection)...
    Route::post('payments/{payment}/mark-paid', MarkPaymentPaidController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.mark-paid');
    Route::post('payments/{payment}/unmark', UnmarkPaymentPaidController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.unmark');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // Language...
    Route::get('settings/language', fn () => Inertia::render('locale/update'))->name('locale.edit');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');

    // Login Challenge (email two-factor)...
    Route::get('login-challenge', [LoginChallengeController::class, 'create'])
        ->name('login-challenge.show');
    Route::post('login-challenge', [LoginChallengeController::class, 'store'])
        ->name('login-challenge.store');
    Route::post('login-challenge/resend', [LoginChallengeController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('login-challenge.resend');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});

// Stripe Webhook (signed by STRIPE_WEBHOOK_SECRET; must bypass CSRF)...
Route::post('stripe/webhook', [StripeWebhookController::class, '__invoke'])
    ->name('cashier.webhook');
