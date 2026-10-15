<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Services\Fachora\ConsentPolicy;
use App\Services\Fachora\JobService;
use App\Services\Fachora\LetterService;
use App\Services\Fachora\Notifier;
use App\Services\Fachora\Presenter;
use App\Services\Fachora\ProtectService;
use App\Services\Fachora\ProviderService;
use App\Services\Fachora\VerificationService;
use App\Support\Fachora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Eigenaarspagina's van Fachora (/fachora/admin): de bedrijvendatabase
 * (importeren, brieven, goedkeuren, schorsen), de opdrachten (bellijst,
 * handmatig aanbieden, geschillen beslissen) en de cijfers.
 */
class AdminController extends Controller
{
    public function __construct(
        private ProviderService $providers,
        private VerificationService $verification,
        private JobService $jobs,
        private Presenter $presenter,
        private LetterService $letters,
        private Notifier $notifier,
        private ProtectService $protect,
    ) {}

    /* ---------- bedrijven ---------- */

    public function providers(Request $request): Response
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));
        $category = (string) $request->query('categorie', '');

        $providers = Provider::query()
            ->when($status !== 'all' && in_array($status, Provider::STATUSES, true), fn ($b) => $b->where('status', $status))
            ->when(Fachora::hasCategory($category), fn ($b) => $b->inCategory($category))
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('display_name', 'like', "%{$q}%")->orWhere('legal_name', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%")->orWhere('nip', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'claimed' THEN 1 WHEN 'verified' THEN 2 WHEN 'suspended' THEN 3 WHEN 'unclaimed' THEN 4 ELSE 5 END")
            ->orderByDesc('updated_at')
            ->paginate(50)->withQueryString()
            ->through(fn (Provider $p) => [
                'id' => $p->id, 'name' => $p->display_name, 'city' => $p->city, 'nip' => $p->nip, 'status' => $p->status,
                'categories' => $p->categories(), 'email' => $p->email, 'phone' => $p->phone, 'source' => $p->source,
                'consent' => ConsentPolicy::mayEmail($p), 'consent_reason' => ConsentPolicy::reason($p),
                'rating' => $p->rating_avg, 'jobs_done' => $p->jobs_done, 'geocoded' => $p->lat !== null,
                'updated_at' => $p->updated_at?->translatedFormat('j M'), 'url' => route('fachora.admin.providers.show', $p),
            ]);

        return Inertia::render('Fachora/Admin/Providers', [
            'providers' => $providers,
            'counts' => $this->providers->statusCounts(),
            'filters' => ['status' => $status, 'q' => $q, 'categorie' => $category],
            'categories' => Fachora::categoryList('pl'),
            'letterCandidates' => $this->letters->candidates(null, null, 1000)->count(),
            'ceidg' => app(\App\Services\Fachora\CeidgClient::class)->configured(),
            'jobCounts' => $this->jobCounts(),
        ]);
    }

    public function provider(Provider $provider): Response
    {
        $jobs = ServiceJob::query()->where('provider_id', $provider->id)->latest()->limit(20)->get();
        $offers = ServiceJobOffer::query()->where('provider_id', $provider->id)->with('job')->latest()->limit(20)->get();

        return Inertia::render('Fachora/Admin/Provider', [
            'provider' => $this->presenter->ownProvider($provider) + [
                'notes' => $provider->notes,
                'source' => $provider->source,
                'registry_status' => $provider->registry_status,
                'vat_status' => $provider->vat_status,
                'consent' => ConsentPolicy::mayEmail($provider),
                'consent_reason' => ConsentPolicy::reason($provider),
                'consent_source' => $provider->consent_source,
                'claim_code' => $provider->claim_code,
                'claim_requested_at' => $provider->claim_requested_at?->translatedFormat('j F Y, H:i'),
                'claim_email' => $provider->claim_email,
                'user' => $provider->user ? ['name' => $provider->user->name, 'email' => $provider->user->email] : null,
                'company_id' => $provider->company_id,
                'lat' => $provider->lat, 'lng' => $provider->lng,
                'verified_at' => $provider->verified_at?->translatedFormat('j F Y'),
                'blocked_at' => $provider->blocked_at?->translatedFormat('j F Y'),
            ],
            'files' => FachoraFile::query()->where('owner_type', 'provider')->where('owner_id', $provider->id)->get()
                ->map(fn (FachoraFile $f) => $f->summary(route('fachora.admin.providers.file', [$provider, $f->id])))->values()->all(),
            'jobs' => $jobs->map(fn (ServiceJob $j) => ['id' => $j->id, 'reference' => $j->reference, 'title' => $j->title, 'status' => $j->status, 'status_label' => $j->statusLabel(), 'amount' => $j->agreed_amount, 'url' => route('fachora.admin.jobs.show', $j)])->values()->all(),
            'offers' => $offers->map(fn (ServiceJobOffer $o) => ['id' => $o->id, 'reference' => $o->job?->reference, 'status' => $o->status, 'status_label' => $o->statusLabel(), 'price' => $o->price, 'sent_at' => $o->sent_at?->translatedFormat('j M'), 'url' => $o->job ? route('fachora.admin.jobs.show', $o->job) : null])->values()->all(),
            'checkKinds' => Provider::CHECKS,
            'protect_enabled' => $this->protect->enabled(),
        ]);
    }

    public function approve(Request $request, Provider $provider): RedirectResponse
    {
        return $this->attempt(fn () => $this->verification->approve($provider, $request->user()), __(':name is nu Fachora Verified.', ['name' => $provider->display_name]));
    }

    public function suspend(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'until' => ['nullable', 'date', 'after:today']]);

        return $this->attempt(fn () => $this->verification->suspend($provider, $data['reason'], $data['until'] ?? null, $request->user()), __('Geschorst.'));
    }

    public function block(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->attempt(fn () => $this->verification->block($provider, $data['reason'], $request->user()), __('Geblokkeerd.'));
    }

    public function restore(Provider $provider): RedirectResponse
    {
        return $this->attempt(fn () => $this->verification->restore($provider), __('Teruggezet; beoordeel opnieuw.'));
    }

    public function note(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $provider->forceFill(['notes' => $data['notes'] ?? null])->save();

        return back()->with('flash', __('Aantekening opgeslagen.'));
    }

    public function check(Request $request, Provider $provider): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', Rule::in(Provider::CHECKS)], 'note' => ['nullable', 'string', 'max:300']]);

        return $this->attempt(fn () => $this->verification->manualPass($provider, $data['kind'], $request->user(), $data['note'] ?? null), __('Controle afgevinkt.'));
    }

    public function recheck(Provider $provider): RedirectResponse
    {
        $this->verification->runAutomatic($provider);
        if ($provider->stripe_account_id) {
            try {
                $this->protect->syncAccount($provider);
                $this->verification->refreshBankCheck($provider->fresh());
            } catch (\Throwable $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return back()->with('flash', __('Automatische controles opnieuw gedraaid.'));
    }

    /** Opt-in-uitnodiging: alleen bedrijven met vastgelegde toestemming (ConsentPolicy). */
    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'max:200'], 'ids.*' => ['integer']]);
        $sent = $skipped = 0;
        foreach (Provider::query()->whereIn('id', $data['ids'])->where('status', 'unclaimed')->get() as $provider) {
            if ($this->notifier->provider($provider, 'invite')) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        return back()->with('flash', __(':sent uitnodigingen verstuurd, :skipped overgeslagen (geen toestemming of al geclaimd).', ['sent' => $sent, 'skipped' => $skipped]));
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string', 'max:2000000'],
            'file' => ['nullable', 'file', 'mimes:txt,csv', 'max:10240'],
            'source' => ['nullable', 'string', 'max:60'],
            'consent_source' => ['nullable', 'string', 'max:80'],
        ]);
        $text = (string) ($data['text'] ?? '');
        if ($file = $request->file('file')) {
            $text .= "\n" . file_get_contents($file->getRealPath());
        }
        if (trim($text) === '') {
            return back()->withErrors(['text' => __('Plak minstens één regel of kies een bestand.')]);
        }
        $result = $this->providers->importLines($text, ($data['source'] ?? null) ?: 'import', ($data['consent_source'] ?? null) ?: null);

        return back()->with('flash', __(':created bedrijven toegevoegd, :skipped overgeslagen (bestonden al), :consented met opt-in.', $result));
    }

    public function letters(Request $request): HttpResponse
    {
        $category = Fachora::hasCategory($request->query('categorie')) ? (string) $request->query('categorie') : null;
        $city = trim((string) $request->query('miasto', '')) ?: null;
        $limit = max(1, min((int) config('fachora.letters_per_batch', 100), (int) $request->query('aantal', 50)));
        $providers = $this->letters->candidates($category, $city, $limit);
        abort_if($providers->isEmpty(), 404, __('Geen bedrijven met een postadres in deze selectie.'));

        return response($this->letters->pdf($providers), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="fachora-listy-' . now()->format('Ymd') . '.pdf"',
        ]);
    }

    public function providerFile(Provider $provider, int $file): HttpResponse
    {
        $f = FachoraFile::query()->where('owner_type', 'provider')->where('owner_id', $provider->id)->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"']);
    }

    /* ---------- opdrachten ---------- */

    public function jobs(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');
        $groups = [
            'open' => ['open', 'quoted', 'no_match', 'pending'],
            'active' => ServiceJob::WITH_PROVIDER,
            'disputed' => ['disputed'],
            'closed' => ServiceJob::CLOSED,
            'all' => array_keys(ServiceJob::STATUSES),
        ];
        $jobs = ServiceJob::query()->whereIn('status', $groups[$status] ?? $groups['open'])->with('provider')->latest()
            ->paginate(50)->withQueryString()
            ->through(fn (ServiceJob $j) => [
                'id' => $j->id, 'reference' => $j->reference, 'title' => $j->title, 'category_name' => $j->categoryName('pl'), 'city' => $j->city,
                'status' => $j->status, 'status_label' => $j->statusLabel(), 'language' => $j->language, 'created_at' => $j->created_at?->translatedFormat('j M, H:i'),
                'provider' => $j->provider?->display_name, 'amount' => $j->agreed_amount, 'protect' => $j->protect, 'paid' => $j->isPaid(),
                'offers' => $j->offers()->count(), 'responded' => $j->offers()->whereIn('status', ['responded', 'chosen'])->count(),
                'url' => route('fachora.admin.jobs.show', $j),
            ]);

        return Inertia::render('Fachora/Admin/Jobs', [
            'jobs' => $jobs,
            'status' => $status,
            'counts' => $this->jobCounts(),
            'stats' => $this->stats(),
        ]);
    }

    public function job(ServiceJob $job): Response
    {
        $job->load(['offers.provider', 'extras', 'events', 'review', 'files', 'provider']);
        $claimable = Provider::query()->whereIn('status', ['claimed', 'pending', 'verified'])->inCategory($job->category)
            ->whereNotIn('id', $job->offers->pluck('provider_id'))->orderBy('city')->limit(100)->get();

        return Inertia::render('Fachora/Admin/Job', [
            'job' => $this->presenter->job($job, 'owner'),
            'offers' => $job->offers->map(fn (ServiceJobOffer $o) => $this->presenter->offer($o))->values()->all(),
            'provider' => $job->provider?->publicSummary(),
            'files' => $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.admin.jobs.file', [$job, $f->id])),
            // Bellijst: bedrijven uit het register in de buurt, zonder toestemming voor mail.
            'nearby' => $this->providers->nearbyUnclaimed($job, 15)->map(fn (Provider $p) => [
                'id' => $p->id, 'name' => $p->display_name, 'city' => $p->city, 'phone' => $p->phone, 'website' => $p->website,
                'status' => $p->status, 'distance_km' => $p->distance_km, 'url' => route('fachora.admin.providers.show', $p),
            ])->values()->all(),
            // Bedrijven die wél mail mogen krijgen (geclaimd), om handmatig aan te bieden.
            'claimable' => $claimable->map(fn (Provider $p) => ['id' => $p->id, 'name' => $p->display_name, 'city' => $p->city, 'status' => $p->status, 'consent' => ConsentPolicy::mayEmail($p)])->values()->all(),
            'protect_enabled' => $this->protect->enabled(),
        ]);
    }

    public function offerTo(Request $request, ServiceJob $job): RedirectResponse
    {
        $data = $request->validate(['provider_ids' => ['required', 'array', 'min:1', 'max:20'], 'provider_ids.*' => ['integer']]);

        return $this->attempt(function () use ($job, $data) {
            $n = $this->jobs->dispatch($job, array_map('intval', $data['provider_ids']), 'owner');
            if ($n === 0) {
                throw new \DomainException(__('Geen van deze bedrijven kon worden gemaild (geen toestemming of al aangeboden).'));
            }
        }, __('Aangeboden.'));
    }

    public function resolve(Request $request, ServiceJob $job): RedirectResponse
    {
        $data = $request->validate([
            'resolution' => ['required', Rule::in(['payout', 'refund', 'split'])],
            'payout_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        return $this->attempt(fn () => $this->jobs->resolve($job, $data['resolution'], isset($data['payout_amount']) ? (float) $data['payout_amount'] : null, $data['note'] ?? null, $request->user()->name), __('Geschil beslist.'));
    }

    public function cancelJob(Request $request, ServiceJob $job): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        return $this->attempt(fn () => $this->jobs->cancel($job, 'owner', $data['reason'] ?? null), __('Geannuleerd.'));
    }

    public function acceptJob(ServiceJob $job): RedirectResponse
    {
        return $this->attempt(fn () => $this->jobs->accept($job, 'owner'), __('Goedgekeurd namens de klant.'));
    }

    public function retryPayout(ServiceJob $job): RedirectResponse
    {
        return $this->attempt(function () use ($job) {
            $this->protect->payout($job);
            $this->jobs->log($job, 'payout', 'owner', __('Uitbetaling opnieuw geprobeerd'));
        }, __('Uitbetaling gestart.'));
    }

    public function jobFile(ServiceJob $job, int $file): HttpResponse
    {
        $f = FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $job->id)->findOrFail($file);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"']);
    }

    /* ---------- hulp ---------- */

    /** @return array<string, int> */
    private function jobCounts(): array
    {
        $by = ServiceJob::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all();
        $sum = fn (array $statuses) => (int) array_sum(array_intersect_key($by, array_flip($statuses)));

        return [
            'open' => $sum(['open', 'quoted', 'no_match', 'pending']),
            'active' => $sum(ServiceJob::WITH_PROVIDER),
            'disputed' => $sum(['disputed']),
            'closed' => $sum(ServiceJob::CLOSED),
            'all' => (int) array_sum($by),
        ];
    }

    /** @return array<string, mixed> */
    private function stats(): array
    {
        $accepted = ServiceJob::query()->where('status', 'accepted');

        return [
            'accepted' => (clone $accepted)->count(),
            'revenue' => (float) (clone $accepted)->sum('agreed_amount'),
            'fees' => (float) (clone $accepted)->sum('fee_amount'),
            'month_accepted' => (clone $accepted)->where('accepted_at', '>=', now()->startOfMonth())->count(),
            'month_fees' => (float) (clone $accepted)->where('accepted_at', '>=', now()->startOfMonth())->sum('fee_amount'),
            'verified' => Provider::query()->verified()->count(),
            'pending' => Provider::query()->where('status', 'pending')->count(),
        ];
    }

    private function attempt(\Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (\DomainException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('flash', $success);
    }
}
