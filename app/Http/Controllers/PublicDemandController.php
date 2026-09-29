<?php

namespace App\Http\Controllers;

use App\Mail\PaymentDemandMail;
use App\Mail\PublicDemandMail;
use App\Models\PageView;
use App\Models\PaymentDemand;
use App\Models\PaymentDemandFile;
use App\Services\PaymentDemandService;
use App\Services\PublicDemandService;
use App\Support\DemandText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Online aanmaning zonder account, op /online-aanmaning: het formulier, de
 * wachtpagina ("bevestig je e-mailadres"), de bevestiging via de link uit de
 * mail en het resultaat met de links. De pagina van de aanmaning zelf zit in
 * PaymentDemandPageController; de regels in PublicDemandService.
 */
class PublicDemandController extends Controller
{
    public function __construct(private PublicDemandService $public, private PaymentDemandService $demands) {}

    public function page(Request $request)
    {
        // Na de bevestiging: de links en de stand. Alleen met de sleutel van de schuldeiser.
        if ($request->filled('ok')) {
            $demand = $this->find((string) $request->query('ok'));
            if ($demand && $demand->isCreditorKey($request->query('k'))) {
                if ($demand->isPending()) {
                    return redirect($demand->confirmUrl());
                }

                return $this->render('ok', $demand);
            }
        }

        // Na het formulier: wachten op de klik in de bevestigingsmail. De sleutel staat alleen in die mail.
        if ($request->filled('wacht')) {
            $demand = $this->find((string) $request->query('wacht'));
            if ($demand && $demand->isPending()) {
                return $this->render('wait', $demand, [
                    'mailFailed' => $request->boolean('fout'),
                    'resent' => $request->query('opnieuw') === '1',
                    'resendLimit' => $request->query('opnieuw') === 'limiet',
                ]);
            }
        }

        return $this->render('form');
    }

    public function store(Request $request): RedirectResponse|HttpResponse
    {
        // Lokveld: een mens vult het niet in.
        if ($request->filled('website')) {
            return redirect()->route('aanmaning');
        }

        $data = $this->validated($request);

        if (! $this->public->mayCreate($request)) {
            return back()->withInput()->withErrors(['limiet' => __('Er zijn vanaf deze verbinding te veel aanmaningen gemaakt. Probeer het over een uur opnieuw, of mail naar :email.', ['email' => brand('email')])]);
        }
        if ($this->public->debtorLimitReached($data['aan_email'] ?? null)) {
            return back()->withInput()->withErrors(['aan_email' => __('Naar dit adres zijn vandaag al een paar aanmaningen gegaan. Probeer het morgen, of laat het veld leeg en stuur de link zelf.')]);
        }

        $demand = $this->public->create($data, $request->file('factuur'), $request);
        PageView::milestone($request, PageView::EVENT_DEMAND);
        $mailed = $this->public->mailConfirm($demand);

        return redirect()->route('aanmaning', array_filter(['wacht' => $demand->token, 'fout' => $mailed ? null : 1]));
    }

    /** Bevestigingsmail opnieuw sturen vanaf de wachtpagina. */
    public function resend(Request $request): RedirectResponse
    {
        $demand = $this->find((string) $request->input('token'));
        if (! $demand || ! $demand->isPending() || $demand->confirmExpired()) {
            return redirect()->route('aanmaning');
        }

        $result = $this->public->resend($demand, $request);

        return redirect()->route('aanmaning', ['wacht' => $demand->token] + match ($result) {
            'sent' => ['opnieuw' => 1],
            'limit' => ['opnieuw' => 'limiet'],
            default => ['fout' => 1],
        });
    }

    /**
     * De link uit de mail: de gegevens met de knop om te bevestigen. Openen
     * alleen bevestigt niets; dat doet pas de knop.
     */
    public function confirmShow(Request $request, string $token)
    {
        $demand = $this->find($token);
        abort_unless($demand && $demand->isCreditorKey($request->query('k')), 404);

        if (! $demand->isPending()) {
            return redirect()->route('aanmaning', ['ok' => $demand->token, 'k' => $demand->creditor_key]);
        }

        return $this->render('confirm', $demand);
    }

    public function confirm(Request $request, string $token)
    {
        $demand = $this->find($token);
        abort_unless($demand && $demand->isCreditorKey($request->input('k')), 404);

        $done = redirect()->route('aanmaning', ['ok' => $demand->token, 'k' => $demand->creditor_key]);
        if (! $demand->isPending()) {
            return $done;
        }
        if ($demand->confirmExpired()) {
            return $this->render('confirm', $demand)->setStatusCode(410);
        }
        if ($this->public->debtorLimitReached($demand->sent_to)) {
            return $this->render('confirm', $demand, ['debtorLimit' => true])->setStatusCode(429);
        }

        if ($this->public->confirm($demand, $request)) {
            PageView::milestone($request, PageView::EVENT_DEMAND_CONFIRMED);
        }

        return $done;
    }

    /**
     * De berekening vooraf, voor het blok onder het formulier. Alleen bedragen
     * en datums komen binnen; namen en adressen blijven in de browser.
     */
    public function calculation(Request $request): JsonResponse
    {
        $this->normalizeAmount($request);
        $data = $request->validate([
            'bedrag' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'vervaldatum' => ['required', 'date', 'before:today', 'after_or_equal:' . config('rente.from')],
            'klant' => ['required', Rule::in(['zakelijk', 'particulier'])],
            'termijn' => ['nullable', 'integer', 'min:1', 'max:' . PaymentDemandService::TERM_MAX],
            'rente' => ['nullable', 'boolean'],
            'geen_btw_aftrek' => ['nullable', 'boolean'],
            'aan_email' => ['nullable', 'boolean'], // alleen óf er een adres is: zonder adres tellen de dagen voor de post mee
        ]);

        // Zonder adres van de klant verstuurt de schuldeiser de brief zelf: dan tellen de dagen voor de post mee.
        $demand = $this->demands->make(['aan_email' => empty($data['aan_email']) ? null : 'klant@voorbeeld.nl', 'van_bedrijf' => '', 'aan_naam' => '', 'factuurnummer' => ''] + $data);
        $claim = $this->demands->claimAsSent($demand);
        $day = $demand->deadline->translatedFormat('j F Y');

        return response()->json([
            'with_interest' => $claim['with_interest'],
            'principal' => money($claim['principal']),
            'interest' => money($claim['interest']),
            'interest_label' => ($claim['business'] ? __('Wettelijke handelsrente') : __('Wettelijke rente'))
                . ' (' . __(':days dagen', ['days' => $claim['interest_days']]) . ', ' . rtrim(rtrim(number_format($claim['rate'], 2, ',', ''), '0'), ',') . '%)',
            'total' => money($claim['total']),
            'per_day' => money($claim['per_day']),
            'costs' => money($claim['costs_total']),
            'costs_label' => $claim['costs_vat'] > 0 ? __('Incassokosten na de termijn, inclusief btw') : __('Incassokosten na de termijn'),
            'term_days' => $demand->term_days,
            'note' => $demand->isBusiness()
                ? __('Bevestig je vandaag, dan kan je klant tot en met :date zonder incassokosten betalen.', ['date' => $day])
                : __('Termijn van :days dagen, te rekenen vanaf de dag na ontvangst. Bevestig je vandaag, dan loopt de termijn tot en met :date.', ['days' => $demand->term_days, 'date' => $day]),
        ]);
    }

    /**
     * Voorbeeld: precies wat wij versturen. De pagina van de klant met
     * verzonnen gegevens, en de drie mails. Er wordt niets opgeslagen.
     */
    public function example()
    {
        $demand = $this->public->sample();
        $claim = $this->demands->claim($demand);
        $url = route('aanmaning.example');

        return Inertia::render('Demands/Show', PaymentDemandPageController::pageProps($demand, $claim, $this->demands) + [
            'demo' => [
                'url' => route('aanmaning'),
                'mails' => [
                    ['title' => __('1. Verzoek om te bevestigen (aan jou)'), 'to' => $demand->creditor_email, 'subject' => (new PublicDemandMail($demand, 'confirm', $claim, $url))->envelope()->subject,
                        'html' => (new PublicDemandMail($demand, 'confirm', $claim, $url))->render()],
                    ['title' => __('2. Mail aan je klant, na je bevestiging'), 'to' => $demand->sent_to, 'reply_to' => $demand->creditor_email, 'attachments' => 'aanmaning-2026-089.pdf, ' . $demand->file->filename,
                        'subject' => (new PaymentDemandMail($demand, $claim, '', ''))->envelope()->subject,
                        'html' => str_replace($demand->url(), $url, (new PaymentDemandMail($demand, $claim, '', ''))->render())],
                    ['title' => __('3. Mail aan jou, na je bevestiging'), 'to' => $demand->creditor_email, 'subject' => (new PublicDemandMail($demand, 'ready', $claim, $url))->envelope()->subject,
                        'html' => (new PublicDemandMail($demand, 'ready', $claim, $url))->render()],
                ],
            ],
        ]);
    }

    /** De pagina in één van haar standen: formulier, wachten, bevestigen of klaar. */
    private function render(string $state, ?PaymentDemand $demand = null, array $extra = []): HttpResponse
    {
        return response()->view('marketing.aanmaning', $extra + [
            'state' => $state,
            'demand' => $demand,
            'claim' => $demand ? $this->demands->claim($demand) : null,
            'facts' => $demand ? $this->public->factsSummary($demand) : null,
            'text' => $demand ? DemandText::letter($demand, $this->demands->claimAsSent($demand)) : null,
            'expired' => (bool) $demand?->confirmExpired(),
            'terms' => [
                'zakelijk' => PaymentDemandService::TERM_BUSINESS,
                'particulier' => PaymentDemandService::TERM_CONSUMER,
                'min_zakelijk' => PaymentDemandService::TERM_MIN_BUSINESS,
                'max' => PaymentDemandService::TERM_MAX,
            ],
        ])->header('Cache-Control', $demand ? 'private, no-store' : 'no-cache, private');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $this->normalizeAmount($request);
        $consumer = $request->input('klant') === 'particulier';

        return $request->validate([
            'van_bedrijf' => ['required', 'string', 'max:120'],
            'van_email' => ['required', 'email', 'max:180'],
            'van_telefoon' => ['nullable', 'string', 'max:40'],
            'van_adres' => ['nullable', 'string', 'max:300'],
            'van_kvk' => ['nullable', 'string', 'max:20'],
            'van_iban' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z]{2}\d{2}[A-Za-z0-9 ]{10,35}$/'],
            'geen_btw_aftrek' => ['nullable', 'boolean'],
            'aan_naam' => ['required', 'string', 'max:120'],
            'aan_email' => ['nullable', 'email', 'max:180', 'different:van_email'],
            'aan_kvk' => ['nullable', 'digits:8'],
            'aan_adres' => ['nullable', 'string', 'max:300'],
            'klant' => ['required', Rule::in(['zakelijk', 'particulier'])],
            'factuurnummer' => ['required', 'string', 'max:40'],
            'factuurdatum' => ['nullable', 'date', 'before_or_equal:today'],
            'vervaldatum' => ['required', 'date', 'before:today', 'after_or_equal:' . config('rente.from')],
            'bedrag' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'termijn' => ['nullable', 'integer', 'min:' . ($consumer ? PaymentDemandService::TERM_CONSUMER : PaymentDemandService::TERM_MIN_BUSINESS), 'max:' . PaymentDemandService::TERM_MAX],
            'rente' => ['nullable', 'boolean'],
            'factuur' => ['nullable', 'file', 'max:' . PaymentDemandFile::MAX_KB, 'mimetypes:' . implode(',', PaymentDemandFile::MIME_TYPES)],
        ], [
            'van_email.required' => __('Vul je e-mailadres in: daar komt de link om te bevestigen.'),
            'van_iban.regex' => __('Dit rekeningnummer klopt niet. Vul een IBAN in, bijvoorbeeld NL91 ABNA 0417 1643 00.'),
            'aan_email.different' => __('Het e-mailadres van je klant is hetzelfde als dat van jou.'),
            'aan_kvk.digits' => __('Een KvK-nummer bestaat uit 8 cijfers.'),
            'vervaldatum.before' => __('De betaaltermijn moet voorbij zijn: kies een vervaldatum vóór vandaag.'),
            'vervaldatum.after_or_equal' => __('Er wordt gerekend met facturen vanaf :date.', ['date' => \Illuminate\Support\Carbon::parse(config('rente.from'))->translatedFormat('j F Y')]),
            'termijn.min' => $consumer
                ? __('Voor een particulier is de termijn minstens :min dagen.', ['min' => PaymentDemandService::TERM_CONSUMER])
                : __('Kies een termijn van minstens :min dagen.', ['min' => PaymentDemandService::TERM_MIN_BUSINESS]),
            'bedrag.gt' => __('Vul het bedrag in dat nog openstaat.'),
            'factuur.mimetypes' => __('De kopie van de factuur moet een PDF, PNG of JPG zijn.'),
            'factuur.max' => __('De kopie van de factuur mag maximaal 8 MB groot zijn.'),
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

    /** "1.250,50" wordt "1250.50"; invoer met alleen een punt blijft zoals hij is. */
    private function normalizeAmount(Request $request): void
    {
        $amount = trim((string) $request->input('bedrag'));
        if (str_contains($amount, ',')) {
            $amount = str_replace(',', '.', str_replace('.', '', $amount));
        }
        $request->merge(['bedrag' => preg_replace('/[^\d.\-]/', '', $amount)]);
    }

    private function find(string $token): ?PaymentDemand
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        return PaymentDemand::withoutGlobalScope('company')->whereNull('invoice_id')->where('token', $token)->first();
    }
}
