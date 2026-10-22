<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Models\ServiceReview;
use App\Services\Fachora\JobService;
use App\Services\Fachora\Presenter;
use App\Services\Fachora\ProtectService;
use App\Services\Fachora\VerificationService;
use App\Services\SmsService;
use App\Support\Fachora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Het panel van een aangesloten bedrijf (ingelogd, merk fachora): opdrachten
 * en aanbiedingen, het profiel en de verificatie. De administratie zelf
 * (facturen, offertes) is de gewone app eromheen.
 */
class ProviderController extends Controller
{
    public function __construct(
        private JobService $jobs,
        private Presenter $presenter,
        private VerificationService $verification,
        private ProtectService $protect,
    ) {}

    public function dashboard(Request $request)
    {
        $provider = $this->provider($request);
        if (! $provider) {
            return $this->noProvider();
        }

        $offers = ServiceJobOffer::query()->where('provider_id', $provider->id)->whereIn('status', ['sent', 'responded'])
            ->whereHas('job', fn ($q) => $q->whereIn('status', ['open', 'quoted']))->with('job')->latest('sent_at')->get();
        $active = ServiceJob::query()->where('provider_id', $provider->id)->whereIn('status', ServiceJob::WITH_PROVIDER)->whereNotIn('status', ['accepted'])->latest('agreed_at')->get();
        $closed = ServiceJob::query()->where('provider_id', $provider->id)->whereIn('status', ['accepted', 'cancelled'])->latest('updated_at')->limit(10)->get();

        return Inertia::render('Fachora/Provider/Dashboard', [
            'provider' => $this->presenter->ownProvider($provider),
            'offers' => $offers->map(fn (ServiceJobOffer $o) => $this->presenter->offer($o, false) + ['job' => $this->presenter->job($o->job, 'provider'), 'url' => route('fachora.offers.show', $o)])->values()->all(),
            'active' => $active->map(fn (ServiceJob $j) => $this->presenter->job($j, 'provider'))->values()->all(),
            'closed' => $closed->map(fn (ServiceJob $j) => $this->presenter->job($j, 'provider'))->values()->all(),
            'reviews' => ServiceReview::query()->where('provider_id', $provider->id)->latest()->limit(10)->get()
                ->map(fn (ServiceReview $r) => ['id' => $r->id, 'rating' => $r->rating, 'text' => $r->text, 'reply' => $r->reply, 'at' => $r->created_at?->translatedFormat('j F Y')])->values()->all(),
        ]);
    }

    /* ---------- aanbiedingen ---------- */

    public function showOffer(Request $request, ServiceJobOffer $offer)
    {
        $provider = $this->own($request, $offer->provider_id);
        $job = $offer->job->load(['files', 'extras', 'events', 'review']);

        return Inertia::render('Fachora/Offer', [
            'valid' => true,
            'token' => null,
            'inPanel' => true,
            'offer' => $this->presenter->offer($offer, false),
            'job' => $this->presenter->job($job, 'provider'),
            'provider' => $provider->publicSummary(),
            'files' => $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.jobs.file', [$job, $f->id]), ['photo']),
            'can_respond' => $offer->isOpen(),
            'protect' => $this->protect->enabled() && $provider->protectReady(),
            'fee_percent' => $provider->feePercent(),
            'panel_url' => route('fachora.dashboard'),
            'needs_terms' => $provider->claimed_at === null && $provider->terms_accepted_at === null,
        ]);
    }

    public function respondOffer(Request $request, ServiceJobOffer $offer): RedirectResponse
    {
        $this->own($request, $offer->provider_id);
        $data = OfferController::validateResponse($request);

        return $this->attempt(fn () => $this->jobs->respond($offer, $data), __('Uw prijs is doorgegeven aan de klant.'));
    }

    public function declineOffer(Request $request, ServiceJobOffer $offer): RedirectResponse
    {
        $this->own($request, $offer->provider_id);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        return $this->attempt(fn () => $this->jobs->decline($offer, $data['reason'] ?? null), __('Afgezegd.'), route('fachora.dashboard'));
    }

    /* ---------- opdrachten ---------- */

    public function showJob(Request $request, ServiceJob $job)
    {
        $provider = $this->own($request, $job->provider_id);
        $job->load(['files', 'extras', 'events', 'review', 'chosenOffer']);

        return Inertia::render('Fachora/Provider/Job', [
            'provider' => $provider->publicSummary(),
            'job' => $this->presenter->job($job, 'provider'),
            'offer' => $job->chosenOffer ? $this->presenter->offer($job->chosenOffer, false) : null,
            'files' => $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.jobs.file', [$job, $f->id])),
            'invoice_url' => $job->invoice_id ? route('invoices.show', $job->invoice_id) : null,
            'dispute_hours' => (int) config('fachora.dispute_hours', 48),
        ]);
    }

    public function startJob(Request $request, ServiceJob $job): RedirectResponse
    {
        $this->own($request, $job->provider_id);

        return $this->attempt(fn () => $this->jobs->start($job), __('Gestart. Succes!'));
    }

    public function doneJob(Request $request, ServiceJob $job): RedirectResponse
    {
        $this->own($request, $job->provider_id);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000'], 'photos' => ['nullable', 'array', 'max:8'], 'photos.*' => ['file', 'image', 'max:8192']]);

        return $this->attempt(fn () => $this->jobs->done($job, $data['note'] ?? null, array_values((array) $request->file('photos', []))), __('Gemeld als klaar; de klant krijgt bericht.'));
    }

    public function extraJob(Request $request, ServiceJob $job): RedirectResponse
    {
        $this->own($request, $job->provider_id);
        $data = $request->validate(['description' => ['required', 'string', 'max:200'], 'amount' => ['required', 'numeric', 'min:1', 'max:1000000']]);

        return $this->attempt(fn () => $this->jobs->proposeExtra($job, $data['description'], (float) $data['amount']), __('Voorstel verstuurd naar de klant.'));
    }

    public function disputeReply(Request $request, ServiceJob $job): RedirectResponse
    {
        $this->own($request, $job->provider_id);
        $data = $request->validate(['text' => ['required', 'string', 'min:5', 'max:3000']]);

        return $this->attempt(fn () => $this->jobs->replyDispute($job, $data['text']), __('Uw reactie is vastgelegd.'));
    }

    public function cancelJob(Request $request, ServiceJob $job): RedirectResponse
    {
        $this->own($request, $job->provider_id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        return $this->attempt(fn () => $this->jobs->cancel($job, 'provider', $data['reason']), __('Geannuleerd. Let op: afzeggen zonder goede reden telt mee in uw beoordeling.'), route('fachora.dashboard'));
    }

    public function jobFile(Request $request, ServiceJob $job, int $file): HttpResponse
    {
        $this->own($request, $job->provider_id, allowOffer: true);
        $f = FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $job->id)->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"']);
    }

    public function replyReview(Request $request, ServiceReview $review): RedirectResponse
    {
        $this->own($request, $review->provider_id);
        $data = $request->validate(['text' => ['required', 'string', 'min:2', 'max:2000']]);
        $this->jobs->replyReview($review, $data['text']);

        return back()->with('flash', __('Uw antwoord staat bij de beoordeling.'));
    }

    /* ---------- profiel ---------- */

    public function profile(Request $request)
    {
        $provider = $this->provider($request);
        if (! $provider) {
            return $this->noProvider();
        }

        return Inertia::render('Fachora/Provider/Profile', [
            'provider' => $this->presenter->ownProvider($provider),
            'categories' => Fachora::categoryList(),
            'languages' => Fachora::languageList(),
            'levels' => Fachora::levelList(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:180'],
            'address_line' => ['nullable', 'string', 'max:200'],
            'postal_code' => ['nullable', 'regex:/^\d{2}-\d{3}$/'],
            'city' => ['required', 'string', 'max:120'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(array_keys(Fachora::categories()))],
            'languages' => ['nullable', 'array'],
            'radius_km' => ['required', 'integer', 'min:5', 'max:200'],
            'rates' => ['nullable', 'array'],
            'rates.callout' => ['nullable', 'numeric', 'min:0'],
            'rates.first_hour' => ['nullable', 'numeric', 'min:0'],
            'rates.extra_hour' => ['nullable', 'numeric', 'min:0'],
            'warranty_months' => ['required', 'integer', 'min:0', 'max:60'],
            'invoice_promise' => ['boolean'],
            'protect_opt_in' => ['boolean'],
            'photo' => ['nullable', 'file', 'image', 'max:1024'],
        ]);

        app(\App\Services\Fachora\ProviderService::class)->updateProfile($provider, $data);
        if ($file = $request->file('photo')) {
            $provider->forceFill(['photo_data' => 'data:' . $file->getMimeType() . ';base64,' . base64_encode((string) file_get_contents($file->getRealPath()))])->save();
        }

        return back()->with('flash', __('Profiel opgeslagen.'));
    }

    /* ---------- verificatie ---------- */

    public function verification(Request $request)
    {
        $provider = $this->provider($request);
        if (! $provider) {
            return $this->noProvider();
        }
        $this->verification->runAutomatic($provider);

        return Inertia::render('Fachora/Provider/Verification', [
            'provider' => $this->presenter->ownProvider($provider->fresh()),
            'sms_available' => app(SmsService::class)->configured() && filled($provider->phone),
            'protect_enabled' => $this->protect->enabled(),
            'terms_version' => (string) config('fachora.terms_version'),
            'fee_percent' => (float) config('fachora.fee_percent'),
            'intro_fee_percent' => (float) config('fachora.intro_fee_percent'),
            'intro_days' => (int) config('fachora.intro_days'),
            'insurance_files' => FachoraFile::query()->where('owner_type', 'provider')->where('owner_id', $provider->id)->where('kind', 'insurance')->get()
                ->map(fn (FachoraFile $f) => $f->summary(route('fachora.verification.file', $f->id)))->values()->all(),
        ]);
    }

    public function requestVerification(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);

        return $this->attempt(fn () => $this->verification->request($provider), __('Aangevraagd. Wij controleren de gegevens en nemen contact op.'));
    }

    public function sendPhoneCode(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        try {
            $sent = $this->verification->sendPhoneCode($provider);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($sent ? 'flash' : 'error', $sent ? __('Code verstuurd per sms.') : __('Sms is op deze omgeving niet beschikbaar; wij bellen u voor de controle.'));
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        return $this->verification->verifyPhone($provider, $data['code'])
            ? back()->with('flash', __('Telefoonnummer bevestigd.'))
            : back()->withErrors(['code' => __('De code klopt niet of is verlopen.')]);
    }

    public function acceptTerms(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        $request->validate(['terms' => ['accepted'], 'standard' => ['accepted']], ['terms.accepted' => __('Accepteer het regulamin.'), 'standard.accepted' => __('Accepteer de Fachora Standard.')]);
        $this->verification->acceptTerms($provider);

        return back()->with('flash', __('Voorwaarden geaccepteerd.'));
    }

    public function stripeStart(Request $request)
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        if (! $this->protect->enabled()) {
            return redirect()->route('fachora.verification')->with('error', __('Fachora Protect staat op deze omgeving nog uit.'));
        }
        try {
            $url = $this->protect->onboardingUrl($provider, route('fachora.verification.stripe.return'), route('fachora.verification.stripe'));
        } catch (\Throwable $e) {
            return redirect()->route('fachora.verification')->with('error', __('Stripe is even niet bereikbaar: :error', ['error' => $e->getMessage()]));
        }

        return Inertia::location($url);
    }

    public function stripeReturn(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        try {
            $this->protect->syncAccount($provider);
            $this->verification->refreshBankCheck($provider->fresh());
        } catch (\Throwable $e) {
            return redirect()->route('fachora.verification')->with('error', __('Stripe is even niet bereikbaar: :error', ['error' => $e->getMessage()]));
        }

        return redirect()->route('fachora.verification')->with('flash', $provider->fresh()->stripe_payouts_enabled ? __('Stripe is ingericht: uitbetalingen staan aan.') : __('Stripe heeft nog gegevens nodig; rond de onboarding af.'));
    }

    public function uploadInsurance(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], 'until' => ['nullable', 'date', 'after:today']]);
        $this->verification->uploadInsurance($provider, $request->file('file'), $data['until'] ?? null);

        return back()->with('flash', __('Polis geüpload; wij beoordelen hem.'));
    }

    public function verificationFile(Request $request, int $file): HttpResponse
    {
        $provider = $this->provider($request);
        abort_unless($provider, 404);
        $f = FachoraFile::query()->where('owner_type', 'provider')->where('owner_id', $provider->id)->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"']);
    }

    /* ---------- hulp ---------- */

    /** Het bedrijf van de ingelogde gebruiker (via de gebruiker, anders via de administratie). */
    public static function providerFor(Request $request): ?Provider
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return Provider::query()->where('user_id', $user->id)->first()
            ?: ($user->company_id ? Provider::query()->where('company_id', $user->company_id)->first() : null);
    }

    private function provider(Request $request): ?Provider
    {
        return self::providerFor($request);
    }

    private function own(Request $request, ?int $providerId, bool $allowOffer = false): Provider
    {
        $provider = $this->provider($request);
        abort_unless($provider && $providerId === $provider->id, 403);

        return $provider;
    }

    private function noProvider()
    {
        return Inertia::render('Fachora/Provider/Dashboard', ['provider' => null, 'offers' => [], 'active' => [], 'closed' => [], 'reviews' => []]);
    }

    private function attempt(\Closure $action, string $success, ?string $to = null): RedirectResponse
    {
        try {
            $action();
        } catch (\DomainException $e) {
            return ($to ? redirect($to) : back())->with('error', $e->getMessage());
        }

        return ($to ? redirect($to) : back())->with('flash', $success);
    }
}
