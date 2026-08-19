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

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $quote->customer_email) !== 0) {
            $request->session()->put('portal_pending_email', mb_strtolower($quote->customer_email));
            $request->session()->put('portal_intended', route('portal.quote', $token));
            $request->session()->put('portal_gate', [
                'company' => $quote->company?->name,
                'number' => $quote->number,
            ]);

            return redirect()->route('portal.verify.show');
        }

        $quote->load('lines');
        $company = $quote->brandedCompany();

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
        if (! $email || strcasecmp($email, $quote->customer_email) !== 0) {
            return redirect()->route('portal.quote', $token);
        }

        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:120'],
            // PNG-data-URL uit het tekenveld; ruim onder de limiet houden.
            'signature' => ['required', 'string', 'max:200000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'],
            'agree' => ['accepted'],
        ], [
            'signed_name.required' => 'Vul je naam in.',
            'signature.required' => 'Zet je handtekening in het tekenveld.',
            'signature.regex' => 'De handtekening kon niet worden gelezen — probeer het opnieuw.',
            'signature.max' => 'De handtekening is te groot — wis het veld en teken opnieuw.',
            'agree.accepted' => 'Vink aan dat je akkoord gaat met de offerte.',
        ]);

        try {
            $manager->accept($quote);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $quote->forceFill([
            'signed_name' => trim($data['signed_name']),
            'signature_data' => $data['signature'],
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'signed_email' => $email,
        ])->save();

        $this->notifyCompany($quote->fresh(), accepted: true);

        return back()->with('flash', 'Bedankt! De offerte is ondertekend — je ontvangt vanzelf bericht.');
    }

    /** Afwijzen, met een (optionele) reden voor de afzender. */
    public function decline(Request $request, string $token, QuoteManager $manager): RedirectResponse
    {
        $quote = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $quote->customer_email) !== 0) {
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
            'signed_email' => $email,
        ])->save();

        $this->notifyCompany($quote->fresh(), accepted: false);

        return back()->with('flash', 'De offerte is afgewezen — de afzender krijgt hiervan bericht.');
    }

    /** De offerte-PDF (met handtekeningblok zodra ondertekend). */
    public function pdf(Request $request, string $token): HttpResponse|RedirectResponse
    {
        $quote = $this->findByToken($token);

        $email = PortalAuthController::verifiedEmail($request);
        if (! $email || strcasecmp($email, $quote->customer_email) !== 0) {
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
            ->where('portal_token', $token)
            ->whereNotIn('status', ['draft'])
            ->firstOrFail();
    }

    /** Mail de ondernemer over de beslissing van zijn klant. */
    protected function notifyCompany(Quote $quote, bool $accepted): void
    {
        try {
            $to = $quote->company?->copy_email ?: $quote->company?->email;
            if ($to) {
                Mail::to($to)->send(new QuoteDecisionMail($quote, $accepted));
            }
        } catch (\Throwable $e) {
            Log::error('Offertebeslissing mailen mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
        }
    }
}
