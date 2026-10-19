<?php

declare(strict_types=1);

use App\Actions\GetSystemStatus;
use App\Http\Controllers\AcceptQuoteController;
use App\Http\Controllers\AnonymizeClientController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CatalogServiceController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientRouteNotificationController;
use App\Http\Controllers\ConnectedPaymentController;
use App\Http\Controllers\ConnectWebhookController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclineSeasonRenewalController;
use App\Http\Controllers\DownloadContractPdfController;
use App\Http\Controllers\DownloadPaymentReceiptController;
use App\Http\Controllers\ExportSeasonPaymentsController;
use App\Http\Controllers\FinishTourController;
use App\Http\Controllers\GenerateContractPdfController;
use App\Http\Controllers\GeneratePaymentReceiptController;
use App\Http\Controllers\ImportClientsController;
use App\Http\Controllers\ImportContractsController;
use App\Http\Controllers\ImportUsersController;
use App\Http\Controllers\LoginChallengeController;
use App\Http\Controllers\MarkPaymentPaidController;
use App\Http\Controllers\OptimizeTourController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PrintContractDocumentController;
use App\Http\Controllers\PrintPaymentReceiptController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReplaceQuoteController;
use App\Http\Controllers\SeasonRenewalController;
use App\Http\Controllers\SendContractEmailController;
use App\Http\Controllers\SendPaymentReminderController;
use App\Http\Controllers\SendQuoteController;
use App\Http\Controllers\SendUserPasswordResetController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StartTourController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TourDispatchController;
use App\Http\Controllers\TourNotificationController;
use App\Http\Controllers\TourTemplateController;
use App\Http\Controllers\TransferOrganizationOwnershipController;
use App\Http\Controllers\UnmarkPaymentPaidController;
use App\Http\Controllers\UnsubscribeRouteNotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserInvitationController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\WorkerDashboardController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('pay/{organization}/{payment}', [ConnectedPaymentController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])->name('connected-payments.show');
Route::post('pay/{organization}/{payment}', [ConnectedPaymentController::class, 'store'])
    ->middleware(['signed', 'throttle:10,1'])->name('connected-payments.store');
Route::post('stripe/connect/webhook', ConnectWebhookController::class)->name('stripe-connect.webhook');

Route::get('service-updates/{organization}/{client}/unsubscribe', [UnsubscribeRouteNotificationController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])->name('service-updates.unsubscribe');
Route::post('service-updates/{organization}/{client}/unsubscribe', [UnsubscribeRouteNotificationController::class, 'store'])
    ->middleware(['signed', 'throttle:10,1'])->name('service-updates.unsubscribe.store');

// Marketing (public)...
Route::get('/', fn () => Inertia::render('marketing/home'))->name('home');
Route::get('design-system', function (): \Inertia\Response {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return Inertia::render('design-system');
})->name('design-system');

Route::get('features', fn () => Inertia::render('marketing/features'))->name('features');
Route::get('pricing', fn () => Inertia::render('marketing/pricing'))->name('pricing');
Route::get('about', fn () => Inertia::render('marketing/about'))->name('about');
Route::get('contact', fn () => Inertia::render('marketing/contact'))->name('contact');
Route::post('contact', [ContactInquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');
Route::get('terms', fn () => Inertia::render('marketing/terms'))->name('terms');
Route::get('privacy', fn () => Inertia::render('marketing/privacy'))->name('privacy');
Route::get('status', fn (GetSystemStatus $systemStatus) => Inertia::render('marketing/status', [
    'components' => $systemStatus->handle(),
]))->name('status');

Route::get('sitemap.xml', function () {
    $urls = collect(['/', '/features', '/pricing', '/about', '/contact', '/terms', '/privacy'])
        ->map(fn (string $path): string => '<url><loc>'.e(mb_rtrim(config()->string('app.url'), '/').$path).'</loc></url>')
        ->implode('');

    return Response::make('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>', 200, [
        'Content-Type' => 'application/xml',
    ]);
})->name('sitemap');

Route::get('offers/{organization}/{quote}', [AcceptQuoteController::class, 'show'])->middleware(['signed', 'throttle:60,1'])->name('quotes.review');
Route::post('offers/{organization}/{quote}', [AcceptQuoteController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('quotes.accept');

Route::middleware(['auth', 'verified:verification.notice'])->group(function (): void {
    Route::middleware('can:manage-contracts')->group(function (): void {
        Route::resource('quotes', QuoteController::class)->except('destroy');
        Route::post('quotes/{quote}/send', SendQuoteController::class)->middleware('throttle:10,1')->name('quotes.send');
        Route::post('quotes/{quote}/replace', ReplaceQuoteController::class)->name('quotes.replace');
    });

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('my-tours', [WorkerDashboardController::class, 'index'])->middleware('can:use-worker-dashboard')->name('tours.worker');
    Route::middleware('can:manage-tours')->group(function (): void {
        Route::resource('tour-templates', TourTemplateController::class)->except('show');
        Route::post('tour-dispatches', TourDispatchController::class)->name('tour-dispatches.store');
        Route::get('tours', [TourController::class, 'index'])->name('tours.index');
        Route::get('tours/create', [TourController::class, 'create'])->name('tours.create');
        Route::post('tours', [TourController::class, 'store'])->name('tours.store');
        Route::put('tours/{tour}', [TourController::class, 'update'])->name('tours.update');
        Route::post('tours/{tour}/optimize', OptimizeTourController::class)->middleware('throttle:5,1')->name('tours.optimize');
    });
    Route::get('tours/{tour}', [TourController::class, 'show'])->name('tours.show');
    Route::post('tours/{tour}/start', StartTourController::class)->name('tours.start');
    Route::post('tours/{tour}/finish', FinishTourController::class)->name('tours.finish');

    Route::middleware('can:manage-billing')->group(function (): void {
        Route::get('settings/payments', [StripeConnectController::class, 'show'])->name('stripe-connect.show');
        Route::post('settings/payments', [StripeConnectController::class, 'store'])->middleware('throttle:6,1')->name('stripe-connect.store');
    });
    Route::patch('payments/{payment}/online', [ConnectedPaymentController::class, 'update'])
        ->middleware(['can:manage-contracts', 'throttle:10,1'])->name('connected-payments.update');
    Route::delete('payments/{payment}/online', [ConnectedPaymentController::class, 'destroy'])
        ->middleware(['can:manage-contracts', 'throttle:10,1'])->name('connected-payments.destroy');

    Route::post('tours/{tour}/announcements', [TourNotificationController::class, 'store'])
        ->middleware(['can:manage-tours', 'throttle:10,1'])->name('tour-notifications.store');
    Route::patch('clients/{client}/route-notifications', [ClientRouteNotificationController::class, 'update'])
        ->middleware('can:manage-clients')->name('client-route-notifications.update');

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
        Route::get('users/import', [ImportUsersController::class, 'index'])->name('users.import');
        Route::get('users/import/template', [ImportUsersController::class, 'show'])->name('users.import.template');
        Route::post('users/import', [ImportUsersController::class, 'store'])->name('users.import.store');
        Route::resource('users', UserManagementController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('users');

        Route::post('users/{user}/send-password-reset', SendUserPasswordResetController::class)
            ->name('users.send-password-reset');
    });

    // Clients (staff can browse; admins manage)...
    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])
        ->middleware('can:manage-clients')
        ->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])
        ->middleware('can:manage-clients')
        ->name('clients.store');
    Route::middleware('can:manage-clients')->group(function (): void {
        Route::get('clients/import', [ImportClientsController::class, 'index'])->name('clients.import');
        Route::get('clients/import/template', [ImportClientsController::class, 'show'])->name('clients.import.template');
        Route::post('clients/import', [ImportClientsController::class, 'store'])->name('clients.import.store');
    });
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
    Route::post('clients/{client}/anonymize', AnonymizeClientController::class)
        ->middleware('can:manage-clients')
        ->name('clients.anonymize');

    Route::middleware('can:manage-contracts')->group(function (): void {
        Route::get('contracts/renewals', [SeasonRenewalController::class, 'index'])->name('renewals.index');
        Route::post('contracts/renewals', [SeasonRenewalController::class, 'store'])->name('renewals.store');
        Route::post('quotes/{quote}/decline-renewal', DeclineSeasonRenewalController::class)->name('renewals.decline');
    });

    // Contracts (staff can browse; admins manage)...
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::middleware('can:manage-contracts')->group(function (): void {
        Route::get('contracts/import', [ImportContractsController::class, 'index'])->name('contracts.import');
        Route::get('contracts/import/template', [ImportContractsController::class, 'show'])->name('contracts.import.template');
        Route::post('contracts/import', [ImportContractsController::class, 'store'])->name('contracts.import.store');
    });
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
    Route::get('payments/export', ExportSeasonPaymentsController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.export');
    Route::post('payments/{payment}/mark-paid', MarkPaymentPaidController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.mark-paid');
    Route::post('payments/{payment}/send-reminder', SendPaymentReminderController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.send-reminder');
    Route::post('payments/{payment}/unmark', UnmarkPaymentPaidController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.unmark');

    // Payment receipts (admin: proof of a collected installment)...
    Route::get('payments/{payment}/receipt/print', PrintPaymentReceiptController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.receipt.print');
    Route::post('payments/{payment}/receipt', GeneratePaymentReceiptController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.receipt.generate');
    Route::get('payments/{payment}/receipt', DownloadPaymentReceiptController::class)
        ->middleware('can:manage-contracts')
        ->name('payments.receipt.download');

    Route::post('contracts/send-email', SendContractEmailController::class)->middleware(['can:manage-contracts', 'throttle:10,1'])->name('contracts.send-email');

    // Contract documents (staff can print; PDFs are generated on the queue)...
    Route::get('contracts/{contract}/print', PrintContractDocumentController::class)->name('contracts.print');
    Route::post('contracts/{contract}/pdf', GenerateContractPdfController::class)->name('contracts.pdf.generate');
    Route::get('contracts/{contract}/pdf', DownloadContractPdfController::class)->name('contracts.pdf.download');
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

    Route::middleware(['verified', 'can:manage-contracts'])->group(function (): void {
        Route::get('settings/services', [CatalogServiceController::class, 'index'])->name('catalog-services.index');
        Route::post('settings/services', [CatalogServiceController::class, 'store'])->name('catalog-services.store');
        Route::put('settings/services/{catalogService}', [CatalogServiceController::class, 'update'])->name('catalog-services.update');
        Route::delete('settings/services/{catalogService}', [CatalogServiceController::class, 'destroy'])->name('catalog-services.destroy');
    });

    // Organization (admin settings)...
    Route::middleware('can:manage-organization')->group(function (): void {
        Route::get('settings/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
        Route::put('settings/organization', [OrganizationController::class, 'update'])->name('organization.update');
        Route::put('settings/organization/owner', TransferOrganizationOwnershipController::class)
            ->name('organization.transfer');
    });
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    // User Invitation (staff onboarding)...
    Route::get('accept-invitation/{token}', [UserInvitationController::class, 'create'])
        ->name('user-invitation.create');
    Route::post('accept-invitation', [UserInvitationController::class, 'store'])
        ->name('user-invitation.store');

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
