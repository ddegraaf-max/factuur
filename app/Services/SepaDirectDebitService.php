<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DirectDebitBatch;
use App\Models\Invoice;
use App\Support\Iban;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use XMLWriter;

/**
 * Automatische incasso (SEPA Direct Debit, CORE en B2B): openstaande facturen
 * van klanten met een machtiging bundelen in een batch en daar een
 * pain.008.001.02-bestand van maken dat elke Nederlandse bank inleest
 * (Rabobank, ING, ABN AMRO, bunq, Knab, Triodos, SNS …).
 */
class SepaDirectDebitService
{
    /** Wat ontbreekt er nog om te kunnen incasseren? Leeg = klaar. */
    public function blockers(Company $company): array
    {
        $missing = [];
        if (! Iban::valid($company->iban)) $missing[] = __('een geldig IBAN van je bedrijf');
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{3}\d{6,}$/', strtoupper(preg_replace('/\s/', '', (string) $company->sepa_creditor_id)))) $missing[] = __('je Incassant-ID (bijv. NL12ZZZ123456780000, aan te vragen bij je bank)');

        return $missing;
    }

    /** Openstaande, verstuurde facturen van klanten met een actieve machtiging die nog niet in een batch zitten. */
    public function collectable(Company $company): Collection
    {
        return Invoice::query()->where('company_id', $company->id)
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereNull('direct_debit_batch_id')
            ->whereHas('customer', fn ($q) => $q->where('mandate_status', 'active'))
            ->with('customer')
            ->orderBy('due_date')
            ->get()
            ->filter(fn ($i) => (float) $i->remaining_amount > 0.004)
            ->values();
    }

    /** Batch aanmaken uit gekozen facturen; per factuur één transactie voor het openstaande bedrag. */
    public function createBatch(Company $company, array $invoiceIds, Carbon $collectionDate, ?int $userId = null): DirectDebitBatch
    {
        if ($this->blockers($company)) {
            throw new \DomainException(__('Vul eerst :missing in bij Bedrijfsgegevens.', ['missing' => implode(' ' . __('en') . ' ', $this->blockers($company))]));
        }

        $invoices = $this->collectable($company)->whereIn('id', $invoiceIds);
        if ($invoices->isEmpty()) {
            throw new \DomainException(__('Geen incasseerbare facturen gekozen.'));
        }

        return DB::transaction(function () use ($company, $invoices, $collectionDate, $userId) {
            $reference = 'EI' . $company->id . '-' . now()->format('ymdHis');
            $lines = [];
            foreach ($invoices as $n => $invoice) {
                /** @var Customer $customer */
                $customer = $invoice->customer;
                $lines[] = [
                    'invoice_id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'amount' => round((float) $invoice->remaining_amount, 2),
                    'end_to_end_id' => mb_substr(preg_replace('/[^A-Za-z0-9-]/', '', $invoice->number ?: ('F' . $invoice->id)), 0, 35),
                    'mandate_reference' => $customer->mandate_reference,
                    'mandate_signed_on' => optional($customer->mandate_signed_on)->format('Y-m-d'),
                    'iban' => Iban::normalize($customer->mandate_iban),
                    'holder' => $customer->mandate_holder ?: $customer->name,
                    'scheme' => $customer->mandate_type === 'B2B' ? 'B2B' : 'CORE',
                    'sequence' => $customer->mandate_first_collected_at ? 'RCUR' : 'FRST',
                ];
            }

            $batch = DirectDebitBatch::create([
                'company_id' => $company->id,
                'reference' => $reference,
                'collection_date' => $collectionDate->toDateString(),
                'count' => count($lines),
                'total' => round(array_sum(array_column($lines, 'amount')), 2),
                'lines' => $lines,
                'created_by' => $userId,
            ]);

            Invoice::whereIn('id', $invoices->pluck('id'))->update(['direct_debit_batch_id' => $batch->id]);
            Customer::whereIn('id', $invoices->pluck('customer_id')->unique())->whereNull('mandate_first_collected_at')->update(['mandate_first_collected_at' => now()]);

            return $batch;
        });
    }

    /** Batch weer vrijgeven (bestand niet ingediend): facturen komen terug in de lijst. */
    public function cancel(DirectDebitBatch $batch): void
    {
        Invoice::where('direct_debit_batch_id', $batch->id)->update(['direct_debit_batch_id' => null]);
        $batch->delete();
    }

    /** pain.008.001.02 — één PmtInf per combinatie schema (CORE/B2B) × volgorde (FRST/RCUR). */
    public function pain008(DirectDebitBatch $batch): string
    {
        $company = $batch->company;
        $groups = collect($batch->lines)->groupBy(fn ($l) => $l['scheme'] . '-' . $l['sequence']);
        $amount = fn ($v) => number_format((float) $v, 2, '.', '');
        $clean = fn ($s, $max = 70) => mb_substr(trim(preg_replace('/[^A-Za-z0-9\/\-?:().,\'+ ]/u', ' ', (string) $s)), 0, $max) ?: 'X';

        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->setIndentString('  ');
        $w->startDocument('1.0', 'UTF-8');
        $w->startElementNs(null, 'Document', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02');
        $w->writeAttributeNs('xmlns', 'xsi', null, 'http://www.w3.org/2001/XMLSchema-instance');
        $w->startElement('CstmrDrctDbtInitn');

        $w->startElement('GrpHdr');
        $w->writeElement('MsgId', $batch->reference);
        $w->writeElement('CreDtTm', $batch->created_at->toIso8601String());
        $w->writeElement('NbOfTxs', (string) $batch->count);
        $w->writeElement('CtrlSum', $amount($batch->total));
        $w->startElement('InitgPty');
        $w->writeElement('Nm', $clean($company->name));
        $w->endElement();
        $w->endElement();

        foreach ($groups as $key => $lines) {
            [$scheme, $sequence] = explode('-', $key);
            $w->startElement('PmtInf');
            $w->writeElement('PmtInfId', mb_substr($batch->reference . '-' . $key, 0, 35));
            $w->writeElement('PmtMtd', 'DD');
            $w->writeElement('BtchBookg', 'true');
            $w->writeElement('NbOfTxs', (string) $lines->count());
            $w->writeElement('CtrlSum', $amount($lines->sum('amount')));
            $w->startElement('PmtTpInf');
            $w->startElement('SvcLvl'); $w->writeElement('Cd', 'SEPA'); $w->endElement();
            $w->startElement('LclInstrm'); $w->writeElement('Cd', $scheme); $w->endElement();
            $w->writeElement('SeqTp', $sequence);
            $w->endElement();
            $w->writeElement('ReqdColltnDt', $batch->collection_date->format('Y-m-d'));
            $w->startElement('Cdtr'); $w->writeElement('Nm', $clean($company->name)); $w->endElement();
            $w->startElement('CdtrAcct'); $w->startElement('Id'); $w->writeElement('IBAN', Iban::normalize($company->iban)); $w->endElement(); $w->endElement();
            $w->startElement('CdtrAgt'); $w->startElement('FinInstnId'); $w->startElement('Othr'); $w->writeElement('Id', 'NOTPROVIDED'); $w->endElement(); $w->endElement(); $w->endElement();
            $w->writeElement('ChrgBr', 'SLEV');
            $w->startElement('CdtrSchmeId'); $w->startElement('Id'); $w->startElement('PrvtId'); $w->startElement('Othr');
            $w->writeElement('Id', strtoupper(preg_replace('/\s/', '', $company->sepa_creditor_id)));
            $w->startElement('SchmeNm'); $w->writeElement('Prtry', 'SEPA'); $w->endElement();
            $w->endElement(); $w->endElement(); $w->endElement(); $w->endElement();

            foreach ($lines as $l) {
                $w->startElement('DrctDbtTxInf');
                $w->startElement('PmtId'); $w->writeElement('EndToEndId', $l['end_to_end_id']); $w->endElement();
                $w->startElement('InstdAmt'); $w->writeAttribute('Ccy', 'EUR'); $w->text($amount($l['amount'])); $w->endElement();
                $w->startElement('DrctDbtTx'); $w->startElement('MndtRltdInf');
                $w->writeElement('MndtId', mb_substr($l['mandate_reference'], 0, 35));
                $w->writeElement('DtOfSgntr', $l['mandate_signed_on'] ?: $batch->created_at->format('Y-m-d'));
                $w->endElement(); $w->endElement();
                $w->startElement('DbtrAgt'); $w->startElement('FinInstnId'); $w->startElement('Othr'); $w->writeElement('Id', 'NOTPROVIDED'); $w->endElement(); $w->endElement(); $w->endElement();
                $w->startElement('Dbtr'); $w->writeElement('Nm', $clean($l['holder'])); $w->endElement();
                $w->startElement('DbtrAcct'); $w->startElement('Id'); $w->writeElement('IBAN', $l['iban']); $w->endElement(); $w->endElement();
                $w->startElement('RmtInf'); $w->writeElement('Ustrd', $clean(__('Factuur') . ' ' . $l['number'] . ' ' . $company->name, 140)); $w->endElement();
                $w->endElement();
            }
            $w->endElement();
        }

        $w->endElement(); // CstmrDrctDbtInitn
        $w->endElement(); // Document
        $w->endDocument();

        return $w->outputMemory();
    }

    /** Eerst mogelijke incassodatum: minimaal 3 werkdagen vooruit (CORE en B2B vragen D-2 bij de bank; één dag marge). */
    public static function earliestCollectionDate(): Carbon
    {
        $d = now()->startOfDay();
        $added = 0;
        while ($added < 3) {
            $d->addDay();
            if ($d->isWeekday()) $added++;
        }

        return $d;
    }
}
