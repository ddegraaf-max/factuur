<?php

namespace App\Http\Controllers;

use App\Services\ResendScheduler;
use App\Services\StripeService;
use App\Support\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private ResendScheduler $resend,
    ) {
    }

    public function show(Request $request)
    {
        $company = $request->user()->company;

        // AI-verbruik van deze administratie (voor de teller op de pagina).
        $usage = $company->aiUsageThisMonth();
        $aiUsage = [
            'month_label' => now()->translatedFormat('F Y'),
            'receipt_scans' => $usage['receipt_scans'],
            'quote_parses' => $usage['quote_parses'],
            'total' => $usage['total'],
            'limit' => $company->aiMonthlyLimit(),
            'has_ai' => $company->hasAiAccess(),
        ];

        // Platformoverzicht (alleen voor het vrijgestelde beheerdersaccount):
        // totaal AI-gebruik en de administraties die het meest scannen — de
        // cijfers om een fair-use-grens en de marge op Slim te bewaken.
        $platformAi = null;
        if ($company->is_exempt) {
            $months = collect(range(0, 2))->map(function ($i) {
                $start = now()->subMonthsNoOverflow($i)->startOfMonth();
                $rows = \App\Models\AiUsageEvent::whereBetween('created_at', [$start, (clone $start)->endOfMonth()])
                    ->selectRaw('kind, COUNT(*) AS c')
                    ->groupBy('kind')
                    ->pluck('c', 'kind');

                return [
                    'label' => $start->translatedFormat('F Y'),
                    'receipt_scans' => (int) ($rows['receipt_scan'] ?? 0),
                    'quote_parses' => (int) ($rows['quote_parse'] ?? 0),
                    'total' => (int) $rows->sum(),
                ];
            })->values();

            $top = \App\Models\AiUsageEvent::where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('company_id, COUNT(*) AS c')
                ->groupBy('company_id')
                ->orderByDesc('c')
                ->limit(10)
                ->get();
            $names = \App\Models\Company::whereIn('id', $top->pluck('company_id'))->pluck('name', 'id');

            $platformAi = [
                'months' => $months,
                'top' => $top->map(fn ($r) => [
                    'name' => $names[$r->company_id] ?? __('Administratie #:id', ['id' => $r->company_id]),
                    'total' => (int) $r->c,
                ])->values(),
            ];
        }

        // Naam, prijs en btw-noot per markt: Nederland (EUR, 21%) of Polen (PLN, 23% VAT).
        // De taglines en kenmerken zijn vertaalsleutels.
        $pl = Market::isPl();
        $aiLimit = (int) config('services.anthropic.monthly_limit', 250);

        return Inertia::render('Billing/Index', [
            'subscription' => $company->subscriptionSummary(),
            'ai_usage' => $aiUsage,
            'platform_ai' => $platformAi,
            'plans' => [
                [
                    'key' => 'basis',
                    'name' => $pl ? 'Podstawowy' : __('Basis'),
                    'amount' => $pl ? '49' : '10',
                    'vat_note' => $pl ? 'netto · 60,27 zł brutto (23% VAT)' : __('Excl. 21% btw · € 12,10 incl. btw'),
                    'tagline' => __('Alles om te factureren en je administratie bij te houden.'),
                    'features' => [
                        __('Onbeperkt facturen, offertes, klanten en producten'),
                        __('BTW-overzicht, herinneringen, incasso en klantenportaal'),
                        __('Inkoop, uren, ritten en jaaroverzicht'),
                        __('Digitaal visitekaartje en eigen website in je huisstijl'),
                        __('Maandelijks opzegbaar'),
                        __('Optioneel: automatische bankkoppeling voor :price per rekening per maand', ['price' => money((float) config('services.ponto.account_price', 5))]),
                    ],
                    'available' => $this->stripe->configured(),
                ],
                [
                    'key' => 'slim',
                    'name' => $pl ? 'Smart' : __('Slim'),
                    'amount' => $pl ? '79' : '17,50',
                    'vat_note' => $pl ? 'netto · 97,17 zł brutto (23% VAT)' : __('Excl. 21% btw · € 21,18 incl. btw'),
                    'tagline' => __('Alles uit Basis, plus de AI-assistent die werk uit handen neemt.'),
                    'features' => array_values(array_filter([
                        __('Alles uit Basis (bankkoppeling optioneel, per rekening)'),
                        __('Scan & herken: bonnen en inkoopfacturen automatisch ingevuld'),
                        __('Postvak IN met automatische boekingsvoorstellen'),
                        __('Offerte uit tekst: plak je conceptofferte, het formulier vult zich in'),
                        __('Claude-koppeling: maak offertes en facturen rechtstreeks vanuit je Claude-gesprek'),
                        __('Huisstijl herkennen met AI: upload je huisstijlgids en alles staat goed'),
                        __('Huisstijl en websitetekst laten ontwerpen door AI — ideaal als starter'),
                        $aiLimit > 0 ? __('Ruime fair-use: :count AI-acties per maand', ['count' => $aiLimit]) : null,
                    ])),
                    'available' => $this->stripe->slimConfigured(),
                ],
            ],
            'stripeReady' => $this->stripe->configured(),
        ]);
    }

    public function checkout(Request $request)
    {
        $company = $request->user()->company;

        $plan = $request->input('plan') === 'slim' ? 'slim' : 'basis';

        if (! $this->stripe->configured() || ($plan === 'slim' && ! $this->stripe->slimConfigured())) {
            return back()->with('error', __('Betalen is nog niet beschikbaar. Probeer het later opnieuw.'));
        }

        if ($company->is_exempt) {
            return back()->with('flash', __('Dit account is vrijgesteld — je hoeft geen abonnement af te sluiten.'));
        }

        // Wordt er tijdens de proefperiode afgesloten, laat Stripe dan pas
        // afschrijven aan het einde van de proef (Stripe vereist >48u vooruit).
        $trialEnd = null;
        if ($company->onTrial() && $company->trial_ends_at?->greaterThan(now()->addHours(49))) {
            $trialEnd = $company->trial_ends_at->timestamp;
        }

        try {
            $url = $this->stripe->createCheckoutSession(
                $company,
                route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                route('billing.show'),
                $trialEnd,
                $plan,
            );
        } catch (\Throwable $e) {
            Log::error('Stripe checkout aanmaken mislukt', ['error' => $e->getMessage(), 'company' => $company->id]);

            return back()->with('error', __('Er ging iets mis bij het starten van de betaling. Probeer het opnieuw.'));
        }

        // Externe redirect naar de Stripe-checkout (werkt ook met Inertia).
        return Inertia::location($url);
    }

    public function success(Request $request)
    {
        $company = $request->user()->company;
        $sessionId = $request->query('session_id');

        // Activeer direct (i.p.v. wachten op de webhook) voor een vlotte ervaring.
        if ($sessionId && $this->stripe->configured()) {
            try {
                $session = $this->stripe->retrieveSession($sessionId);
                if ($session && ($session['client_reference_id'] ?? null) == $company->id) {
                    if (! empty($session['customer'])) {
                        $company->stripe_customer_id = $session['customer'];
                        $company->save();
                    }
                    if (! empty($session['subscription'])) {
                        $subscription = $this->stripe->retrieveSubscription($session['subscription']);
                        if ($subscription) {
                            $company = $company->fresh();
                            $this->stripe->applySubscriptionToCompany($company, $subscription);
                            $this->resend->cancelTrialEmails($company->fresh());
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe success-afhandeling mislukt', ['error' => $e->getMessage()]);
            }
        }

        return redirect()->route('billing.show')
            ->with('flash', __('Bedankt! Je abonnement is actief. Je hebt weer volledig toegang.'));
    }

    public function portal(Request $request)
    {
        $company = $request->user()->company;

        if (! $company->stripe_customer_id || ! $this->stripe->configured()) {
            return back()->with('error', __('Er is nog geen abonnement om te beheren.'));
        }

        try {
            $url = $this->stripe->createPortalSession($company, route('billing.show'));
        } catch (\Throwable $e) {
            Log::error('Stripe portaal aanmaken mislukt', ['error' => $e->getMessage(), 'company' => $company->id]);

            return back()->with('error', __('Het beheerportaal is even niet bereikbaar. Probeer het later opnieuw.'));
        }

        return Inertia::location($url);
    }
}
