<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Definitieve creditnota's van vóór 1.55.1 werden niet verrekend met de
 * factuur die ze crediteren: die factuur bleef 'verstuurd' of 'achterstallig',
 * telde mee als openstaand (dashboard, dagoverzicht, debiteuren) en kreeg
 * zelfs betalingsherinneringen voor een bedrag dat de klant niet meer
 * verschuldigd is. Hier krijgen die paren alsnog de verrekenboeking (soort
 * 'credit' op beide documenten) die CreditNoteService::finalize sindsdien
 * direct zet — voor hoogstens het bedrag dat aan beide kanten nog openstaat.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pairs = DB::table('invoices as c')
            ->join('invoices as i', 'i.id', '=', 'c.credits_invoice_id')
            ->where('c.is_credit', true)
            ->whereNotIn('c.status', ['draft', 'cancelled'])
            ->whereIn('i.status', ['sent', 'partial', 'overdue'])
            ->orderBy('c.id')
            ->get([
                'c.id as credit_id', 'c.number as credit_number', 'c.company_id', 'c.sent_at',
                'c.total as credit_total', 'c.paid_total as credit_paid',
                'i.id as invoice_id', 'i.number as invoice_number', 'i.paid_at as invoice_paid_at',
                'i.total as invoice_total', 'i.paid_total as invoice_paid',
            ]);

        foreach ($pairs as $p) {
            $amount = round(min(
                (float) $p->invoice_total - (float) $p->invoice_paid,
                (float) $p->credit_total - (float) $p->credit_paid
            ), 2);
            if ($amount < 0.01) {
                continue;
            }

            // Verrekend op de dag dat de creditnota definitief werd.
            $paidOn = $p->sent_at ? substr((string) $p->sent_at, 0, 10) : now()->toDateString();
            $label = '€ ' . number_format($amount, 2, ',', '.');

            DB::transaction(function () use ($p, $amount, $paidOn, $label) {
                foreach ([
                    [$p->invoice_id, 'Verrekend met creditnota ' . $p->credit_number],
                    [$p->credit_id, 'Verrekend met factuur ' . $p->invoice_number],
                ] as [$documentId, $reference]) {
                    DB::table('payments')->insert([
                        'company_id' => $p->company_id,
                        'invoice_id' => $documentId,
                        'kind' => 'credit',
                        'amount' => $amount,
                        'paid_on' => $paidOn,
                        'method' => 'other',
                        'reference' => $reference,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Zelfde regels als Invoice::refreshStatus.
                $invoicePaid = round((float) $p->invoice_paid + $amount, 2);
                $invoiceSettled = $invoicePaid >= (float) $p->invoice_total;
                DB::table('invoices')->where('id', $p->invoice_id)->update([
                    'paid_total' => $invoicePaid,
                    'status' => $invoiceSettled ? 'paid' : 'partial',
                    'paid_at' => $invoiceSettled ? ($p->invoice_paid_at ?: now()) : $p->invoice_paid_at,
                    'updated_at' => now(),
                ]);

                $creditPaid = round((float) $p->credit_paid + $amount, 2);
                DB::table('invoices')->where('id', $p->credit_id)->update([
                    'paid_total' => $creditPaid,
                    'status' => $creditPaid >= (float) $p->credit_total - 0.004 ? 'settled' : 'sent',
                    'updated_at' => now(),
                ]);

                foreach ([
                    [$p->invoice_id, $p->invoice_number, 'verrekend met creditnota ' . $p->credit_number],
                    [$p->credit_id, $p->credit_number, 'verrekend met factuur ' . $p->invoice_number],
                ] as [$documentId, $number, $what]) {
                    DB::table('activity_logs')->insert([
                        'company_id' => $p->company_id,
                        'user_id' => null,
                        'user_name' => 'Systeem',
                        'action' => 'settled',
                        'subject_type' => 'factuur',
                        'subject_id' => $documentId,
                        'subject_label' => 'Factuur ' . $number,
                        'description' => "Factuur {$number} {$what} ({$label})",
                        'changes' => null,
                        'ip' => null,
                        'created_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        // Niets terug te draaien: de ontbrekende verrekening was een fout.
    }
};
