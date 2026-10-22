<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\FachoraFile;
use App\Models\ServiceJobOffer;
use App\Services\Fachora\JobService;
use App\Services\Fachora\LeadService;
use App\Services\Fachora\Presenter;
use App\Services\Fachora\ProtectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * De aanbieding aan een bedrijf (/oferta/{token}): de klus, de foto's en het
 * formulier voor prijs en beschikbaarheid. Zonder inlog, via de geheime link
 * uit de mail (zoals de prijsaanvraag aan een onderaannemer).
 */
class OfferController extends Controller
{
    public function __construct(private JobService $jobs, private Presenter $presenter, private ProtectService $protect, private LeadService $leads) {}

    public function show(string $token): Response
    {
        $offer = $this->jobs->findOfferByToken($token);
        if (! $offer) {
            return Inertia::render('Fachora/Offer', ['valid' => false]);
        }
        $this->jobs->openOffer($offer);
        $job = $offer->job->load(['files', 'extras', 'events', 'review']);

        return Inertia::render('Fachora/Offer', [
            'valid' => true,
            'token' => $token,
            'offer' => $this->presenter->offer($offer, false),
            'job' => $this->presenter->job($job, 'provider'),
            'provider' => $offer->provider->publicSummary(),
            'files' => $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.offer.file', [$token, $f->id]), ['photo']),
            'can_respond' => $offer->isOpen(),
            'protect' => $this->protect->enabled() && $offer->provider->protectReady(),
            'fee_percent' => $offer->provider->feePercent(),
            'panel_url' => $offer->provider->user_id ? route('fachora.dashboard') : null,
            'needs_terms' => $this->jobs->needsTerms($offer),
            'lead' => $this->leads->summary($offer),
            'unlock_url' => route('fachora.offer.unlock', $token),
        ]);
    }

    /** Ontgrendelen (1.87): gratis welkomstlead meteen, anders via Stripe Checkout. */
    public function unlock(Request $request, string $token)
    {
        $offer = $this->jobs->findOfferByToken($token);
        abort_unless($offer, 404);
        $data = $request->validate(['accept_terms' => ['nullable', 'boolean']]);

        try {
            $this->jobs->acceptTerms($offer, (bool) ($data['accept_terms'] ?? false));
            $url = $this->leads->unlock($offer);
        } catch (\DomainException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('De betaling kon niet worden gestart. Probeer het zo opnieuw.'));
        }

        return $url ? Inertia::location($url) : back()->with('flash', __('Aanvraag ontgrendeld. Geef hieronder uw prijs door.'));
    }

    /** Terug van Stripe na het ontgrendelen. */
    public function unlocked(string $token): RedirectResponse
    {
        $offer = $this->jobs->findOfferByToken($token);
        abort_unless($offer, 404);
        $ok = $this->leads->syncPayment($offer);

        return redirect($offer->url())->with('flash', $ok
            ? __('Bedankt! De aanvraag is ontgrendeld. Geef hieronder uw prijs door.')
            : __('We wachten nog op de bevestiging van de betaling; dit kan een minuut duren.'));
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $offer = $this->jobs->findOfferByToken($token);
        abort_unless($offer, 404);
        $data = self::validateResponse($request);

        try {
            $this->jobs->respond($offer, $data);
        } catch (\DomainException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }

        return back()->with('flash', __('Uw prijs is doorgegeven aan de klant. U hoort het zodra de klant kiest.'));
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $offer = $this->jobs->findOfferByToken($token);
        abort_unless($offer, 404);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $this->jobs->decline($offer, $data['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }

        return back()->with('flash', __('Bedankt voor uw bericht; de klant ziet dat u niet kunt.'));
    }

    public function file(string $token, int $file): HttpResponse
    {
        $offer = $this->jobs->findOfferByToken($token);
        abort_unless($offer, 404);
        $f = FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $offer->service_job_id)->where('kind', 'photo')->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"', 'X-Robots-Tag' => 'noindex']);
    }

    /** Dezelfde regels voor de tokenpagina en het panel. @return array<string, mixed> */
    public static function validateResponse(Request $request): array
    {
        return $request->validate([
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'price_type' => ['required', Rule::in(['fixed', 'hourly', 'estimate'])],
            'callout' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'hourly' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'available_at' => ['nullable', 'date', 'after:now'],
            'available_note' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => ['nullable', 'boolean'],
        ], [
            'price.required' => __('Vul een prijs in (bruto, in złoty).'),
            'available_at.after' => __('Kies een moment in de toekomst.'),
        ]);
    }
}
