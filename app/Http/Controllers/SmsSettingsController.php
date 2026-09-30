<?php

namespace App\Http\Controllers;

use App\Models\SmsCreditEntry;
use App\Models\SmsMessage;
use App\Models\SmsPurchase;
use App\Services\SmsCreditService;
use App\Services\SmsService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Instellingen → Sms: het tegoed, een bundel kopen, en wat er is verstuurd.
 * Betalen loopt via Stripe; het tegoed komt erbij zodra de betaling binnen is.
 */
class SmsSettingsController extends Controller
{
    public function __construct(private SmsService $sms, private SmsCreditService $credits) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless($this->sms->enabled($company), 404);

        // Terug van de betaalpagina: meteen verwerken, ook als de melding van Stripe nog onderweg is.
        if ($sessionId = $request->query('betaald')) {
            $purchase = $this->credits->fulfilSession($company, (string) $sessionId);

            return redirect()->route('settings.sms')->with($purchase?->isPaid() ? 'flash' : 'error', $purchase?->isPaid()
                ? __('Betaald: :n sms\'en bij je tegoed gezet.', ['n' => $purchase->credits])
                : __('De betaling is nog niet bevestigd. Het tegoed komt erbij zodra de betaling binnen is.'));
        }

        $free = $this->sms->free($company);

        return Inertia::render('Settings/Sms', [
            'balance' => $this->sms->remaining($company),
            // De eigen administraties van het platform versturen zonder tegoed, met een grens per maand.
            'free' => $free,
            'monthly_limit' => $free ? (int) config('services.smstools.monthly_limit') : null,
            'sender' => $this->sms->sender($company),
            'bundles' => $this->credits->bundles(),
            'vat_rate' => (float) config('sms.vat_rate'),
            'can_buy' => $request->user()->isOwner(),
            'purchases' => SmsPurchase::where('company_id', $company->id)->where('status', 'paid')->orderByDesc('id')->limit(12)->get()
                ->map(fn (SmsPurchase $p) => [
                    'id' => $p->id,
                    'credits' => $p->credits,
                    'price_excl' => (float) $p->price_excl,
                    'price_incl' => (float) $p->price_incl,
                    'paid_at_label' => $p->paid_at?->translatedFormat('j M Y, H:i'),
                ])->values(),
            'messages' => SmsMessage::where('company_id', $company->id)->orderByDesc('id')->limit(25)->get()
                ->map(fn (SmsMessage $m) => [
                    'id' => $m->id,
                    'to' => PhoneNumber::display($m->recipient),
                    'body' => $m->body,
                    'segments' => $m->segments,
                    'status' => $m->status,
                    'error' => $m->error,
                    'sent_at_label' => $m->created_at?->translatedFormat('j M Y, H:i'),
                ])->values(),
            'used_this_month' => (int) abs((int) SmsCreditEntry::where('company_id', $company->id)->where('kind', 'use')
                ->where('created_at', '>=', now()->startOfMonth())->sum('amount')),
        ]);
    }

    /** Een bundel kopen: door naar de betaalpagina van Stripe. */
    public function buy(Request $request): HttpResponse|RedirectResponse
    {
        $company = $request->user()->company;
        abort_unless($this->sms->enabled($company), 404);
        $data = $request->validate(['credits' => ['required', 'integer']], [
            'credits.required' => __('Kies een van de bundels.'),
            'credits.integer' => __('Kies een van de bundels.'),
        ]);

        try {
            $url = $this->credits->checkout(
                $company,
                (int) $data['credits'],
                $request->user()->id,
                route('settings.sms') . '?betaald={CHECKOUT_SESSION_ID}',
                route('settings.sms'),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['sms' => $e->getMessage()]);
        }

        // De betaalpagina staat bij Stripe: een gewone doorverwijzing, geen Inertia-bezoek.
        return Inertia::location($url);
    }
}
