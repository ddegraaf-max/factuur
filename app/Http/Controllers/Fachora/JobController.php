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

        return Inertia::render('Fachora/Confirm', ['job' => $this->presenter->job($job, 'customer'), 'token' => $token]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $job = $this->find($token);
        if ($job->isPending()) {
            $this->jobs->confirm($job, $request);
        }

        return redirect($job->url())->with('flash', __('Je aanvraag is bevestigd. We bieden hem nu aan bij bedrijven in de buurt.'));
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
        ]);
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
