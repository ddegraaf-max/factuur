<?php

use App\Http\Controllers\Fachora\AdminController;
use App\Http\Controllers\Fachora\ClaimController;
use App\Http\Controllers\Fachora\IntakeController;
use App\Http\Controllers\Fachora\JobController;
use App\Http\Controllers\Fachora\OfferController;
use App\Http\Controllers\Fachora\ProviderController;
use App\Http\Controllers\Fachora\PublicController;
use App\Support\Market;
use Illuminate\Support\Facades\Route;

/*
 * FACHORA — de marktplaats voor gecontroleerde vakmannen (merk fachora, markt pl).
 *
 * Openbaar: de aanvraag van de klant, zijn opdrachtpagina (geheime link), de
 * aanbieding aan een bedrijf (geheime link), profielen en categoriepagina's,
 * claimen en afmelden. Ingelogd: het panel van het bedrijf en, voor de
 * eigenaar, de beheerpagina's. Onder een ander merk bestaat dit alles niet (404).
 *
 * De pagina's /o-nas, /kontakt, /faq, /regulamin en /polityka-prywatnosci
 * delen hun adres met Lopra Polska en staan in routes/web.php (pl.*).
 */
Route::middleware('brand:fachora')->group(function () {
    $page = fn (string $leaf) => fn () => view(Market::view('fachora.' . $leaf));

    Route::get('/jak-to-dziala', $page('jak-to-dziala'))->name('fachora.how');
    Route::get('/fachora-protect', $page('protect'))->name('fachora.protect');
    Route::get('/standard', $page('standard'))->name('fachora.standard');
    Route::get('/dla-firm', $page('dla-firm'))->name('fachora.for-companies');

    // ---------- klant: aanvraag ----------
    Route::get('/zlecenie/nowe', [IntakeController::class, 'show'])->name('fachora.intake');
    Route::post('/zlecenie', [IntakeController::class, 'store'])->middleware(['throttle:10,1', 'turnstile'])->name('fachora.intake.store');
    Route::post('/zlecenie/ponownie', [IntakeController::class, 'resend'])->middleware('throttle:10,1')->name('fachora.intake.resend');

    // ---------- klant: opdrachtpagina (geheime link) ----------
    Route::prefix('zlecenie/{token}')->where(['token' => '[a-f0-9]{64}'])->name('fachora.job.')->group(function () {
        Route::get('potwierdz', [JobController::class, 'confirmShow'])->name('confirm.show');
        Route::post('potwierdz', [JobController::class, 'confirm'])->middleware('throttle:20,1')->name('confirm');
        Route::get('/', [JobController::class, 'show'])->name('show');
        Route::post('firmy', [JobController::class, 'select'])->middleware('throttle:20,1')->name('select');
        Route::get('oplacono', [JobController::class, 'requestPaid'])->middleware('throttle:20,1')->name('request.paid');
        Route::post('wybierz/{offer}', [JobController::class, 'choose'])->middleware('throttle:20,1')->name('choose');
        Route::post('zaplac', [JobController::class, 'pay'])->middleware('throttle:20,1')->name('pay');
        Route::get('zaplacono', [JobController::class, 'paid'])->name('paid');
        Route::post('akceptuj', [JobController::class, 'accept'])->middleware('throttle:20,1')->name('accept');
        Route::post('spor', [JobController::class, 'dispute'])->middleware('throttle:20,1')->name('dispute');
        Route::post('dodatki/{extra}', [JobController::class, 'extra'])->middleware('throttle:20,1')->name('extra');
        Route::post('opinia', [JobController::class, 'review'])->middleware('throttle:20,1')->name('review');
        Route::post('anuluj', [JobController::class, 'cancel'])->middleware('throttle:20,1')->name('cancel');
        Route::get('plik/{file}', [JobController::class, 'file'])->whereNumber('file')->middleware('throttle:120,1')->name('file');
    });

    // ---------- bedrijf: aanbieding (geheime link) ----------
    Route::prefix('oferta/{token}')->where(['token' => '[a-f0-9]{64}'])->name('fachora.offer.')->group(function () {
        Route::get('/', [OfferController::class, 'show'])->name('show');
        Route::post('/', [OfferController::class, 'respond'])->middleware('throttle:20,1')->name('respond');
        Route::post('odrzuc', [OfferController::class, 'decline'])->middleware('throttle:20,1')->name('decline');
        Route::get('plik/{file}', [OfferController::class, 'file'])->whereNumber('file')->middleware('throttle:120,1')->name('file');
    });

    // ---------- openbaar: profielen en categorieën ----------
    Route::get('/firma/{slug}', [PublicController::class, 'provider'])->where('slug', '[a-z0-9-]+')->name('fachora.provider.show');
    Route::get('/uslugi/{category}', [PublicController::class, 'category'])->where('category', '[a-z-]+')->name('fachora.category');
    Route::get('/uslugi/{category}/{city}', [PublicController::class, 'category'])->where(['category' => '[a-z-]+', 'city' => '[a-z0-9-]+'])->name('fachora.category.city');

    // ---------- bedrijf: profiel claimen, aanmelden, afmelden ----------
    Route::get('/przejmij', [ClaimController::class, 'show'])->name('fachora.claim');
    Route::post('/przejmij', [ClaimController::class, 'start'])->middleware(['throttle:10,1', 'turnstile'])->name('fachora.claim.start');
    Route::get('/przejmij/{token}', [ClaimController::class, 'confirmShow'])->where('token', '[a-f0-9]{64}')->name('fachora.claim.confirm.show');
    Route::post('/przejmij/{token}', [ClaimController::class, 'confirm'])->where('token', '[a-f0-9]{64}')->middleware('throttle:10,1')->name('fachora.claim.confirm');
    Route::get('/dla-firm/zglos', [ClaimController::class, 'apply'])->name('fachora.apply');
    Route::post('/dla-firm/zglos', [ClaimController::class, 'applyStore'])->middleware(['throttle:10,1', 'turnstile'])->name('fachora.apply.store');
    Route::get('/rezygnacja/{token}', [ClaimController::class, 'optout'])->where('token', '[a-f0-9]{64}')->name('fachora.optout');

    // ---------- bedrijf: panel (ingelogd) ----------
    Route::middleware('auth')->prefix('fachora')->name('fachora.')->group(function () {
        Route::get('/', [ProviderController::class, 'dashboard'])->name('dashboard');

        Route::get('oferty/{offer}', [ProviderController::class, 'showOffer'])->name('offers.show');
        Route::post('oferty/{offer}', [ProviderController::class, 'respondOffer'])->name('offers.respond');
        Route::post('oferty/{offer}/odrzuc', [ProviderController::class, 'declineOffer'])->name('offers.decline');

        Route::get('zlecenia/{job}', [ProviderController::class, 'showJob'])->name('jobs.show');
        Route::post('zlecenia/{job}/start', [ProviderController::class, 'startJob'])->name('jobs.start');
        Route::post('zlecenia/{job}/gotowe', [ProviderController::class, 'doneJob'])->name('jobs.done');
        Route::post('zlecenia/{job}/dodatki', [ProviderController::class, 'extraJob'])->name('jobs.extra');
        Route::post('zlecenia/{job}/spor', [ProviderController::class, 'disputeReply'])->name('jobs.dispute');
        Route::post('zlecenia/{job}/anuluj', [ProviderController::class, 'cancelJob'])->name('jobs.cancel');
        Route::get('zlecenia/{job}/plik/{file}', [ProviderController::class, 'jobFile'])->whereNumber('file')->name('jobs.file');
        Route::post('opinie/{review}', [ProviderController::class, 'replyReview'])->name('reviews.reply');

        Route::get('profil', [ProviderController::class, 'profile'])->name('profile');
        Route::post('profil', [ProviderController::class, 'updateProfile'])->name('profile.update');

        Route::get('weryfikacja', [ProviderController::class, 'verification'])->name('verification');
        Route::post('weryfikacja/wniosek', [ProviderController::class, 'requestVerification'])->name('verification.request');
        Route::post('weryfikacja/telefon', [ProviderController::class, 'sendPhoneCode'])->middleware('throttle:5,1')->name('verification.phone');
        Route::post('weryfikacja/telefon/kod', [ProviderController::class, 'verifyPhone'])->middleware('throttle:10,1')->name('verification.phone.verify');
        Route::post('weryfikacja/regulamin', [ProviderController::class, 'acceptTerms'])->name('verification.terms');
        Route::get('weryfikacja/stripe', [ProviderController::class, 'stripeStart'])->name('verification.stripe');
        Route::get('weryfikacja/stripe/powrot', [ProviderController::class, 'stripeReturn'])->name('verification.stripe.return');
        Route::post('weryfikacja/ubezpieczenie', [ProviderController::class, 'uploadInsurance'])->name('verification.insurance');
        Route::get('weryfikacja/plik/{file}', [ProviderController::class, 'verificationFile'])->whereNumber('file')->name('verification.file');

        // ---------- eigenaar ----------
        Route::middleware('owner')->prefix('admin')->name('admin.')->group(function () {
            Route::get('firmy', [AdminController::class, 'providers'])->name('providers');
            Route::post('firmy/import', [AdminController::class, 'import'])->name('import');
            Route::post('firmy/ceidg', [AdminController::class, 'importCeidg'])->name('ceidg');
            Route::get('firmy/listy.pdf', [AdminController::class, 'letters'])->name('letters');
            Route::post('firmy/zaproszenia', [AdminController::class, 'invite'])->name('invite');
            Route::get('firmy/{provider}', [AdminController::class, 'provider'])->name('providers.show');
            Route::get('firmy/{provider}/plik/{file}', [AdminController::class, 'providerFile'])->whereNumber('file')->name('providers.file');
            Route::post('firmy/{provider}/zatwierdz', [AdminController::class, 'approve'])->name('providers.approve');
            Route::post('firmy/{provider}/zawies', [AdminController::class, 'suspend'])->name('providers.suspend');
            Route::post('firmy/{provider}/zablokuj', [AdminController::class, 'block'])->name('providers.block');
            Route::post('firmy/{provider}/przywroc', [AdminController::class, 'restore'])->name('providers.restore');
            Route::post('firmy/{provider}/notatka', [AdminController::class, 'note'])->name('providers.note');
            Route::post('firmy/{provider}/kontrola', [AdminController::class, 'check'])->name('providers.check');
            Route::post('firmy/{provider}/sprawdz', [AdminController::class, 'recheck'])->name('providers.recheck');

            Route::get('zlecenia', [AdminController::class, 'jobs'])->name('jobs');
            Route::get('zlecenia/{job}', [AdminController::class, 'job'])->name('jobs.show');
            Route::get('zlecenia/{job}/plik/{file}', [AdminController::class, 'jobFile'])->whereNumber('file')->name('jobs.file');
            Route::post('zlecenia/{job}/zaoferuj', [AdminController::class, 'offerTo'])->name('jobs.offer');
            Route::post('zlecenia/{job}/rozstrzygnij', [AdminController::class, 'resolve'])->name('jobs.resolve');
            Route::post('zlecenia/{job}/anuluj', [AdminController::class, 'cancelJob'])->name('jobs.cancel');
            Route::post('zlecenia/{job}/akceptuj', [AdminController::class, 'acceptJob'])->name('jobs.accept');
            Route::post('zlecenia/{job}/wyplata', [AdminController::class, 'retryPayout'])->name('jobs.payout');
        });
    });
});
