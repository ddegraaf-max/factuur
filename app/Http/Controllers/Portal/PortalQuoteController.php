<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\QuoteDecisionMail;
use App\Models\Quote;
use App\Services\QuoteManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Offertes in het klantenportaal: bekijken, downloaden en — het belangrijkste —
 * digitaal ondertekenen of afwijzen. Zelfde beveiligingsmodel als facturen:
 * bezit van de geheime link + verificatie van het e-mailadres met een code.
 *
 * Het bewijsdossier van de handtekening: geverifieerd e-mailadres, naam,
 * getekende handtekening (afbeelding), tijdstip en IP-adres.
 */
class PortalQuoteController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $quote = $this->findByToken($token);

        // 1.103.6: e-mailadres geverifieerd, of (offerte per sms aan een klant zonder e-mail) het mobiele nummer.
        // 1.107: via de link van de tweede ondertekenaar gaat de code naar diens adres.
        $asCoSigner = $quote->co_signer_token !== null && hash_equals((string) $quote->co_signer_token, $token);
        if (! PortalAuthController::canAccessQuote($request, $quote)) {
            PortalAuthController::startQuoteGate($request, $quote, route('portal.quote', $token), $asCoSigner);

            return redirect()->route('portal.verify.show');
        }
        $role = PortalAuthController::quoteSignerRole($request, $quote);

        $quote->load('lines', 'replacedBy');
        $company = $quote->brandedCompany();

        // 1.102: bekeken-moment en teller voor de opvolgingsbalk van de ondernemer (eerste keer ook in het logboek).
        $firstView = ! $quote->portal_viewed_at;
        $quote->forceFill(['portal_viewed_at' => $quote->portal_viewed_at ?? now(), 'portal_views' => (int) $quote->portal_views + 1])->saveQuietly();
        if ($firstView) {
            \App\Support\Audit::log('viewed', $quote, __(':label voor het eerst bekeken door de klant in het portaal', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);
        }
        // Vervangen door een nieuwe offerte die al bij de klant ligt? Dan daarheen linken.
        $replacement = $quote->replacedBy;
        $replacementLive = $replacement && in_array($replacement->status, ['sent', 'expired', 'accepted'], true);

        return Inertia::render('Portal/QuoteShow', [
            'quote' => [
                'token' => $token,
                'number' => $quote->number,
                'status' => $quote->status,
                'is_expired' => $quote->is_expired,
                'quote_date_label' => $quote->quote_date->translatedFormat('j F Y'),
                'valid_until_label' => $quote->valid_until->translatedFormat('j F Y'),
                'reference' => $quote->reference,
                'intro' => $quote->intro,
                'notes' => $quote->notes,
                'customer_name' => $quote->customer_name,
                'subtotal' => (float) $quote->subtotal,
                'vat_total' => (float) $quote->vat_total,
                'total' => (float) $quote->total,
                'vat_breakdown' => $quote->vat_breakdown,
                'vat_reversed' => (bool) $quote->vat_reversed,
                'vat_exempt' => (bool) $quote->vat_exempt,
                'lines' => $quote->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'description' => $l->description,
                    'details' => $l->details,
                    'quantity' => (float) $l->quantity,
                    'unit' => $l->unit,
                    'unit_price' => (float) $l->unit_price,
                    'vat_rate' => (float) $l->vat_rate,
                    'line_subtotal' => (float) $l->line_subtotal,
                ]),
                'signed_name' => $quote->signed_name,
                'signed_at_label' => $quote->signed_at?->translatedFormat('j F Y, H:i'),
                'accepted_at_label' => $quote->accepted_at?->translatedFormat('j F Y'),
                'rejected_at_label' => $quote->rejected_at?->translatedFormat('j F Y'),
                'withdrawn_at_label' => $quote->withdrawn_at?->translatedFormat('j F Y'),
                'withdrawal_note' => $quote->withdrawal_note,
                'replacement' => $replacementLive ? [
                    'number' => $replacement->number,
                    'url' => $replacement->portalUrl(),
                ] : null,
                // 1.107: twee ondertekenaars — wie is wie, wie heeft al getekend, en wie zit er nu in het portaal.
                'signing' => $quote->needsCoSigner() ? [
                    'my_role' => $role,
                    'my_name' => $role === 'co' ? $quote->co_signer_name : $quote->customer_name,
                    'mine_signed' => $role === 'co' ? (bool) $quote->co_signed_at : (bool) $quote->signed_at,
                    'co_signed_name' => $quote->co_signed_name,
                    'co_signed_at_label' => $quote->co_signed_at?->translatedFormat('j F Y, H:i'),
                    'signers' => array_map(fn (array $s) => [
                        'role' => $s['role'],
                        'name' => $s['name'],
                        'is_me' => $s['role'] === $role,
                        'signed_at_label' => $s['signed_at']?->translatedFormat('j F Y, H:i'),
                    ], $quote->signers()),
                ] : null,
            ],
            'company' => [
                'name' => $company?->name,
                'email' => $company?->email,
                'phone' => $company?->phone,
                'kvk_number' => $company?->kvk_number,
                'brand_color' => $company?->brand_color,
                'logo_data' => $company?->logo_data,
            ],
        ]);
    }

    /** Digitaal ondertekenen: naam + handtekening + akkoord. */
    public function sign(Request $request, string $token, QuoteManager $manager): RedirectResponse
    {
        $quote = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        // 1.107: de klant of de tweede ondertekenaar; elk zet zijn eigen handtekening.
        $role = PortalAuthController::quoteSignerRole($request, $quote);
        if ($role === null) {
            return redirect()->route('portal.quote', $token);
        }

        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:120'],
            // PNG-data-URL uit het tekenveld; ruim onder de limiet houden.
            'signature' => ['required', 'string', 'max:200000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
            'agree' => ['accepted'],
        ], [
            'signed_name.required' => __('Vul je naam in.'),
            'signature.required' => __('Zet je handtekening in het tekenveld.'),
            'signature.regex' => __('De handtekening kon niet worden gelezen — probeer het opnieuw.'),
            'signature.max' => __('De handtekening is te groot — wis het veld en teken opnieuw.'),
            'agree.accepted' => __('Vink aan dat je akkoord gaat met de offerte.'),
        ]);

        if (! in_array($quote->status, ['sent', 'expired'], true)) {
            return back()->with('error', __('Alleen een verstuurde offerte kan worden geaccepteerd.'));
        }
        if ($role === 'co' ? $quote->co_signed_at : $quote->signed_at) {
            return back()->with('error', __('Deze handtekening is al gezet.'));
        }

        $fields = [
            'signed_name' => trim($data['signed_name']),
            'signature_data' => $data['signature'],
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'signed_email' => $email ?: PortalAuthController::verifiedContact($request), // 1.103.6: bij een offerte per sms het geverifieerde mobiele nummer
        ];
        if ($role === 'co') {
            $fields = array_combine(array_map(fn (string $k) => 'co_' . $k, array_keys($fields)), array_values($fields));
        }
        $quote->forceFill($fields)->save();

        if ($quote->needsCoSigner()) {
            \App\Support\Audit::log('signed', $quote, __(':label ondertekend door :name (:n van 2)', [
                'label' => \App\Support\Audit::label($quote), 'name' => trim($data['signed_name']), 'n' => $quote->signaturesDone(),
            ]), [], $quote->company_id);
        }

        if (! $quote->isFullySigned()) {
            // 1.107: één handtekening binnen — de ander is aan de beurt, de ondernemer hoort het ook.
            $other = $role === 'co' ? $quote->customer_name : $quote->co_signer_name;
            $manager->notifyOtherSigner($quote->fresh(), $role);
            $this->notifyCompany($quote->fresh(), accepted: true, partialBy: trim($data['signed_name']));

            return back()->with('flash', __('Bedankt! Uw handtekening is gezet. De offerte is definitief zodra :name ook heeft ondertekend.', ['name' => $other]));
        }

        try {
            $manager->accept($quote);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifyCompany($quote->fresh(), accepted: true);

        // Bevestiging (met de ondertekende PDF) naar de klant zelf — als de
        // ondernemer dat aan heeft staan. Nooit blokkerend voor het portaal.
        $fresh = $quote->fresh();
        if ($fresh->company?->quote_accept_mail_enabled) {
            try {
                $manager->sendAcceptConfirmation($fresh);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bevestiging akkoord naar klant mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
            }
        }

        return back()->with('flash', __('Bedankt! De offerte is ondertekend — je ontvangt vanzelf bericht.'));
    }

    /** Afwijzen, met een (optionele) reden voor de afzender. */
    public function decline(Request $request, string $token, QuoteManager $manager): RedirectResponse
    {
        $quote = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! PortalAuthController::canAccessQuote($request, $quote)) {
            return redirect()->route('portal.quote', $token);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $manager->reject($quote);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $quote->forceFill([
            'decline_reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
            'signed_email' => $email ?: PortalAuthController::verifiedContact($request), // 1.103.6: bij een offerte per sms het geverifieerde mobiele nummer
        ])->save();

        $this->notifyCompany($quote->fresh(), accepted: false);

        return back()->with('flash', __('De offerte is afgewezen — de afzender krijgt hiervan bericht.'));
    }

    /** De offerte-PDF (met handtekeningblok zodra ondertekend). */
    public function pdf(Request $request, string $token): HttpResponse|RedirectResponse
    {
        $quote = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! PortalAuthController::canAccessQuote($request, $quote)) {
            return redirect()->route('portal.quote', $token);
        }

        $quote->load('lines');

        $pdf = \App\Support\DocumentLocale::using($quote->language, fn () => Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $quote->brandedCompany(),
        ])->setPaper('a4'));

        return $pdf->download(($quote->number ?: 'offerte') . '.pdf');
    }

    /* ===================== Helpers ===================== */

    protected function findByToken(string $token): Quote
    {
        abort_unless(strlen($token) === 64 && ctype_xdigit($token), 404);

        return Quote::withoutGlobalScope('company')
            ->with('company')
            // 1.107: de tweede ondertekenaar heeft een eigen link (co_signer_token) naar dezelfde offerte.
            ->where(fn ($q) => $q->where('portal_token', $token)->orWhere('co_signer_token', $token))
            ->whereNotIn('status', ['draft'])
            ->firstOrFail();
    }

    /** Mail de ondernemer over de beslissing van zijn klant. */
    protected function notifyCompany(Quote $quote, bool $accepted, ?string $partialBy = null): void
    {
        try {
            $to = $quote->company?->copy_email ?: $quote->company?->email;
            if ($to) {
                Mail::to($to)->send(new QuoteDecisionMail($quote, $accepted, $partialBy));
            }
        } catch (\Throwable $e) {
            Log::error('Offertebeslissing mailen mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
        }
    }
}
