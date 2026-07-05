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

        return Inertia::render('Billing/Index', [
            'subscription' => $company->subscriptionSummary(),
            'price' => [
                'amount' => '10',
                'currency' => '€',
                'period' => 'maand',
                'vat_note' => 'Excl. 21% btw · €12,10 incl. btw',
            ],
            'stripeReady' => $this->stripe->configured(),
        ]);
    }

    public function checkout(Request $request)
    {
        $company = $request->user()->company;

        if (! $this->stripe->configured()) {
            return back()->with('error', 'Betalen is nog niet beschikbaar. Probeer het later opnieuw.');
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
