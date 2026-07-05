<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\ResendScheduler;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
