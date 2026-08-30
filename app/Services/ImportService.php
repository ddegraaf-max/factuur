<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Support\Brand;
use App\Support\DocumentLocale;
use App\Support\Market;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Overstapwizard: CSV-exports van WeFact, Moneybird, e-Boekhouden, Excel of
 * elk ander pakket inlezen — en voor de Poolse markt Fakturownia, iFirma,
 * wFirma en inFakt. Herkent scheidingsteken en tekenset, stelt de
 * kolomkoppeling voor op basis van de kopregel (Nederlandse, Engelse en
 * Poolse koppen) en importeert klanten, producten en openstaande facturen
 * zonder dubbelen. Btw-tarieven, land, taal en eenheid volgen de markt
 * (App\Support\Market): nl 21/9/0, pl 23/8/5/0.
 */
class ImportService
{
    public const TYPES = ['customers' => 'Klanten', 'products' => 'Producten', 'invoices' => 'Openstaande facturen'];

    /**
     * Doelvelden per type: veld => [label, verplicht, synoniemen in kopregels].
     *
     * Synoniemen staan genormaliseerd zoals key() ze maakt: kleine letters, alleen
     * a-z/0-9, diakrieten getranslitereerd ("Termin płatności" → terminplatnosci).
     * Volgorde telt: bij een exacte overeenkomst wint het eerstgenoemde synoniem,
     * dus zet de specifieke koppen vóór de algemene ("Stawka VAT" vóór "VAT",
     * "Data wystawienia" vóór "Data"). Nederlands/Engels (WeFact, Moneybird,
     * e-Boekhouden) en Pools (Fakturownia, iFirma, wFirma, inFakt) staan door elkaar.
     */
    public const FIELDS = [
        'customers' => [
            'name' => ['Bedrijfs-/klantnaam', true, ['bedrijfsnaam', 'naam', 'name', 'company', 'bedrijf', 'klant', 'klantnaam', 'organisatie', 'relatie', 'companyname', 'debiteur',
                'nazwakontrahenta', 'nazwafirmy', 'nazwaklienta', 'nazwanabywcy', 'nazwa', 'kontrahent', 'nabywca', 'firma', 'klient', 'odbiorca']],
            'contact_name' => ['Contactpersoon', false, ['contactpersoon', 'contact', 'contactname', 'tav', 'aanspreekpunt', 'voornaamachternaam',
                'osobakontaktowa', 'kontakt', 'imieinazwisko']],
            'email' => ['E-mailadres', false, ['email', 'emailadres', 'mail', 'emailaddress',
                'adresemail', 'emailkontrahenta', 'emailklienta', 'emailkontaktowy', 'adresemailowy']],
            'phone' => ['Telefoon', false, ['telefoon', 'telefoonnummer', 'phone', 'tel', 'mobiel',
                'telefon', 'nrtelefonu', 'numertelefonu', 'telefonkontaktowy', 'komorka']],
            'address_line' => ['Adres (straat + nr)', false, ['adres', 'straat', 'address', 'straatnaam', 'adresregel', 'address1', 'straatennummer',
                'ulica', 'ulicainumer', 'ulicainrdomu', 'adresulica']],
            'house_number' => ['Huisnummer (los)', false, ['huisnummer', 'nummer', 'housenumber', 'nr', 'nrdomu', 'numerdomu']],
            'postal_code' => ['Postcode', false, ['postcode', 'zip', 'zipcode', 'postalcode', 'kodpocztowy', 'kod']],
            'city' => ['Plaats', false, ['plaats', 'woonplaats', 'city', 'stad', 'vestigingsplaats', 'miasto', 'miejscowosc']],
            'country' => ['Land', false, ['land', 'country', 'landcode', 'countrycode', 'kraj', 'kodkraju']],
            'kvk_number' => ['KvK-nummer', false, ['kvk', 'kvknummer', 'kvknr', 'chamberofcommerce', 'coc', 'handelsregister',
                'regon', 'numerregon', 'nrregon', 'krs', 'nrkrs', 'numerkrs']],
            'vat_number' => ['Btw-nummer', false, ['btwnummer', 'btwnr', 'vat', 'vatnumber', 'btw', 'btwid', 'vatid',
                'nip', 'nipkontrahenta', 'nipnabywcy', 'nipklienta', 'numernip', 'nrnip', 'nippesel', 'vatue', 'nipue']],
            'payment_terms' => ['Betalingstermijn (dagen)', false, ['betalingstermijn', 'betaaltermijn', 'paymentterms', 'termijn',
                'terminplatnoscidni', 'termindni', 'dniplatnosci', 'terminplatnosci']],
            'notes' => ['Notities', false, ['opmerking', 'opmerkingen', 'notities', 'notes', 'memo', 'uwagi', 'notatki', 'opis']],
        ],
        'products' => [
            'name' => ['Productnaam', true, ['productnaam', 'naam', 'omschrijving', 'product', 'artikel', 'description', 'name', 'artikelnaam', 'dienst',
                'nazwaproduktu', 'nazwatowaru', 'nazwauslugi', 'nazwatowaruuslugi', 'nazwa', 'produkt', 'usluga', 'towar', 'towarusluga']],
            'description' => ['Toelichting', false, ['toelichting', 'details', 'beschrijving', 'extra', 'longdescription', 'opis', 'opisdodatkowy', 'uwagi']],
            'sku' => ['Artikelnummer', false, ['artikelnummer', 'code', 'sku', 'productcode', 'artikelcode', 'nummer',
                'kodproduktu', 'kodtowaru', 'kod', 'indeks', 'symbol', 'pkwiu']],
            'unit' => ['Eenheid', false, ['eenheid', 'unit', 'per', 'jednostka', 'jednostkamiary', 'jm']],
            'price' => ['Prijs excl. btw', true, ['prijs', 'prijsexcl', 'prijsexclbtw', 'unitprice', 'price', 'bedrag', 'verkoopprijs', 'stuksprijs', 'tarief',
                'cenanetto', 'cenajednostkowanetto', 'cenajednostkowa', 'cenasprzedazy', 'cenasprzedazynetto', 'cena']],
            'vat_rate' => ['Btw-tarief (%)', false, ['btwtarief', 'btwpercentage', 'btwperc', 'vatrate', 'btw', 'vat',
                'stawkavat', 'stawkapodatku', 'stawka', 'podatek']],
        ],
        'invoices' => [
            'number' => ['Factuurnummer', true, ['factuurnummer', 'nummer', 'invoicenumber', 'factuur', 'factuurnr', 'invoice',
                'numerfaktury', 'nrfaktury', 'numerdokumentu', 'nrdokumentu', 'numer', 'faktura', 'nr']],
            'customer' => ['Klantnaam', true, ['klant', 'klantnaam', 'bedrijfsnaam', 'debiteur', 'customer', 'naam', 'relatie',
                'nazwakontrahenta', 'nazwanabywcy', 'nazwaklienta', 'kontrahent', 'nabywca', 'klient', 'odbiorca', 'firma']],
            'date' => ['Factuurdatum', true, ['factuurdatum', 'datum', 'date', 'invoicedate',
                'datawystawienia', 'datafaktury', 'datasprzedazy', 'data', 'wystawiono']],
            'due_date' => ['Vervaldatum', false, ['vervaldatum', 'duedate', 'vervalt', 'betaaldatum', 'uiterlijk',
                'terminplatnosci', 'terminzaplaty', 'datazaplaty', 'dataplatnosci', 'platnoscdo', 'termin']],
            'total' => ['Totaal incl. btw', true, ['totaal', 'bedraginclbtw', 'totaalbedrag', 'total', 'bedrag', 'inclbtw', 'totaalincl', 'brutobedrag', 'incl',
                'wartoscbrutto', 'kwotabrutto', 'razembrutto', 'sumabrutto', 'brutto', 'dozaplaty', 'kwota', 'suma', 'razem']],
            'vat_rate' => ['Btw-tarief (%)', false, ['btwtarief', 'btwpercentage', 'btwperc', 'btw', 'stawkavat', 'stawka', 'vat']],
            'paid' => ['Reeds betaald', false, ['betaald', 'paid', 'ontvangen', 'voldaan',
                'zaplacono', 'oplacono', 'zaplacona', 'wplacono', 'kwotazaplacona']],
            'reference' => ['Referentie/omschrijving', false, ['referentie', 'kenmerk', 'omschrijving', 'reference', 'onderwerp',
                'opis', 'tytul', 'uwagi', 'referencja']],
            'email' => ['E-mail klant', false, ['email', 'emailadres', 'mail', 'emailkontrahenta', 'emailnabywcy', 'emailklienta', 'adresemail']],
            'vat_number' => ['Btw-nummer klant', false, ['btwnummer', 'btwnummerklant', 'vatnumber', 'btwnr',
                'nip', 'nipnabywcy', 'nipkontrahenta', 'nipklienta', 'numernip', 'nrnip']],
        ],
    ];

    /** @return array{headers: string[], rows: array<int, string[]>, delimiter: string} */
    public function parse(string $contents): array
    {
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        $first = $lines[0] ?? '';
        $delimiter = collect([';', ',', "\t", '|'])->sortByDesc(fn ($d) => substr_count($first, $d))->first();
        if (substr_count($first, $delimiter) === 0) {
            throw new \DomainException('Dit lijkt geen CSV-bestand: er is geen scheidingsteken (; of ,) gevonden in de eerste regel.');
        }

        $rows = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, implode("\n", $lines));
        rewind($handle);
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;
            $rows[] = array_map(fn ($v) => trim((string) $v), $row);
        }
        fclose($handle);
        if (count($rows) < 2) {
            throw new \DomainException('Het bestand bevat geen gegevensregels onder de kopregel.');
        }

        $headers = array_shift($rows);

        return ['headers' => $headers, 'rows' => $rows, 'delimiter' => $delimiter];
    }

    /** Voorgestelde koppeling: kolomindex per doelveld op basis van de kopregel. */
    public function suggestMapping(string $type, array $headers): array
    {
        $mapping = [];
        $normalized = array_map(fn ($h) => self::key((string) $h), $headers);
        // Eerste ronde: exacte overeenkomst, in de volgorde van de synoniemen — zo wint
        // "Stawka VAT" van de kolom "VAT" (het btw-bedrag) en "Data wystawienia" van "Data sprzedaży".
        foreach (self::FIELDS[$type] as $field => [, , $synonyms]) {
            foreach ([...$synonyms, self::key($field)] as $syn) {
                foreach ($normalized as $i => $h) {
                    if ($h === $syn && ! in_array($i, $mapping, true)) {
                        $mapping[$field] = $i;
                        continue 3;
                    }
                }
            }
        }
        // Tweede ronde: gedeeltelijke overeenkomst ("emailadres klant" → email).
        foreach (self::FIELDS[$type] as $field => [, , $synonyms]) {
            if (isset($mapping[$field])) continue;
            foreach ($normalized as $i => $h) {
                if (in_array($i, $mapping, true)) continue;
                foreach ($synonyms as $syn) {
                    if (strlen($syn) >= 4 && str_contains($h, $syn)) {
                        $mapping[$field] = $i;
                        continue 3;
                    }
                }
            }
        }

        return $mapping;
    }

    /** @return array{created: int, skipped: int, errors: string[]} */
    public function import(Company $company, string $type, array $rows, array $mapping): array
    {
        $get = fn (array $row, string $field) => isset($mapping[$field]) && $mapping[$field] !== '' && $mapping[$field] !== null ? trim((string) ($row[(int) $mapping[$field]] ?? '')) : '';
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($company, $type, $rows, $get, &$result) {
            foreach ($rows as $n => $row) {
                try {
                    $done = match ($type) {
                        'customers' => $this->importCustomer($company, $row, $get),
                        'products' => $this->importProduct($company, $row, $get),
                        'invoices' => $this->importInvoice($company, $row, $get),
                    };
                    $done ? $result['created']++ : $result['skipped']++;
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    if (count($result['errors']) < 20) {
                        $result['errors'][] = 'Regel ' . ($n + 2) . ': ' . $e->getMessage();
                    }
                }
            }
        });

        return $result;
    }

    protected function importCustomer(Company $company, array $row, callable $get): bool
    {
        $name = $get($row, 'name');
        if ($name === '') throw new \DomainException('geen naam');
        $email = mb_strtolower($get($row, 'email'));

        $exists = Customer::withoutGlobalScope('company')->where('company_id', $company->id)
            ->where(fn ($q) => $email ? $q->whereRaw('LOWER(email) = ?', [$email])->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]) : $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]))
            ->exists();
        if ($exists) return false;

        $address = trim($get($row, 'address_line') . ' ' . $get($row, 'house_number'));
        $kvk = $this->registryNumber($get($row, 'kvk_number'));
        $vat = $this->vatNumber($get($row, 'vat_number'));

        Customer::create([
            'company_id' => $company->id,
            'name' => mb_substr($name, 0, 255),
            'type' => $kvk !== null || $vat !== null || $this->looksLikeBusiness($name) ? 'business' : 'consumer',
            'contact_name' => mb_substr($get($row, 'contact_name'), 0, 255) ?: null,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => mb_substr($get($row, 'phone'), 0, 50) ?: null,
            'kvk_number' => $kvk,
            'vat_number' => $vat,
            'address_line' => mb_substr($address, 0, 255) ?: null,
            'postal_code' => mb_substr(strtoupper($get($row, 'postal_code')), 0, 20) ?: null,
            'city' => mb_substr($get($row, 'city'), 0, 100) ?: null,
            'country' => $this->country($get($row, 'country')),
            'language' => DocumentLocale::default(),
            'payment_terms' => preg_match('/^\s*(\d{1,3})\s*(dni|dagen|days|d)?\.?\s*$/i', $get($row, 'payment_terms'), $m) ? (int) $m[1] : null,
            'notes' => $get($row, 'notes') ?: null,
        ]);

        return true;
    }

    protected function importProduct(Company $company, array $row, callable $get): bool
    {
        $name = $get($row, 'name');
        if ($name === '') throw new \DomainException('geen productnaam');
        if (Product::withoutGlobalScope('company')->where('company_id', $company->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return false;
        }
        Product::create([
            'company_id' => $company->id,
            'name' => mb_substr($name, 0, 255),
            'description' => $get($row, 'description') ?: null,
            'sku' => mb_substr($get($row, 'sku'), 0, 50) ?: null,
            'unit' => mb_substr($get($row, 'unit'), 0, 20) ?: $this->defaultUnit(),
            'price' => round($this->number($get($row, 'price')), 2),
            'vat_rate' => $this->vatRate($get($row, 'vat_rate')),
            'is_active' => true,
        ]);

        return true;
    }

    protected function importInvoice(Company $company, array $row, callable $get): bool
    {
        $number = $get($row, 'number');
        $customerName = $get($row, 'customer');
        if ($number === '' || $customerName === '') throw new \DomainException('factuurnummer of klant ontbreekt');
        if (Invoice::withoutGlobalScope('company')->where('company_id', $company->id)->where('number', $number)->exists()) {
            return false;
        }

        $date = $this->date($get($row, 'date')) ?? throw new \DomainException('ongeldige factuurdatum');
        $due = $this->date($get($row, 'due_date')) ?? $date->copy()->addDays($company->default_payment_terms ?: 30);
        $total = round($this->number($get($row, 'total')), 2);
        if ($total == 0.0) throw new \DomainException('geen bedrag');
        $rate = $this->vatRate($get($row, 'vat_rate'));
        $paid = round($this->number($get($row, 'paid')), 2);
        $pl = Market::isPl();

        $customer = Customer::withoutGlobalScope('company')->where('company_id', $company->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($customerName)])->first()
            ?? Customer::create([
                'company_id' => $company->id, 'name' => mb_substr($customerName, 0, 255), 'type' => 'business',
                'country' => Market::country(), 'language' => DocumentLocale::default(),
                'email' => filter_var($get($row, 'email'), FILTER_VALIDATE_EMAIL) ? mb_strtolower($get($row, 'email')) : null,
                'vat_number' => $this->vatNumber($get($row, 'vat_number')),
            ]);

        $subtotal = round($total / (1 + $rate / 100), 2);
        $vat = round($total - $subtotal, 2);
        $status = $paid >= $total - 0.004 ? 'paid' : ($paid > 0 ? 'partial' : ($due->isPast() ? 'overdue' : 'sent'));

        $invoice = new Invoice();
        $invoice->forceFill([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'number' => mb_substr($number, 0, 50), 'status' => $status,
            'invoice_date' => $date->toDateString(), 'due_date' => $due->toDateString(), 'payment_terms' => (int) max(0, $date->diffInDays($due, false)),
            'reference' => mb_substr($get($row, 'reference'), 0, 255) ?: null, 'language' => $customer->language ?: DocumentLocale::default(),
            'customer_name' => $customer->name, 'customer_address_line' => $customer->address_line, 'customer_postal_code' => $customer->postal_code,
            'customer_city' => $customer->city, 'customer_country' => $customer->country, 'customer_vat_number' => $customer->vat_number,
            'customer_kvk_number' => $customer->kvk_number, 'customer_email' => $customer->email,
            'subtotal' => $subtotal, 'vat_total' => $vat, 'total' => $total, 'paid_total' => min($paid, $total),
            'vat_breakdown' => [(string) $rate => $vat],
            'notes' => $pl ? 'Przeniesiono z poprzedniego programu przy przejściu do ' . Brand::name() . '.' : 'Overgenomen uit vorig pakket bij de overstap naar ' . Brand::name() . '.',
            'sent_at' => $date->toDateTimeString(), 'paid_at' => $status === 'paid' ? now() : null,
            'portal_token' => bin2hex(random_bytes(32)),
        ])->save();

        $invoice->lines()->create([
            'sort_order' => 0, 'description' => $pl ? 'Faktura ' . $number . ' (przeniesiona)' : 'Factuur ' . $number . ' (overgenomen)', 'quantity' => 1, 'unit' => $this->defaultUnit(),
            'unit_price' => $subtotal, 'vat_rate' => $rate, 'line_subtotal' => $subtotal, 'line_vat' => $vat, 'line_total' => $total,
        ]);

        if ($paid > 0) {
            Payment::create(['company_id' => $company->id, 'invoice_id' => $invoice->id, 'kind' => 'payment', 'amount' => min($paid, $total), 'paid_on' => $date->toDateString(), 'method' => 'bank_transfer', 'notes' => $pl ? 'Przeniesiono przy imporcie' : 'Overgenomen bij import']);
        }

        return true;
    }

    /**
     * Herkent uit welk pakket een export vermoedelijk komt, op basis van de kopregel.
     *
     * Bewust conservatief: alleen een uitslag bij (a) de pakketnaam in een kolomkop
     * (bijv. "ID Fakturownia", "wFirma ID", "Moneybird-nummer") of (b) een combinatie
     * van kolommen die kenmerkend is voor één pakket. De exacte exportkoppen van de
     * Poolse pakketten zijn per versie en per module (klanten/producten/faktury) niet
     * vast gedocumenteerd; de combinaties hieronder zijn de best bekende kenmerken:
     *  - Fakturownia: nabywca/sprzedawca-terminologie ("Nabywca", "NIP nabywcy",
     *    "Sprzedawca") of de Engelse API-veldnamen (buyer_name, buyer_tax_no, seller_name);
     *  - inFakt: "Klient" naast "Kwota brutto"/"Kwota netto";
     *  - wFirma: "Kontrahent" naast "Wartość brutto"/"Wartość netto";
     *  - iFirma: "Kontrahent" naast "Nr faktury" of "Do zapłaty" én "Brutto";
     *  - WeFact: "Debiteurcode"/"Debiteurnummer", of Bedrijfsnaam + Contactpersoon + KvK/BTW-nummer
     *    zonder klantnummer/relatiecode;
     *  - Moneybird: "Klantnummer"/"Contact ID" naast "Adres 1"/"Voornaam"/"Achternaam", of "Totaalprijs …";
     *  - e-Boekhouden: "Relatiecode", of "Code" + "Bedrijf" naast "Geslacht"/"GSM".
     * Bij twijfel null. De kolomkoppeling zelf werkt altijd op synoniemen; de pakketnaam
     * is alleen bedoeld voor uitleg en voorkeuzes in de wizard.
     *
     * @param  string[]  $headers  ruwe kopregel zoals parse() die teruggeeft
     * @return string|null 'fakturownia'|'ifirma'|'wfirma'|'infakt'|'wefact'|'moneybird'|'e-boekhouden'|null
     */
    public static function detectPackage(array $headers): ?string
    {
        $keys = array_values(array_unique(array_filter(array_map(fn ($h) => self::key((string) $h), $headers))));
        if ($keys === []) return null;
        $has = fn (string ...$all) => array_diff($all, $keys) === [];
        $any = fn (string ...$one) => array_intersect($one, $keys) !== [];
        $like = fn (string $needle) => (bool) array_filter($keys, fn ($k) => str_contains($k, $needle));

        // (a) Pakketnaam in een kolomkop ("ID Fakturownia", "wFirma ID", "e-Boekhouden relatiecode").
        $raw = mb_strtolower(Str::ascii(implode(' | ', array_map('strval', $headers))));
        foreach (['fakturownia' => 'fakturownia', 'ifirma' => 'ifirma', 'wfirma' => 'wfirma', 'infakt' => 'infakt', 'wefact' => 'wefact', 'moneybird' => 'moneybird', 'e-boekhouden' => 'e-?boekhouden'] as $package => $pattern) {
            if (preg_match('/(?<![a-z])' . $pattern . '(?![a-z])/', $raw)) return $package;
        }

        // (b) Kenmerkende kolomcombinaties — Pools.
        if ($any('buyername', 'buyertaxno', 'sellername') || ($has('nabywca') && $any('sprzedawca', 'nipnabywcy', 'nazwanabywcy', 'nipsprzedawcy'))) return 'fakturownia';
        if ($has('klient') && $any('kwotabrutto', 'kwotanetto')) return 'infakt';
        if ($has('kontrahent') && $any('wartoscbrutto', 'wartoscnetto')) return 'wfirma';
        if ($has('kontrahent', 'brutto') && $any('nrfaktury', 'dozaplaty')) return 'ifirma';

        // Nederlands.
        if ($any('debiteurcode', 'debiteurnummer', 'debtorcode')) return 'wefact';
        if (($any('klantnummer', 'contactid', 'customerid') && $any('adres1', 'voornaam', 'achternaam', 'address1', 'firstname')) || $like('totaalprijs')) return 'moneybird';
        if ($any('relatiecode') || ($has('code', 'bedrijf') && $any('geslacht', 'gsm'))) return 'e-boekhouden';
        if ($has('bedrijfsnaam', 'contactpersoon') && $any('kvknummer', 'btwnummer') && ! $any('klantnummer', 'relatiecode')) return 'wefact';

        return null;
    }

    /**
     * Kopregel → sleutel: kleine letters, diakrieten weg, alleen a-z en 0-9.
     * Poolse letters worden getranslitereerd (ą→a, ę→e, ł→l, ó→o, ś→s, ż→z, ź→z, ć→c, ń→n),
     * dus "Nazwa kontrahenta" → nazwakontrahenta en "Termin płatności" → terminplatnosci.
     * Str::ascii doet dit al; de expliciete tabel maakt het onafhankelijk van de
     * transliteratieregels van de onderliggende bibliotheek.
     */
    private static function key(string $header): string
    {
        $lower = strtr(mb_strtolower($header), ['ą' => 'a', 'ę' => 'e', 'ł' => 'l', 'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z', 'ć' => 'c', 'ń' => 'n']);

        return preg_replace('/[^a-z0-9]/', '', Str::ascii($lower));
    }

    /**
     * Bedrag uit een cel: "1.234,56" / "1234.56" / "€ 1.234,56" / "1 234,56 zł" (Poolse
     * schrijfwijze met spatie of vaste spatie als duizendtal) / "1,234.56" → 1234.56.
     * Valuta, spaties en andere tekens worden genegeerd; bij zowel komma als punt
     * bepaalt het laatste scheidingsteken de decimalen.
     */
    private function number(string $value): float
    {
        $v = preg_replace('/[^\d,.\-]/', '', $value);
        if ($v === '' || $v === '-') return 0.0;
        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = strrpos($v, ',') > strrpos($v, '.') ? str_replace('.', '', $v) : str_replace(',', '', $v);
        }
        $v = str_replace(',', '.', $v);

        return (float) $v;
    }

    /**
     * Btw-tarief uit een cel, marktbewust: "21", "23%", "23 %", "8", "0,23" → dichtstbijzijnde
     * geldig tarief van de markt (nl 21/9/0, pl 23/8/5/0). Poolse exports schrijven het
     * vrijgestelde tarief als "zw" (zwolniony), "np" (nie podlega) of "oo" (odwrotne
     * obciążenie) → 0; ook "vrijgesteld"/"exempt" → 0. Leeg → standaardtarief van de markt.
     */
    private function vatRate(string $value): int
    {
        $v = trim($value);
        if ($v === '') return Market::defaultVatRate();
        if (! preg_match('/\d/', $v)) {
            $word = preg_replace('/[^a-z]/', '', Str::ascii(mb_strtolower($v)));
            if (preg_match('/^(zw|np|oo|nie|vrijgesteld|exempt|none|geen)/', $word)) return 0;
            if (preg_match('/^(laag|low|verlaagd|reduced|obnizon)/', $word)) return (int) (Market::vatRates()[1] ?? Market::defaultVatRate());

            return Market::defaultVatRate();
        }
        $n = $this->number($v);
        if ($n > 0 && $n < 1) $n *= 100; // "0,23" → 23

        return Market::nearestVatRate($n);
    }

    /**
     * Datum uit een cel: 2026-09-12, 12-09-2026, 12.09.2026 (Pools), 12/09/2026, 2026/09/12,
     * 12-09-26, 12.09.26 en varianten zonder voorloopnullen; een tijd erachter wordt genegeerd.
     */
    private function date(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;
        $value = trim(preg_replace('/[T ]\d{1,2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:?\d{2})?$/', '', $value));
        foreach (['Y-m-d', 'd-m-Y', 'd.m.Y', 'd/m/Y', 'Y/m/d', 'Y.m.d', 'd-m-y', 'd.m.y', 'j-n-Y', 'j.n.Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $value);
                if ($d && $d->year > 1990 && $d->year < 2100) return $d->startOfDay();
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Landcode uit een cel: "NL", "PL", "Polska", "Nederland", "Deutschland", "Belgique", …
     * Leeg of onbekend → land van de markt.
     */
    private function country(string $value): string
    {
        $v = trim($value);
        if ($v === '') return Market::country();
        $name = preg_replace('/[^a-z]/', '', Str::ascii(mb_strtolower($v)));
        $byName = [
            'nederland' => 'NL', 'netherlands' => 'NL', 'thenetherlands' => 'NL', 'holland' => 'NL', 'holandia' => 'NL', 'niederlande' => 'NL',
            'belgie' => 'BE', 'belgium' => 'BE', 'belgia' => 'BE', 'belgique' => 'BE', 'belgien' => 'BE',
            'duitsland' => 'DE', 'germany' => 'DE', 'deutschland' => 'DE', 'niemcy' => 'DE',
            'polen' => 'PL', 'poland' => 'PL', 'polska' => 'PL', 'rzeczpospolitapolska' => 'PL',
            'frankrijk' => 'FR', 'france' => 'FR', 'francja' => 'FR',
            'verenigdkoninkrijk' => 'GB', 'unitedkingdom' => 'GB', 'greatbritain' => 'GB', 'wielkabrytania' => 'GB', 'engeland' => 'GB', 'england' => 'GB',
            'spanje' => 'ES', 'spain' => 'ES', 'hiszpania' => 'ES', 'italie' => 'IT', 'italy' => 'IT', 'italia' => 'IT', 'wlochy' => 'IT',
            'luxemburg' => 'LU', 'luxembourg' => 'LU', 'luksemburg' => 'LU', 'oostenrijk' => 'AT', 'austria' => 'AT', 'osterreich' => 'AT',
            'denemarken' => 'DK', 'denmark' => 'DK', 'dania' => 'DK', 'zweden' => 'SE', 'sweden' => 'SE', 'szwecja' => 'SE',
            'portugal' => 'PT', 'portugalia' => 'PT', 'ierland' => 'IE', 'ireland' => 'IE', 'irlandia' => 'IE',
            'verenigdestaten' => 'US', 'unitedstates' => 'US', 'usa' => 'US', 'stanyzjednoczone' => 'US',
            'tsjechie' => 'CZ', 'czechia' => 'CZ', 'czechrepublic' => 'CZ', 'czechy' => 'CZ', 'slowakije' => 'SK', 'slovakia' => 'SK', 'slowacja' => 'SK',
            'litouwen' => 'LT', 'lithuania' => 'LT', 'litwa' => 'LT', 'oekraine' => 'UA', 'ukraine' => 'UA', 'ukraina' => 'UA',
            'noorwegen' => 'NO', 'norway' => 'NO', 'norwegia' => 'NO', 'zwitserland' => 'CH', 'switzerland' => 'CH', 'szwajcaria' => 'CH',
            'hongarije' => 'HU', 'hungary' => 'HU', 'wegry' => 'HU', 'finland' => 'FI', 'finlandia' => 'FI',
        ];
        if (isset($byName[$name])) return $byName[$name];
        $code = strtoupper(substr($v, 0, 2));
        $known = ['NL', 'BE', 'DE', 'FR', 'GB', 'ES', 'IT', 'LU', 'AT', 'DK', 'SE', 'PL', 'PT', 'IE', 'US', 'CZ', 'SK', 'LT', 'UA', 'NO', 'CH', 'HU', 'FI'];

        return strlen($v) <= 3 && in_array($code, $known, true) ? $code : Market::country();
    }

    /** Rechtsvorm in de naam (NL: BV/NV/VOF/…, PL: sp. z o.o./S.A./sp.j./sp.k./s.c./fundacja, plus GmbH/Ltd). */
    private function looksLikeBusiness(string $name): bool
    {
        return (bool) preg_match('/(?<![\p{L}\d])(bv|b\.v\.?|nv|n\.v\.?|vof|v\.o\.f\.?|holding|bedrijf|group|groep|stichting|sp\. ?z ?o\. ?o\.?|s\.a\.?|sp\. ?j\.?|sp\. ?k\.?|s\.c\.?|spolka|spółka|fundacja|stowarzyszenie|gmbh|ltd|llc|sarl|s\.r\.o\.?)(?![\p{L}\d])/iu', $name);
    }

    /** Btw-nummer/NIP: hoofdletters, zonder spaties, punten en streepjes ("525-224-84-81" → 5252248481). */
    private function vatNumber(string $value): ?string
    {
        return mb_substr(strtoupper(preg_replace('/[\s.\-]/', '', $value)), 0, 20) ?: null;
    }

    /** KvK/REGON/KRS: zonder spaties. */
    private function registryNumber(string $value): ?string
    {
        return mb_substr(preg_replace('/\s+/', '', $value), 0, 20) ?: null;
    }

    private function defaultUnit(): string
    {
        return Market::isPl() ? 'szt.' : 'stuk';
    }
}
