<?php

namespace App\Http\Controllers;

use App\Services\ResendScheduler;
use App\Services\StripeService;
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
                    'name' => $names[$r->company_id] ?? ('Administratie #' . $r->company_id),
                    'total' => (int) $r->c,
                ])->values(),
            ];
        }

        return Inertia::render('Billing/Index', [
            'subscription' => $company->subscriptionSummary(),
            'ai_usage' => $aiUsage,
            'platform_ai' => $platformAi,
            'plans' => [
                [
                    'key' => 'basis',
                    'name' => 'Basis',
                    'amount' => '10',
                    'vat_note' => 'Excl. 21% btw · € 12,10 incl. btw',
                    'tagline' => 'Alles om te factureren en je administratie bij te houden.',
                    'features' => [
                        'Onbeperkt facturen, offertes, klanten en producten',
                        'BTW-overzicht, herinneringen, incasso en klantenportaal',
                        'Inkoop, uren, ritten en jaaroverzicht',
                        'Maandelijks opzegbaar',
                        'Optioneel: automatische bankkoppeling voor € ' . number_format((float) config('services.ponto.account_price', 5), 2, ',', '.') . ' per rekening per maand',
                    ],
                    'available' => $this->stripe->configured(),
                ],
                [
                    'key' => 'slim',
                    'name' => 'Slim',
                    'amount' => '17,50',
                    'vat_note' => 'Excl. 21% btw · € 21,18 incl. btw',
                    'tagline' => 'Alles uit Basis, plus de AI-assistent die werk uit handen neemt.',
                    'features' => array_values(array_filter([
                        'Alles uit Basis (bankkoppeling optioneel, per rekening)',
                        'Scan & herken: bonnen en inkoopfacturen automatisch ingevuld',
                        'Postvak IN met automatische boekingsvoorstellen',
                        'Offerte uit tekst: plak je conceptofferte, het formulier vult zich in',
                        'Claude-koppeling: maak offertes en facturen rechtstreeks vanuit je Claude-gesprek',
                        'Huisstijl herkennen met AI: upload je huisstijlgids en alles staat goed',
                        ((int) config('services.anthropic.monthly_limit', 250)) > 0
                            ? sprintf('Ruime fair-use: %d AI-acties per maand', (int) config('services.anthropic.monthly_limit', 250))
                            : null,
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
            return back()->with('error', 'Betalen is nog niet beschikbaar. Probeer het later opnieuw.');
        }

        if ($company->is_exempt) {
            return back()->with('flash', 'Dit account is vrijgesteld — je hoeft geen abonnement af te sluiten.');
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

            return back()->with('error', 'Er ging iets mis bij het starten van de betaling. Probeer het opnieuw.');
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
            ->with('flash', 'Bedankt! Je abonnement is actief. Je hebt weer volledig toegang.');
    }

    public function portal(Request $request)
    {
        $company = $request->user()->company;

        if (! $company->stripe_customer_id || ! $this->stripe->configured()) {
            return back()->with('error', 'Er is nog geen abonnement om te beheren.');
        }

        try {
            $url = $this->stripe->createPortalSession($company, route('billing.show'));
        } catch (\Throwable $e) {
            Log::error('Stripe portaal aanmaken mislukt', ['error' => $e->getMessage(), 'company' => $company->id]);

            return back()->with('error', 'Het beheerportaal is even niet bereikbaar. Probeer het later opnieuw.');
        }

        return Inertia::location($url);
    }
}
