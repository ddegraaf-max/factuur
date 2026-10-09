<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

/**
 * Status herberekenen van facturen die bij het versturen al een verrekening
 * hadden (1.76.9).
 *
 * Tot 1.76.9 zag InvoiceManager::send() een verrekening die in dezelfde
 * handeling was opgeslagen niet (paid_total stond op het exemplaar in het
 * geheugen nog op 0). Zo'n factuur kwam op "verstuurd" terwijl er al een deel
 * binnen was; factuur 2026-0021 van 9 oktober 2026 was het eerste geval dat
 * opviel. De status herstelde zich pas bij een volgende betaling. Dit zet alle
 * betrokken facturen in één keer goed, met dezelfde regels als de app zelf
 * (Invoice::refreshStatus): deels betaald, of betaald als alles binnen is.
 */
return new class extends Migration
{
    public function up(): void
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('paid_total', '>', 0)
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->refreshStatus();

            if ($invoice->isDirty()) {
                $invoice->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Niets terug te draaien: de status volgt de betalingen, zoals de app hem ook zou zetten.
    }
};
