<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use XMLWriter;

/**
 * Auditfile Financieel XAF 3.2 — dé standaard waarmee Nederlandse accountants
 * en de Belastingdienst een administratie inlezen. EasyInvoice is een
 * factuurpakket, geen grootboek; we bouwen daarom een compacte, sluitende
 * boekhouding op uit wat we wél weten:
 *
 *   verkoopboek (S)  factuur → debiteuren (D) / omzet per btw-tarief (C) / af te dragen btw (C)
 *   inkoopboek  (P)  inkoopfactuur → kosten (D) / te vorderen btw (D) / crediteuren (C)
 *   bankboek    (B)  betaling → bank (D) / debiteuren (C)  ·  inkoop betaald → crediteuren (D) / bank (C)
 *
 * Creditnota's en afboekingen worden als tegengestelde boekingen opgenomen.
 * Het rekeningschema volgt de gangbare NL-nummering (RGS-achtig).
 */
class XafExporter
{
    private const ACCOUNTS = [
        '1100' => ['Bank', 'B'],
        '1300' => ['Debiteuren', 'B'],
        '1520' => ['Te vorderen btw (voorbelasting)', 'B'],
        '1500' => ['Af te dragen btw hoog', 'B'],
        '1510' => ['Af te dragen btw laag', 'B'],
        '1600' => ['Crediteuren', 'B'],
        '4000' => ['Inkoopkosten en overige kosten', 'P'],
        '8000' => ['Omzet hoog tarief (21%)', 'P'],
        '8010' => ['Omzet laag tarief (9%)', 'P'],
        '8020' => ['Omzet 0% / verlegd / buitenland', 'P'],
        '8100' => ['Overige opbrengsten en afboekingen', 'P'],
    ];

    private const VAT = [
        '21' => ['H', 'Btw hoog 21%', '1500', '1520'],
        '9' => ['L', 'Btw laag 9%', '1510', '1520'],
        '0' => ['N', 'Btw 0% / verlegd', null, null],
    ];

    private int $lineCount = 0;
    private float $totalDebit = 0.0;
    private float $totalCredit = 0.0;

    public function generate(Company $company, int $year): string
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $invoices = Invoice::withoutGlobalScope('company')->where('company_id', $company->id)
            ->whereNotIn('status', ['draft', 'cancelled'])->whereBetween('invoice_date', [$start, $end])
            ->with(['lines', 'payments'])->orderBy('invoice_date')->orderBy('id')->get();
        $purchases = PurchaseInvoice::withoutGlobalScope('company')->where('company_id', $company->id)
            ->whereBetween('invoice_date', [$start, $end])->orderBy('invoice_date')->orderBy('id')->get();
        $customers = Customer::withoutGlobalScope('company')->where('company_id', $company->id)->orderBy('id')->get();
        $suppliers = $purchases->pluck('supplier_name')->filter()->unique()->values();

        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->setIndentString('  ');
        $w->startDocument('1.0', 'UTF-8');
        $w->startElementNs(null, 'auditfile', 'http://www.auditfiles.nl/XAF/3.2');

        // ---- header
        $w->startElement('header');
        $this->el($w, 'fiscalYear', (string) $year);
        $this->el($w, 'startDate', $start->format('Y-m-d'));
        $this->el($w, 'endDate', $end->format('Y-m-d'));
        $this->el($w, 'curCode', 'EUR');
        $this->el($w, 'dateCreated', now()->format('Y-m-d'));
        $this->el($w, 'softwareDesc', 'EasyInvoice');
        $this->el($w, 'softwareVersion', mb_substr((string) config('app.version'), 0, 20));
        $w->endElement();

        // ---- company
        $w->startElement('company');
        $this->el($w, 'companyIdent', $this->id($company->kvk_number ?: (string) $company->id));
        $this->el($w, 'companyName', $this->str($company->name, 999));
        $this->el($w, 'taxRegistrationCountry', strtoupper($company->country ?: 'NL'));
        $this->el($w, 'taxRegIdent', $this->str($company->vat_number ?: $company->kvk_number ?: '-', 30));
        $this->address($w, $company->address_line, $company->postal_code, $company->city, $company->country ?: 'NL');

        $w->startElement('customersSuppliers');
        foreach ($customers as $c) {
            $w->startElement('customerSupplier');
            $this->el($w, 'custSupID', 'K' . $c->id);
            $this->el($w, 'custSupName', $this->str($c->name, 50));
            if ($c->email) $this->el($w, 'eMail', $this->str($c->email, 999));
            if ($c->kvk_number) $this->el($w, 'commerceNr', $this->str($c->kvk_number, 999));
            $this->el($w, 'taxRegistrationCountry', strtoupper($c->country ?: 'NL'));
            if ($c->vat_number) $this->el($w, 'taxRegIdent', $this->str($c->vat_number, 30));
            $this->el($w, 'custSupTp', 'C');
            $this->address($w, $c->address_line, $c->postal_code, $c->city, $c->country ?: 'NL');
            $w->endElement();
        }
        foreach ($suppliers as $i => $name) {
            $w->startElement('customerSupplier');
            $this->el($w, 'custSupID', 'L' . ($i + 1));
            $this->el($w, 'custSupName', $this->str($name, 50));
            $this->el($w, 'custSupTp', 'S');
            $w->endElement();
        }
        $w->endElement();

        $w->startElement('generalLedger');
        foreach (self::ACCOUNTS as $id => [$desc, $type]) {
            $w->startElement('ledgerAccount');
            $this->el($w, 'accID', $id);
            $this->el($w, 'accDesc', $desc);
            $this->el($w, 'accTp', $type);
            $w->endElement();
        }
        $w->endElement();

        $w->startElement('vatCodes');
        foreach (self::VAT as [$id, $desc, $pay, $claim]) {
            $w->startElement('vatCode');
            $this->el($w, 'vatID', $id);
            $this->el($w, 'vatDesc', $desc);
            if ($pay) $this->el($w, 'vatToPayAccID', $pay);
            if ($claim) $this->el($w, 'vatToClaimAccID', $claim);
            $w->endElement();
        }
        $w->endElement();

        $w->startElement('periods');
        for ($m = 1; $m <= 12; $m++) {
            $w->startElement('period');
            $this->el($w, 'periodNumber', (string) $m);
            $this->el($w, 'periodDesc', Carbon::create($year, $m, 1)->locale('nl')->translatedFormat('F Y'));
            $this->el($w, 'startDatePeriod', Carbon::create($year, $m, 1)->format('Y-m-d'));
            $this->el($w, 'endDatePeriod', Carbon::create($year, $m, 1)->endOfMonth()->format('Y-m-d'));
            $w->endElement();
        }
        $w->endElement();

        // ---- transactions (eerst opbouwen, dan schrijven: de totalen staan vooraan)
        $supplierIds = $suppliers->flip()->map(fn ($i) => 'L' . ($i + 1))->all();
        $journals = [
            'S' => ['desc' => 'Verkoopboek', 'transactions' => []],
            'P' => ['desc' => 'Inkoopboek', 'transactions' => []],
            'B' => ['desc' => 'Bankboek', 'transactions' => []],
        ];

        foreach ($invoices as $inv) {
            $sign = $inv->is_credit ? -1 : 1;
            $lines = [];
            $lines[] = $this->line('1300', $inv->number, $inv->invoice_date, 'Factuur ' . $inv->number . ' ' . $inv->customer_name, $sign * (float) $inv->total, 'D', 'K' . $inv->customer_id, $inv->number);
            foreach ($this->buckets($inv->lines->map(fn ($l) => ['rate' => (float) $l->vat_rate, 'base' => (float) $l->line_subtotal, 'vat' => (float) $l->line_vat])) as $rate => $b) {
                $acc = ['21' => '8000', '9' => '8010'][$rate] ?? '8020';
                $lines[] = $this->line($acc, $inv->number, $inv->invoice_date, 'Omzet ' . $rate . '%', $sign * $b['base'], 'C', null, $inv->number, [self::VAT[$rate][0] ?? 'N', (float) $rate, $sign * $b['vat'], 'C']);
                if (abs($b['vat']) > 0.004) {
                    $lines[] = $this->line($rate === '9' ? '1510' : '1500', $inv->number, $inv->invoice_date, 'Btw ' . $rate . '%', $sign * $b['vat'], 'C', null, $inv->number);
                }
            }
            $journals['S']['transactions'][] = ['nr' => 'V' . $inv->id, 'desc' => ($inv->is_credit ? 'Creditnota ' : 'Factuur ') . $inv->number, 'date' => $inv->invoice_date, 'lines' => $lines];

            foreach ($inv->payments as $p) {
                $date = $p->paid_on ? Carbon::parse($p->paid_on) : $inv->invoice_date;
                if ($date->year !== $year) continue;
                $amount = (float) $p->amount;
                $bankAcc = $p->kind === 'payment' ? '1100' : '8100';   // verrekening/afboeking: geen bankmutatie
                $journals['B']['transactions'][] = ['nr' => 'B' . $p->id, 'desc' => 'Betaling ' . $inv->number, 'date' => $date, 'lines' => [
                    $this->line($bankAcc, $inv->number, $date, 'Ontvangst ' . $inv->number, $amount, 'D', null, $inv->number),
                    $this->line('1300', $inv->number, $date, 'Ontvangst ' . $inv->number, $amount, 'C', 'K' . $inv->customer_id, $inv->number),
                ]];
            }
        }

        foreach ($purchases as $pi) {
            $ref = $pi->supplier_reference ?: ('INK-' . $pi->id);
            $supId = $supplierIds[$pi->supplier_name] ?? null;
            $lines = [];
            foreach ($this->buckets(collect($pi->vat_lines ?? [])->map(fn ($l) => ['rate' => (float) ($l['rate'] ?? 0), 'base' => (float) ($l['base'] ?? 0), 'vat' => (float) ($l['vat'] ?? 0)])) as $rate => $b) {
                $lines[] = $this->line('4000', $ref, $pi->invoice_date, 'Inkoop ' . $pi->supplier_name, $b['base'], 'D', null, $ref, [self::VAT[$rate][0] ?? 'N', (float) $rate, $b['vat'], 'D']);
                if (abs($b['vat']) > 0.004) {
                    $lines[] = $this->line('1520', $ref, $pi->invoice_date, 'Voorbelasting ' . $rate . '%', $b['vat'], 'D', null, $ref);
                }
            }
            $lines[] = $this->line('1600', $ref, $pi->invoice_date, 'Inkoopfactuur ' . $pi->supplier_name, (float) $pi->total, 'C', $supId, $ref);
            $journals['P']['transactions'][] = ['nr' => 'I' . $pi->id, 'desc' => 'Inkoopfactuur ' . $pi->supplier_name . ' ' . $ref, 'date' => $pi->invoice_date, 'lines' => $lines];

            if ($pi->paid_at && Carbon::parse($pi->paid_at)->year === $year) {
                $date = Carbon::parse($pi->paid_at);
                $journals['B']['transactions'][] = ['nr' => 'BI' . $pi->id, 'desc' => 'Betaald ' . $ref, 'date' => $date, 'lines' => [
                    $this->line('1600', $ref, $date, 'Betaling ' . $pi->supplier_name, (float) $pi->total, 'D', $supId, $ref),
                    $this->line('1100', $ref, $date, 'Betaling ' . $pi->supplier_name, (float) $pi->total, 'C', null, $ref),
                ]];
            }
        }

        $w->startElement('transactions');
        $this->el($w, 'linesCount', (string) $this->lineCount);
        $this->el($w, 'totalDebit', $this->amount($this->totalDebit));
        $this->el($w, 'totalCredit', $this->amount($this->totalCredit));
        foreach ($journals as $jrnId => $journal) {
            if (! $journal['transactions']) continue;
            $w->startElement('journal');
            $this->el($w, 'jrnID', $jrnId);
            $this->el($w, 'desc', $journal['desc']);
            $this->el($w, 'jrnTp', $jrnId);
            foreach ($journal['transactions'] as $t) {
                $w->startElement('transaction');
                $this->el($w, 'nr', $t['nr']);
                $this->el($w, 'desc', $this->str($t['desc'], 9999));
                $this->el($w, 'periodNumber', (string) $t['date']->month);
                $this->el($w, 'trDt', $t['date']->format('Y-m-d'));
                foreach ($t['lines'] as $i => $l) {
                    $w->startElement('trLine');
                    $this->el($w, 'nr', (string) ($i + 1));
                    $this->el($w, 'accID', $l['acc']);
                    $this->el($w, 'docRef', $this->str($l['doc'], 999));
                    $this->el($w, 'effDate', $l['date']->format('Y-m-d'));
                    $this->el($w, 'desc', $this->str($l['desc'], 9999));
                    $this->el($w, 'amnt', $this->amount($l['amount']));
                    $this->el($w, 'amntTp', $l['tp']);
                    if ($l['custSup']) $this->el($w, 'custSupID', $l['custSup']);
                    if ($l['inv']) $this->el($w, 'invRef', $this->str($l['inv'], 999));
                    if ($l['vat']) {
                        [$vatId, $perc, $vatAmount, $vatTp] = $l['vat'];
                        $w->startElement('vat');
                        $this->el($w, 'vatID', $vatId);
                        $this->el($w, 'vatPerc', number_format($perc, 2, '.', ''));
                        $this->el($w, 'vatAmnt', $this->amount(abs($vatAmount)));
                        $this->el($w, 'vatAmntTp', $vatAmount < 0 ? ($vatTp === 'D' ? 'C' : 'D') : $vatTp);
                        $w->endElement();
                    }
                    $w->endElement();
                }
                $w->endElement();
            }
            $w->endElement();
        }
        $w->endElement();

        $w->endElement(); // company
        $w->endElement(); // auditfile
        $w->endDocument();

        return $w->outputMemory();
    }

    /** Boekingsregel; negatieve bedragen (creditnota's) draaien de D/C-kant om. */
    private function line(string $acc, string $doc, $date, string $desc, float $amount, string $tp, ?string $custSup, ?string $inv, ?array $vat = null): array
    {
        $amount = round($amount, 2);
        if ($amount < 0) {
            $amount = abs($amount);
            $tp = $tp === 'D' ? 'C' : 'D';
        }
        $this->lineCount++;
        if ($tp === 'D') $this->totalDebit += $amount; else $this->totalCredit += $amount;

        return ['acc' => $acc, 'doc' => $doc ?: '-', 'date' => Carbon::parse($date), 'desc' => $desc, 'amount' => $amount, 'tp' => $tp, 'custSup' => $custSup, 'inv' => $inv, 'vat' => $vat];
    }

    /** Grondslag + btw per tarief (21 / 9 / 0), afgerond. */
    private function buckets($lines): array
    {
        $b = [];
        foreach ($lines as $l) {
            $rate = (string) (int) round($l['rate']);
            if (! in_array($rate, ['21', '9'], true)) $rate = '0';
            $b[$rate] = ['base' => ($b[$rate]['base'] ?? 0) + $l['base'], 'vat' => ($b[$rate]['vat'] ?? 0) + $l['vat']];
        }
        ksort($b);

        return array_filter($b, fn ($x) => abs($x['base']) > 0.004 || abs($x['vat']) > 0.004);
    }

    private function address(XMLWriter $w, ?string $line, ?string $postal, ?string $city, ?string $country): void
    {
        if (! $line && ! $city) return;
        $w->startElement('streetAddress');
        if ($line) {
            if (preg_match('/^(.*?)\s+(\d+[a-zA-Z]?(?:[-\s]?\d+)?)\s*$/u', trim($line), $m)) {
                $this->el($w, 'streetname', $this->str($m[1], 999));
                $this->el($w, 'number', $this->str($m[2], 15));
            } else {
                $this->el($w, 'streetname', $this->str($line, 999));
            }
        }
        if ($city) $this->el($w, 'city', $this->str($city, 50));
        if ($postal) $this->el($w, 'postalCode', $this->str($postal, 10));
        $this->el($w, 'country', strtoupper($country ?: 'NL'));
        $w->endElement();
    }

    private function el(XMLWriter $w, string $name, string $value): void
    {
        $w->writeElement($name, $value);
    }

    private function amount(float $v): string
    {
        return number_format(round($v, 2), 2, '.', '');
    }

    private function str(?string $v, int $max): string
    {
        return mb_substr(trim((string) $v), 0, $max) ?: '-';
    }

    private function id(string $v): string
    {
        return mb_substr(preg_replace('/[^A-Za-z0-9._-]/', '', $v) ?: '-', 0, 35);
    }
}
