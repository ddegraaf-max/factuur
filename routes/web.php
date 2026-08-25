<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\IncassoController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

// ---------- DEMO-OMGEVING ----------
// Elke bezoeker krijgt een eigen sandbox met voorbeeldgegevens en ziet daarin
// de échte applicatie. Zie App\Http\Middleware\DemoMode voor de beveiliging.
Route::get('/demo', [DemoController::class, 'show'])->name('demo');
Route::post('/demo', [DemoController::class, 'start'])
    ->middleware('throttle:10,1')->name('demo.start');
Route::post('/demo/verlaten', [DemoController::class, 'stop'])->name('demo.stop');

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
Route::get('/status', [StatusController::class, 'index'])->name('status');

// ---------- GRATIS TOOLS ----------
// Instapkanaal voor nieuwe klanten: zonder account een factuur maken of iets
// uitrekenen, met onderaan een uitnodiging om EasyInvoice te proberen.
Route::get('/gratis-factuur-maken', [\App\Http\Controllers\FreeInvoiceController::class, 'show'])->name('gratis-factuur');
Route::post('/gratis-factuur-maken', [\App\Http\Controllers\FreeInvoiceController::class, 'download'])
    ->middleware(['throttle:15,1', 'turnstile'])->name('gratis-factuur.download');
Route::view('/btw-calculator', 'marketing.btw-calculator')->name('btw-calculator');
Route::view('/uurtarief-calculator', 'marketing.uurtarief-calculator')->name('uurtarief-calculator');

// ---------- EXTRA MARKETINGPAGINA'S ----------
Route::view('/facturatie-met-ai', 'marketing.facturatie-met-ai')->name('ai');
Route::view('/boekhouders', 'marketing.boekhouders')->name('boekhouders');

// ---------- KENNISBANK ----------
// SEO-artikelen over factureren, btw en betaald krijgen (config/kennisbank.php).
Route::view('/kennisbank', 'marketing.kennisbank')->name('kennisbank');
Route::get('/kennisbank/{slug}', function (string $slug) {
    $articles = config('kennisbank.articles');
    abort_unless(isset($articles[$slug]), 404);

    return view('marketing.kennisbank-artikel', [
        'slug' => $slug,
        'article' => $articles[$slug],
        'articles' => $articles,
    ]);
})->name('kennisbank.artikel');

// ---------- MARKETING-INZICHTEN (intern) ----------
// Bezoekersstatistieken van de publieke pagina's; alleen voor de eigenaar
// (zie MarketingStatsController voor de toegangscheck).
Route::get('/marketing-inzichten', [\App\Http\Controllers\MarketingStatsController::class, 'index'])
    ->middleware('auth')->name('marketing.inzichten');

// ---------- SITEMAP (voor zoekmachines) ----------
// Dynamisch: nieuwe helpartikelen in config/help.php lopen automatisch mee.
Route::get('/sitemap.xml', function () {
    $paths = [
        '/', '/over-ons', '/contact', '/demo', '/veelgestelde-vragen', '/helpcentrum',
        '/roadmap', '/wat-is-nieuw', '/status', '/privacy', '/voorwaarden', '/cookies',
        '/login', '/register',
        '/gratis-factuur-maken', '/btw-calculator', '/uurtarief-calculator',
        '/facturatie-met-ai', '/boekhouders', '/kennisbank',
    ];
    foreach (array_keys(config('help.articles', [])) as $slug) {
        $paths[] = '/helpcentrum/' . $slug;
    }
    foreach (array_keys(config('kennisbank.articles', [])) as $slug) {
        $paths[] = '/kennisbank/' . $slug;
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($paths as $path) {
        $xml .= '  <url><loc>' . e(url($path)) . '</loc></url>' . "\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');
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
})->middleware(['throttle:5,1', 'turnstile'])->name('contact.send');

// ---------- KLANTENPORTAAL ----------
// Voor de klánten van onze gebruikers: facturen online inzien en downloaden.
// Inloggen zonder wachtwoord, maar met twee stappen (e-mail + 6-cijferige
// code) en stevige rate-limiting. Elke inzage wordt gelogd zodat de
// ondernemer ziet of zijn factuur is bekeken.
Route::prefix('portaal')->name('portal.')->group(function () {
    Route::get('/', [PortalAuthController::class, 'show'])->name('login');
    Route::post('/code', [PortalAuthController::class, 'requestCode'])
        ->middleware(['throttle:8,1', 'turnstile'])->name('code.request');

    Route::get('/verificatie', [PortalAuthController::class, 'showVerify'])->name('verify.show');
    Route::post('/verificatie/stuur', [PortalAuthController::class, 'sendCode'])
        ->middleware('throttle:8,1')->name('code.send');
    Route::post('/verificatie', [PortalAuthController::class, 'verify'])
        ->middleware('throttle:10,1')->name('verify');

    Route::post('/uitloggen', [PortalAuthController::class, 'logout'])->name('logout');

    Route::get('/overzicht', [PortalController::class, 'index'])
        ->middleware('portal.verified')->name('index');

    // Beveiligde factuurlink uit de e-mail (verificatie zit in de controller,
    // zodat de codestap de factuurcontext kent).
    Route::get('/f/{token}', [PortalController::class, 'show'])
        ->middleware('throttle:30,1')->name('invoice');
    Route::get('/f/{token}/pdf', [PortalController::class, 'pdf'])
        ->middleware('throttle:15,1')->name('invoice.pdf');
    Route::get('/f/{token}/bijlage/{attachment}', [PortalController::class, 'attachment'])
        ->middleware('throttle:20,1')->name('invoice.attachment');
    // Betaallink: start een iDEAL-betaling via het Mollie-account van de afzender.
    Route::post('/f/{token}/betalen', [PortalController::class, 'pay'])
        ->middleware('throttle:10,1')->name('invoice.pay');

    // Offertes: bekijken, downloaden en digitaal ondertekenen of afwijzen.
    Route::get('/o/{token}', [\App\Http\Controllers\Portal\PortalQuoteController::class, 'show'])
        ->middleware('throttle:30,1')->name('quote');
    Route::get('/o/{token}/pdf', [\App\Http\Controllers\Portal\PortalQuoteController::class, 'pdf'])
        ->middleware('throttle:15,1')->name('quote.pdf');
    Route::post('/o/{token}/onderteken', [\App\Http\Controllers\Portal\PortalQuoteController::class, 'sign'])
        ->middleware('throttle:10,1')->name('quote.sign');
    Route::post('/o/{token}/afwijzen', [\App\Http\Controllers\Portal\PortalQuoteController::class, 'decline'])
        ->middleware('throttle:10,1')->name('quote.decline');
});

// ---------- STRIPE WEBHOOK (publiek, geen CSRF) ----------
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// ---------- MOLLIE WEBHOOK (publiek, geen CSRF) ----------
Route::post('/webhooks/mollie', [\App\Http\Controllers\MollieWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')->name('mollie.webhook');

// ---------- INBOUND MAIL WEBHOOK (publiek, geen CSRF, geheim in de URL) ----------
Route::post('/webhooks/inbound-mail/{secret}', [\App\Http\Controllers\InboundMailController::class, 'handle'])
    ->middleware('throttle:60,1')->name('inbound.mail');

// ---------- CLAUDE-KOPPELING (MCP, publiek, geen CSRF, geheim in de URL) ----------
Route::match(['get', 'post', 'delete'], '/mcp/{token}', [\App\Http\Controllers\McpController::class, 'handle'])
    ->middleware('throttle:120,1')->name('mcp');

// ---------- GUEST AUTH ----------
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware(['throttle:10,1', 'turnstile']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['throttle:5,1', 'turnstile']);

    // 2FA challenge (after credentials but before full auth)
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:5,1');

    // E-mail verification (6-digit code, after registration or unverified login)
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('verification.show');
    Route::post('verify-email', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1')->name('verification.verify');
    Route::post('verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');

    // Teamuitnodiging accepteren (collega of boekhouder)
    Route::get('uitnodiging/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('uitnodiging/{token}', [InvitationController::class, 'accept'])
        ->middleware('throttle:10,1')->name('invitation.accept');

    // Wachtwoord vergeten / opnieuw instellen
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.store');
});

// ---------- AUTHENTICATED ----------
// 'readonly': de boekhouder-rol mag alles inzien maar niets wijzigen.
Route::middleware(['auth', 'readonly'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Abonnement (ook bereikbaar wanneer de proefperiode/het abonnement is verlopen)
    Route::get('abonnement', [BillingController::class, 'show'])->name('billing.show');
    Route::get('abonnement/gelukt', [BillingController::class, 'success'])->name('billing.success');
    // Afrekenen en beheren raakt echt geld: alleen de beheerder.
    Route::post('abonnement/afrekenen', [BillingController::class, 'checkout'])
        ->middleware('role:owner')->name('billing.checkout');
    Route::post('abonnement/beheren', [BillingController::class, 'portal'])
        ->middleware('role:owner')->name('billing.portal');

    // Administraties: meerdere bedrijven onder één inlog. Ook bereikbaar als
    // de proefperiode van de actieve administratie is verlopen — wisselen
    // naar (of aanmaken van) een andere administratie moet altijd kunnen.
    Route::get('administraties', [\App\Http\Controllers\AdministrationController::class, 'index'])->name('administrations.index');
    Route::post('administraties', [\App\Http\Controllers\AdministrationController::class, 'store'])
        ->middleware('throttle:5,1')->name('administrations.store');
    Route::post('administraties/wissel/{company}', [\App\Http\Controllers\AdministrationController::class, 'switch'])->name('administrations.switch');

    // Alles hieronder vereist een actieve proefperiode of abonnement.
    Route::middleware('subscribed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Customers
        Route::resource('customers', CustomerController::class);

        // Peppol: bereikbaarheid checken + facturen afleveren
        Route::post('customers/{customer}/peppol-check', [\App\Http\Controllers\PeppolController::class, 'check'])
            ->middleware('throttle:20,1')->name('customers.peppol.check');
        Route::post('invoices/{invoice}/peppol', [\App\Http\Controllers\PeppolController::class, 'send'])
            ->middleware('throttle:10,1')->name('invoices.peppol.send');

        // KvK-register doorzoeken (voor het klantformulier)
        Route::get('kvk/zoeken', [\App\Http\Controllers\KvkController::class, 'search'])
            ->middleware('throttle:30,1')->name('kvk.search');
        Route::get('kvk/profiel/{kvkNumber}', [\App\Http\Controllers\KvkController::class, 'profile'])
            ->middleware('throttle:30,1')->name('kvk.profile');

    // Products
    Route::resource('products', ProductController::class);

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/ubl', [InvoiceController::class, 'ubl'])->name('invoices.ubl');
    Route::post('invoices/{invoice}/herinnering', [InvoiceController::class, 'remind'])->name('invoices.remind');
    Route::post('invoices/{invoice}/bedankmail', [InvoiceController::class, 'thank'])->name('invoices.thank');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');
    Route::post('invoices/{invoice}/dupliceren', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::patch('invoices/{invoice}/notitie', [InvoiceController::class, 'updateInternalNotes'])->name('invoices.notes.update');
    Route::post('invoices/{invoice}/inplannen', [InvoiceController::class, 'schedule'])->name('invoices.schedule');
    Route::delete('invoices/{invoice}/inplannen', [InvoiceController::class, 'unschedule'])->name('invoices.unschedule');

    // Offertes
    // "Offerte uit tekst" (AI) — vóór de resource, zodat 'herken' niet als
    // {quote}-parameter wordt gelezen.
    Route::post('offertes/herken', [QuoteController::class, 'parseText'])
        ->middleware('throttle:15,1')->name('quotes.parse');
    Route::resource('offertes', QuoteController::class)
        ->parameters(['offertes' => 'quote'])
        ->names('quotes');
    Route::post('offertes/{quote}/versturen', [QuoteController::class, 'send'])->name('quotes.send');
    Route::post('offertes/{quote}/bijlagen', [\App\Http\Controllers\AttachmentController::class, 'storeForQuote'])->name('quotes.attachments.store');
    Route::get('offertes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::post('offertes/{quote}/accepteren', [QuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('offertes/{quote}/afwijzen', [QuoteController::class, 'reject'])->name('quotes.reject');
    Route::post('offertes/{quote}/naar-factuur', [QuoteController::class, 'convert'])->name('quotes.convert');

    // Termijnfacturen: een offerte in delen factureren (30% vooraf, 70% bij oplevering)
    Route::post('offertes/{quote}/termijnen', [\App\Http\Controllers\QuoteInstallmentController::class, 'store'])->name('quotes.installments.store');
    Route::delete('offertes/{quote}/termijnen', [\App\Http\Controllers\QuoteInstallmentController::class, 'destroy'])->name('quotes.installments.destroy');
    Route::post('offertes/{quote}/termijnen/{installment}/factureren', [\App\Http\Controllers\QuoteInstallmentController::class, 'invoice'])->name('quotes.installments.invoice');

    // Urenregistratie: uren schrijven, timer en met één klik factureren
    Route::get('uren', [\App\Http\Controllers\TimeEntryController::class, 'index'])->name('hours.index');
    Route::post('uren', [\App\Http\Controllers\TimeEntryController::class, 'store'])->name('hours.store');
    Route::post('uren/timer/start', [\App\Http\Controllers\TimeEntryController::class, 'timerStart'])->name('hours.timer.start');
    Route::post('uren/timer/stop', [\App\Http\Controllers\TimeEntryController::class, 'timerStop'])->name('hours.timer.stop');
    Route::post('uren/factureren', [\App\Http\Controllers\TimeEntryController::class, 'invoice'])->name('hours.invoice');
    Route::patch('uren/{entry}', [\App\Http\Controllers\TimeEntryController::class, 'update'])->name('hours.update');
    Route::delete('uren/{entry}', [\App\Http\Controllers\TimeEntryController::class, 'destroy'])->name('hours.destroy');

    // Strippenkaarten: vooraf betaalde urenbundels per klant
    Route::post('uren/strippenkaarten', [\App\Http\Controllers\TimeCardController::class, 'store'])->name('timecards.store');
    Route::post('uren/strippenkaarten/{card}/factureren', [\App\Http\Controllers\TimeCardController::class, 'invoice'])->name('timecards.invoice');
    Route::delete('uren/strippenkaarten/{card}', [\App\Http\Controllers\TimeCardController::class, 'destroy'])->name('timecards.destroy');

    // Kilometerregistratie: ritten bijhouden en doorbelasten
    Route::get('ritten', [\App\Http\Controllers\TripController::class, 'index'])->name('trips.index');
    Route::post('ritten', [\App\Http\Controllers\TripController::class, 'store'])->name('trips.store');
    Route::post('ritten/factureren', [\App\Http\Controllers\TripController::class, 'invoice'])->name('trips.invoice');
    Route::patch('ritten/{trip}', [\App\Http\Controllers\TripController::class, 'update'])->name('trips.update');
    Route::delete('ritten/{trip}', [\App\Http\Controllers\TripController::class, 'destroy'])->name('trips.destroy');

    // Terugkerende facturen
    Route::get('terugkerend', [RecurringInvoiceController::class, 'index'])->name('recurring.index');
    Route::post('invoices/{invoice}/recurring', [RecurringInvoiceController::class, 'store'])->name('invoices.recurring.store');
    Route::patch('terugkerend/{recurring}', [RecurringInvoiceController::class, 'update'])->name('recurring.update');
    Route::delete('terugkerend/{recurring}', [RecurringInvoiceController::class, 'destroy'])->name('recurring.destroy');

    // Bank & transacties (afschriften importeren en koppelen)
    Route::get('bank', [\App\Http\Controllers\BankController::class, 'index'])->name('bank.index');
    Route::post('bank/upload', [\App\Http\Controllers\BankController::class, 'upload'])->name('bank.upload');
    Route::post('bank/{transaction}/factuur', [\App\Http\Controllers\BankController::class, 'matchInvoice'])->name('bank.match.invoice');
    Route::post('bank/{transaction}/inkoop', [\App\Http\Controllers\BankController::class, 'matchPurchase'])->name('bank.match.purchase');
    Route::post('bank/{transaction}/negeren', [\App\Http\Controllers\BankController::class, 'ignore'])->name('bank.ignore');
    Route::post('bank/{transaction}/herstel', [\App\Http\Controllers\BankController::class, 'restore'])->name('bank.restore');

    // Inkoopfacturen / crediteuren
    Route::get('inkoop', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('inkoop/nieuw', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::get('inkoop/export', [PurchaseInvoiceController::class, 'csv'])->name('purchases.csv');
    Route::post('inkoop', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');
    // Bon scannen met AI (zonder ANTHROPIC_API_KEY geeft dit een 404)
    Route::post('inkoop/scan', [PurchaseInvoiceController::class, 'scan'])
        ->middleware('throttle:10,1')->name('purchases.scan');
    Route::get('inkoop/{purchase}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');
    Route::get('inkoop/{purchase}/bewerken', [PurchaseInvoiceController::class, 'edit'])->name('purchases.edit');
    Route::patch('inkoop/{purchase}', [PurchaseInvoiceController::class, 'update'])->name('purchases.update');
    Route::delete('inkoop/{purchase}', [PurchaseInvoiceController::class, 'destroy'])->name('purchases.destroy');
    Route::post('inkoop/{purchase}/betaald', [PurchaseInvoiceController::class, 'markPaid'])->name('purchases.paid');
    Route::post('inkoop/{purchase}/heropen', [PurchaseInvoiceController::class, 'reopen'])->name('purchases.reopen');
    Route::post('inkoop/{purchase}/bijlagen', [PurchaseInvoiceController::class, 'storeAttachments'])->name('purchases.attachments.store');

    // Postvak IN: per e-mail aangeleverde bonnen en facturen
    Route::get('inkoop-postvak', [\App\Http\Controllers\PurchaseInboxController::class, 'index'])->name('purchases.inbox.index');
    Route::get('inkoop-postvak/{item}/bestand', [\App\Http\Controllers\PurchaseInboxController::class, 'file'])->name('purchases.inbox.file');
    Route::post('inkoop-postvak/{item}/inboeken', [\App\Http\Controllers\PurchaseInboxController::class, 'book'])->name('purchases.inbox.book');
    Route::post('inkoop-postvak/{item}/afwijzen', [\App\Http\Controllers\PurchaseInboxController::class, 'dismiss'])->name('purchases.inbox.dismiss');
    Route::delete('inkoop-postvak/{item}', [\App\Http\Controllers\PurchaseInboxController::class, 'destroy'])->name('purchases.inbox.destroy');
    Route::post('inkoop-postvak/adres', [\App\Http\Controllers\PurchaseInboxController::class, 'rotateAddress'])->name('purchases.inbox.rotate');

    // Vaste lasten: terugkerende inkoop automatisch inboeken
    Route::get('vaste-lasten', [\App\Http\Controllers\RecurringPurchaseController::class, 'index'])->name('purchases.recurring.index');
    Route::post('vaste-lasten', [\App\Http\Controllers\RecurringPurchaseController::class, 'store'])->name('purchases.recurring.store');
    Route::patch('vaste-lasten/{profile}', [\App\Http\Controllers\RecurringPurchaseController::class, 'update'])->name('purchases.recurring.update');
    Route::delete('vaste-lasten/{profile}', [\App\Http\Controllers\RecurringPurchaseController::class, 'destroy'])->name('purchases.recurring.destroy');
    Route::post('inkoop/{purchase}/terugkerend', [\App\Http\Controllers\RecurringPurchaseController::class, 'createFromPurchase'])->name('purchases.recurring.from');

    // Export naar boekhouder (rapporten: beheerder + boekhouder)
    Route::middleware('role:owner,accountant')->group(function () {
        Route::get('export', [ExportController::class, 'index'])->name('export.index');
        Route::get('export/download', [ExportController::class, 'download'])->name('export.download');
    });

    // Credit notes
    Route::post('invoices/{invoice}/credit', [CreditNoteController::class, 'store'])->name('invoices.credit.store');
    Route::post('invoices/{invoice}/credit/finalize', [CreditNoteController::class, 'finalize'])->name('invoices.credit.finalize');

    // Incasso
    Route::get('incasso', [IncassoController::class, 'index'])->name('incasso.index');
    Route::post('invoices/{invoice}/incasso', [IncassoController::class, 'send'])->name('incasso.send');
    Route::patch('invoices/{invoice}/incasso/phase', [IncassoController::class, 'updatePhase'])->name('incasso.phase');

    // Attachments
    Route::post('invoices/{invoice}/attachments', [AttachmentController::class, 'store'])->name('invoices.attachments.store');
    Route::patch('attachments/{attachment}', [AttachmentController::class, 'update'])->name('attachments.update');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Rapporten: beheerder + boekhouder
    Route::middleware('role:owner,accountant')->group(function () {
        Route::get('stats', [StatsController::class, 'index'])->name('stats.index');

        // Btw-aangifte "aangifte-klaar": rubrieken per tijdvak, betaalgegevens, status
        Route::get('btw', [\App\Http\Controllers\VatController::class, 'index'])->name('vat.index');
        Route::get('btw/pdf', [\App\Http\Controllers\VatController::class, 'pdf'])->name('vat.pdf');
        Route::patch('btw/instellingen', [\App\Http\Controllers\VatController::class, 'updateSettings'])->name('vat.settings');
        Route::patch('btw/{year}/{type}/{period}', [\App\Http\Controllers\VatController::class, 'updateFiling'])
            ->where(['year' => '[0-9]{4}', 'type' => 'quarter|month|year', 'period' => '[0-9]{1,2}'])
            ->name('vat.filing.update');

        // Jaaroverzicht: omzet, kosten en resultaat uit de facturatie
        Route::get('jaaroverzicht', [\App\Http\Controllers\YearReportController::class, 'index'])->name('yearreport.index');
        Route::get('jaaroverzicht/pdf', [\App\Http\Controllers\YearReportController::class, 'pdf'])->name('yearreport.pdf');

        // Cashflow-prognose: verwachte ontvangsten en uitgaven per maand
        Route::get('cashflow', [\App\Http\Controllers\CashflowController::class, 'index'])->name('cashflow.index');

        // Ouderdomsanalyse debiteuren: wie staat er hoe lang open
        Route::get('debiteuren', [\App\Http\Controllers\DebtorAgingController::class, 'index'])->name('aging.index');
    });

    // Settings (alleen de beheerder)
    Route::middleware('role:owner')->group(function () {
        // Koppelingen: de Claude-koppeling (MCP) beheren
        Route::get('settings/koppelingen', [SettingsController::class, 'integrations'])->name('settings.integrations');
        Route::post('settings/koppelingen/claude', [SettingsController::class, 'rotateMcpToken'])
            ->middleware('throttle:10,1')->name('settings.integrations.claude.rotate');
        Route::delete('settings/koppelingen/claude', [SettingsController::class, 'disableMcpToken'])->name('settings.integrations.claude.disable');

        Route::get('settings/company', [SettingsController::class, 'company'])->name('settings.company');
        Route::patch('settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');

        Route::get('settings/brand', [SettingsController::class, 'brand'])->name('settings.brand');
        Route::post('settings/brand', [SettingsController::class, 'updateBrand'])->name('settings.brand.update');
        Route::delete('settings/brand/logo', [SettingsController::class, 'removeLogo'])->name('settings.brand.logo.remove');
        Route::delete('settings/brand/briefpapier', [SettingsController::class, 'removeStationery'])->name('settings.brand.stationery.remove');
        Route::post('settings/brand/herkennen', [SettingsController::class, 'scanBrand'])
            ->middleware('throttle:10,1')->name('settings.brand.scan');

        // Handelsnamen: meerdere huisstijlen onder één administratie
        Route::get('settings/handelsnamen', [\App\Http\Controllers\BrandProfileController::class, 'index'])->name('settings.brands');
        Route::post('settings/handelsnamen', [\App\Http\Controllers\BrandProfileController::class, 'store'])->name('settings.brands.store');
        Route::post('settings/handelsnamen/{profile}', [\App\Http\Controllers\BrandProfileController::class, 'update'])->name('settings.brands.update');
        Route::delete('settings/handelsnamen/{profile}', [\App\Http\Controllers\BrandProfileController::class, 'destroy'])->name('settings.brands.destroy');

        Route::get('settings/numbering', [SettingsController::class, 'numbering'])->name('settings.numbering');
        Route::patch('settings/numbering', [SettingsController::class, 'updateNumbering'])->name('settings.numbering.update');

        Route::get('settings/reminders', [SettingsController::class, 'reminders'])->name('settings.reminders');
        Route::patch('settings/reminders', [SettingsController::class, 'updateReminders'])->name('settings.reminders.update');

        // E-mailteksten: eigen onderwerp en tekst voor factuur- en offertemail
        Route::get('settings/emailteksten', [SettingsController::class, 'emails'])->name('settings.emails');
        Route::patch('settings/emailteksten', [SettingsController::class, 'updateEmails'])->name('settings.emails.update');
        // Voorbeeld van de bedankmail in de browser (tekst uit het formulier; niets wordt opgeslagen)
        Route::get('settings/emailteksten/voorbeeld-bedankmail', [SettingsController::class, 'previewThanks'])->name('settings.emails.preview.thanks');

        // Team: collega's en boekhouder uitnodigen, rollen beheren
        Route::get('settings/team', [TeamController::class, 'index'])->name('settings.team');
        Route::post('settings/team/uitnodigen', [TeamController::class, 'invite'])
            ->middleware('throttle:10,1')->name('settings.team.invite');
        Route::patch('settings/team/{member}', [TeamController::class, 'updateRole'])->name('settings.team.role');
        Route::delete('settings/team/{member}', [TeamController::class, 'removeUser'])->name('settings.team.remove');
        Route::post('settings/team/uitnodiging/{invitation}/opnieuw', [TeamController::class, 'resendInvite'])
            ->middleware('throttle:10,1')->name('settings.team.invite.resend');
        Route::delete('settings/team/uitnodiging/{invitation}', [TeamController::class, 'revokeInvite'])->name('settings.team.invite.revoke');
    });

    // Security / 2FA
    Route::get('settings/security', [SecurityController::class, 'index'])->name('settings.security');
    Route::post('settings/security/setup', [SecurityController::class, 'startSetup'])->name('settings.security.setup');
    Route::post('settings/security/verify', [SecurityController::class, 'verifySetup'])->name('settings.security.verify');
    Route::delete('settings/security', [SecurityController::class, 'disable'])->name('settings.security.disable');
    Route::post('settings/security/recovery', [SecurityController::class, 'regenerateBackupCodes'])->name('settings.security.recovery');
    });
});
