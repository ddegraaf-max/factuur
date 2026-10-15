<?php

namespace App\Services\Fachora;

use App\Models\FachoraFile;
use App\Models\Provider;
use App\Models\ServiceJob;
use App\Models\ServiceJobOffer;
use App\Support\Fachora;

/**
 * Wat de schermen van een opdracht te zien krijgen, per rol: de klant ziet
 * de bedrijven en hun prijzen, het bedrijf ziet de klus (en pas na de keuze
 * het adres), de eigenaar alles. Geheimen (tokens, Stripe-id's) nooit.
 */
class Presenter
{
    public function __construct(private ProtectService $protect, private VerificationService $verification) {}

    /** @return array<string, mixed> */
    public function job(ServiceJob $job, string $for): array
    {
        $out = [
            'id' => $job->id,
            'reference' => $job->reference,
            'status' => $job->status,
            'status_label' => $job->statusLabel(),
            'category' => $job->category,
            'category_name' => $job->categoryName(),
            'title' => $job->title,
            'title_pl' => $job->ai['title_pl'] ?? null,
            'description' => $job->description,
            'description_pl' => $job->description_pl,
            'summary_en' => $job->ai['summary_en'] ?? null,
            'questions_pl' => $job->ai['questions_pl'] ?? [],
            'language_fallback' => (bool) ($job->ai['language_fallback'] ?? false),
            'urgency' => $job->urgency,
            'urgency_label' => $this->urgency($job),
            'wanted_date' => $job->wanted_date?->toDateString(),
            'time_window' => $job->time_window,
            'language' => $job->language,
            'language_label' => $this->language($job->language),
            'city' => $job->city,
            'postal_code' => $job->postal_code,
            'estimate_low' => $job->estimate_low,
            'estimate_high' => $job->estimate_high,
            'created_at' => $job->created_at?->translatedFormat('j F Y, H:i'),
            'offered_at' => $job->offered_at?->translatedFormat('j F Y, H:i'),
            'offers_deadline' => $job->offers_deadline_at?->translatedFormat('j F, H:i'),
            'agreed_amount' => $job->agreed_amount,
            'agreed_type' => $job->agreed_type,
            'agreed_scope' => $job->agreed_scope,
            'scheduled_at' => $job->scheduled_at?->toIso8601String(),
            'scheduled_label' => $job->scheduled_at?->translatedFormat('l j F Y, H:i'),
            'scheduled_window' => $job->scheduled_window,
            'warranty_months' => $job->warranty_months,
            'protect' => $job->protect,
            'paid' => $job->isPaid(),
            'paid_at' => $job->paid_at?->translatedFormat('j F Y, H:i'),
            'payout_at' => $job->payout_at?->translatedFormat('j F Y'),
            'refunded' => $job->refunded_at !== null,
            'total' => $job->totalAmount(),
            'started_at' => $job->started_at?->translatedFormat('j F Y, H:i'),
            'done_at' => $job->done_at?->translatedFormat('j F Y, H:i'),
            'done_note' => $job->done_note,
            'accepted_at' => $job->accepted_at?->translatedFormat('j F Y'),
            'accepted_by' => $job->accepted_by,
            'auto_accept_on' => $job->status === 'done' && $job->done_at ? $job->done_at->copy()->addDays((int) config('fachora.auto_accept_days', 5))->translatedFormat('j F') : null,
            'dispute_reason' => $job->dispute_reason,
            'dispute_reply' => $job->dispute_reply,
            'dispute_opened_at' => $job->dispute_opened_at?->translatedFormat('j F Y, H:i'),
            'dispute_resolution' => $job->dispute_resolution,
            'dispute_note' => $job->dispute_note,
            'cancelled_by' => $job->cancelled_by,
            'cancel_reason' => $job->cancel_reason,
            'extras' => $job->extras->map(fn ($e) => ['id' => $e->id, 'description' => $e->description, 'amount' => $e->amount, 'status' => $e->status])->values()->all(),
            'review' => $job->review ? ['rating' => $job->review->rating, 'text' => $job->review->text, 'reply' => $job->review->reply] : null,
            'events' => $job->events->filter(fn ($e) => $for === 'owner' || ! in_array($e->kind, ['payout_failed', 'no_match'], true))
                ->map(fn ($e) => ['kind' => $e->kind, 'actor' => $e->actor, 'description' => $e->description, 'at' => $e->created_at?->translatedFormat('j M, H:i')])->values()->all(),
        ];

        if ($for !== 'provider' || $job->hasProvider()) {
            $out += [
                'customer_name' => $job->customer_name,
                'customer_phone' => $job->customer_phone,
                'address_line' => $job->address_line,
            ];
        }
        if ($for !== 'provider') {
            $out['customer_email'] = $job->customer_email;
        }
        if ($for === 'owner') {
            $out += [
                'fee_percent' => $job->fee_percent,
                'fee_amount' => $job->fee_amount,
                'payout_amount' => $job->payout_amount,
                'waves' => $job->waves,
                'provider_id' => $job->provider_id,
                'invoice_id' => $job->invoice_id,
                'has_payment_intent' => filled($job->stripe_payment_intent),
                'has_transfer' => filled($job->stripe_transfer_id),
            ];
        }
        if ($for === 'customer') {
            $out['can_pay'] = $job->status === 'agreed' && $job->protect && $this->protect->availableFor($job);
            $out['can_cancel'] = ! $job->isClosed() && ! in_array($job->status, ['done', 'disputed'], true);
        }

        return $out;
    }

    /** Aanbieding zoals de klant hem ziet (met het bedrijf) of het bedrijf zelf. @return array<string, mixed> */
    public function offer(ServiceJobOffer $offer, bool $withProvider = true): array
    {
        $out = [
            'id' => $offer->id,
            'status' => $offer->status,
            'status_label' => $offer->statusLabel(),
            'price' => $offer->price,
            'price_type' => $offer->price_type,
            'callout' => $offer->callout,
            'hourly' => $offer->hourly,
            'available_at' => $offer->available_at?->toIso8601String(),
            'available_label' => $offer->available_at?->translatedFormat('l j F, H:i'),
            'available_note' => $offer->available_note,
            'remarks' => $offer->remarks,
            'decline_reason' => $offer->decline_reason,
            'sent_at' => $offer->sent_at?->translatedFormat('j M, H:i'),
            'responded_at' => $offer->responded_at?->translatedFormat('j M, H:i'),
            'wave' => $offer->wave,
        ];
        if ($withProvider && $offer->provider) {
            $out['provider'] = $offer->provider->publicSummary();
        }

        return $out;
    }

    /** Bestand bij een opdracht, met de URL waarop de huidige rol hem mag ophalen. @return list<array<string, mixed>> */
    public function files(ServiceJob $job, \Closure $url, ?array $kinds = null): array
    {
        return $job->files->filter(fn (FachoraFile $f) => $kinds === null || in_array($f->kind, $kinds, true))
            ->map(fn (FachoraFile $f) => $f->summary($url($f)))->values()->all();
    }

    /** Het bedrijf zoals het zijn eigen profiel ziet (meer dan het publiek). @return array<string, mixed> */
    public function ownProvider(Provider $provider): array
    {
        return $provider->publicSummary() + [
            'display_name' => $provider->display_name,
            'protect_opt_in' => $provider->protect_opt_in,
            'response_minutes' => $provider->response_minutes,
            'legal_name' => $provider->legal_name,
            'nip' => $provider->nip,
            'regon' => $provider->regon,
            'legal_form' => $provider->legal_form,
            'address_line' => $provider->address_line,
            'postal_code' => $provider->postal_code,
            'email' => $provider->email,
            'phone' => $provider->phone,
            'website' => $provider->website,
            'languages_raw' => $provider->languages ?? ['pl' => 'native'],
            'status_reason' => $provider->status_reason,
            'claimed_at' => $provider->claimed_at?->translatedFormat('j F Y'),
            'verification_requested_at' => $provider->verification_requested_at?->translatedFormat('j F Y'),
            'suspended_until' => $provider->suspended_until?->format('d.m.Y'),
            'insurance_until' => $provider->insurance_until?->toDateString(),
            'insurance_note' => $provider->insurance_note,
            'phone_verified' => $provider->phone_verified_at !== null,
            'phone_code_sent' => $provider->phone_code_sent_at !== null && $provider->phone_code_sent_at->gt(now()->subMinutes(30)),
            'terms_accepted' => $provider->terms_accepted_at !== null && $provider->terms_version === (string) config('fachora.terms_version'),
            'stripe_started' => filled($provider->stripe_account_id),
            'stripe_ready' => $provider->protectReady(),
            'stripe_due' => $provider->stripe_requirements['currently_due'] ?? [],
            'fee_percent' => $provider->feePercent(),
            'checks' => $this->verification->overview($provider),
            'ready' => $this->verification->ready($provider),
        ];
    }

    private function urgency(ServiceJob $job): string
    {
        return match ($job->urgency) {
            'now' => __('Zo snel mogelijk'),
            'week' => __('Deze week'),
            'month' => __('Binnen een maand'),
            default => $job->wanted_date?->translatedFormat('j F Y') ?? __('In overleg'),
        };
    }

    private function language(string $code): string
    {
        $l = (array) config('fachora.languages.' . $code, []);
        $locale = app()->getLocale() === 'en' ? 'en' : 'pl';

        return trim(($l['flag'] ?? '') . ' ' . ($l[$locale] ?? strtoupper($code)));
    }
}
