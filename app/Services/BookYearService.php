<?php

namespace App\Services;

use App\Models\BookYear;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Support\Rgs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Het boekjaar: openen, een beginbalans invoeren, en vaststellen.
 */
class BookYearService
{
    public function __construct(
        private LedgerService $ledger,
        private LedgerReportService $reports,
    ) {}

    /**
     * Zet de beginbalans van een boekjaar.
     *
     * ── Waarom dit er moet zijn ───────────────────────────────────────────
     *
     * Wie overstapt heeft al een administratie. Zonder beginbalans begint het
     * grootboek bij nul, en dan staan de openstaande facturen van vorig jaar
     * nergens, is het banksaldo nul terwijl er geld op staat, en klopt de eerste
     * jaarrekening met niets. Een jaar overtypen is geen optie; de stand
     * overnemen wel.
     *
     * De boeking staat op 1 januari van het boekjaar en gaat in het memoriaal.
     * Sluit hij niet, dan komt het verschil op de beginbalansregel van het eigen
     * vermogen te staan — met de omschrijving erbij, zodat zichtbaar blijft dat
     * er iets niet is meegekomen in plaats van dat het stil wordt weggewerkt.
     *
     * @param  array<int, array{account_id?:int, rgs?:string, debit?:float, credit?:float, description?:string}>  $rows
     *                                                                                                                  bedragen in euro's
     */
    public function setOpeningBalance(Company $company, int $year, array $rows): JournalEntry
    {
        $datum = Carbon::create($year, 1, 1)->startOfDay();

        // Een beginbalans hoort er één te zijn. Is er al een, dan vervangen we
        // hem — zolang het jaar open is.
        $this->ledger->removeForSource($company, 'opening', $year);

        $regels = [];
        $debet = 0;
        $credit = 0;

        foreach ($rows as $row) {
            $d = (int) round(((float) ($row['debit'] ?? 0)) * 100);
            $c = (int) round(((float) ($row['credit'] ?? 0)) * 100);
            if ($d === 0 && $c === 0) {
                continue;
            }

            $regel = [
                'debit' => $d,
                'credit' => $c,
                'description' => $row['description'] ?? 'Beginbalans',
            ];
            if (isset($row['rgs'])) {
                $regel['rgs'] = $row['rgs'];
            } else {
                $regel['account'] = (int) $row['account_id'];
            }

            $regels[] = $regel;
            $debet += $d;
            $credit += $c;
        }

        if (! $regels) {
            throw new RuntimeException('een beginbalans zonder regels is geen beginbalans');
        }

        $verschil = $debet - $credit;
        if ($verschil !== 0) {
            $regels[] = [
                'rgs' => Rgs::KAPITAAL_BEGINBALANS,
                $verschil > 0 ? 'credit' : 'debit' => abs($verschil),
                'description' => 'Verschil beginbalans — nog uit te zoeken',
            ];
        }

        return $this->ledger->post(
            $company,
            'MEM',
            $datum,
            "Beginbalans {$year}",
            $regels,
            ['source_type' => 'opening', 'source_id' => $year]
        );
    }

    /**
     * Stelt een boekjaar vast: het resultaat naar het eigen vermogen, en het
     * jaar op slot.
     *
     * De volgorde is niet vrij. De afsluitboeking moet erin voordat het jaar
     * dichtgaat, want daarna laat de database er niets meer in. Eerst dus
     * controleren, dan boeken, dan sluiten — en dat alles in één transactie,
     * zodat er geen half vastgesteld jaar kan bestaan.
     */
    public function close(Company $company, int $year, ?int $userId = null): BookYear
    {
        $boekjaar = $this->ledger->bookYear($company, $year);

        if ($boekjaar->isClosed()) {
            throw new RuntimeException("boekjaar {$year} is al vastgesteld");
        }

        $van = Carbon::create($year, 1, 1)->startOfDay();
        $tot = Carbon::create($year, 12, 31)->endOfDay();

        /*
         * Sluit de proefbalans? Zo niet, dan is er buiten het grootboek om iets
         * gewijzigd, en dan is vaststellen het laatste wat je wilt doen: je zet
         * een fout vast die je daarna niet meer kunt corrigeren.
         */
        $proef = $this->reports->trialBalance($company, $van, $tot);
        if (! $proef['totals']['balanced']) {
            throw new RuntimeException(
                'de proefbalans van ' . $year . ' sluit niet (debet € '
                . number_format($proef['totals']['debit'] / 100, 2, ',', '.') . ', credit € '
                . number_format($proef['totals']['credit'] / 100, 2, ',', '.')
                . '). Zoek dat eerst uit; vaststellen kan daarna niet meer terug.'
            );
        }

        // Is er een volgend jaar dat nog niet bestaat, dan maken we het aan:
        // anders kan er na het vaststellen even niets geboekt worden.
        $this->ledger->bookYear($company, $year + 1);

        return DB::transaction(function () use ($company, $year, $van, $tot, $boekjaar, $userId) {
            $this->postResultToEquity($company, $year, $van, $tot);

            $boekjaar->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $userId ?? (auth()->check() ? auth()->id() : null),
            ]);

            return $boekjaar->fresh();
        });
    }

    /** Draait het vaststellen terug: het jaar weer open, de afsluitboeking eruit. */
    public function reopen(Company $company, int $year): BookYear
    {
        $boekjaar = $this->ledger->bookYear($company, $year);

        if (! $boekjaar->isClosed()) {
            throw new RuntimeException("boekjaar {$year} staat al open");
        }

        return DB::transaction(function () use ($company, $year, $boekjaar) {
            // Eerst open, anders weigert de database de afsluitboeking te wissen.
            $boekjaar->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null]);
            $this->ledger->removeForSource($company, 'close', $year);

            return $boekjaar->fresh();
        });
    }

    /**
     * De afsluitboeking: elke resultaatrekening terug naar nul, het saldo naar
     * het ondernemingsvermogen.
     *
     * Na deze boeking is de omzet van het jaar nul — niet omdat hij weg is, maar
     * omdat hij in het vermogen zit. Dat is precies wat vaststellen betekent, en
     * de reden dat de balans van volgend jaar begint met het vermogen van dit
     * jaar zonder dat er iets hoeft te worden overgetypt.
     */
    private function postResultToEquity(Company $company, int $year, Carbon $van, Carbon $tot): ?JournalEntry
    {
        if ($this->ledger->findBySource($company, 'close', $year)) {
            return null;
        }

        $rekeningen = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('statement', 'resultaat')
            ->where('postable', true)
            ->pluck('id');

        if ($rekeningen->isEmpty()) {
            return null;
        }

        $sommen = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.company_id', $company->id)
            ->whereIn('journal_lines.ledger_account_id', $rekeningen)
            ->whereBetween('journal_entries.date', [$van->toDateString(), $tot->toDateString()])
            ->groupBy('journal_lines.ledger_account_id')
            ->selectRaw('journal_lines.ledger_account_id AS id,
                COALESCE(SUM(journal_lines.debit_cents),0) AS d,
                COALESCE(SUM(journal_lines.credit_cents),0) AS c')
            ->get();

        $regels = [];
        $netto = 0;   // positief = winst

        foreach ($sommen as $r) {
            $saldo = ((int) $r->d) - ((int) $r->c);
            if ($saldo === 0) {
                continue;
            }
            // Staat de rekening debet, dan gaat hij credit om op nul te komen.
            $regels[] = [
                'account' => (int) $r->id,
                $saldo > 0 ? 'credit' : 'debit' => abs($saldo),
                'description' => "Afsluiting {$year}",
            ];
            $netto -= $saldo;
        }

        if (! $regels) {
            return null;
        }

        $regels[] = [
            'rgs' => Rgs::KAPITAAL,
            $netto >= 0 ? 'credit' : 'debit' => abs($netto),
            'description' => $netto >= 0
                ? "Winst {$year} naar ondernemingsvermogen"
                : "Verlies {$year} ten laste van ondernemingsvermogen",
        ];

        return $this->ledger->post(
            $company,
            'MEM',
            Carbon::create($year, 12, 31),
            "Afsluiting boekjaar {$year}",
            $regels,
            ['source_type' => 'close', 'source_id' => $year]
        );
    }
}
