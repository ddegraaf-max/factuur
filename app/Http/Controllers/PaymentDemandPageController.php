<?php

namespace App\Http\Controllers;

use App\Models\PaymentDemand;
use App\Services\MolliePaymentService;
use App\Services\PaymentDemandService;
use App\Services\PublicDemandService;
use App\Support\DemandText;
use App\Support\DocumentLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * De pagina van de online aanmaning: bereikbaar via de geheime link uit de
 * mail, zonder inlog. De klant ziet het bedrag van vandaag, betaalt, of geeft
 * door dat hij heeft betaald, wanneer hij betaalt of waarom hij het er niet
 * mee eens is. De teksten volgen de taal van de factuur.
 *
 * Dezelfde pagina is met de sleutel van de schuldeiser (?k=…) zijn overzicht:
 * wanneer de klant keek, wat hij antwoordde, en bij een aanmaning zonder
 * account de knoppen betaald, intrekken en overdragen aan de deurwaarder.
 */
class PaymentDemandPageController extends Controller
{
    public function __construct(private PaymentDemandService $service) {}

    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $demand = $this->find($token);
        // Nog niet bevestigd: voor de buitenwereld bestaat de aanmaning niet.
        if ($demand?->isPending()) {
            if ($demand->isCreditorKey($request->query('k'))) {
                return redirect($demand->confirmUrl());
            }
            $demand = null;
        }
        if (! $demand) {
            return Inertia::render('Demands/Show', ['valid' => false, 't' => [
                'invalid_title' => __('Deze link is niet (meer) geldig'),
                'invalid_text' => __('Neem contact op met de afzender van de aanmaning.'),
            ]]);
        }

        // Betaald of overgedragen sinds het versturen? Dan zegt de pagina dat.
        $demand = $this->service->settle($demand);
        if ($demand->isActive()) {
            $this->service->viewed($demand, $request);
        }

        // Het overzicht van de schuldeiser: alleen met zijn sleutel of zijn inlog. Hetzelfde
        // IP-adres telt wel voor 'niet geopend door de klant', maar geeft geen inzage.
        $key = $demand->isCreditorKey($request->query('k')) ? (string) $request->query('k') : null;
        $creditor = $key !== null
            || ($demand->company_id && (int) $request->user()?->company_id === (int) $demand->company_id);

        return DocumentLocale::using($demand->invoice->language, fn () => Inertia::render(
            'Demands/Show',
            self::pageProps($demand, $this->service->claim($demand), $this->service, $creditor, $key),
        ));
    }

    /**
     * Wat de pagina nodig heeft. Ook gebruikt door het voorbeeld op de website.
     *
     * @param  array<string, mixed>  $claim
     * @return array<string, mixed>
     */
    public static function pageProps(PaymentDemand $demand, array $claim, PaymentDemandService $service, bool $creditor = false, ?string $key = null): array
    {
        $invoice = $demand->invoice;
        $company = $invoice->brandedCompany();
        $day = fn ($date) => $date?->translatedFormat('j F Y');
        $stamp = fn ($date) => $date?->translatedFormat('j F Y, H:i');
        $file = $demand->isStandalone() ? $demand->fileInfo() : null;
        $real = $demand->exists;

        return [
            'valid' => true,
            'token' => $demand->token,
            't' => DemandText::page($demand) + ['letter' => DemandText::letter($demand, $claim)],
            'company' => [
                'name' => $company->name,
                'holder' => $invoice->company?->name,
                'kvk' => $company->kvk_number,
                'email' => $company->email,
                'phone' => $company->phone,
                'iban' => $company->iban,
                'color' => $company->brand_color,
            ],
            'invoice' => [
                'number' => $invoice->number,
                'customer_name' => $invoice->customer_name,
                'customer_kvk' => $invoice->customer_kvk_number,
                // Inzien en online betalen loopt via het klantenportaal, met de code per mail.
                'portal_url' => $real && ! $demand->isStandalone() && app(MolliePaymentService::class)->payable($invoice) ? $invoice->portalUrl() : null,
            ],
            'demand' => [
                'status' => $demand->status,
                'reference' => strtoupper(substr((string) $demand->token, 0, 8)),
                'issued_label' => $day($demand->sent_at ?? $demand->created_at ?? now()),
                'expired' => $demand->isExpired(),
                'deadline_label' => $day($demand->deadline),
                'opened' => (bool) $demand->first_opened_at,
                'response' => $demand->response,
                'response_label' => $demand->response ? $service->responseLabel($demand) : null,
                'response_note' => $demand->response_note,
                'pdf_url' => $real ? route('demand.pdf', array_filter(['token' => $demand->token, 'k' => $key])) : null,
                'file' => $file ? [
                    'name' => $file->filename,
                    'size_kb' => max(1, (int) round($file->size_bytes / 1024)),
                    'url' => $real ? route('demand.file', array_filter(['token' => $demand->token, 'k' => $key])) : null,
                ] : null,
                'promise_max' => now()->addDays(PaymentDemandService::PROMISE_MAX_DAYS)->toDateString(),
                'today' => now()->toDateString(),
                'standalone' => $demand->isStandalone(),
                'make_url' => route('aanmaning'),
            ],
            'claim' => [
                'principal' => $claim['principal'],
                'with_interest' => $claim['with_interest'],
                'interest' => $claim['interest'],
                'per_day' => $claim['per_day'],
                'costs_total' => $claim['costs_total'],
                'costs_due' => $claim['costs_due'],
                'total' => $claim['total'],
                'total_after' => $claim['total_after'],
                'principal_label' => money($claim['principal']),
                'interest_label' => money($claim['interest']),
                'per_day_label' => money($claim['per_day']),
                'costs_label' => money($claim['costs_total']),
                'total_label' => money($claim['total']),
                'total_after_label' => money($claim['total_after']),
            ],
            'qr' => $demand->isActive() ? $service->paymentQr($demand, $claim) : null,
            // Het overzicht van de schuldeiser: alleen met zijn sleutel of zijn inlog.
            'creditor' => $creditor ? self::creditorProps($demand, $service, $key, $stamp) : null,
        ];
    }

    /** @return array<string, mixed> */
    private static function creditorProps(PaymentDemand $demand, PaymentDemandService $service, ?string $key, \Closure $stamp): array
    {
        $opens = $demand->exists ? $service->opens($demand) : ['opens' => 0, 'first' => null, 'last' => null];
        // Met de sleutel van een aanmaning zonder account regelt de schuldeiser alles op deze pagina.
        $manage = $demand->isStandalone() && $key !== null && $demand->exists;

        return [
            'debtor_url' => $demand->exists ? $demand->url() : route('aanmaning.example'),
            'invoice_url' => ! $demand->isStandalone() ? route('invoices.show', $demand->invoice_id) : null,
            'opens' => $opens['opens'],
            'first_open_label' => $stamp($opens['first']),
            'last_open_label' => $opens['opens'] > 1 ? $stamp($opens['last']) : null,
            'responded_at_label' => $stamp($demand->responded_at),
            'facts' => $demand->isStandalone() ? app(PublicDemandService::class)->factsSummary($demand) : null,
            'partner' => \App\Support\Market::incasso('partner_name'),
            'key' => $manage ? $key : null,
            'can_transfer' => $manage && $demand->isDue(),
            'can_close' => $manage && $demand->isActive(),
            'actions' => $manage ? [
                'paid' => route('demand.paid', $demand->token),
                'withdraw' => route('demand.withdraw', $demand->token),
                'transfer' => route('demand.transfer', $demand->token),
            ] : null,
        ];
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $demand = $this->live($token);
        // Lokveld: een mens vult het niet in.
        if ($request->filled('website')) {
            return redirect($demand->url());
        }

        return DocumentLocale::using($demand->invoice->language, function () use ($request, $demand) {
            $limit = 'demand-reply:' . $request->ip();
            if (RateLimiter::tooManyAttempts($limit, PublicDemandService::LIMIT['reply_hour'])) {
                return back()->withErrors(['demand' => __('Te veel pogingen. Probeer het over een uur opnieuw.')]);
            }
            RateLimiter::hit($limit, 3600);

            // De schuldeiser kan niet namens zijn klant antwoorden.
            if ($this->service->actor($demand, $request) === 'creditor' && ($demand->isCreditorKey($request->input('k')) || $request->user())) {
                return back()->withErrors(['demand' => __('Alleen je klant kan reageren, via zijn eigen link.')]);
            }

            $data = $request->validate([
                'response' => ['required', Rule::in(PaymentDemand::RESPONSES)],
                'date' => array_filter([
                    Rule::requiredIf(fn () => $request->input('response') === 'promise'), 'nullable', 'date',
                    $request->input('response') === 'promise' ? 'after_or_equal:today' : 'before_or_equal:today',
                    $request->input('response') === 'promise' ? 'before_or_equal:' . now()->addDays(PaymentDemandService::PROMISE_MAX_DAYS)->toDateString() : null,
                ]),
                'note' => [Rule::requiredIf(fn () => $request->input('response') === 'dispute'), 'nullable', 'string', 'max:2000'],
            ], [
                'response.required' => __('Kies een van de drie antwoorden.'),
                'response.in' => __('Kies een van de drie antwoorden.'),
                'date.required' => __('Kies de dag waarop u betaalt.'),
                'date.after_or_equal' => __('Kies vandaag of een latere dag.'),
                'date.before_or_equal' => $request->input('response') === 'promise'
                    ? __('Kies een dag binnen :days dagen.', ['days' => PaymentDemandService::PROMISE_MAX_DAYS])
                    : __('De dag van betaling kan niet in de toekomst liggen.'),
                'note.required' => __('Beschrijf kort waarom u het niet eens bent met de factuur.'),
            ]);

            try {
                $this->service->respond($demand, $data['response'], $data, $request);
            } catch (\DomainException $e) {
                return back()->withErrors(['demand' => $e->getMessage()]);
            }

            $company = $demand->invoice->brandedCompany()->name;

            return back()->with('flash', match ($data['response']) {
                'paid' => __('Bedankt voor uw bericht. :company controleert de betaling.', ['company' => $company]),
                'promise' => __('Uw toezegging is doorgegeven aan :company.', ['company' => $company]),
                default => __('Uw bezwaar is doorgegeven aan :company.', ['company' => $company]),
            });
        });
    }

    /** De brief als PDF, voor wie de link van de aanmaning heeft. */
    public function pdf(Request $request, string $token): HttpResponse
    {
        $demand = $this->live($token);
        $this->service->viewed($demand, $request, 'letter');

        return $this->service->pdf($demand)->stream('aanmaning-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $demand->invoice->number) . '.pdf');
    }

    /** De kopie van de factuur die de schuldeiser meestuurde. */
    public function file(Request $request, string $token): HttpResponse
    {
        $demand = $this->live($token);
        $file = $demand->file()->first() ?? abort(404);
        $contents = $file->contents() ?? abort(404);
        $this->service->viewed($demand, $request, 'file');

        return response($contents, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $file->filename, 'factuur'),
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /* ---------- De schuldeiser van een aanmaning zonder account, met zijn sleutel ---------- */

    public function paid(Request $request, string $token): RedirectResponse
    {
        return $this->act($request, $token, fn (PaymentDemand $demand) => $this->service->markPaid($demand),
            __('De aanmaning is gesloten: betaald. Op de pagina van je klant staat dat ook.'));
    }

    public function withdraw(Request $request, string $token): RedirectResponse
    {
        return $this->act($request, $token, fn (PaymentDemand $demand) => $this->service->withdraw($demand),
            __('Aanmaning ingetrokken. Op de pagina van de klant staat dat ook.'));
    }

    /** Termijn voorbij en niet betaald: met één klik naar de deurwaarder. */
    public function transfer(Request $request, string $token): RedirectResponse
    {
        return $this->act($request, $token, fn (PaymentDemand $demand) => $this->service->transfer($demand),
            __('Het dossier is overgedragen aan :partner, met de aanmaning en het logboek erbij. Je krijgt een bevestiging per mail.', ['partner' => \App\Support\Market::incasso('partner_name')]));
    }

    private function act(Request $request, string $token, \Closure $action, string $done): RedirectResponse
    {
        $demand = $this->live($token);
        abort_unless($demand->isStandalone() && $demand->isCreditorKey($request->input('k')), 404);

        try {
            $action($demand);
        } catch (\DomainException $e) {
            return redirect($demand->creditorUrl())->withErrors(['demand' => $e->getMessage()]);
        }

        return redirect($demand->creditorUrl())->with('flash', $done);
    }

    /** De aanmaning, als ze bestaat én (bij een aanmaning zonder account) is bevestigd. */
    private function live(string $token): PaymentDemand
    {
        $demand = $this->find($token);
        abort_if(! $demand || $demand->isPending(), 404);

        return $demand;
    }

    private function find(string $token): ?PaymentDemand
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        return PaymentDemand::withoutGlobalScope('company')
            ->with(['invoice.company', 'invoice.brandProfile'])
            ->where('token', $token)
            ->first();
    }
}
