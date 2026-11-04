<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\FachoraFile;
use App\Models\ServiceJob;
use App\Models\ServiceJobExtra;
use App\Models\ServiceJobOffer;
use App\Services\Fachora\JobService;
use App\Services\Fachora\Presenter;
use App\Services\Fachora\ProtectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use App\Support\Fachora;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * De opdrachtpagina van de klant (/zlecenie/{token}): bevestigen, aanbiedingen
 * vergelijken en kiezen, reserveren via Protect, goedkeuren, geschil, beoordelen.
 * Alles via de geheime link uit de mail; geen inlog.
 */
class JobController extends Controller
{
    public function __construct(private JobService $jobs, private Presenter $presenter, private ProtectService $protect) {}

    public function confirmShow(string $token)
    {
        $job = $this->find($token);
        if (! $job->isPending()) {
            return redirect($job->url());
        }

        return Inertia::render('Fachora/Confirm', [
            'job' => $this->presenter->job($job, 'customer') + [
                'wanted_date_value' => $job->wanted_date?->toDateString(),
            ],
            'token' => $token,
            'advice' => $this->jobs->advice($job),
            'customerChoice' => (bool) config('fachora.customer_choice', false),
            'categories' => Fachora::categoryList(),
            'languages' => Fachora::languageList(),
        ]);
    }

    /** Terug in de aanvraag: aanpassen vóór de bevestiging. */
    public function updatePending(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        if (! $job->isPending()) {
            return redirect($job->url());
        }
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Fachora::categories()))],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'urgency' => ['required', Rule::in((array) config('fachora.urgencies'))],
            'wanted_date' => ['nullable', 'required_if:urgency,date', 'date', 'after_or_equal:today'],
            'time_window' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', Rule::in(array_keys((array) config('fachora.languages')))],
            'languages' => ['nullable', 'array', 'max:5'],
            'languages.*' => ['string', Rule::in(array_keys((array) config('fachora.languages')))],
            'postal_code' => ['nullable', 'regex:/^\d{2}-\d{3}$/'],
            'city' => ['required', 'string', 'max:120'],
        ], [
            'description.min' => __('Beschrijf de klus in minstens een paar woorden.'),
            'postal_code.regex' => __('Een Poolse postcode ziet eruit als 00-000.'),
        ]);
        $this->jobs->updatePending($job, $data);

        return redirect($job->confirmUrl())->with('flash', __('Je aanvraag is aangepast. Controleer hem nog één keer en bevestig.'));
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        if ($job->isPending()) {
            $this->jobs->confirm($job, $request);
        }

        return redirect($job->url())->with('flash', $job->fresh()->isSelecting()
            ? __('Je e-mailadres is bevestigd. Kies nu de bedrijven die je aanvraag krijgen.')
            : __('Je aanvraag is bevestigd. We bieden hem nu aan bij bedrijven in de buurt.'));
    }

    public function show(string $token)
    {
        $job = $this->find($token);
        if ($job->isPending()) {
            return redirect($job->confirmUrl());
        }
        $job->load(['offers.provider', 'extras', 'events', 'review', 'files', 'provider']);

        return Inertia::render('Fachora/Job', [
            'token' => $token,
            'job' => $this->presenter->job($job, 'customer'),
            // De reden van een afzegging is voor het bedrijf en de eigenaar, niet voor de klant.
            'offers' => $job->offers->filter(fn (ServiceJobOffer $o) => $o->status !== 'expired' || $o->hasPrice())
                ->map(fn (ServiceJobOffer $o) => array_diff_key($this->presenter->offer($o), ['decline_reason' => true]))->values()->all(),
            'cancel_free_hours' => (int) config('fachora.cancel_free_hours', 24),
            'provider' => $job->provider?->publicSummary(),
            'files' => $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.job.file', [$token, $f->id])),
            'protect_enabled' => $this->protect->enabled(),
            'auto_accept_days' => (int) config('fachora.auto_accept_days', 5),
            // 1.86: de klant kiest zelf de bedrijven en betaalt een kleine bijdrage per aanvraag.
            // 1.87: gekozen bedrijven uit het register; de klant neemt zelf contact op.
            'direct' => $job->isSelecting() ? [] : $this->jobs->directContacts($job)->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->display_name, 'city' => $p->city,
                'email' => $p->email, 'phone' => $p->phone, 'url' => $p->publicUrl(),
                // 1.96.2: hoofdactiviteit (welk bedrijf past het best) en WhatsApp-nummer
                'pkd_fit' => $p->pkd_fit, 'pkd_main_name' => $p->pkd_main_name,
                'phone_wa' => \App\Support\Fachora::whatsappNumber($p->phone),
                'contacted' => $this->jobs->directContacted($job, $p->id), // 1.96.4: {channels, at} of null
                // 1.98: link naar de deelpagina voor dit bedrijf, en of het die opende / wat het antwoordde
                'share_url' => $this->jobs->shareUrl($job, $p),
                'response' => $this->jobs->directResponse($job, $p->id),
            ])->values()->all(),
            'choice' => $job->isSelecting() ? [
                'candidates' => app(\App\Services\Fachora\ProviderService::class)->choiceCandidates($job)
                    ->map(fn ($p) => array_intersect_key($p->publicSummary(), array_flip(['id', 'name', 'city', 'status', 'verified', 'languages', 'rating', 'rating_count', 'jobs_done', 'url', 'invoice', 'warranty_months']))
                        + ['distance_km' => $p->distance_km, 'pkd_fit' => $p->pkd_fit, 'pkd_main_name' => $p->claimed_at ? null : $p->pkd_main_name])
                    ->values()->all(),
                'max' => (int) config('fachora.max_offers', 5),
                'fee' => $this->jobs->requestFee(),
                'selected' => array_map('intval', (array) $job->selected_providers),
                'sms_required' => $this->jobs->smsRequired($job),
                'searching' => $job->provider_search_at !== null,
                'phone' => $job->customer_phone,
                'code_sent' => $job->phone_code_sent_at !== null && $job->phone_verified_at === null,
            ] : null,
        ]);
    }

    /** De klant kiest 1 tot vijf bedrijven en betaalt (zo nodig) de bijdrage. */
    /** 1.97: de klant maakte zelf een afspraak met een registerbedrijf (prijs en datum optioneel). */
    public function agreeDirect(Request $request, string $token, int $provider)
    {
        $job = $this->find($token);
        $data = $request->validate([
            'price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        try {
            $this->jobs->agreeDirect($job, $provider, $data);
        } catch (\DomainException $e) {
            return back()->withErrors(['direct' => $e->getMessage()]);
        }

        return back()->with('flash', __('Vastgelegd. Rond de opdracht hier af zodra het werk klaar is.'));
    }

    /** 1.97: zelf geregeld werk klaar en in orde. */
    public function finishDirect(string $token)
    {
        $job = $this->find($token);
        try {
            $this->jobs->finishDirect($job);
        } catch (\DomainException $e) {
            return back()->withErrors(['direct' => $e->getMessage()]);
        }

        return back()->with('flash', __('Afgerond. Bedankt dat je Fachora hebt gebruikt.'));
    }

    /** 1.97: opnieuw bedrijven kiezen. */
    public function reopenSelection(string $token)
    {
        $job = $this->find($token);
        try {
            $this->jobs->reopenSelection($job);
        } catch (\DomainException $e) {
            return back()->withErrors(['direct' => $e->getMessage()]);
        }

        return back()->with('flash', __('Kies hieronder opnieuw tot :n bedrijven.', ['n' => (int) config('fachora.max_offers', 5)]));
    }

    /** 1.96.4: de klant benaderde een registerbedrijf zelf; zie JobService::markDirectContacted(). */
    public function markContacted(Request $request, string $token, int $provider)
    {
        $job = $this->find($token);
        $data = $request->validate(['channel' => ['required', Rule::in(['mail', 'copy', 'copy_mail', 'whatsapp', 'phone'])]]);
        $this->jobs->markDirectContacted($job, $provider, $data['channel']);

        return back();
    }

    /** 1.96.3: de klant haalt een registerbedrijf van zijn lijst; zie JobService::removeDirectContact(). */
    public function removeDirect(string $token, int $provider)
    {
        $job = $this->find($token);
        $result = $this->jobs->removeDirectContact($job, $provider);
        if (! $result['removed']) {
            return back();
        }

        return back()->with('flash', $result['deleted']
            ? __('Bedrijf van je lijst gehaald en uit het register van Fachora verwijderd.')
            : __('Bedrijf van je lijst gehaald.'));
    }

    public function select(Request $request, string $token)
    {
        $job = $this->find($token);
        $max = (int) config('fachora.max_offers', 5);
        $rules = ['providers' => ['required', 'array', 'min:1', 'max:' . $max], 'providers.*' => ['integer']];
        $messages = ['providers.required' => __('Kies minstens één bedrijf.'), 'providers.max' => __('Kies maximaal :n bedrijven.', ['n' => $max])];
        if ($this->jobs->requestFee() > 0) {
            // Consumentenrecht: de aanvraag wordt meteen uitgevoerd; daar vraagt de klant uitdrukkelijk om.
            $rules['express'] = ['accepted'];
            $messages['express.accepted'] = __('Vink aan dat je aanvraag meteen mag worden verstuurd.');
        }
        $data = $request->validate($rules, $messages);

        try {
            $url = $this->jobs->selectProviders($job, $data['providers']);
        } catch (\DomainException $e) {
            return back()->withErrors(['providers' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return redirect($job->url())->with('error', __('De betaling kon niet worden gestart. Probeer het zo opnieuw.'));
        }

        return $url ? Inertia::location($url) : redirect($job->url())->with('flash', __('Je aanvraag is verstuurd naar de gekozen bedrijven.'));
    }

    /** Sms-code naar de klant (1.87). */
    public function smsSend(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate(['phone' => ['required', 'string', 'max:40']], ['phone.required' => __('Vul je mobiele nummer in.')]);

        try {
            $sent = $this->jobs->sendPhoneCode($job, $data['phone']);
        } catch (\DomainException $e) {
            return back()->withErrors(['phone' => $e->getMessage()]);
        }

        return $sent ? back()->with('flash', __('Code verstuurd per sms.')) : back()->withErrors(['phone' => __('De sms kon niet worden verstuurd. Controleer het nummer.')]);
    }

    public function smsVerify(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);

        try {
            $ok = $this->jobs->verifyPhone($job, $data['code']);
        } catch (\DomainException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return $ok ? back()->with('flash', __('Telefoonnummer bevestigd. Kies nu de bedrijven.')) : back()->withErrors(['code' => __('Deze code klopt niet.')]);
    }

    /** Terug van Stripe na de bijdrage voor de aanvraag. */
    public function requestPaid(string $token): RedirectResponse
    {
        $job = $this->find($token);
        $sent = $this->jobs->syncRequestPayment($job);

        return redirect($job->url())->with('flash', $sent
            ? __('Bedankt! Je aanvraag is verstuurd naar de gekozen bedrijven.')
            : __('We wachten nog op de bevestiging van de betaling; dit kan een minuut duren.'));
    }

    public function choose(Request $request, string $token, ServiceJobOffer $offer): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate([
            'address_line' => ['required', 'string', 'max:200'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->attempt(fn () => $this->jobs->choose($job, $offer, $data), $job, __('Je keuze is doorgegeven aan het bedrijf.'));
    }

    public function pay(Request $request, string $token)
    {
        $job = $this->find($token);
        // Consumentenrecht: de klant vraagt uitdrukkelijk om uitvoering binnen de bedenktermijn
        // (art. 38 ust. 1 pkt 1 ustawy o prawach konsumenta); zonder dat vinkje geen reservering.
        $request->validate(['express' => ['accepted']], ['express.accepted' => __('Vink aan dat je om uitvoering binnen de bedenktermijn vraagt.')]);
        try {
            return Inertia::location($this->jobs->checkoutUrl($job));
        } catch (\Throwable $e) {
            return redirect($job->url())->with('error', $e->getMessage());
        }
    }

    public function paid(string $token): RedirectResponse
    {
        $job = $this->find($token);
        $this->jobs->syncPayment($job);

        return redirect($job->url())->with('flash', $job->fresh()->isPaid() ? __('Bedankt, het bedrag is gereserveerd.') : __('We wachten nog op de bevestiging van de betaling; dit kan een minuut duren.'));
    }

    public function accept(string $token): RedirectResponse
    {
        $job = $this->find($token);

        return $this->attempt(fn () => $this->jobs->accept($job, 'customer'), $job, __('Bedankt! Het werk is goedgekeurd.'));
    }

    public function dispute(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:3000'], 'photos' => ['nullable', 'array', 'max:5'], 'photos.*' => ['file', 'image', 'max:8192']]);

        return $this->attempt(fn () => $this->jobs->dispute($job, $data['reason'], array_values((array) $request->file('photos', []))), $job, __('Je geschil is geregistreerd.'));
    }

    public function extra(Request $request, string $token, ServiceJobExtra $extra): RedirectResponse
    {
        $job = $this->find($token);
        abort_unless($extra->service_job_id === $job->id, 404);
        $approve = $request->input('decision') === 'approve';

        return $this->attempt(fn () => $this->jobs->decideExtra($extra, $approve), $job, $approve ? __('Meerwerk goedgekeurd.') : __('Meerwerk afgewezen.'));
    }

    public function review(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5'], 'text' => ['nullable', 'string', 'max:2000']]);

        return $this->attempt(fn () => $this->jobs->review($job, (int) $data['rating'], $data['text'] ?? null), $job, __('Bedankt voor je beoordeling.'));
    }

    public function cancel(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        return $this->attempt(fn () => $this->jobs->cancel($job, 'customer', $data['reason'] ?? null), $job, __('De opdracht is geannuleerd.'));
    }

    public function file(string $token, int $file): HttpResponse
    {
        $job = $this->find($token);
        $f = FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $job->id)->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"', 'X-Robots-Tag' => 'noindex']);
    }

    private function find(string $token): ServiceJob
    {
        $job = $this->jobs->findByToken($token);
        abort_unless($job, 404);

        return $job;
    }

    private function attempt(\Closure $action, ServiceJob $job, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (\DomainException $e) {
            return redirect($job->url())->with('error', $e->getMessage());
        }

        return redirect($job->url())->with('flash', $success);
    }
}
