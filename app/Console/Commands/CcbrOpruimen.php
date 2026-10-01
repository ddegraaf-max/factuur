<?php

namespace App\Console\Commands;

use App\Models\CcbrCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Vernietigt verlopen uitkomsten uit het curatele- en bewindregister.
 *
 * ── Waarom dit moet ───────────────────────────────────────────────────────
 *
 * Artikel 2 van de gebruiksvoorwaarden van de Rechtspraak: de gegevens dienen
 * binnen zes maanden na beëindiging van de maatregel te worden vernietigd. Dat
 * is geen opruimadvies maar een voorwaarde van het abonnement — artikel 5 laat
 * de Raad de overeenkomst opzeggen als artikel 2 niet wordt nageleefd.
 *
 * De datum staat per uitkomst in `vernietigen_op`, berekend bij het vastleggen
 * (zie CcbrCheck::vernietigingsdatum). Deze taak is daarom met opzet dom: één
 * vergelijking, geen termijn die hier opnieuw wordt uitgerekend en dus ook niet
 * stilletjes kan afwijken van wat er bij het opslaan is afgesproken.
 *
 * Draait dagelijks; zie routes/console.php.
 */
class CcbrOpruimen extends Command
{
    protected $signature = 'ccbr:opruimen {--dry-run : Alleen tonen wat er zou verdwijnen}';

    protected $description = 'Vernietigt uitkomsten uit het curatele- en bewindregister waarvan de bewaartermijn is verlopen.';

    public function handle(): int
    {
        $verlopen = CcbrCheck::withoutGlobalScope('company')
            ->whereDate('vernietigen_op', '<=', today())
            ->get();

        if ($verlopen->isEmpty()) {
            $this->info('Niets te vernietigen.');

            return self::SUCCESS;
        }

        foreach ($verlopen as $check) {
            $this->line(sprintf(
                '  administratie %d, klant %d — gecontroleerd %s, te vernietigen op %s',
                $check->company_id,
                $check->customer_id,
                $check->checked_at->format('Y-m-d'),
                $check->vernietigen_op->format('Y-m-d')
            ));
        }

        if ($this->option('dry-run')) {
            $this->warn($verlopen->count() . ' uitkomst(en) zouden worden vernietigd (dry-run).');

            return self::SUCCESS;
        }

        $aantal = $verlopen->count();

        CcbrCheck::withoutGlobalScope('company')
            ->whereIn('id', $verlopen->pluck('id'))
            ->delete();

        /*
         * Wél vastleggen dát er vernietigd is, niet wát. Dat is het bewijs dat
         * de termijn wordt nageleefd; de gegevens zelf horen juist weg te zijn.
         */
        Log::info('CCBR: verlopen uitkomsten vernietigd', ['aantal' => $aantal]);

        $this->info($aantal . ' uitkomst(en) vernietigd.');

        return self::SUCCESS;
    }
}
