<?php

namespace App\Services\Ksef;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Support\Brand;
use DOMDocument;
use DOMElement;

/**
 * KSeF (Krajowy System e-Faktur): bouwt per factuur het gestructureerde
 * FA-XML-bestand dat de ondernemer naar KSeF stuurt. Stap 1 is downloaden en
 * zelf indienen (handmatig of via een ander programma) en het toegekende
 * KSeF-nummer bij de factuur bewaren; directe verzending via de KSeF-API
 * (token/sessie) is een latere stap.
 *
 * Schema: FA(2) — logische structuur e-faktury, wersja schematu 1-0E
 * (namespace http://crd.gov.pl/wzor/2023/06/29/12648/, KodFormularza
 * kodSystemowy="FA (2)", WariantFormularza 2). FA(3) voor KSeF 2.0 bouwt op
 * dezelfde elementen (Naglowek, Podmiot1/Podmiot2, Fa, FaWiersz, Platnosc);
 * alleen namespace, kodSystemowy, wersjaSchemy en variant verschillen. Die
 * staan hieronder centraal in constanten (SCHEMA_*), zodat de overstap in
 * één regel te maken is.
 *
 * Bedragen: string met twee decimalen en een punt. Netto en btw per tarief
 * worden uit de factuurregels berekend (niet alleen uit vat_breakdown), zodat
 * P_13_x/P_14_x altijd sluiten op de regels. Creditnota's (RodzajFaktury KOR)
 * staan negatief in de data en blijven negatief, zoals KSeF verwacht.
 */
class FaXmlBuilder
{
    public const SCHEMA_NAMESPACE = 'http://crd.gov.pl/wzor/2023/06/29/12648/';
    public const SCHEMA_CODE = 'FA (2)';   // KodFormularza@kodSystemowy
    public const SCHEMA_VERSION = '1-0E';  // KodFormularza@wersjaSchemy
    public const SCHEMA_VARIANT = '2';     // WariantFormularza

    /** Pools btw-tarief → [netto-veld, btw-veld] in het Fa-blok (0% = P_13_6_1 zonder btw-veld). */
    private const RATE_FIELDS = [
        23 => ['P_13_1', 'P_14_1'],
        8 => ['P_13_2', 'P_14_2'],
        5 => ['P_13_3', 'P_14_3'],
        0 => ['P_13_6_1', null],
    ];

    /** Landcodes zoals KSeF ze voor EU-btw-nummers verwacht (TKodyKrajowUE; Griekenland = EL). */
    private const EU = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'ES', 'FI', 'FR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI'];

    private DOMDocument $doc;

    public function build(Invoice $invoice): string
    {
        $invoice->loadMissing(['lines', 'company', 'originalInvoice']);
        $company = $invoice->company;

        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        $root = $this->doc->createElementNS(self::SCHEMA_NAMESPACE, 'Faktura');
        $this->doc->appendChild($root);

        // ---- Naglowek
        $head = $this->el($root, 'Naglowek');
        $this->el($head, 'KodFormularza', 'FA', ['kodSystemowy' => self::SCHEMA_CODE, 'wersjaSchemy' => self::SCHEMA_VERSION]);
        $this->el($head, 'WariantFormularza', self::SCHEMA_VARIANT);
        $this->el($head, 'DataWytworzeniaFa', now()->utc()->format('Y-m-d\TH:i:s\Z'));
        $this->el($head, 'SystemInfo', $this->text(brand('name') ?: Brand::name(), 256));

        // ---- Podmiot1 (verkoper)
        $seller = $this->el($root, 'Podmiot1');
        $id = $this->el($seller, 'DaneIdentyfikacyjne');
        $this->el($id, 'NIP', $this->digits($company->vat_number) ?: '-');
        $this->el($id, 'Nazwa', $this->text($company->name, 512));
        $this->address($seller, $company->country, $company->address_line, $company->postal_code, $company->city, true);

        // ---- Podmiot2 (koper)
        $buyer = $this->el($root, 'Podmiot2');
        $id = $this->el($buyer, 'DaneIdentyfikacyjne');
        $this->buyerIdentification($id, $invoice);
        $this->el($id, 'Nazwa', $this->text($invoice->customer_name, 512));
        $this->address($buyer, $invoice->customer_country, $invoice->customer_address_line, $invoice->customer_postal_code, $invoice->customer_city, false);
        $this->el($buyer, 'JST', '2');  // geen jednostka samorządu terytorialnego
        $this->el($buyer, 'GV', '2');   // geen grupa VAT

        // ---- Fa
        $fa = $this->el($root, 'Fa');
        $this->el($fa, 'KodWaluty', strtoupper($company->currency ?: 'PLN'));
        $this->el($fa, 'P_1', $invoice->invoice_date->format('Y-m-d'));
        if ($company->city) {
            $this->el($fa, 'P_1M', $this->text($company->city, 256));
        }
        $this->el($fa, 'P_2', $this->text($invoice->number, 256));
        $this->el($fa, 'P_6', $invoice->invoice_date->format('Y-m-d'));

        foreach ($this->buckets($invoice) as $rate => $b) {
            [$netField, $vatField] = self::RATE_FIELDS[$rate];
            $this->el($fa, $netField, $this->amount($b['net']));
            if ($vatField) {
                $this->el($fa, $vatField, $this->amount($b['vat']));
            }
        }
        $this->el($fa, 'P_15', $this->amount((float) $invoice->total));

        $this->annotations($fa);

        if ($invoice->is_credit) {
            $this->el($fa, 'RodzajFaktury', 'KOR');
            if (filled($invoice->notes)) {
                $this->el($fa, 'PrzyczynaKorekty', $this->text($invoice->notes, 256));
            }
            if ($original = $invoice->originalInvoice) {
                $corrected = $this->el($fa, 'DaneFaKorygowanej');
                $this->el($corrected, 'DataWystFaKorygowanej', $original->invoice_date->format('Y-m-d'));
                $this->el($corrected, 'NrFaKorygowanej', $this->text($original->number, 256));
                if ($original->ksef_number) {
                    $this->el($corrected, 'NrKSeF', '1');
                    $this->el($corrected, 'NrKSeFFaKorygowanej', $this->text($original->ksef_number, 40));
                } else {
                    $this->el($corrected, 'NrKSeFN', '1');
                }
            }
        } else {
            $this->el($fa, 'RodzajFaktury', 'VAT');
        }

        foreach ($invoice->lines as $i => $line) {
            $this->line($fa, $i + 1, $line);
        }

        $this->payment($fa, $invoice);

        return $this->doc->saveXML();
    }

    /** Identificatie van de koper: NIP (Polen), KodUE + NrVatUE (EU), KodKraju + NrID (overig) of BrakID. */
    private function buyerIdentification(DOMElement $parent, Invoice $invoice): void
    {
        $vat = strtoupper(preg_replace('/[\s.\-]/', '', (string) $invoice->customer_vat_number));
        $country = $this->countryCode($invoice->customer_country);
        $prefix = preg_match('/^([A-Z]{2})/', $vat, $m) ? $m[1] : null;
        $number = $prefix ? substr($vat, 2) : $vat;

        if ($vat === '') {
            $this->el($parent, 'BrakID', '1');

            return;
        }

        $nip = preg_replace('/\D/', '', $vat);
        $polish = $prefix === 'PL' || ($prefix === null && $country === 'PL');
        if ($polish && strlen($nip) === 10) {
            $this->el($parent, 'NIP', $nip);

            return;
        }

        $euCode = $prefix ?: $country;
        if ($euCode === 'GR') {
            $euCode = 'EL';
        }
        if (in_array($euCode, self::EU, true) && $number !== '') {
            $this->el($parent, 'KodUE', $euCode);
            $this->el($parent, 'NrVatUE', $this->text($number, 50));

            return;
        }

        $this->el($parent, 'KodKraju', $prefix ?: $country);
        $this->el($parent, 'NrID', $this->text($number ?: $vat, 50));
    }

    /**
     * Adresblok. Voor de verkoper verplicht (met '-' als een veld ontbreekt);
     * voor de koper alleen als er iets van een adres bekend is.
     */
    private function address(DOMElement $parent, ?string $country, ?string $line, ?string $postal, ?string $city, bool $required): void
    {
        if (! $required && blank($line) && blank($city)) {
            return;
        }
        $address = $this->el($parent, 'Adres');
        $this->el($address, 'KodKraju', $this->countryCode($country));
        $this->el($address, 'AdresL1', $this->text($line, 512));
        $line2 = trim(($postal ?? '') . ' ' . ($city ?? ''));
        if ($line2 !== '') {
            $this->el($address, 'AdresL2', $this->text($line2, 512));
        }
    }

    /** Adnotacje: alle bijzondere regelingen "nie" (2) / niet van toepassing (…N = 1). */
    private function annotations(DOMElement $fa): void
    {
        $a = $this->el($fa, 'Adnotacje');
        $this->el($a, 'P_16', '2');   // metoda kasowa
        $this->el($a, 'P_17', '2');   // samofakturowanie
        $this->el($a, 'P_18', '2');   // odwrotne obciążenie
        $this->el($a, 'P_18A', '2');  // mechanizm podzielonej płatności (MPP)
        $this->el($this->el($a, 'Zwolnienie'), 'P_19N', '1');
        $this->el($this->el($a, 'NoweSrodkiTransportu'), 'P_22N', '1');
        $this->el($a, 'P_23', '2');   // procedura uproszczona (trójstronna)
        $this->el($this->el($a, 'PMarzy'), 'P_PMarzyN', '1');
    }

    private function line(DOMElement $fa, int $nr, InvoiceLine $line): void
    {
        $w = $this->el($fa, 'FaWiersz');
        $this->el($w, 'NrWierszaFa', (string) $nr);
        $this->el($w, 'P_7', $this->text($line->description, 512));
        $this->el($w, 'P_8A', $this->text($line->unit, 256, 'szt.'));
        $this->el($w, 'P_8B', $this->quantity((float) $line->quantity));
        $this->el($w, 'P_9A', $this->amount((float) $line->unit_price));
        $discount = round((float) $line->quantity * (float) $line->unit_price - (float) $line->line_subtotal, 2);
        if ($discount > 0.004 && (float) $line->discount_pct > 0) {
            $this->el($w, 'P_10', $this->amount($discount));
        }
        $this->el($w, 'P_11', $this->amount((float) $line->line_subtotal));
        $this->el($w, 'P_12', (string) $this->rate($line->vat_rate));
    }

    private function payment(DOMElement $fa, Invoice $invoice): void
    {
        $p = $this->el($fa, 'Platnosc');
        if ($invoice->status === 'paid' && $invoice->paid_at) {
            $this->el($p, 'Zaplacono', '1');
            $this->el($p, 'DataZaplaty', $invoice->paid_at->format('Y-m-d'));
        }
        if ($invoice->due_date) {
            $this->el($this->el($p, 'TerminPlatnosci'), 'Termin', $invoice->due_date->format('Y-m-d'));
        }
        $this->el($p, 'FormaPlatnosci', '6'); // przelew
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $invoice->company->iban));
        if ($iban !== '') {
            $this->el($this->el($p, 'RachunekBankowy'), 'NrRB', mb_substr($iban, 0, 32));
        }
    }

    /** Netto en btw per Pools tarief, opgeteld uit de regels (op volgorde 23, 8, 5, 0). */
    private function buckets(Invoice $invoice): array
    {
        $b = [];
        foreach ($invoice->lines as $line) {
            $rate = $this->rate($line->vat_rate);
            $b[$rate] = [
                'net' => ($b[$rate]['net'] ?? 0) + (float) $line->line_subtotal,
                'vat' => ($b[$rate]['vat'] ?? 0) + (float) $line->line_vat,
            ];
        }
        $ordered = [];
        foreach (array_keys(self::RATE_FIELDS) as $rate) {
            if (isset($b[$rate]) && (abs($b[$rate]['net']) > 0.004 || abs($b[$rate]['vat']) > 0.004)) {
                $ordered[$rate] = $b[$rate];
            }
        }

        return $ordered;
    }

    /**
     * Pools btw-tarief van een regel (23/8/5/0). Een afwijkend tarief (bijv. een
     * oude 21%-regel) valt in het dichtstbijzijnde tarief; de bedragen blijven
     * zoals ze op de factuur staan, zodat P_15 blijft sluiten.
     */
    private function rate(mixed $value): int
    {
        $value = (float) $value;
        $rates = array_keys(self::RATE_FIELDS);
        usort($rates, fn ($a, $b) => abs($a - $value) <=> abs($b - $value));

        return (int) $rates[0];
    }

    private function el(DOMElement $parent, string $name, ?string $value = null, array $attributes = []): DOMElement
    {
        $el = $this->doc->createElementNS(self::SCHEMA_NAMESPACE, $name);
        if ($value !== null) {
            $el->appendChild($this->doc->createTextNode($value));
        }
        foreach ($attributes as $attr => $v) {
            $el->setAttribute($attr, $v);
        }
        $parent->appendChild($el);

        return $el;
    }

    private function amount(float $v): string
    {
        $v = round($v, 2);

        return number_format(abs($v) < 0.005 ? 0.0 : $v, 2, '.', '');
    }

    /** Aantal zonder overbodige nullen: "1", "2.5", "0.125". */
    private function quantity(float $v): string
    {
        $s = rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }

    private function digits(?string $v): string
    {
        return preg_replace('/\D/', '', (string) $v);
    }

    private function countryCode(?string $v): string
    {
        $v = strtoupper(trim((string) $v));

        return preg_match('/^[A-Z]{2}$/', $v) ? $v : 'PL';
    }

    /** Tekst zonder tekens die XML 1.0 niet toestaat, samengevouwen witruimte, afgekapt op $max. */
    private function text(?string $v, int $max, string $fallback = '-'): string
    {
        $v = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $v) ?? '';
        $v = trim((string) preg_replace('/\s+/u', ' ', $v));
        $v = mb_substr($v, 0, $max);

        return $v === '' ? $fallback : $v;
    }
}
