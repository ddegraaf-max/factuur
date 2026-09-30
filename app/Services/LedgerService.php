<?php

namespace App\Services;

use App\Models\BookYear;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Boeken in het grootboek.
 *
 * ── Eén deur ──────────────────────────────────────────────────────────────
 *
 * Alles wat boekt gaat hier langs: de verkoopfactuur, de inkoopfactuur, de
 * betaling, de bankmutatie, de beginbalans, de handmatige correctie en de
 * jaarafsluiting. Daardoor is er één plek waar de regels staan, en één plek om
 * te lezen als je wilt weten waarom een boeking is zoals hij is.
 *
 * De harde invarianten staan niet hier maar in de database (zie de migratie):
 * debet is credit, en een vastgesteld jaar zit dicht. Wat hier staat is de
 * vriendelijke variant ervan — een foutmelding in gewone taal in plaats van een
 * databasefout — plus de dingen die een database niet kan weten, zoals dat je
 * niet op een rubriek mag boeken.
 */
class LedgerService
{
    /** @var array<int, array<string, int>> rekening-id per RGS-code, per administratie */
    private array $cache = [];

    /**
     * Boekt één journaalpost.
     *
     * @param  array<int, array{
     *     rgs?: string, account?: LedgerAccount|int, debit?: int, credit?: int,
     *     description?: string, vat_rate?: float|null, vat_cents?: int,
     *     customer_id?: int|null, supplier_name?: string|null
     * }>  $lines  bedragen in hele centen
     * @param  array{source_type?: string|null, source_id?: int|null, created_by?: int|null, number?: string|null}  $meta
     *                                                                                                                  `number` is voor het opnieuw boeken van hetzelfde document: dan houdt de
     *                                                                                                                  boeking haar oorspronkelijke boekstuknummer in plaats van de teller op te
     *                                                                                                                  hogen. Anders laat elke herboeking een gat in de nummering achter, en dat is
     *                                                                                                                  precies waar een accountant naar gaat zoeken.
     */
    public function post(
        Company $company,
        string $journalCode,
        Carbon|string $date,
        string $description,
        array $lines,
        array $meta = []
    ): JournalEntry {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $year = (int) $date->format('Y');

        $journal = Journal::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('code', $journalCode)
            ->first();

        if (! $journal) {
            throw new RuntimeException("dagboek {$journalCode} bestaat niet in deze administratie");
        }

        $voorbereid = $this->prepare($company, $lines);
        $this->assertBalanced($voorbereid);
        $this->assertYearOpen($company, $year);

        return DB::transaction(function () use ($company, $journal, $date, $year, $description, $voorbereid, $meta) {
            $entry = JournalEntry::withoutGlobalScope('company')->create([
                'company_id' => $company->id,
                'journal_id' => $journal->id,
                'year' => $year,
                'number' => $meta['number'] ?? $this->nextNumber($company, $journal, $year),
                'date' => $date->toDateString(),
                'description' => mb_substr($description, 0, 300),
                'source_type' => $meta['source_type'] ?? null,
                'source_id' => $meta['source_id'] ?? null,
                'created_by' => $meta['created_by'] ?? (auth()->check() ? auth()->id() : null),
            ]);

            $sort = 0;
            foreach ($voorbereid as $line) {
                $sort += 10;
                JournalLine::withoutGlobalScope('company')->create([
                    'company_id' => $company->id,
                    'journal_entry_id' => $entry->id,
                    'ledger_account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit_cents' => $line['debit'],
                    'credit_cents' => $line['credit'],
                    'vat_rate' => $line['vat_rate'],
                    'vat_cents' => $line['vat_cents'],
                    'customer_id' => $line['customer_id'],
                    'supplier_name' => $line['supplier_name'],
                    'sort' => $sort,
                ]);
            }

            return $entry->load('lines');
        });
    }

    /**
     * Boekt een post terug met een tegengestelde post, in plaats van hem te
     * verwijderen.
     *
     * Een geboekte post weghalen zou het boekstuknummer laten vervallen, en een
     * gat in de nummering is precies waar een accountant naar gaat zoeken. Een
     * tegenboeking laat zien wát er is teruggedraaid en wanneer.
     */
    public function reverse(JournalEntry $entry, ?Carbon $date = null, ?string $reason = null): JournalEntry
    {
        $company = Company::findOrFail($entry->company_id);
        $date ??= Carbon::parse($entry->date);

        $lines = $entry->lines->map(fn (JournalLine $line) => [
            'account' => $line->ledger_account_id,
            // Om en om: wat debet stond gaat credit en omgekeerd.
            'debit' => $line->credit_cents,
            'credit' => $line->debit_cents,
            'description' => $line->description,
            'vat_rate' => $line->vat_rate,
            'vat_cents' => -$line->vat_cents,
            'customer_id' => $line->customer_id,
            'supplier_name' => $line->supplier_name,
        ])->all();

        $omschrijving = 'Tegenboeking ' . $entry->number
            . ($reason ? ' — ' . $reason : '');

        return $this->post(
            $company,
            $entry->journal->code,
            $date,
            $omschrijving,
            $lines,
            ['source_type' => 'reversal', 'source_id' => $entry->id]
        );
    }

    /**
     * De boeking die bij dit brondocument hoort, of null.
     *
     * Hiermee kan alles wat boekt eerst kijken of het al gebeurd is. De
     * database dwingt af dat er niet twee zijn.
     */
    public function findBySource(Company $company, string $type, int $id): ?JournalEntry
    {
        return JournalEntry::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('source_type', $type)
            ->where('source_id', $id)
            ->first();
    }

    /**
     * Haalt de boeking van een brondocument weg zodat hij opnieuw geboekt kan
     * worden — bijvoorbeeld nadat een concept-factuur is aangepast.
     *
     * Alleen in een open jaar, en alleen als het document nog niet definitief
     * is: een verstuurde factuur wordt tegengeboekt, niet gewist.
     */
    public function removeForSource(Company $company, string $type, int $id): bool
    {
        $entry = $this->findBySource($company, $type, $id);
        if (! $entry) {
            return false;
        }

        $this->assertYearOpen($company, (int) $entry->year);

        DB::transaction(function () use ($entry) {
            // De regels eerst: de uitgestelde balanscontrole in de database
            // kijkt of de post nog bestaat en laat een verwijderde post met
            // rust.
            JournalLine::withoutGlobalScope('company')->where('journal_entry_id', $entry->id)->delete();
            $entry->delete();
        });

        return true;
    }

    /** Het boekjaar, aangemaakt als het nog niet bestond. */
    public function bookYear(Company $company, int $year): BookYear
    {
        return BookYear::withoutGlobalScope('company')->firstOrCreate(
            ['company_id' => $company->id, 'year' => $year],
            ['status' => 'open']
        );
    }

    /**
     * Het id van de rekening met deze RGS-code; bestaat hij nog niet in deze
     * administratie, dan wordt hij uit RGS bijgezet.
     *
     * Het startschema van RGS is klein, en dat is goed: een zzp'er hoeft geen
     * honderd rekeningen te zien. Maar er komt een dag dat er een factuur met
     * 6% btw wordt ingevoerd, en dan moet rubriek 1c er zijn. Bijzetten uit de
     * officiële lijst is dan beter dan de boeking weigeren of hem ergens anders
     * parkeren.
     */
    public function accountOrAdd(Company $company, string $rgsCode): int
    {
        try {
            return $this->account($company, $rgsCode);
        } catch (RuntimeException $e) {
            if (! \App\Support\Rgs::vind($rgsCode)) {
                throw $e;
            }

            $rekening = app(ChartOfAccountsService::class)->addFromRgs($company, $rgsCode);
            $this->forget($company);

            return $rekening->id;
        }
    }

    /** Het id van de rekening met deze RGS-code, of een duidelijke fout. */
    public function account(Company $company, string $rgsCode): int
    {
        if (! isset($this->cache[$company->id])) {
            $this->cache[$company->id] = LedgerAccount::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->whereNotNull('rgs_code')
                ->pluck('id', 'rgs_code')
                ->all();
        }

        $id = $this->cache[$company->id][$rgsCode] ?? null;

        if (! $id) {
            throw new RuntimeException(
                "rekening {$rgsCode} bestaat niet in deze administratie; "
                . 'is het rekeningschema aangelegd?'
            );
        }

        return $id;
    }

    /** Vergeet de rekeningen die in het geheugen staan (na het aanleggen). */
    public function forget(?Company $company = null): void
    {
        if ($company) {
            unset($this->cache[$company->id]);

            return;
        }

        $this->cache = [];
    }

    /**
     * Zet de meegegeven regels om in iets waar de database mee overweg kan:
     * rekening-id's in plaats van RGS-codes, en centen als hele getallen.
     */
    private function prepare(Company $company, array $lines): array
    {
        $uit = [];

        foreach ($lines as $i => $line) {
            $accountId = match (true) {
                isset($line['rgs']) => $this->accountOrAdd($company, $line['rgs']),
                ($line['account'] ?? null) instanceof LedgerAccount => $line['account']->id,
                isset($line['account']) => (int) $line['account'],
                default => throw new RuntimeException("regel {$i} heeft geen rekening"),
            };

            $debit = (int) round($line['debit'] ?? 0);
            $credit = (int) round($line['credit'] ?? 0);

            /*
             * Een negatief bedrag is geen fout van de gebruiker maar van de
             * code die de boeking samenstelt: een creditnota is een boeking aan
             * de andere kant, niet een boeking met een min ervoor. Toch vangen
             * we het op, want anders weigert de database het en staat er een
             * onbegrijpelijke melding op het scherm.
             */
            if ($debit < 0) {
                $credit -= $debit;
                $debit = 0;
            }
            if ($credit < 0) {
                $debit -= $credit;
                $credit = 0;
            }

            // Een regel van nul cent zegt niets en mag er niet in. Stilletjes
            // weglaten is beter dan de hele boeking weigeren: een factuurregel
            // met een bedrag van nul komt echt voor.
            if ($debit === 0 && $credit === 0) {
                continue;
            }

            $this->assertPostable($company, $accountId);

            $uit[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => isset($line['description'])
                    ? mb_substr((string) $line['description'], 0, 300)
                    : null,
                'vat_rate' => $line['vat_rate'] ?? null,
                'vat_cents' => (int) round($line['vat_cents'] ?? 0),
                'customer_id' => $line['customer_id'] ?? null,
                'supplier_name' => isset($line['supplier_name'])
                    ? mb_substr((string) $line['supplier_name'], 0, 200)
                    : null,
            ];
        }

        if (count($uit) < 2) {
            throw new RuntimeException(
                'een boeking heeft ten minste twee regels nodig: '
                . 'één waar het vandaan komt en één waar het naartoe gaat'
            );
        }

        return $uit;
    }

    private function assertBalanced(array $lines): void
    {
        $debet = array_sum(array_column($lines, 'debit'));
        $credit = array_sum(array_column($lines, 'credit'));

        if ($debet !== $credit) {
            throw new RuntimeException(sprintf(
                'deze boeking is niet in balans: debet € %s, credit € %s (verschil € %s)',
                number_format($debet / 100, 2, ',', '.'),
                number_format($credit / 100, 2, ',', '.'),
                number_format(abs($debet - $credit) / 100, 2, ',', '.')
            ));
        }
    }

    private function assertYearOpen(Company $company, int $year): void
    {
        $boekjaar = $this->bookYear($company, $year);

        if ($boekjaar->isClosed()) {
            throw new RuntimeException(
                "boekjaar {$year} is vastgesteld; een correctie hoort in het lopende jaar"
            );
        }
    }

    /**
     * Op een hoofdrubriek of rubriek mag niet geboekt worden. Dat kan de
     * database niet zien — het is een eigenschap van de rekening, niet van de
     * regel — dus staat het hier.
     */
    private function assertPostable(Company $company, int $accountId): void
    {
        $account = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->find($accountId);

        if (! $account) {
            throw new RuntimeException("rekening {$accountId} hoort niet bij deze administratie");
        }

        if (! $account->postable) {
            throw new RuntimeException(
                "op {$account->number} {$account->name} wordt niet geboekt: "
                . 'dat is een rubriek, kies een rekening eronder'
            );
        }

        if (! $account->active) {
            throw new RuntimeException("rekening {$account->number} {$account->name} staat op inactief");
        }
    }

    /**
     * Het volgende boekstuknummer, bijv. "VRK 2026-0001".
     *
     * De teller staat in een eigen tabel en wordt met een UPDATE opgehoogd, niet
     * met max()+1: twee boekingen op hetzelfde moment krijgen anders hetzelfde
     * nummer, en dat valt pas op als de accountant een dubbel boekstuk ziet.
     */
    private function nextNumber(Company $company, Journal $journal, int $year): string
    {
        $sleutel = ['company_id' => $company->id, 'journal_id' => $journal->id, 'year' => $year];

        // De rij moet bestaan voordat hij vergrendeld kan worden.
        DB::table('journal_sequences')->insertOrIgnore($sleutel + ['last' => 0]);

        // Deze UPDATE vergrendelt de rij tot het eind van de transactie; een
        // tweede boeking wacht en krijgt dus een ander nummer.
        DB::table('journal_sequences')->where($sleutel)->increment('last');

        $laatste = (int) DB::table('journal_sequences')->where($sleutel)->value('last');

        return sprintf('%s %d-%04d', $journal->code, $year, $laatste);
    }
}
