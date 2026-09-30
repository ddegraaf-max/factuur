<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LedgerAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * De stukken die uit het grootboek komen: proefbalans, balans,
 * winst-en-verliesrekening en grootboekkaart.
 *
 * ── Twee soorten rekeningen, twee soorten tijd ────────────────────────────
 *
 * Een balansrekening is een toestand: het banksaldo is wat er sinds het begin
 * van de administratie op en af is gegaan. Een resultaatrekening is een
 * beweging: de omzet van dít jaar, niet van alle jaren bij elkaar.
 *
 * Dat onderscheid zit in elke functie hieronder, en het is de reden dat er twee
 * datums in het spel zijn: het begin van het boekjaar en de peildatum. Wie dat
 * door elkaar haalt krijgt een balans die niet sluit, of omzet die elk jaar
 * hoger wordt zonder dat er iets gebeurt.
 */
class LedgerReportService
{
    /**
     * De proefbalans: per rekening het totaal debet en credit over een periode,
     * met het saldo dat daaruit volgt.
     *
     * De optelling onderaan hoort links en rechts gelijk te zijn. Is hij dat
     * niet, dan is er buiten het grootboek om iets gewijzigd; het scherm zegt
     * dat dan ook in plaats van het verschil weg te laten.
     *
     * @return array{rows: array<int, array>, totals: array{debit:int, credit:int, balanced:bool}}
     */
    public function trialBalance(Company $company, Carbon $from, Carbon $to): array
    {
        $sommen = $this->sums($company, $from, $to);
        $rekeningen = $this->accounts($company);

        $rows = [];
        $totaalDebet = 0;
        $totaalCredit = 0;

        foreach ($rekeningen as $account) {
            if (! $account->postable) {
                continue;
            }

            $debet = $sommen[$account->id]['debit'] ?? 0;
            $credit = $sommen[$account->id]['credit'] ?? 0;

            // Een rekening waar in deze periode niets op staat laten we weg: een
            // proefbalans met tweehonderd nulregels leest niemand.
            if ($debet === 0 && $credit === 0) {
                continue;
            }

            $totaalDebet += $debet;
            $totaalCredit += $credit;

            $rows[] = [
                'id' => $account->id,
                'number' => $account->number,
                'name' => $account->name,
                'rgs_code' => $account->rgs_code,
                'statement' => $account->statement,
                'debit' => $debet,
                'credit' => $credit,
                // Het saldo aan de kant waar deze rekening thuishoort.
                'balance' => $account->signedCents($debet, $credit),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => $totaalDebet,
                'credit' => $totaalCredit,
                'balanced' => $totaalDebet === $totaalCredit,
            ],
        ];
    }

    /**
     * De balans op een peildatum, met de winst-en-verliesrekening van het
     * boekjaar eronder.
     *
     * Het resultaat van het lopende jaar staat als aparte regel bij het eigen
     * vermogen. Dat is niet een kunstje om de balans te laten sluiten: zolang
     * het jaar niet is vastgesteld hóórt de winst nog niet op een
     * vermogensrekening te staan, want de ondernemer heeft er nog niets over
     * besloten.
     *
     * @return array{
     *     assets: array<int, array>, liabilities: array<int, array>,
     *     income: array<int, array>, expenses: array<int, array>,
     *     result: int, totals: array{assets:int, liabilities:int, balanced:bool}
     * }
     */
    public function balanceSheet(Company $company, int $year, ?Carbon $to = null): array
    {
        $to ??= Carbon::create($year, 12, 31)->endOfDay();
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();

        // Balansrekeningen: alles vanaf het begin van de administratie.
        $balans = $this->sums($company, Carbon::create(1900, 1, 1), $to);
        // Resultaatrekeningen: alleen dit boekjaar.
        $resultaat = $this->sums($company, $yearStart, $to);

        $rekeningen = $this->accounts($company);

        $assets = [];
        $liabilities = [];
        $income = [];
        $expenses = [];
        $totaalActief = 0;
        $totaalPassief = 0;
        $winst = 0;

        foreach ($rekeningen as $account) {
            if (! $account->postable) {
                continue;
            }

            if ($account->statement === 'balans') {
                $debet = $balans[$account->id]['debit'] ?? 0;
                $credit = $balans[$account->id]['credit'] ?? 0;
                if ($debet === 0 && $credit === 0) {
                    continue;
                }
                $saldo = $debet - $credit;

                /*
                 * De kant waar een rekening thuishoort bepaalt waar hij wordt
                 * afgedrukt, maar een saldo mag de andere kant op staan: een
                 * bankrekening die rood staat is een schuld. We kijken dus naar
                 * het werkelijke saldo en niet naar de bedoelde kant — anders
                 * staat er een negatief bedrag bij de bezittingen en telt de
                 * balans nog wel op, maar zegt hij iets onwaars.
                 */
                $regel = [
                    'id' => $account->id,
                    'number' => $account->number,
                    'name' => $account->name,
                    'rgs_code' => $account->rgs_code,
                    'group' => $this->groupName($account, $rekeningen),
                    'amount' => abs($saldo),
                ];

                if ($saldo > 0) {
                    $assets[] = $regel;
                    $totaalActief += $saldo;
                } else {
                    $liabilities[] = $regel;
                    $totaalPassief += -$saldo;
                }

                continue;
            }

            $debet = $resultaat[$account->id]['debit'] ?? 0;
            $credit = $resultaat[$account->id]['credit'] ?? 0;
            if ($debet === 0 && $credit === 0) {
                continue;
            }

            $regel = [
                'id' => $account->id,
                'number' => $account->number,
                'name' => $account->name,
                'rgs_code' => $account->rgs_code,
                'group' => $this->groupName($account, $rekeningen),
                'amount' => abs($credit - $debet),
            ];

            // Opbrengsten staan credit, kosten debet.
            if ($credit >= $debet) {
                $income[] = $regel;
                $winst += $credit - $debet;
            } else {
                $expenses[] = $regel;
                $winst -= $debet - $credit;
            }
        }

        /*
         * Het resultaat hoort bij het eigen vermogen. Winst verhoogt het
         * (passiefzijde), verlies verlaagt het.
         *
         * ── Waarom hier het hele resultaat staat en niet dat van dit jaar ──
         *
         * De balans telt de balansrekeningen vanaf het begin van de
         * administratie; het verschil tussen bezittingen en schulden is dus álle
         * winst die nog nergens is ondergebracht, niet alleen die van dit jaar.
         * Is 2025 nooit vastgesteld, dan zit die winst nog in de debiteuren en
         * de bank. Zou hier alleen het resultaat van 2026 staan, dan sluit de
         * balans niet — en dat is geen rekenfout van ons maar een echte stand
         * van zaken die je moet zien.
         *
         * Een vastgesteld jaar valt hier automatisch buiten: de afsluitboeking
         * heeft zijn resultaatrekeningen op nul gezet, dus tellen ze niet meer
         * mee. Daarom staat er ná het vaststellen niets dubbel.
         */
        // $balans is de optelling vanaf het begin over álle rekeningen; daar
        // zitten de resultaatrekeningen ook in. Een tweede vraag aan de database
        // is dus niet nodig.
        $cumulatief = 0;
        foreach ($balans as $id => $bedrag) {
            $account = $rekeningen[$id] ?? null;
            if ($account && $account->statement === 'resultaat') {
                $cumulatief += $bedrag['credit'] - $bedrag['debit'];
            }
        }

        // Wat er uit eerdere, nog niet vastgestelde jaren komt, krijgt zijn eigen
        // regel. Anders lijkt het alsof dit jaar meer heeft opgeleverd dan de
        // winst-en-verliesrekening eronder zegt.
        $eerder = $cumulatief - $winst;

        foreach ([
            [$winst, $winst >= 0 ? "Resultaat {$year} (winst)" : "Resultaat {$year} (verlies)"],
            [$eerder, 'Resultaat eerdere jaren (nog niet vastgesteld)'],
        ] as [$bedrag, $naam]) {
            if ($bedrag === 0) {
                continue;
            }

            $regel = [
                'id' => null,
                'number' => '',
                'name' => $naam,
                'rgs_code' => null,
                'group' => 'Eigen vermogen',
                'amount' => abs($bedrag),
            ];

            if ($bedrag > 0) {
                $liabilities[] = $regel;
                $totaalPassief += $bedrag;
            } else {
                $assets[] = $regel;
                $totaalActief += -$bedrag;
            }
        }

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'income' => $income,
            'expenses' => $expenses,
            'result' => $winst,
            'totals' => [
                'assets' => $totaalActief,
                'liabilities' => $totaalPassief,
                'balanced' => $totaalActief === $totaalPassief,
            ],
        ];
    }

    /**
     * De grootboekkaart van één rekening: het beginsaldo, elke boeking in de
     * periode, en het saldo dat na elke regel overblijft.
     *
     * Het beginsaldo hoort erbij. Zonder dat is een grootboekkaart een lijstje
     * mutaties waar je zelf mee moet gaan rekenen om te zien of het klopt met de
     * proefbalans, en dan klopt het meestal ergens niet.
     *
     * @return array{account: array, opening: int, rows: array<int, array>, closing: int}
     */
    public function accountCard(Company $company, LedgerAccount $account, Carbon $from, Carbon $to): array
    {
        /*
         * Bij een resultaatrekening begint het bij nul aan het begin van het
         * boekjaar: de omzet van vorig jaar hoort niet in de kaart van dit jaar.
         * Bij een balansrekening telt alles ervoor mee.
         */
        $vanaf = $account->statement === 'balans'
            ? Carbon::create(1900, 1, 1)
            : Carbon::create((int) $from->format('Y'), 1, 1)->startOfDay();

        $tot = $from->copy()->subDay();

        // Begint de periode op of vóór het startpunt, dan is er niets vóór de
        // periode en is het beginsaldo nul. Zonder deze regel zou er een
        // omgekeerde datumreeks naar de database gaan; die geeft toevallig ook
        // nul terug, maar dat is geluk en geen uitleg.
        $opening = 0;
        if ($tot->gte($vanaf)) {
            $voor = DB::table('journal_lines')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_lines.company_id', $company->id)
                ->where('journal_lines.ledger_account_id', $account->id)
                ->whereBetween('journal_entries.date', [$vanaf->toDateString(), $tot->toDateString()])
                ->selectRaw('COALESCE(SUM(journal_lines.debit_cents),0) AS d, COALESCE(SUM(journal_lines.credit_cents),0) AS c')
                ->first();

            $opening = ((int) ($voor->d ?? 0)) - ((int) ($voor->c ?? 0));
        }

        $regels = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->leftJoin('journals', 'journals.id', '=', 'journal_entries.journal_id')
            ->leftJoin('customers', 'customers.id', '=', 'journal_lines.customer_id')
            ->where('journal_lines.company_id', $company->id)
            ->where('journal_lines.ledger_account_id', $account->id)
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.sort')
            ->select([
                'journal_lines.id',
                'journal_lines.debit_cents',
                'journal_lines.credit_cents',
                'journal_lines.description',
                'journal_lines.supplier_name',
                'journal_entries.id as entry_id',
                'journal_entries.number as entry_number',
                'journal_entries.date',
                'journal_entries.description as entry_description',
                'journals.code as journal_code',
                'customers.name as customer_name',
            ])
            ->get();

        $loop = $opening;
        $rows = [];
        foreach ($regels as $r) {
            $loop += ((int) $r->debit_cents) - ((int) $r->credit_cents);
            $rows[] = [
                'id' => $r->id,
                'entry_id' => $r->entry_id,
                'entry_number' => $r->entry_number,
                'journal' => $r->journal_code,
                'date' => $r->date,
                'description' => $r->description ?: $r->entry_description,
                'relation' => $r->customer_name ?: $r->supplier_name,
                'debit' => (int) $r->debit_cents,
                'credit' => (int) $r->credit_cents,
                'running' => $loop,
                // Hetzelfde saldo, positief als het aan de goede kant staat.
                'running_signed' => $account->side === 'C' ? -$loop : $loop,
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'number' => $account->number,
                'name' => $account->name,
                'rgs_code' => $account->rgs_code,
                'side' => $account->side,
                'statement' => $account->statement,
            ],
            'opening' => $account->side === 'C' ? -$opening : $opening,
            'rows' => $rows,
            'closing' => $account->side === 'C' ? -$loop : $loop,
        ];
    }

    /**
     * De btw-aangifte uit het grootboek: per rubriek van het formulier het
     * bedrag dat erin hoort.
     *
     * Dit is de reden dat de btw op subrekeningen per rubriek staat en niet op
     * één hoop: de aangifte is dan een som en geen herberekening. Een
     * herberekening uit de facturen geeft een ander getal zodra er één boeking
     * is die geen factuur was, en dan weet niemand welk van de twee klopt.
     *
     * @return array<string, array{label:string, base:int, vat:int}>
     */
    public function vatReturn(Company $company, Carbon $from, Carbon $to): array
    {
        $sommen = $this->sums($company, $from, $to);
        $perCode = [];
        foreach ($this->accounts($company) as $account) {
            if ($account->rgs_code) {
                $perCode[$account->rgs_code] = $sommen[$account->id] ?? ['debit' => 0, 'credit' => 0];
            }
        }

        $credit = fn (string $code) => ($perCode[$code]['credit'] ?? 0) - ($perCode[$code]['debit'] ?? 0);
        $debet = fn (string $code) => ($perCode[$code]['debit'] ?? 0) - ($perCode[$code]['credit'] ?? 0);

        return [
            '1a' => ['label' => 'Leveringen/diensten belast met hoog tarief',
                'base' => $credit('WOmzNodOdh') + $credit('WOmzNohOlh'), 'vat' => $credit('BSchBepBtwOla')],
            '1b' => ['label' => 'Leveringen/diensten belast met laag tarief',
                'base' => $credit('WOmzNodOdl') + $credit('WOmzNohOlv'), 'vat' => $credit('BSchBepBtwOlv')],
            '1c' => ['label' => 'Leveringen/diensten belast met overige tarieven',
                'base' => $credit('WOmzNodOdo') + $credit('WOmzNohOlo'), 'vat' => $credit('BSchBepBtwOlo')],
            '1e' => ['label' => 'Leveringen/diensten belast met 0% of niet bij u belast',
                'base' => $credit('WOmzNodOdg'), 'vat' => 0],
            '2a' => ['label' => 'Leveringen/diensten waarbij de btw naar u is verlegd',
                'base' => $credit('WOmzNodOdv'), 'vat' => $credit('BSchBepBtwOlw')],
            '3b' => ['label' => 'Leveringen naar landen binnen de EU',
                'base' => $credit('WOmzNodOdi') + $credit('WOmzNohOli'), 'vat' => 0],
            '5b' => ['label' => 'Voorbelasting',
                'base' => 0, 'vat' => $debet('BSchBepBtwVoo')],
        ];
    }

    /**
     * Debet en credit per rekening over een periode.
     *
     * @return array<int, array{debit:int, credit:int}>
     */
    private function sums(Company $company, Carbon $from, Carbon $to): array
    {
        $rijen = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.company_id', $company->id)
            ->whereBetween('journal_entries.date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('journal_lines.ledger_account_id')
            ->selectRaw('journal_lines.ledger_account_id AS id,
                COALESCE(SUM(journal_lines.debit_cents),0) AS d,
                COALESCE(SUM(journal_lines.credit_cents),0) AS c')
            ->get();

        $uit = [];
        foreach ($rijen as $r) {
            $uit[(int) $r->id] = ['debit' => (int) $r->d, 'credit' => (int) $r->c];
        }

        return $uit;
    }

    /** @return \Illuminate\Support\Collection<int, LedgerAccount> */
    private function accounts(Company $company)
    {
        return LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->orderBy('sort')
            ->get()
            ->keyBy('id');
    }

    /**
     * De naam van de rubriek waar deze rekening onder valt, voor de kopjes in
     * de balans en de winst-en-verliesrekening. We nemen niveau 3 als dat er is,
     * anders niveau 2 — dat is het detail waarop een jaarrekening wordt gelezen.
     */
    private function groupName(LedgerAccount $account, $alle): string
    {
        $namen = [];   // niveau => naam
        $huidig = $account;

        // Naar boven lopen en per niveau de naam onthouden. Een teller erbij,
        // want een verwijzing die per ongeluk naar zichzelf wijst mag geen
        // eindeloze lus opleveren.
        for ($stap = 0; $stap < 10 && $huidig && $huidig->parent_id; $stap++) {
            if (! isset($alle[$huidig->parent_id])) {
                break;
            }
            $huidig = $alle[$huidig->parent_id];
            $namen[$huidig->level] = $huidig->name;
        }

        // Niveau 3 is het detail waarop een jaarrekening wordt gelezen
        // ("Vorderingen op handelsdebiteuren"); niveau 2 is de hoofdrubriek
        // ("Vorderingen") en dient als terugval.
        return $namen[3] ?? $namen[2] ?? $account->name;
    }
}
