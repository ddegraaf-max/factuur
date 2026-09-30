<?php

namespace App\Http\Controllers;

use App\Models\BookYear;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\BookYearService;
use App\Services\ChartOfAccountsService;
use App\Services\LedgerPostingService;
use App\Services\LedgerReportService;
use App\Services\LedgerService;
use App\Support\Rgs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Het grootboek op het scherm: rekeningschema, journaal, proefbalans, balans en
 * grootboekkaart.
 *
 * ── Waarom de bedragen als euro's naar het scherm gaan ────────────────────
 *
 * In de database staan hele centen, want daar moet debet exact credit zijn. Op
 * het scherm wil een gebruiker euro's zien. De omzetting gebeurt hier, op één
 * plek, zodat er geen pagina is die per ongeluk centen als euro's afdrukt.
 */
class LedgerController extends Controller
{
    public function __construct(
        private LedgerService $ledger,
        private LedgerReportService $reports,
        private BookYearService $years,
        private ChartOfAccountsService $chart,
    ) {}

    /** Het rekeningschema. */
    public function accounts(Request $request)
    {
        $company = $this->bedrijf($request);

        $accounts = LedgerAccount::orderBy('sort')->get()
            ->map(fn (LedgerAccount $a) => [
                'id' => $a->id,
                'number' => $a->number,
                'name' => $a->name,
                'rgs_code' => $a->rgs_code,
                'side' => $a->side,
                'statement' => $a->statement,
                'level' => $a->level,
                'postable' => $a->postable,
                'is_system' => $a->is_system,
                'active' => $a->active,
            ]);

        // Hoe vaak er op een rekening is geboekt: bepaalt of hij nog weg kan.
        $gebruikt = DB::table('journal_lines')
            ->where('company_id', $company->id)
            ->groupBy('ledger_account_id')
            ->selectRaw('ledger_account_id AS id, COUNT(*) AS n')
            ->pluck('n', 'id');

        return Inertia::render('Ledger/Accounts', [
            'accounts' => $accounts->map(fn ($a) => $a + ['used' => (int) ($gebruikt[$a['id']] ?? 0)]),
            // Wat er nog bij te kiezen is uit RGS. Alleen rekeningen waarop
            // geboekt kan worden; rubrieken komen automatisch mee.
            'available' => $this->availableFromRgs($accounts->pluck('rgs_code')->filter()->all()),
            'journals' => Journal::orderBy('sort')->get(['id', 'code', 'name', 'kind', 'active']),
        ]);
    }

    /** Een rekening uit RGS bijzetten. */
    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'rgs_code' => ['required', 'string', 'max:20'],
        ]);

        $rekening = $this->chart->addFromRgs($this->bedrijf($request), $data['rgs_code']);
        $this->ledger->forget($this->bedrijf($request));

        return back()->with('flash', "Rekening {$rekening->number} {$rekening->name} is toegevoegd.");
    }

    /** Naam aanpassen of een rekening op inactief zetten. */
    public function updateAccount(Request $request, LedgerAccount $account)
    {
        abort_unless($account->company_id === $this->bedrijf($request)->id, 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:200'],
            'active' => ['sometimes', 'boolean'],
        ]);

        // Een rekening waar het pakket zelf op boekt mag niet op inactief: dan
        // loopt de eerste factuur daarna vast.
        if (($data['active'] ?? true) === false && $account->is_system) {
            return back()->withErrors([
                'active' => 'Deze rekening hoort bij het standaardschema en wordt door het pakket zelf gebruikt. '
                    . 'Hem uitzetten zou boekingen laten mislukken.',
            ]);
        }

        $account->update($data);

        return back()->with('flash', 'De rekening is bijgewerkt.');
    }

    /** De proefbalans over een periode. */
    public function trialBalance(Request $request)
    {
        $company = $this->bedrijf($request);
        [$van, $tot, $jaar] = $this->period($request);

        $proef = $this->reports->trialBalance($company, $van, $tot);

        return Inertia::render('Ledger/TrialBalance', [
            'rows' => collect($proef['rows'])->map(fn ($r) => [
                'id' => $r['id'],
                'number' => $r['number'],
                'name' => $r['name'],
                'rgs_code' => $r['rgs_code'],
                'statement' => $r['statement'],
                'debit' => $this->euro($r['debit']),
                'credit' => $this->euro($r['credit']),
                'balance' => $this->euro($r['balance']),
            ]),
            'totals' => [
                'debit' => $this->euro($proef['totals']['debit']),
                'credit' => $this->euro($proef['totals']['credit']),
                'balanced' => $proef['totals']['balanced'],
                'difference' => $this->euro($proef['totals']['debit'] - $proef['totals']['credit']),
            ],
            'filters' => ['from' => $van->toDateString(), 'to' => $tot->toDateString(), 'year' => $jaar],
            'years' => $this->knownYears($company),
        ]);
    }

    /** De balans met de winst-en-verliesrekening. */
    public function balanceSheet(Request $request)
    {
        $company = $this->bedrijf($request);
        $jaar = (int) ($request->integer('year') ?: now()->year);
        $tot = $request->date('to') ? Carbon::parse($request->input('to')) : Carbon::create($jaar, 12, 31);

        $balans = $this->reports->balanceSheet($company, $jaar, $tot->endOfDay());

        $naarEuro = fn (array $regels) => collect($regels)->map(fn ($r) => [
            'id' => $r['id'],
            'number' => $r['number'],
            'name' => $r['name'],
            'group' => $r['group'],
            'amount' => $this->euro($r['amount']),
        ])->values();

        return Inertia::render('Ledger/BalanceSheet', [
            'assets' => $naarEuro($balans['assets']),
            'liabilities' => $naarEuro($balans['liabilities']),
            'income' => $naarEuro($balans['income']),
            'expenses' => $naarEuro($balans['expenses']),
            'result' => $this->euro($balans['result']),
            'totals' => [
                'assets' => $this->euro($balans['totals']['assets']),
                'liabilities' => $this->euro($balans['totals']['liabilities']),
                'balanced' => $balans['totals']['balanced'],
            ],
            'filters' => ['year' => $jaar, 'to' => $tot->toDateString()],
            'years' => $this->knownYears($company),
        ]);
    }

    /** De grootboekkaart van één rekening. */
    public function card(Request $request, LedgerAccount $account)
    {
        abort_unless($account->company_id === $this->bedrijf($request)->id, 403);

        $company = $this->bedrijf($request);
        [$van, $tot, $jaar] = $this->period($request);

        $kaart = $this->reports->accountCard($company, $account, $van, $tot);

        return Inertia::render('Ledger/Card', [
            'account' => $kaart['account'],
            'opening' => $this->euro($kaart['opening']),
            'closing' => $this->euro($kaart['closing']),
            'rows' => collect($kaart['rows'])->map(fn ($r) => [
                'id' => $r['id'],
                'entry_id' => $r['entry_id'],
                'entry_number' => $r['entry_number'],
                'journal' => $r['journal'],
                'date' => $r['date'],
                'description' => $r['description'],
                'relation' => $r['relation'],
                'debit' => $this->euro($r['debit']),
                'credit' => $this->euro($r['credit']),
                'running' => $this->euro($r['running_signed']),
            ]),
            'filters' => ['from' => $van->toDateString(), 'to' => $tot->toDateString(), 'year' => $jaar],
            'years' => $this->knownYears($company),
            'accounts' => LedgerAccount::where('postable', true)->orderBy('sort')
                ->get(['id', 'number', 'name']),
        ]);
    }

    /** Het journaal: alle boekingen, nieuwste eerst. */
    public function entries(Request $request)
    {
        $company = $this->bedrijf($request);
        [$van, $tot, $jaar] = $this->period($request);

        $query = JournalEntry::with(['journal:id,code,name', 'lines.account:id,number,name'])
            ->whereBetween('date', [$van->toDateString(), $tot->toDateString()])
            ->orderByDesc('date')->orderByDesc('id');

        if ($code = $request->string('journal')->toString()) {
            $query->whereHas('journal', fn ($q) => $q->where('code', $code));
        }
        if ($zoek = trim($request->string('q')->toString())) {
            $query->where(function ($q) use ($zoek) {
                $q->where('description', 'like', "%{$zoek}%")
                    ->orWhere('number', 'like', "%{$zoek}%");
            });
        }

        $posten = $query->paginate(50)->withQueryString();

        return Inertia::render('Ledger/Entries', [
            'entries' => [
                'data' => collect($posten->items())->map(fn (JournalEntry $e) => [
                    'id' => $e->id,
                    'number' => $e->number,
                    'journal' => $e->journal?->code,
                    'date' => $e->date?->toDateString(),
                    'description' => $e->description,
                    'source_type' => $e->source_type,
                    'source_id' => $e->source_id,
                    'total' => $this->euro($e->totalCents()),
                    'lines' => $e->lines->map(fn ($l) => [
                        'account_id' => $l->ledger_account_id,
                        'number' => $l->account?->number,
                        'name' => $l->account?->name,
                        'description' => $l->description,
                        'debit' => $this->euro($l->debit_cents),
                        'credit' => $this->euro($l->credit_cents),
                    ]),
                ]),
                'links' => $posten->linkCollection(),
                'total' => $posten->total(),
            ],
            'journals' => Journal::orderBy('sort')->get(['id', 'code', 'name', 'kind']),
            'accounts' => LedgerAccount::where('postable', true)->where('active', true)
                ->orderBy('sort')->get(['id', 'number', 'name']),
            'filters' => [
                'from' => $van->toDateString(), 'to' => $tot->toDateString(), 'year' => $jaar,
                'journal' => $request->string('journal')->toString(),
                'q' => $request->string('q')->toString(),
            ],
            'years' => $this->knownYears($company),
        ]);
    }

    /**
     * Een handmatige boeking in het memoriaal.
     *
     * Hier komt alles terecht wat geen factuur is: een afschrijving, een
     * privé-opname, een correctie. Het scherm rekent de twee kanten voor, maar de
     * database is de baas: sluit het niet, dan komt er niets in.
     */
    public function storeEntry(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'journal' => ['required', 'string', 'max:10'],
            'description' => ['required', 'string', 'min:2', 'max:300'],
            'lines' => ['required', 'array', 'min:2', 'max:100'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.debit' => ['nullable', 'numeric', 'between:0,99999999'],
            'lines.*.credit' => ['nullable', 'numeric', 'between:0,99999999'],
            'lines.*.description' => ['nullable', 'string', 'max:300'],
        ]);

        $company = $this->bedrijf($request);

        /*
         * De rekeningen moeten van deze administratie zijn. Zonder deze controle
         * kan iemand met een gewijzigd formulier op de rekening van een ander
         * boeken.
         *
         * Uitdrukkelijk op company_id en niet op de globale filter: die slaat
         * over zodra company_id leeg is, en dan zou deze lijst de rekeningen van
         * alle administraties bevatten. bedrijf() vangt dat al af; dit is het
         * tweede slot op de plek waar het écht misgaat als het misgaat.
         */
        $eigen = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)->pluck('id')->all();
        foreach ($data['lines'] as $line) {
            if (! in_array((int) $line['account_id'], $eigen, true)) {
                return back()->withErrors(['lines' => 'Een van de rekeningen hoort niet bij deze administratie.']);
            }
        }

        $regels = collect($data['lines'])->map(fn ($l) => [
            'account' => (int) $l['account_id'],
            'debit' => (int) round(((float) ($l['debit'] ?? 0)) * 100),
            'credit' => (int) round(((float) ($l['credit'] ?? 0)) * 100),
            'description' => $l['description'] ?? null,
        ])->all();

        try {
            $post = $this->ledger->post(
                $company,
                $data['journal'],
                Carbon::parse($data['date']),
                $data['description'],
                $regels,
                ['source_type' => 'manual']
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['lines' => $this->melding($e)]);
        }

        return back()->with('flash', "Boeking {$post->number} is vastgelegd.");
    }

    /** Een boeking terugdraaien met een tegenboeking. */
    public function reverseEntry(Request $request, JournalEntry $entry)
    {
        abort_unless($entry->company_id === $this->bedrijf($request)->id, 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);

        try {
            $tegen = $this->ledger->reverse($entry->load('lines', 'journal'), null, $data['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['entry' => $this->melding($e)]);
        }

        return back()->with('flash', "Boeking {$entry->number} is teruggedraaid met {$tegen->number}.");
    }

    /** De boekjaren, met de stand van de controle per jaar. */
    public function bookYears(Request $request)
    {
        $company = $this->bedrijf($request);

        $jaren = collect($this->knownYears($company));
        // Ook het huidige jaar, ook als er nog niets in geboekt is.
        if (! $jaren->contains(now()->year)) {
            $jaren->push(now()->year);
        }

        $standen = BookYear::pluck('status', 'year');
        $gesloten = BookYear::with('closedBy:id,name')->get()->keyBy('year');

        $rijen = $jaren->sort()->values()->map(function (int $jaar) use ($company, $standen, $gesloten) {
            $van = Carbon::create($jaar, 1, 1)->startOfDay();
            $tot = Carbon::create($jaar, 12, 31)->endOfDay();

            $proef = $this->reports->trialBalance($company, $van, $tot);
            $balans = $this->reports->balanceSheet($company, $jaar);

            // ->get() en niet [$jaar]: een jaar zonder rij in book_years is het
            // gewone geval, en dat mag geen waarschuwing over een ontbrekende
            // sleutel opleveren.
            $rij = $gesloten->get($jaar);

            return [
                'year' => $jaar,
                'status' => $standen[$jaar] ?? 'open',
                'closed_at' => $rij?->closed_at?->toDateString(),
                'closed_by' => $rij?->closedBy?->name,
                'entries' => JournalEntry::where('year', $jaar)->count(),
                'turnover' => $this->euro($proef['totals']['debit']),
                'trial_balanced' => $proef['totals']['balanced'],
                'sheet_balanced' => $balans['totals']['balanced'],
                'result' => $this->euro($balans['result']),
            ];
        });

        return Inertia::render('Ledger/BookYears', [
            'years' => $rijen,
            'opening' => $this->openingRows($company, (int) ($request->integer('year') ?: now()->year)),
            'accounts' => LedgerAccount::where('postable', true)->where('active', true)
                ->orderBy('sort')->get(['id', 'number', 'name', 'side', 'statement']),
            'suggested' => $this->suggestedOpeningAccounts(),
        ]);
    }

    /** Een boekjaar vaststellen. */
    public function closeYear(Request $request, int $year)
    {
        try {
            $this->years->close($this->bedrijf($request), $year, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['year' => $this->melding($e)]);
        }

        return back()->with('flash', "Boekjaar {$year} is vastgesteld. Het resultaat staat in het eigen vermogen.");
    }

    /** Een vastgesteld boekjaar weer openen. */
    public function reopenYear(Request $request, int $year)
    {
        try {
            $this->years->reopen($this->bedrijf($request), $year);
        } catch (\Throwable $e) {
            return back()->withErrors(['year' => $this->melding($e)]);
        }

        return back()->with('flash', "Boekjaar {$year} staat weer open.");
    }

    /** De beginbalans van een boekjaar vastleggen. */
    public function storeOpeningBalance(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*.account_id' => ['required', 'integer'],
            'rows.*.debit' => ['nullable', 'numeric', 'between:0,99999999'],
            'rows.*.credit' => ['nullable', 'numeric', 'between:0,99999999'],
        ]);

        // Zie storeEntry: uitdrukkelijk op deze administratie.
        $company = $this->bedrijf($request);
        $eigen = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)->pluck('id')->all();
        foreach ($data['rows'] as $row) {
            if (! in_array((int) $row['account_id'], $eigen, true)) {
                return back()->withErrors(['rows' => 'Een van de rekeningen hoort niet bij deze administratie.']);
            }
        }

        try {
            $post = $this->years->setOpeningBalance($this->bedrijf($request), (int) $data['year'], $data['rows']);
        } catch (\Throwable $e) {
            return back()->withErrors(['rows' => $this->melding($e)]);
        }

        return back()->with('flash', "De beginbalans staat erin ({$post->number}).");
    }

    /** Alles opnieuw boeken vanuit de facturen. */
    public function rebuild(Request $request, LedgerPostingService $posting)
    {
        $company = $this->bedrijf($request);

        // Een administratie van vóór het grootboek heeft nog geen rekeningschema
        // en geen dagboeken; zonder dagboek kan er niets worden geboekt. Het
        // schema aanleggen kan altijd: wat er al staat, blijft staan.
        $this->chart->seed($company);
        $this->ledger->forget($company);

        $jaar = $request->integer('year') ?: null;
        $uit = $posting->rebuild($company, $jaar ?: null);

        $melding = sprintf('Geboekt: %d factu(u)r(en), %d inkoop, %d ontvangst(en).',
            $uit['invoices'], $uit['purchases'], $uit['payments']);

        if ($uit['errors']) {
            /*
             * De losse foutmeldingen gaan naar het logboek en niet naar het
             * scherm: er kan een databasefout tussen zitten, en die bevat de hele
             * query met tabelnamen. Wat de gebruiker moet weten is hoeveel er
             * niet lukte en waar het staat.
             */
            \Illuminate\Support\Facades\Log::warning('Grootboek: herbouw met fouten', [
                'company' => $company->id,
                'fouten' => $uit['errors'],
            ]);

            return back()->withErrors(['rebuild' => $melding . ' ' . count($uit['errors'])
                . ' document(en) lukten niet; die staan in het logboek.']);
        }

        return back()->with('flash', $melding);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * De administratie van de ingelogde gebruiker, of 403.
     *
     * ── Waarom dit niet gewoon $request->user()->company is ───────────────
     *
     * Omdat company_id NULL kan zijn. users.company_id is nullable met
     * nullOnDelete: verdwijnt een administratie, dan blijft de gebruiker bestaan
     * met zijn rol maar zonder administratie. De bedrijfsfilter in de modellen
     * luidt "if (auth()->check() && auth()->user()->company_id)" en slaat bij
     * NULL dus over — en dan zou dit scherm het journaal van álle administraties
     * tonen, met klantnamen en bedragen erin.
     *
     * De middleware houdt zo iemand al tegen (EnsureSubscriptionActive). Dit is
     * het tweede slot: het grootboek is de plek waar alles bij elkaar staat, en
     * dat wil ik niet laten afhangen van één regel in een middleware die iemand
     * later kan verplaatsen.
     */
    private function bedrijf(Request $request): \App\Models\Company
    {
        $company = $request->user()?->company;

        abort_unless($company !== null, 403, 'Geen actieve administratie.');

        return $company;
    }

    /**
     * Wat de gebruiker van een mislukte boeking te zien krijgt.
     *
     * ── Waarom niet gewoon de foutmelding ─────────────────────────────────
     *
     * Omdat er twee soorten fouten langskomen. De ene komt uit LedgerService en
     * is voor de gebruiker geschreven: "deze boeking is niet in balans: debet
     * € 100,00, credit € 99,00". Die hoort op het scherm.
     *
     * De andere komt uit de database. Zo'n melding bevat de hele query met
     * tabel- en kolomnamen, en soms de waarden van de regel die werd ingevoegd.
     * Dat zegt de gebruiker niets en het vertelt wie meekijkt hoe de database in
     * elkaar zit. Die gaat naar het logboek, met een kenmerk zodat hij terug te
     * vinden is als iemand belt.
     */
    private function melding(\Throwable $e): string
    {
        if ($e instanceof \RuntimeException && ! $e instanceof \Illuminate\Database\QueryException) {
            return $e->getMessage();
        }

        $kenmerk = substr(md5($e->getMessage() . $e->getFile() . $e->getLine()), 0, 8);

        \Illuminate\Support\Facades\Log::error('Grootboek: boeking mislukt', [
            'kenmerk' => $kenmerk,
            'soort' => $e::class,
            'fout' => $e->getMessage(),
        ]);

        return 'Deze boeking kon niet worden vastgelegd. De fout staat in het '
            . "logboek onder kenmerk {$kenmerk}.";
    }

    /**
     * De periode uit de request: een boekjaar, of een eigen van/tot.
     *
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function period(Request $request): array
    {
        $jaar = (int) ($request->integer('year') ?: now()->year);

        $van = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::create($jaar, 1, 1)->startOfDay();
        $tot = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::create($jaar, 12, 31)->endOfDay();

        // Een omgedraaide periode geeft een leeg scherm zonder uitleg; zet hem
        // liever recht.
        if ($tot->lt($van)) {
            [$van, $tot] = [$tot->copy()->startOfDay(), $van->copy()->endOfDay()];
        }

        return [$van, $tot, $jaar];
    }

    /** @return array<int, int> de jaren waarin geboekt is, nieuwste eerst */
    private function knownYears($company): array
    {
        $jaren = JournalEntry::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->distinct()->orderByDesc('year')->pluck('year')
            ->map(fn ($j) => (int) $j)->all();

        if (! in_array(now()->year, $jaren, true)) {
            array_unshift($jaren, now()->year);
        }

        return $jaren;
    }

    /** De regels van de bestaande beginbalans van een jaar, voor het formulier. */
    private function openingRows($company, int $year): array
    {
        $post = $this->ledger->findBySource($company, 'opening', $year);
        if (! $post) {
            return ['year' => $year, 'number' => null, 'rows' => []];
        }

        return [
            'year' => $year,
            'number' => $post->number,
            'rows' => $post->load('lines.account')->lines->map(fn ($l) => [
                'account_id' => $l->ledger_account_id,
                'number' => $l->account?->number,
                'name' => $l->account?->name,
                'debit' => $this->euro($l->debit_cents),
                'credit' => $this->euro($l->credit_cents),
            ])->values()->all(),
        ];
    }

    /**
     * De rekeningen waar een beginbalans bijna altijd uit bestaat, in de
     * volgorde waarin iemand ze van zijn oude pakket overneemt. Bedoeld als
     * startpunt in het formulier, niet als beperking.
     */
    private function suggestedOpeningAccounts(): array
    {
        $codes = [
            Rgs::BANK, Rgs::KAS, Rgs::DEBITEUREN, Rgs::CREDITEUREN,
            Rgs::BTW_BEGINBALANS, Rgs::OVERIGE_VORDERINGEN, Rgs::OVERIGE_SCHULDEN,
            Rgs::KAPITAAL_BEGINBALANS,
        ];

        return LedgerAccount::whereIn('rgs_code', $codes)->get(['id', 'number', 'name', 'rgs_code'])
            ->sortBy(fn ($a) => array_search($a->rgs_code, $codes, true))
            ->values()->all();
    }

    /**
     * Wat er nog bij te kiezen is uit RGS: alleen rekeningen (niveau 4 en 5) die
     * deze administratie nog niet heeft.
     *
     * @param  array<int, string>  $aanwezig
     */
    private function availableFromRgs(array $aanwezig): array
    {
        $heeft = array_flip($aanwezig);

        return collect(Rgs::alles())
            ->filter(fn ($r) => $r['nivo'] >= 4 && ! isset($heeft[$r['code']]))
            ->map(fn ($r) => [
                'rgs_code' => $r['code'],
                'number' => $r['nummer'],
                'name' => $r['naam'],
                'statement' => Rgs::staat($r['code']),
            ])
            ->values()->all();
    }

    /** Centen naar euro's, als getal met twee decimalen. */
    private function euro(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
