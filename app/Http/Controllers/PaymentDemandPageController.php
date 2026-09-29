<?php

namespace App\Http\Controllers;

use App\Models\PaymentDemand;
use App\Services\MolliePaymentService;
use App\Services\PaymentDemandService;
use App\Support\DemandText;
use App\Support\DocumentLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * De pagina van de online aanmaning: bereikbaar via de geheime link uit de
 * mail, zonder inlog. De klant ziet het bedrag van vandaag, betaalt, of geeft
 * door dat hij heeft betaald, wanneer hij betaalt of waarom hij het er niet
 * mee eens is. De teksten volgen de taal van de factuur.
 */
class PaymentDemandPageController extends Controller
{
    public function __construct(private PaymentDemandService $service) {}

    public function show(Request $request, string $token): Response
    {
        $demand = $this->find($token);
        if (! $demand) {
            return Inertia::render('Demands/Show', ['valid' => false, 't' => [
                'invalid_title' => __('Deze link is niet (meer) geldig'),
                'invalid_text' => __('Neem contact op met de afzender van de aanmaning.'),
            ]]);
        }

        // Betaald of overgedragen sinds het versturen? Dan zegt de pagina dat.
        $demand = $this->service->settle($demand);
        if ($demand->isActive()) {
            $this->service->opened($demand, $request);
        }

        $invoice = $demand->invoice;
        $company = $invoice->brandedCompany();
        $claim = $this->service->claim($demand);
        $day = fn ($date) => $date?->translatedFormat('j F Y');

        return DocumentLocale::using($invoice->language, fn () => Inertia::render('Demands/Show', [
            'valid' => true,
            'token' => $token,
            't' => DemandText::page($demand) + ['letter' => DemandText::letter($demand, $claim)],
            'company' => [
                'name' => $company->name,
                'holder' => $invoice->company?->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'iban' => $company->iban,
                'color' => $company->brand_color,
            ],
            'invoice' => [
                'number' => $invoice->number,
                'customer_name' => $invoice->customer_name,
                // Inzien en online betalen loopt via het klantenportaal, met de code per mail.
                'portal_url' => app(MolliePaymentService::class)->payable($invoice) ? $invoice->portalUrl() : null,
            ],
            'demand' => [
                'status' => $demand->status,
                'expired' => $demand->isExpired(),
                'deadline_label' => $day($demand->deadline),
                'response' => $demand->response,
                'response_label' => $demand->response ? $this->service->responseLabel($demand) : null,
                'response_note' => $demand->response_note,
                'pdf_url' => route('demand.pdf', $token),
                'promise_max' => now()->addDays(PaymentDemandService::PROMISE_MAX_DAYS)->toDateString(),
                'today' => now()->toDateString(),
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
            'qr' => $demand->isActive() ? $this->service->paymentQr($demand, $claim) : null,
        ]));
    }

    public function respond(Request $request, string $token): RedirectResponse
    {
        $demand = $this->find($token) ?? abort(404);

        return DocumentLocale::using($demand->invoice->language, function () use ($request, $demand) {
            $data = $request->validate([
                'response' => ['required', Rule::in(PaymentDemand::RESPONSES)],
                'date' => array_filter([
                    Rule::requiredIf(fn () => $request->input('response') === 'promise'), 'nullable', 'date',
                    $request->input('response') === 'promise' ? 'after:today' : 'before_or_equal:today',
                    $request->input('response') === 'promise' ? 'before_or_equal:' . now()->addDays(PaymentDemandService::PROMISE_MAX_DAYS)->toDateString() : null,
                ]),
                'note' => [Rule::requiredIf(fn () => $request->input('response') === 'dispute'), 'nullable', 'string', 'max:2000'],
            ], [
                'response.required' => __('Kies een van de drie antwoorden.'),
                'response.in' => __('Kies een van de drie antwoorden.'),
                'date.required' => __('Kies de dag waarop u betaalt.'),
                'date.after' => __('Kies een dag na vandaag.'),
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

            return back()->with('flash', match ($data['response']) {
                'paid' => __('Bedankt voor uw bericht. :company controleert de betaling.', ['company' => $demand->invoice->brandedCompany()->name]),
                'promise' => __('Uw toezegging is doorgegeven aan :company.', ['company' => $demand->invoice->brandedCompany()->name]),
                default => __('Uw bezwaar is doorgegeven aan :company.', ['company' => $demand->invoice->brandedCompany()->name]),
            });
        });
    }

    /** De brief als PDF, voor wie de link van de aanmaning heeft. */
    public function pdf(string $token): HttpResponse
    {
        $demand = $this->find($token) ?? abort(404);

        return $this->service->pdf($demand)->stream('aanmaning-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $demand->invoice->number) . '.pdf');
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
