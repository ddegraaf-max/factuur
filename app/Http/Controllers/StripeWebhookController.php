<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionCanceledMail;
use App\Models\Company;
use App\Services\ResendScheduler;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private ResendScheduler $resend,
    ) {
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $this->stripe->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Stripe webhook: ongeldige handtekening');

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $type = $event['type'] ?? null;
        $object = $event['data']['object'] ?? [];

        try {
            switch ($type) {
                case 'checkout.session.completed':
                    $this->onCheckoutCompleted($object);
                    break;

                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $this->onSubscriptionChanged($object);
                    break;

                case 'invoice.paid':
                    $this->onInvoicePaid($object);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('Stripe webhook verwerken mislukt', ['type' => $type, 'error' => $e->getMessage()]);

            return response('Webhook error', 500);
        }

        return response('OK', 200);
    }

    private function onCheckoutCompleted(array $session): void
    {
        $company = $this->resolveCompany($session, $session['client_reference_id'] ?? null);
        if (! $company) {
            return;
        }

        if (! empty($session['customer'])) {
            $company->stripe_customer_id = $session['customer'];
        }
        if (! empty($session['subscription'])) {
            $company->stripe_subscription_id = $session['subscription'];
            $subscription = $this->stripe->retrieveSubscription($session['subscription']);
            if ($subscription) {
                $this->stripe->applySubscriptionToCompany($company, $subscription);
                $this->resend->cancelTrialEmails($company->fresh());

                return;
            }
        }

        $company->save();
        $this->resend->cancelTrialEmails($company->fresh());
    }

    private function onSubscriptionChanged(array $subscription): void
    {
        $company = $this->resolveCompany($subscription, $subscription['metadata']['company_id'] ?? null);
        if (! $company) {
            return;
        }

        $this->stripe->applySubscriptionToCompany($company, $subscription);
        $this->maybeSendCancellationEmail($company->fresh(), $subscription);
    }

    /**
     * Stuur eenmalig de "abonnement opgezegd"-mail zodra een betaald abonnement
     * wordt opgezegd (direct of tegen het einde van de periode). Zodra het
     * abonnement weer actief loopt, wordt de vlag gewist zodat een volgende
     * opzegging opnieuw een mail oplevert.
     */
    private function maybeSendCancellationEmail(Company $company, array $subscription): void
    {
        $status = $subscription['status'] ?? null;
        $scheduledToCancel = ($subscription['cancel_at_period_end'] ?? false) === true;
        $ended = $status === 'canceled';

        if (! $scheduledToCancel && ! $ended) {
            // Actief en niet opgezegd: reset zodat een volgende opzegging weer mailt.
            if ($company->subscription_cancel_emailed_at) {
                $company->forceFill(['subscription_cancel_emailed_at' => null])->save();
            }

            return;
        }

        if ($company->subscription_cancel_emailed_at) {
            return; // al gemaild voor deze opzegging
        }

        $user = $company->users()->orderBy('id')->first();
        $to = $company->email ?: $user?->email;
        if (! $to) {
            return;
        }

        $firstName = $user && trim($user->name) !== ''
            ? explode(' ', trim($user->name))[0]
            : 'daar';

        $accessUntil = $ended
            ? null
            : optional($company->subscription_ends_at)->format('d-m-Y');

        try {
            Mail::to($to)->send(new SubscriptionCanceledMail($company, $firstName, $accessUntil, $ended));
            $company->forceFill(['subscription_cancel_emailed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::warning('Opzeg-mail versturen mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
        }
    }

    private function onInvoicePaid(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (! $subscriptionId) {
            return;
        }

        $company = Company::where('stripe_subscription_id', $subscriptionId)
            ->orWhere('stripe_customer_id', $invoice['customer'] ?? '___')
            ->first();

        if (! $company) {
            return;
        }

        $subscription = $this->stripe->retrieveSubscription($subscriptionId);
        if ($subscription) {
            $this->stripe->applySubscriptionToCompany($company, $subscription);
        }
    }

    /**
     * Vind het bedrijf op basis van metadata/client_reference_id of Stripe-ids.
     */
    private function resolveCompany(array $object, ?string $companyId): ?Company
    {
        if ($companyId && ($company = Company::find($companyId))) {
            return $company;
        }

        if (! empty($object['customer'])) {
            $company = Company::where('stripe_customer_id', $object['customer'])->first();
            if ($company) {
                return $company;
            }
        }

        if (! empty($object['id'])) {
            return Company::where('stripe_subscription_id', $object['id'])->first();
        }

        return null;
    }
}
