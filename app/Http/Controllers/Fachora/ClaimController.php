<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Provider;
use App\Services\Fachora\ProviderService;
use App\Services\NipService;
use App\Support\Fachora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profiel claimen (/przejmij): met de code van de brief, vanaf het openbare
 * profiel, of als nieuw bedrijf (/dla-firm/zglos). Eerst een mail naar het
 * opgegeven adres; de link daarin maakt de inlog en de administratie.
 */
class ClaimController extends Controller
{
    public function __construct(private ProviderService $providers) {}

    /** Startpagina: code invullen, of het bedrijf dat via ?firma= of ?kod= is meegegeven. */
    public function show(Request $request): Response
    {
        $provider = null;
        if ($request->filled('kod')) {
            $provider = $this->providers->findByClaimCode((string) $request->query('kod'));
        } elseif ($request->filled('firma')) {
            $provider = Provider::query()->where('slug', (string) $request->query('firma'))->first();
        } elseif ($request->filled('nip')) {
            // Eigen profiel opzoeken op NIP (de brief is niet de enige weg).
            $nip = NipService::normalize((string) $request->query('nip'));
            $provider = $nip !== '' ? Provider::query()->where('nip', $nip)->first() : null;
        }

        return Inertia::render('Fachora/Claim', [
            'mode' => 'start',
            'code' => $request->filled('kod') ? strtoupper((string) $request->query('kod')) : null,
            'codeInvalid' => $request->filled('kod') && ! $provider,
            'nipNotFound' => $request->filled('nip') && ! $provider,
            'provider' => $provider?->publicSummary(),
            'alreadyClaimed' => $provider?->claimed_at !== null,
            'categories' => Fachora::categoryList(),
        ]);
    }

    /** Stap 1: e-mailadres en naam → bevestigingsmail. */
    public function start(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('fachora.claim');
        }
        $data = $request->validate([
            'provider_id' => ['required', 'integer'],
            'code' => ['nullable', 'string', 'max:12'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $provider = Provider::query()->find($data['provider_id']);
        abort_unless($provider, 404);
        // Zonder brief-code moet het profiel openbaar vindbaar zijn; met code alleen voor dat bedrijf.
        if (filled($data['code'] ?? null) && $this->providers->findByClaimCode($data['code'])?->id !== $provider->id) {
            return back()->withErrors(['code' => __('Deze code hoort niet bij dit bedrijf.')]);
        }
        if (! $this->providers->mayClaim($request)) {
            return back()->withErrors(['email' => __('Te veel pogingen vanaf deze verbinding. Probeer het over een uur opnieuw.')]);
        }

        try {
            $this->providers->startClaim($provider, $data, $request);
        } catch (\DomainException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
        PageView::milestone($request, 'fachora_claim');

        return redirect()->route('fachora.claim', ['wyslano' => 1, 'firma' => $provider->slug]);
    }

    /** Nieuw bedrijf aanmelden dat nog niet in de database staat. */
    public function apply(Request $request): Response
    {
        return Inertia::render('Fachora/Claim', [
            'mode' => 'apply',
            'categories' => Fachora::categoryList(),
            'legalForms' => \App\Support\Market::companyTypes(),
        ]);
    }

    public function applyStore(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('fachora.apply');
        }
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:13', function ($attr, $value, $fail) {
                if (! NipService::valid(NipService::normalize($value))) {
                    $fail(__('Vul een geldig NIP-nummer in (10 cijfers).'));
                }
            }],
            'company_name' => ['required', 'string', 'max:160'],
            'legal_form' => ['nullable', Rule::in(array_keys(\App\Support\Market::companyTypes()))],
            'address' => ['nullable', 'string', 'max:200'],
            'postal_code' => ['nullable', 'regex:/^\d{2}-\d{3}$/'],
            'city' => ['required', 'string', 'max:120'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(array_keys(Fachora::categories()))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'site' => ['nullable', 'string', 'max:180'],
        ]);
        $data['website'] = $data['site'] ?? null;

        if (! $this->providers->mayClaim($request)) {
            return back()->withErrors(['email' => __('Te veel pogingen vanaf deze verbinding. Probeer het over een uur opnieuw.')]);
        }

        try {
            $provider = $this->providers->createApplication($data, $request);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['nip' => $e->getMessage()]);
        }
        PageView::milestone($request, 'fachora_claim');

        return redirect()->route('fachora.claim', ['wyslano' => 1, 'firma' => $provider->slug]);
    }

    /** Stap 2: de link uit de mail — wachtwoord kiezen. */
    public function confirmShow(string $token): Response
    {
        $provider = $this->providers->findByClaimToken($token);

        return Inertia::render('Fachora/Claim', [
            'mode' => 'confirm',
            'valid' => $provider !== null,
            'token' => $token,
            'provider' => $provider?->publicSummary(),
            'email' => $provider?->claim_email,
            'existingUser' => $provider ? \App\Models\User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $provider->claim_email)])->exists() : false,
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $provider = $this->providers->findByClaimToken($token);
        abort_unless($provider, 404);
        $existing = \App\Models\User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $provider->claim_email)])->exists();
        $data = $request->validate([
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:8', 'max:200'],
            'terms' => ['accepted'],
        ], ['terms.accepted' => __('Accepteer het regulamin om verder te gaan.')]);

        $user = $this->providers->confirmClaim($provider, (string) ($data['password'] ?? ''));
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('fachora.profile')->with('flash', __('Welkom! Vul je profiel aan en vraag daarna het keurmerk Fachora Verified aan.'));
    }

    /** Afmelden voor e-mail (link onderaan elke mail aan een bedrijf). */
    public function optout(string $token): Response
    {
        $provider = preg_match('/^[a-f0-9]{64}$/', $token) ? $this->providers->blockEmail($token) : null;

        return Inertia::render('Fachora/Claim', ['mode' => 'optout', 'valid' => $provider !== null, 'provider' => $provider?->publicSummary()]);
    }
}
