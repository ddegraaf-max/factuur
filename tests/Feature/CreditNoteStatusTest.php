<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\CreditNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Een creditnota is nooit achterstallig (1.54.0): er valt niets te innen.
 * Het dashboard zette verstuurde creditnota's met een verstreken vervaldatum
 * tóch op 'overdue' (de factuurlijst sloeg ze al over); beide gaan nu via
 * Invoice::markOverdue(), dat creditnota's en incasso met rust laat.
 */
class CreditNoteStatusTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_dashboard_and_invoice_list_never_mark_a_credit_note_overdue(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $original = Invoice::regular()->whereIn('status', ['paid', 'sent'])->has('lines')->orderBy('id')->firstOrFail();
        $credit = app(CreditNoteService::class)->createFromInvoice($original);
        $credit->forceFill([
            'status' => 'sent', 'number' => 'C-2026-0001', 'sent_at' => now(),
            'due_date' => now()->subDays(3), // "0 dagen" betalingstermijn, dus de vervaldatum ligt snel in het verleden
        ])->save();

        $regular = Invoice::regular()->whereIn('status', ['sent', 'overdue'])->where('id', '!=', $original->id)->orderBy('id')->firstOrFail();
        $regular->forceFill(['status' => 'sent', 'due_date' => now()->subDays(3)])->save();

        $this->get(route('dashboard'))->assertOk();
        $this->assertSame('sent', $credit->fresh()->status, 'Creditnota blijft verstuurd');
        $this->assertSame('overdue', $regular->fresh()->status, 'Een gewone factuur wordt wél achterstallig');

        $this->get(route('invoices.index'))->assertOk();
        $this->assertSame('sent', $credit->fresh()->status);
        $this->assertFalse($credit->fresh()->is_overdue);
        $this->assertSame(0, $credit->fresh()->days_overdue);
    }

    public function test_mark_overdue_reports_how_many_invoices_it_touched_and_skips_credit_notes(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        Invoice::query()->update(['status' => 'paid']);
        $late = Invoice::regular()->orderBy('id')->take(2)->get();
        foreach ($late as $invoice) {
            $invoice->forceFill(['status' => 'sent', 'due_date' => now()->subDay()])->save();
        }
        $credit = Invoice::regular()->orderBy('id')->skip(2)->firstOrFail();
        $credit->forceFill(['is_credit' => true, 'status' => 'sent', 'due_date' => now()->subDay()])->save();

        $this->assertSame(2, Invoice::markOverdue());
        $this->assertSame('sent', $credit->fresh()->status);
        $this->assertSame(0, Invoice::markOverdue(), 'Tweede keer is er niets meer te markeren');
    }
}
