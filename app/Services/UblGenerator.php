<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * UblGenerator
 *
 * Genereert een UBL 2.1-factuur (XML) volgens NLCIUS / EN 16931 — het
 * Nederlandse e-facturatieprofiel dat o.a. de Rijksoverheid en boekhoud-
 * pakketten (Exact, Moneybird, e-Boekhouden) kunnen inlezen.
 *
 * Let op: dit genereert het UBL-bestand zelf. Verzending via het
 * Peppol-netwerk vereist daarnaast een access point (staat op de roadmap).
 */
class UblGenerator
{
    /**
     * Genereer de UBL-XML voor een factuur. Vereist een definitieve factuur
     * (met factuurnummer); de regels moeten geladen zijn.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing('lines', 'company');
        $company = $invoice->company;

        $id = $invoice->number ?: ('CONCEPT-'.$invoice->id);
        $typeCode = $invoice->is_credit ? '381' : '380';
        $currency = $company->currency ?: 'EUR';

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"'
            .' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"'
            .' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">';
        $xml[] = '  <cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:fdc:nen.nl:nlcius:v1.0</cbc:CustomizationID>';
        $xml[] = '  <cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>';
        $xml[] = '  <cbc:ID>'.$this->e($id).'</cbc:ID>';
        $xml[] = '  <cbc:IssueDate>'.$invoice->invoice_date->toDateString().'</cbc:IssueDate>';
        if ($invoice->due_date) {
            $xml[] = '  <cbc:DueDate>'.$invoice->due_date->toDateString().'</cbc:DueDate>';
        }
        $xml[] = '  <cbc:InvoiceTypeCode>'.$typeCode.'</cbc:InvoiceTypeCode>';
        if ($invoice->notes) {
            $xml[] = '  <cbc:Note>'.$this->e($invoice->notes).'</cbc:Note>';
        }
        $xml[] = '  <cbc:DocumentCurrencyCode>'.$this->e($currency).'</cbc:DocumentCurrencyCode>';
        // NLCIUS vereist een BuyerReference (of inkoopordernummer).
        $xml[] = '  <cbc:BuyerReference>'.$this->e($invoice->reference ?: $id).'</cbc:BuyerReference>';
        if ($invoice->is_credit && $invoice->originalInvoice?->number) {
            $xml[] = '  <cac:BillingReference><cac:InvoiceDocumentReference><cbc:ID>'
                .$this->e($invoice->originalInvoice->number)
                .'</cbc:ID></cac:InvoiceDocumentReference></cac:BillingReference>';
        }

        // ---------- Leverancier (jouw bedrijf) ----------
        $xml[] = '  <cac:AccountingSupplierParty>';
        $xml[] = $this->party(
            name: $company->trading_name ?: $company->name,
            legalName: $company->name,
            street: $company->address_line,
            city: $company->city,
            postal: $company->postal_code,
            country: $this->countryCode($company->country),
            vatNumber: $company->vat_number,
            kvkNumber: $company->kvk_number,
            email: $company->email,
        );
        $xml[] = '  </cac:AccountingSupplierParty>';

        // ---------- Klant (snapshot op de factuur) ----------
        $xml[] = '  <cac:AccountingCustomerParty>';
        $xml[] = $this->party(
            name: $invoice->customer_name,
            legalName: $invoice->customer_name,
            street: $invoice->customer_address_line,
            city: $invoice->customer_city,
            postal: $invoice->customer_postal_code,
            country: $this->countryCode($invoice->customer_country),
            vatNumber: $invoice->customer_vat_number,
            kvkNumber: $invoice->customer_kvk_number,
            email: $invoice->customer_email,
        );
        $xml[] = '  </cac:AccountingCustomerParty>';

        // ---------- Betaalgegevens ----------
        if ($company->iban) {
            $xml[] = '  <cac:PaymentMeans>';
            $xml[] = '    <cbc:PaymentMeansCode>30</cbc:PaymentMeansCode>'; // credit transfer
            $xml[] = '    <cbc:PaymentID>'.$this->e($id).'</cbc:PaymentID>';
            $xml[] = '    <cac:PayeeFinancialAccount><cbc:ID>'.$this->e(str_replace(' ', '', $company->iban)).'</cbc:ID></cac:PayeeFinancialAccount>';
            $xml[] = '  </cac:PaymentMeans>';
        }
        if ($invoice->payment_terms) {
            $xml[] = '  <cac:PaymentTerms><cbc:Note>'.$this->e("Betaling binnen {$invoice->payment_terms} dagen").'</cbc:Note></cac:PaymentTerms>';
        }

        // ---------- BTW-totalen per tarief ----------
        $buckets = []; // rate => ['base' => x, 'vat' => y]
        foreach ($invoice->lines as $line) {
            $rate = (string) (float) $line->vat_rate;
            $buckets[$rate]['base'] = ($buckets[$rate]['base'] ?? 0) + (float) $line->line_subtotal;
            $buckets[$rate]['vat'] = ($buckets[$rate]['vat'] ?? 0) + (float) $line->line_vat;
        }

        $xml[] = '  <cac:TaxTotal>';
        $xml[] = '    <cbc:TaxAmount currencyID="'.$this->e($currency).'">'.$this->amount($invoice->vat_total).'</cbc:TaxAmount>';
        foreach ($buckets as $rate => $bucket) {
            $xml[] = '    <cac:TaxSubtotal>';
            $xml[] = '      <cbc:TaxableAmount currencyID="'.$this->e($currency).'">'.$this->amount($bucket['base']).'</cbc:TaxableAmount>';
            $xml[] = '      <cbc:TaxAmount currencyID="'.$this->e($currency).'">'.$this->amount($bucket['vat']).'</cbc:TaxAmount>';
            $xml[] = '      <cac:TaxCategory>';
            $xml[] = '        <cbc:ID>'.$this->taxCategory((float) $rate).'</cbc:ID>';
            $xml[] = '        <cbc:Percent>'.$this->amount((float) $rate).'</cbc:Percent>';
            if ((float) $rate === 0.0) {
                $xml[] = '        <cbc:TaxExemptionReason>Nultarief of vrijgesteld van BTW</cbc:TaxExemptionReason>';
            }
            $xml[] = '        <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>';
            $xml[] = '      </cac:TaxCategory>';
            $xml[] = '    </cac:TaxSubtotal>';
        }
        $xml[] = '  </cac:TaxTotal>';

        $xml[] = '  <cac:LegalMonetaryTotal>';
        $xml[] = '    <cbc:LineExtensionAmount currencyID="'.$this->e($currency).'">'.$this->amount($invoice->subtotal).'</cbc:LineExtensionAmount>';
        $xml[] = '    <cbc:TaxExclusiveAmount currencyID="'.$this->e($currency).'">'.$this->amount($invoice->subtotal).'</cbc:TaxExclusiveAmount>';
        $xml[] = '    <cbc:TaxInclusiveAmount currencyID="'.$this->e($currency).'">'.$this->amount($invoice->total).'</cbc:TaxInclusiveAmount>';
        $xml[] = '    <cbc:PayableAmount currencyID="'.$this->e($currency).'">'.$this->amount($invoice->total).'</cbc:PayableAmount>';
        $xml[] = '  </cac:LegalMonetaryTotal>';

        // ---------- Factuurregels ----------
        foreach ($invoice->lines as $index => $line) {
            $xml[] = '  <cac:InvoiceLine>';
            $xml[] = '    <cbc:ID>'.($index + 1).'</cbc:ID>';
            // C62 = "unit" (stuks); we voeren geen UN/ECE-eenhedenadministratie.
            $xml[] = '    <cbc:InvoicedQuantity unitCode="C62">'.$this->qty($line->quantity).'</cbc:InvoicedQuantity>';
            $xml[] = '    <cbc:LineExtensionAmount currencyID="'.$this->e($currency).'">'.$this->amount($line->line_subtotal).'</cbc:LineExtensionAmount>';
            $xml[] = '    <cac:Item>';
            if ($line->details) {
                $xml[] = '      <cbc:Description>'.$this->e($line->details).'</cbc:Description>';
            }
            $xml[] = '      <cbc:Name>'.$this->e(mb_substr($line->description, 0, 100) ?: 'Regel '.($index + 1)).'</cbc:Name>';
            $xml[] = '      <cac:ClassifiedTaxCategory>';
            $xml[] = '        <cbc:ID>'.$this->taxCategory((float) $line->vat_rate).'</cbc:ID>';
            $xml[] = '        <cbc:Percent>'.$this->amount((float) $line->vat_rate).'</cbc:Percent>';
            $xml[] = '        <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>';
            $xml[] = '      </cac:ClassifiedTaxCategory>';
            $xml[] = '    </cac:Item>';
            // Bij regelkorting sturen we de effectieve stuksprijs mee, zodat
            // aantal × prijs = regelbedrag blijft kloppen voor validators.
            $priceAmount = ((float) ($line->discount_pct ?? 0) > 0 && (float) $line->quantity > 0)
                ? round((float) $line->line_subtotal / (float) $line->quantity, 2)
                : (float) $line->unit_price;
            $xml[] = '    <cac:Price><cbc:PriceAmount currencyID="'.$this->e($currency).'">'.$this->amount($priceAmount).'</cbc:PriceAmount></cac:Price>';
            $xml[] = '  </cac:InvoiceLine>';
        }

        $xml[] = '</Invoice>';

        return implode("\n", $xml);
    }

    /** Bestandsnaam voor downloads en e-mailbijlagen. */
    public function filename(Invoice $invoice): string
    {
        return ($invoice->number ?: 'concept-'.$invoice->id).'-ubl.xml';
    }

    protected function party(
        ?string $name, ?string $legalName, ?string $street, ?string $city,
        ?string $postal, string $country, ?string $vatNumber, ?string $kvkNumber, ?string $email,
    ): string {
        $p = [];
        $p[] = '    <cac:Party>';
        if ($name) {
            $p[] = '      <cac:PartyName><cbc:Name>'.$this->e($name).'</cbc:Name></cac:PartyName>';
        }
        $p[] = '      <cac:PostalAddress>';
        if ($street) $p[] = '        <cbc:StreetName>'.$this->e($street).'</cbc:StreetName>';
        if ($city)   $p[] = '        <cbc:CityName>'.$this->e($city).'</cbc:CityName>';
        if ($postal) $p[] = '        <cbc:PostalZone>'.$this->e($postal).'</cbc:PostalZone>';
        $p[] = '        <cac:Country><cbc:IdentificationCode>'.$this->e($country).'</cbc:IdentificationCode></cac:Country>';
        $p[] = '      </cac:PostalAddress>';
        if ($vatNumber) {
            $p[] = '      <cac:PartyTaxScheme>';
            $p[] = '        <cbc:CompanyID>'.$this->e(str_replace([' ', '.'], '', $vatNumber)).'</cbc:CompanyID>';
            $p[] = '        <cac:TaxScheme><cbc:ID>VAT</cbc:ID></cac:TaxScheme>';
            $p[] = '      </cac:PartyTaxScheme>';
        }
        $p[] = '      <cac:PartyLegalEntity>';
        $p[] = '        <cbc:RegistrationName>'.$this->e($legalName ?: ($name ?: 'Onbekend')).'</cbc:RegistrationName>';
        if ($kvkNumber) {
            // schemeID 0106 = NL KVK-nummer
            $p[] = '        <cbc:CompanyID schemeID="0106">'.$this->e($kvkNumber).'</cbc:CompanyID>';
        }
        $p[] = '      </cac:PartyLegalEntity>';
        if ($email) {
            $p[] = '      <cac:Contact><cbc:ElectronicMail>'.$this->e($email).'</cbc:ElectronicMail></cac:Contact>';
        }
        $p[] = '    </cac:Party>';

        return implode("\n", $p);
    }

    /** UNCL5305-categorie: S = standaard/verlaagd tarief, Z = nultarief. */
    protected function taxCategory(float $rate): string
    {
        return $rate > 0 ? 'S' : 'Z';
    }

    /** Landnaam of -code → ISO 3166-1 alpha-2 (default NL). */
    protected function countryCode(?string $country): string
    {
        $c = trim((string) $country);
        if ($c === '') return 'NL';
        if (strlen($c) === 2) return strtoupper($c);

        return match (mb_strtolower($c)) {
            'nederland', 'the netherlands', 'netherlands', 'holland' => 'NL',
            'belgië', 'belgie', 'belgium' => 'BE',
            'duitsland', 'germany', 'deutschland' => 'DE',
            'frankrijk', 'france' => 'FR',
            'luxemburg', 'luxembourg' => 'LU',
            'verenigd koninkrijk', 'united kingdom' => 'GB',
            'spanje', 'spain' => 'ES',
            'italië', 'italie', 'italy' => 'IT',
            default => 'NL',
        };
    }

    protected function amount(float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function qty(float|string $value): string
    {
        // Max 4 decimalen, zonder overbodige nullen ("2.0000" → "2").
        $s = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return $s === '' ? '0' : $s;
    }

    protected function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
