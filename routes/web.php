<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncassoController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

// ---------- PUBLIEKE MARKETINGPAGINA'S ----------
Route::view('/veelgestelde-vragen', 'marketing.faq')->name('faq');
Route::view('/wat-is-nieuw', 'marketing.wat-is-nieuw')->name('changelog');
Route::view('/roadmap', 'marketing.roadmap')->name('roadmap');
Route::view('/over-ons', 'marketing.over-ons')->name('over');
Route::view('/helpcentrum', 'marketing.helpcentrum')->name('helpcentrum');
Route::get('/helpcentrum/{slug}', function (string $slug) {
    $articles = config('help.articles');
    abort_unless(isset($articles[$slug]), 404);

    return view('marketing.help-article', [
        'slug' => $slug,
        'article' => $articles[$slug],
        'articles' => $articles,
    ]);
})->name('help.article');
Route::view('/status', 'marketing.status')->name('status');
Route::view('/voorwaarden', 'marketing.voorwaarden')->name('voorwaarden');
Route::view('/privacy', 'marketing.privacy')->name('privacy');
Route::view('/cookies', 'marketing.cookies')->name('cookies');

Route::get('/contact', fn () => view('marketing.contact'))->name('contact');
Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:180'],
        'subject' => ['nullable', 'string', 'max:160'],
        'message' => ['required', 'string', 'max:4000'],
    ]);

    $subject = $data['subject'] ?: 'Nieuw contactbericht via website';
    $body = "Naam: {$data['name']}\nE-mail: {$data['email']}\nOnderwerp: {$subject}\n\n{$data['message']}";

    try {
        \Illuminate\Support\Facades\Mail::raw($body, function ($mail) use ($data, $subject) {
            $mail->to('hallo@easyinvoice.nl')
                ->replyTo($data['email'], $data['name'])
                ->subject('[Contact] '.$subject);
        });
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Versturen contactbericht mislukt', [
            'error' => $e->getMessage(),
            'email' => $data['email'],
        ]);

        return back()
            ->withInput()
            ->with('contact_error', 'Er ging iets mis bij het versturen. Mail ons gerust direct op hallo@easyinvoice.nl.');
    }

    return back()->with('contact_success', 'Bedankt! Je bericht is verstuurd — we reageren binnen één werkdag.');
})->name('contact.send');

// ---------- STRIPE WEBHOOK (publiek, geen CSRF) ----------
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// ---------- GUEST AUTH ----------
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // 2FA challenge (after credentials but before full auth)
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);

    // E-mail verification (6-digit code, after registration or unverified login)
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('verification.show');
    Route::post('verify-email', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// ---------- AUTHENTICATED ----------
Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Abonnement (ook bereikbaar wanneer de proefperiode/het abonnement is verlopen)
    Route::get('abonnement', [BillingController::class, 'show'])->name('billing.show');
    Route::post('abonnement/afrekenen', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('abonnement/gelukt', [BillingController::class, 'success'])->name('billing.success');
    Route::post('abonnement/beheren', [BillingController::class, 'portal'])->name('billing.portal');

    // Alles hieronder vereist een actieve proefperiode of abonnement.
    Route::middleware('subscribed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Customers
        Route::resource('customers', CustomerController::class);

    // Products
    Route::resource('products', ProductController::class);

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');

    // Credit notes
    Route::post('invoices/{invoice}/credit', [CreditNoteController::class, 'store'])->name('invoices.credit.store');
    Route::post('invoices/{invoice}/credit/finalize', [CreditNoteController::class, 'finalize'])->name('invoices.credit.finalize');

    // Incasso
    Route::get('incasso', [IncassoController::class, 'index'])->name('incasso.index');
    Route::post('invoices/{invoice}/incasso', [IncassoController::class, 'send'])->name('incasso.send');
    Route::patch('invoices/{invoice}/incasso/phase', [IncassoController::class, 'updatePhase'])->name('incasso.phase');

    // Attachments
    Route::post('invoices/{invoice}/attachments', [AttachmentController::class, 'store'])->name('invoices.attachments.store');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Stats
    Route::get('stats', [StatsController::class, 'index'])->name('stats.index');

    // Settings
    Route::get('settings/company', [SettingsController::class, 'company'])->name('settings.company');
    Route::patch('settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');

    Route::get('settings/brand', [SettingsController::class, 'brand'])->name('settings.brand');
    Route::post('settings/brand', [SettingsController::class, 'updateBrand'])->name('settings.brand.update');
    Route::delete('settings/brand/logo', [SettingsController::class, 'removeLogo'])->name('settings.brand.logo.remove');

    Route::get('settings/numbering', [SettingsController::class, 'numbering'])->name('settings.numbering');
    Route::patch('settings/numbering', [SettingsController::class, 'updateNumbering'])->name('settings.numbering.update');

    Route::get('settings/reminders', [SettingsController::class, 'reminders'])->name('settings.reminders');
    Route::patch('settings/reminders', [SettingsController::class, 'updateReminders'])->name('settings.reminders.update');

    // Security / 2FA
    Route::get('settings/security', [SecurityController::class, 'index'])->name('settings.security');
    Route::post('settings/security/setup', [SecurityController::class, 'startSetup'])->name('settings.security.setup');
    Route::post('settings/security/verify', [SecurityController::class, 'verifySetup'])->name('settings.security.verify');
    Route::delete('settings/security', [SecurityController::class, 'disable'])->name('settings.security.disable');
    Route::post('settings/security/recovery', [SecurityController::class, 'regenerateBackupCodes'])->name('settings.security.recovery');
    });
});
