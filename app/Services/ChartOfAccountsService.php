<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Journal;
use App\Models\LedgerAccount;
use App\Support\Rgs;
use Illuminate\Support\Facades\DB;

/**
 * Legt het rekeningschema en de dagboeken aan voor één administratie.
 *
 * ── Waarom een vast startschema en geen lege lijst ────────────────────────
 *
 * Een boekhouding die begint met nul rekeningen is onbruikbaar voor iemand
 * zonder boekhoudkundige achtergrond: die moet dan zelf bedenken dat af te
 * dragen btw een schuld is en voorbelasting een vordering. Dat gaat mis, en het
 * gaat stil mis — de balans klopt namelijk gewoon, hij zegt alleen iets anders
 * dan de ondernemer denkt.
 *
 * Daarom het officiële startschema van RGS. Wie meer detail wil kiest er
 * rekeningen bij uit de rest van RGS; de vaste rekeningen blijven staan zodat de
 * rest van het pakket erop kan rekenen.
 *
 * Idempotent: bestaande rekeningen blijven ongemoeid, ook als de gebruiker de
 * naam heeft aangepast. Twee keer uitvoeren verandert niets.
 */
class ChartOfAccountsService
{
    /**
     * De dagboeken die elke administratie krijgt.
     *
     * code, naam, soort, vaste tegenrekening (RGS-code of null), volgorde
     */
    private const JOURNALS = [
        ['VRK', 'Verkoopboek', 'verkoop', null, 10],
        ['INK', 'Inkoopboek', 'inkoop', null, 20],
        ['BNK', 'Bank', 'bank', Rgs::BANK, 30],
        ['KAS', 'Kas', 'kas', Rgs::KAS, 40],
        ['MEM', 'Memoriaal', 'memoriaal', null, 50],
    ];

    /**
     * @return array{accounts:int, journals:int}
     */
    public function seed(Company $company): array
    {
        return DB::transaction(function () use ($company) {
            $accounts = $this->seedAccounts($company);
            $journals = $this->seedJournals($company);

            return ['accounts' => $accounts, 'journals' => $journals];
        });
    }

    private function seedAccounts(Company $company): int
    {
        $bestaand = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->pluck('id', 'rgs_code')
            ->all();

        $nieuw = 0;
        $sort = 0;

        foreach (Rgs::startschema() as $regel) {
            $sort += 10;

            if (isset($bestaand[$regel['code']])) {
                continue;
            }

            /*
             * De ouder is er al, want het startschema staat in de volgorde van
             * het schema: een rubriek komt voor de rekeningen eronder. Is hij er
             * toch niet — bij een RGS-versie waarin een tussenniveau ontbreekt —
             * dan hangt de rekening aan de rubriek erboven in plaats van in het
             * niets.
             */
            $parentId = null;
            foreach (array_reverse(Rgs::voorouders($regel['code'])) as $voorouder) {
                if (strlen($voorouder) >= 4 && isset($bestaand[$voorouder])) {
                    $parentId = $bestaand[$voorouder];
                    break;
                }
            }

            $account = LedgerAccount::withoutGlobalScope('company')->create([
                'company_id' => $company->id,
                'number' => $regel['nummer'],
                'name' => $regel['naam'],
                'rgs_code' => $regel['code'],
                'side' => $regel['dc'],
                'statement' => Rgs::staat($regel['code']),
                'level' => $regel['nivo'],
                'parent_id' => $parentId,
                // Op een hoofdrubriek of rubriek wordt niet geboekt; die dragen
                // alleen de indeling van de balans en het resultaat.
                'postable' => $regel['nivo'] >= 4,
                'is_system' => true,
                'active' => true,
                'sort' => $sort,
            ]);

            $bestaand[$regel['code']] = $account->id;
            $nieuw++;
        }

        return $nieuw;
    }

    private function seedJournals(Company $company): int
    {
        $rekeningen = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereNotNull('rgs_code')
            ->pluck('id', 'rgs_code')
            ->all();

        $bestaand = Journal::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->pluck('id', 'code')
            ->all();

        $nieuw = 0;

        foreach (self::JOURNALS as [$code, $naam, $soort, $rgs, $volgorde]) {
            if (isset($bestaand[$code])) {
                continue;
            }

            Journal::withoutGlobalScope('company')->create([
                'company_id' => $company->id,
                'code' => $code,
                'name' => $naam,
                'kind' => $soort,
                'ledger_account_id' => $rgs ? ($rekeningen[$rgs] ?? null) : null,
                'is_system' => true,
                'active' => true,
                'sort' => $volgorde,
            ]);

            $nieuw++;
        }

        return $nieuw;
    }

    /**
     * Voegt één rekening uit de rest van RGS toe aan het schema, met de
     * rubrieken erboven als die nog ontbreken.
     *
     * Hiermee kan een gebruiker verfijnen zonder een nummer te verzinnen: hij
     * kiest "Telefoonkosten" en krijgt de officiële code en het officiële
     * nummer erbij. Dat houdt de auditfile bruikbaar.
     */
    public function addFromRgs(Company $company, string $rgsCode): LedgerAccount
    {
        $regel = Rgs::vind($rgsCode);
        if (! $regel) {
            throw new \InvalidArgumentException("onbekende RGS-code: {$rgsCode}");
        }

        return DB::transaction(function () use ($company, $regel, $rgsCode) {
            $bestaand = LedgerAccount::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->pluck('id', 'rgs_code')
                ->all();

            // Eerst de rubrieken erboven, van hoog naar laag.
            $parentId = null;
            foreach (Rgs::voorouders($rgsCode) as $voorouder) {
                if (strlen($voorouder) < 4) {
                    continue;
                }
                if (isset($bestaand[$voorouder])) {
                    $parentId = $bestaand[$voorouder];

                    continue;
                }
                $ouderRegel = Rgs::vind($voorouder);
                if (! $ouderRegel) {
                    continue;
                }
                $gemaakt = $this->maak($company, $ouderRegel, $parentId);
                $bestaand[$voorouder] = $gemaakt->id;
                $parentId = $gemaakt->id;
            }

            if (isset($bestaand[$rgsCode])) {
                return LedgerAccount::withoutGlobalScope('company')->findOrFail($bestaand[$rgsCode]);
            }

            return $this->maak($company, $regel, $parentId);
        });
    }

    private function maak(Company $company, array $regel, ?int $parentId): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScope('company')->create([
            'company_id' => $company->id,
            'number' => $regel['nummer'],
            'name' => $regel['naam'],
            'rgs_code' => $regel['code'],
            'side' => $regel['dc'],
            'statement' => Rgs::staat($regel['code']),
            'level' => $regel['nivo'],
            'parent_id' => $parentId,
            'postable' => $regel['nivo'] >= 4,
            // Bijgekozen rekeningen zijn niet van het systeem: de gebruiker mag
            // ze weer weghalen zolang er niet op geboekt is.
            'is_system' => false,
            'active' => true,
            // Achter de rekening waar hij bij hoort, zodat het schema op nummer
            // blijft lopen.
            'sort' => $this->sortNaNummer($company, $regel['nummer']),
        ]);
    }

    /**
     * De sorteerwaarde vlak achter de rekening met het naast-lagere nummer, of
     * achteraan als er nog niets lager is. Zo komt een bijgekozen rekening op
     * zijn plek in het schema te staan in plaats van onderaan de lijst.
     */
    private function sortNaNummer(Company $company, string $nummer): int
    {
        $vorige = LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('number', '<', $nummer)
            ->max('sort');

        return (int) $vorige + 1;
    }
}
