<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Verstuurt concept-facturen waarvan de ingeplande verzenddatum is bereikt.
 * Draait dagelijks via de scheduler (zie routes/console.php).
 */
class SendScheduledInvoices extends Command
{
    protected $signature = 'invoices:send-scheduled';

    protected $description = 'Verstuur concept-facturen die voor vandaag (of eerder) zijn ingepland.';

    public function handle(InvoiceManager $manager): int
    {
        $due = Invoice::where('status', 'draft')
            ->whereNotNull('scheduled_send_on')
            ->whereDate('scheduled_send_on', '<=', now())
            // Demo-omgevingen mailen nooit echt: overslaan.
            ->whereHas('company', fn ($q) => $q->where('is_demo', false))
            ->with('company')
            ->get();

        $sent = 0;
        foreach ($due as $invoice) {
            // Geen toegang meer (proef verlopen, niet betaald)? Dan blijft het concept staan.
            if (! $invoice->company?->hasAccess()) {
                continue;
            }
            try {
                $invoice->scheduled_send_on = null;
                $manager->send($invoice);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Ingeplande factuur versturen mislukt', [
                    'invoice' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Verstuurd: {$sent} ingeplande factu(u)r(en).");

        return self::SUCCESS;
    }
}
