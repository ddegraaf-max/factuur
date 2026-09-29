<?php

namespace App\Http\Controllers;

use App\Services\FreeDemandImport;
use App\Services\PaymentDemandService;
use App\Support\DocumentLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gratis aanmaning maken op /aanmaning-maken: iedereen maakt zonder account
 * een laatste aanmaning als PDF, met de wettelijke rente, de incassokosten
 * volgens de staffel en de termijn die de wet vraagt.
 *
 * Er wordt niets opgeslagen en niets verstuurd: de bezoeker krijgt de brief en
 * stuurt hem zelf. Online versturen — met de pagina waarop het bedrag oploopt
 * en de klant reageert — kan alleen vanuit een account, waar het e-mailadres
 * van de afzender is bevestigd. Alleen wie daar zelf voor kiest, neemt zijn
 * aanmaning mee (keep).
 */
class FreeDemandController extends Controller
{
    public function __construct(private PaymentDemandService $service) {}

    public function show()
    {
        return view('marketing.aanmaning-maken', [
            'terms' => [
                'zakelijk' => PaymentDemandService::TERM_BUSINESS,
                'particulier' => PaymentDemandService::TERM_CONSUMER,
                'min_zakelijk' => PaymentDemandService::TERM_MIN_BUSINESS,
                'max' => PaymentDemandService::TERM_MAX,
            ],
        ]);
    }

    public function download(Request $request)
    {
        $data = $this->validated($request);
        // Een nieuwe aanmaning: wat eerder is klaargezet om mee te nemen, vervalt.
        $request->session()->forget(FreeDemandImport::SESSION);

        $demand = $this->service->draft($data);
        $pdf = DocumentLocale::using('nl', fn () => $this->service->pdf($demand));

        return $pdf->download('aanmaning-' . (Str::slug($data['factuurnummer'], '-') ?: 'factuur') . '.pdf');
    }

    /**
     * De berekening vooraf, voor het blok onder het formulier. Alleen bedragen
     * en datums komen binnen; namen en adressen blijven in de browser.
     */
    public function calculation(Request $request)
    {
        $amount = trim((string) $request->input('bedrag'));
        if (str_contains($amount, ',')) {
            $amount = str_replace(',', '.', str_replace('.', '', $amount));
        }
        $request->merge(['bedrag' => preg_replace('/[^\d.\-]/', '', $amount)]);

        $data = $request->validate([
            'bedrag' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'vervaldatum' => ['required', 'date', 'before:today', 'after_or_equal:' . config('rente.from')],
            'klant' => ['required', Rule::in(['zakelijk', 'particulier'])],
            'termijn' => ['nullable', 'integer', 'min:1', 'max:' . PaymentDemandService::TERM_MAX],
            'rente' => ['nullable', 'boolean'],
            'geen_btw_aftrek' => ['nullable', 'boolean'],
        ]);

        $demand = $this->service->draft($data + [
            'van_bedrijf' => '', 'aan_naam' => '', 'factuurnummer' => '',
            'factuurdatum' => $data['vervaldatum'],
        ]);
        $claim = $this->service->claimAsSent($demand);
        $day = fn ($date) => $date->translatedFormat('j F Y');

        return response()->json([
            'with_interest' => $claim['with_interest'],
            'principal' => money($claim['principal']),
            'interest' => money($claim['interest']),
            'interest_label' => ($claim['business'] ? __('Wettelijke handelsrente') : __('Wettelijke rente'))
                . ' (' . __(':days dagen', ['days' => $claim['interest_days']]) . ', ' . rtrim(rtrim(number_format($claim['rate'], 2, ',', ''), '0'), ',') . '%)',
            'total' => money($claim['total']),
            'costs' => money($claim['costs_total']),
            'costs_label' => $claim['costs_vat'] > 0 ? __('Incassokosten na de termijn, inclusief btw') : __('Incassokosten na de termijn'),
            'term_days' => $demand->term_days,
            'note' => $demand->isBusiness()
                ? __('Betalen zonder incassokosten kan tot en met :date.', ['date' => $day($demand->deadline)])
                : __('Termijn van :days dagen, te rekenen vanaf de dag na ontvangst. Met twee dagen voor de bezorging loopt de termijn tot en met :date.', ['days' => $demand->term_days, 'date' => $day($demand->deadline)]),
        ]);
    }

    /**
     * De bezoeker kiest ervoor zijn aanmaning online te versturen: de gegevens
     * wachten in de sessie tot het account is aangemaakt en het e-mailadres is
     * bevestigd (RegisteredUserController, FreeDemandImport).
     */
    public function keep(Request $request)
    {
        $request->session()->put(FreeDemandImport::SESSION, $this->validated($request));

        return redirect()->route('register');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request): array
    {
        // "1.250,50" wordt "1250.50"; invoer met alleen een punt blijft zoals hij is.
        $amount = trim((string) $request->input('bedrag'));
        if (str_contains($amount, ',')) {
            $amount = str_replace(',', '.', str_replace('.', '', $amount));
        }
        $request->merge(['bedrag' => preg_replace('/[^\d.\-]/', '', $amount)]);

        $consumer = $request->input('klant') === 'particulier';

        return $request->validate([
            'van_bedrijf' => ['required', 'string', 'max:120'],
            'van_email' => ['nullable', 'email', 'max:120'],
            'van_telefoon' => ['nullable', 'string', 'max:40'],
            'van_adres' => ['nullable', 'string', 'max:300'],
            'van_kvk' => ['nullable', 'string', 'max:20'],
            'van_iban' => ['nullable', 'string', 'max:40'],
            'geen_btw_aftrek' => ['nullable', 'boolean'],
            'aan_naam' => ['required', 'string', 'max:120'],
            'aan_email' => ['nullable', 'email', 'max:180'],
            'aan_adres' => ['nullable', 'string', 'max:300'],
            'klant' => ['required', Rule::in(['zakelijk', 'particulier'])],
            'factuurnummer' => ['required', 'string', 'max:40'],
            'factuurdatum' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . config('rente.from')],
            'vervaldatum' => ['required', 'date', 'before:today', 'after_or_equal:factuurdatum'],
            'bedrag' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'btw' => ['nullable', Rule::in(array_map('strval', \App\Support\Market::vatRates()))],
            'termijn' => ['nullable', 'integer', 'min:' . ($consumer ? PaymentDemandService::TERM_CONSUMER : PaymentDemandService::TERM_MIN_BUSINESS), 'max:' . PaymentDemandService::TERM_MAX],
            'rente' => ['nullable', 'boolean'],
        ], [
            'vervaldatum.before' => __('De betaaltermijn moet voorbij zijn: kies een vervaldatum vóór vandaag.'),
            'vervaldatum.after_or_equal' => __('De vervaldatum kan niet vóór de factuurdatum liggen.'),
            'factuurdatum.after_or_equal' => __('De calculator rekent met facturen vanaf :date.', ['date' => \Illuminate\Support\Carbon::parse(config('rente.from'))->translatedFormat('j F Y')]),
            'termijn.min' => $consumer
                ? __('Voor een particulier is de termijn minstens :min dagen.', ['min' => PaymentDemandService::TERM_CONSUMER])
                : __('Kies een termijn van minstens :min dagen.', ['min' => PaymentDemandService::TERM_MIN_BUSINESS]),
            'bedrag.gt' => __('Vul het bedrag in dat nog openstaat.'),
        ], [
            'van_bedrijf' => __('jouw bedrijfsnaam'),
            'van_email' => __('jouw e-mailadres'),
            'aan_naam' => __('naam van de klant'),
            'aan_email' => __('e-mailadres van de klant'),
            'factuurnummer' => __('factuurnummer'),
            'factuurdatum' => __('factuurdatum'),
            'vervaldatum' => __('vervaldatum'),
            'bedrag' => __('openstaand bedrag'),
            'termijn' => __('termijn'),
        ]);
    }
}
