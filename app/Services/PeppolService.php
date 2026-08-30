<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseInboxItem;
use App\Services\Peppol\RecommandClient;
use App\Support\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Peppol in twee lagen:
 *
 *  1. Bereikbaarheidscheck — via Recommand (/verify) als die is geconfigureerd,
 *     anders via de openbare Peppol Directory. Werkt zonder registratie.
 *  2. Verzenden en ontvangen — via Recommand. Elke administratie wordt als
 *     eigen deelnemer geregistreerd (KvK 0106 + btw 9944) en moet eenmalig
 *     een identiteitscontrole doorlopen. Daarna: facturen afleveren als UBL
 *     (NLCIUS, of BIS 3 als de ontvanger alleen dát accepteert) en ontvangen
 *     e-facturen automatisch in het Postvak IN.
 */
class PeppolService
{
    private const DIRECTORY = 'https://directory.peppol.eu/search/1.0/json';

    public const DOCTYPE_NLCIUS = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice##urn:cen.eu:en16931:2017#compliant#urn:fdc:nen.nl:nlcius:v1.0::2.1';
    public const DOCTYPE_BIS3 = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice##urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0::2.1';
    public const PROCESS_BILLING = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    public function __construct(protected RecommandClient $client) {}

    /** Peppol-deelnemers-ID van de klant: eigen invoer of afgeleid van KvK. */
    public function participantId(Customer $customer): ?string
    {
        if ($customer->peppol_id) {
            $id = trim($customer->peppol_id);

            return str_contains($id, ':') ? $id : '0106:' . preg_replace('/\D/', '', $id);
        }

        $kvk = preg_replace('/\D/', '', (string) $customer->kvk_number);

        return strlen($kvk) === 8 ? "0106:{$kvk}" : null;
    }

    /** Staat dit ID geregistreerd in het Peppol-netwerk? (+ ondersteunde documenttypen) */
    public function lookup(string $participantId): array
    {
        if ($this->client->configured()) {
            $data = $this->client->verify($participantId);

            return [
                'registered' => (bool) ($data['isValid'] ?? false),
                'doctypes' => array_values(array_filter(array_map(fn ($d) => $d['docTypeId'] ?? null, $data['supportedDocuments'] ?? []))),
                'name' => $data['companyName'] ?? null,
            ];
        }

        $response = Http::timeout(8)->get(self::DIRECTORY, [
            'participant' => 'iso6523-actorid-upis::' . $participantId,
        ]);
        if ($response->failed()) {
            throw new \DomainException(__('De Peppol Directory reageert niet. Probeer het later opnieuw.'));
        }

        return ['registered' => (int) $response->json('total-result-count', 0) > 0, 'doctypes' => [], 'name' => null];
    }

    public function isRegistered(string $participantId): bool
    {
        return $this->lookup($participantId)['registered'];
    }

    /**
     * Controleer (en cache op de klant, een week geldig) of de klant
     * bereikbaar is via Peppol. Geeft null terug als er geen ID afleidbaar is.
     */
    public function checkCustomer(Customer $customer, bool $force = false): ?bool
    {
        $participantId = $this->participantId($customer);
        if (! $participantId) {
            return null;
        }

        $fresh = $customer->peppol_checked_at && $customer->peppol_checked_at->gt(now()->subDays(7));
        if (! $force && $fresh) {
            return (bool) $customer->peppol_available;
        }

        try {
            $available = $this->isRegistered($participantId);
        } catch (\DomainException $e) {
            return $customer->peppol_available; // netwerk tijdelijk onbereikbaar: oude waarde houden
        }

        $customer->forceFill(['peppol_available' => $available, 'peppol_checked_at' => now()])->saveQuietly();

        return $available;
    }

    /* ===================== Registratie van de administratie ===================== */

    /** Is Recommand door de beheerder geconfigureerd (teamkey)? */
    public function configured(): bool
    {
        return $this->client->configured();
    }

    /** Kan deze administratie via Peppol verzenden (geregistreerd én geverifieerd)? */
    public function sendingEnabled(?Company $company): bool
    {
        return $company !== null
            && $this->configured()
            && filled($company->peppol_company_id)
            && $company->peppol_verification_status === 'verified';
    }

    /** Wat ontbreekt er nog om te kunnen registreren? Leeg = alles klaar. */
    public function registrationBlockers(Company $company): array
    {
        $missing = [];
        if (strlen(preg_replace('/\D/', '', (string) $company->kvk_number)) !== 8) {
            $missing[] = __('een geldig KvK-nummer (8 cijfers)');
        }
        if (blank($company->address_line)) {
            $missing[] = __('een adres');
        }
        if (blank($company->postal_code)) {
            $missing[] = __('een postcode');
        }
        if (blank($company->city)) {
            $missing[] = __('een plaats');
        }
        if (blank($company->email)) {
            $missing[] = __('een e-mailadres');
        }

        return $missing;
    }

    /** Registreer de administratie als Peppol-deelnemer; geeft de verificatie-URL terug. */
    public function register(Company $company): string
    {
        if (! $this->configured()) {
            throw new \DomainException(__('Peppol is nog niet ingericht door de beheerder van :brand.', ['brand' => Brand::name()]));
        }
        if ($missing = $this->registrationBlockers($company)) {
            throw new \DomainException(__('Vul eerst :missing in bij Instellingen → Bedrijfsgegevens.', ['missing' => implode(', ', $missing)]));
        }
        if ($company->peppol_company_id) {
            return (string) $company->peppol_verification_url;
        }

        $kvk = preg_replace('/\D/', '', (string) $company->kvk_number);
        $vat = filled($company->vat_number) ? strtoupper(preg_replace('/[\s.-]/', '', $company->vat_number)) : null;

        // Staat het bedrijf al in het Recommand-team (bijv. handmatig in het
        // dashboard aangemaakt)? Dan koppelen we dát, geen dubbele registratie.
        $remote = $this->findExisting($company, $kvk, $vat);
        $data = [];
        if (! $remote) {
            $data = $this->client->createCompany([
                'name' => $company->name,
                'address' => $company->address_line,
                'postalCode' => $company->postal_code,
                'city' => $company->city,
                'country' => strtoupper($company->country ?: 'NL'),
                'enterpriseNumberScheme' => strtoupper($company->country ?: 'NL') === 'NL' ? '0106' : null,
                'enterpriseNumber' => $kvk,
                'vatNumber' => $vat,
                'email' => $company->email,
                'isSmpRecipient' => true,
            ]);
            $remote = $data['company'] ?? [];
        }

        $verified = ! empty($remote['isVerified']);
        $company->forceFill([
            'peppol_company_id' => $remote['id'] ?? null,
            'peppol_verification_status' => $verified ? 'verified' : 'pending',
            'peppol_verification_url' => $data['verificationUrl'] ?? null,
            'peppol_registered_at' => now(),
            'peppol_verified_at' => $verified ? now() : null,
        ])->save();

        if (! empty($remote['id'])) {
            $this->ensureIdentifiers($remote['id'], $kvk, $vat);
        }
        $this->ensureWebhook();

        return $verified ? '' : (string) ($data['verificationUrl'] ?? '');
    }

    /** Bestaand Recommand-bedrijf zoeken op KvK, btw-nummer of (genormaliseerde) naam. */
    protected function findExisting(Company $company, string $kvk, ?string $vat): ?array
    {
        $norm = fn ($s) => preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $s));
        $candidates = [];
        try {
            if ($kvk !== '') {
                $candidates = $this->client->listCompanies(['enterpriseNumber' => $kvk]);
            }
            if (! $candidates && $vat) {
                $candidates = $this->client->listCompanies(['vatNumber' => $vat]);
            }
            if (! $candidates) {
                $candidates = array_values(array_filter(
                    $this->client->listCompanies(),
                    fn ($c) => $norm($c['name'] ?? '') !== '' && $norm($c['name'] ?? '') === $norm($company->name)
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Recommand: bestaande registratie zoeken mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);

            return null;
        }

        return $candidates[0] ?? null;
    }

    /** Zorg dat KvK (0106) en btw-nummer (9944) als Peppol-identifier op het bedrijf staan. */
    protected function ensureIdentifiers(string $remoteId, string $kvk, ?string $vat): void
    {
        try {
            $have = array_map(fn ($i) => strtolower(($i['scheme'] ?? '') . ':' . ($i['identifier'] ?? '')), $this->client->getIdentifiers($remoteId));
            $wanted = array_filter(['0106' => $kvk !== '' ? $kvk : null, '9944' => $vat]);
            foreach ($wanted as $scheme => $identifier) {
                if (! in_array(strtolower("{$scheme}:{$identifier}"), $have, true)) {
                    $this->client->createIdentifier($remoteId, (string) $scheme, $identifier);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Recommand: Peppol-identifiers aanvullen mislukt', ['remote' => $remoteId, 'error' => $e->getMessage()]);
        }
    }

    /** Eenmalig (teambreed) de webhook naar EasyInvoice registreren — idempotent. */
    protected function ensureWebhook(): void
    {
        $url = route('recommand.webhook');
        if (! str_starts_with($url, 'https://')) {
            return; // lokaal/test: geen webhook naar een niet-publiek adres
        }
        try {
            foreach ($this->client->listWebhooks() as $hook) {
                if (rtrim((string) ($hook['url'] ?? ''), '/') === rtrim($url, '/')) {
                    return;
                }
            }
            $this->client->createWebhook($url, config('services.peppol.recommand_webhook_secret') ?: null);
            Log::info('Recommand: webhook geregistreerd', ['url' => $url]);
        } catch (\Throwable $e) {
            Log::warning('Recommand: webhook registreren mislukt', ['error' => $e->getMessage()]);
        }
    }

    /** Verificatiestatus ophalen bij Recommand en op de administratie zetten. */
    public function refreshStatus(Company $company): string
    {
        if (! $company->peppol_company_id) {
            return 'none';
        }

        $remote = $this->client->getCompany($company->peppol_company_id)['company'] ?? [];
        $verified = ! empty($remote['isVerified']);

        $company->forceFill([
            'peppol_verification_status' => $verified ? 'verified' : ($company->peppol_verification_status === 'rejected' ? 'rejected' : 'pending'),
            'peppol_verified_at' => $verified ? ($company->peppol_verified_at ?? now()) : null,
        ])->save();

        return $company->peppol_verification_status;
    }

    /** Webhook company.verification: status bijwerken. */
    public function applyVerificationStatus(Company $company, string $status): void
    {
        $status = strtolower($status);
        $company->forceFill([
            'peppol_verification_status' => in_array($status, ['verified', 'rejected', 'error'], true) ? $status : 'pending',
            'peppol_verified_at' => $status === 'verified' ? ($company->peppol_verified_at ?? now()) : null,
        ])->save();
    }

    /** Afmelden: deelnemer verwijderen bij Recommand en lokaal opschonen. */
    public function deregister(Company $company): void
    {
        if ($company->peppol_company_id) {
            try {
                $this->client->deleteCompany($company->peppol_company_id);
            } catch (\DomainException $e) {
                Log::warning('Recommand: bedrijf verwijderen mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        $company->forceFill([
            'peppol_company_id' => null,
            'peppol_verification_status' => null,
            'peppol_verification_url' => null,
            'peppol_registered_at' => null,
            'peppol_verified_at' => null,
        ])->save();
    }

    /* ===================== Verzenden ===================== */

    /**
     * Lever de factuur af via het Peppol-netwerk. NLCIUS als de ontvanger dat
     * accepteert, anders Peppol BIS 3 (zelfde inhoud, ander customization-ID).
     * Geeft de referentie (document-id) van de verzending terug.
     */
    public function send(Invoice $invoice): string
    {
        $company = $invoice->company;
        if (! $this->sendingEnabled($company)) {
            throw new \DomainException($this->configured()
                ? __('Peppol is voor deze administratie nog niet geactiveerd of geverifieerd (Instellingen → Koppelingen).')
                : __('Peppol is nog niet ingericht door de beheerder van :brand.', ['brand' => Brand::name()]));
        }
        if ($invoice->status === 'draft' || ! $invoice->number) {
            throw new \DomainException(__('Verstuur de factuur eerst; concepten kunnen niet via Peppol.'));
        }

        $customer = $invoice->customer;
        $participantId = $customer ? $this->participantId($customer) : null;
        if (! $participantId) {
            throw new \DomainException(__('Geen Peppol-ID bekend voor deze klant (KvK-nummer of Peppol-ID invullen).'));
        }

        $invoice->load('lines');
        $ubl = app(UblGenerator::class)->generate($invoice);

        // Documenttype afstemmen op wat de ontvanger geregistreerd heeft.
        $doctype = self::DOCTYPE_NLCIUS;
        try {
            $doctypes = $this->lookup($participantId)['doctypes'];
            if ($doctypes && ! in_array(self::DOCTYPE_NLCIUS, $doctypes, true) && in_array(self::DOCTYPE_BIS3, $doctypes, true)) {
                $doctype = self::DOCTYPE_BIS3;
                $ubl = str_replace(
                    'urn:cen.eu:en16931:2017#compliant#urn:fdc:nen.nl:nlcius:v1.0',
                    'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
                    $ubl
                );
            }
        } catch (\DomainException $e) {
            // Lookup mislukt: gewoon NLCIUS proberen; Recommand valideert alsnog.
        }

        $data = $this->client->sendXml($company->peppol_company_id, $participantId, $ubl, $doctype, self::PROCESS_BILLING);
        $reference = (string) ($data['id'] ?? $data['peppolMessageId'] ?? 'onbekend');

        $invoice->forceFill([
            'peppol_sent_at' => now(),
            'peppol_reference' => $reference,
            'peppol_status' => ! empty($data['sentOverPeppol']) ? 'sent' : 'email',
        ])->saveQuietly();

        return $reference;
    }

    /* ===================== Ontvangen ===================== */

    /**
     * Webhook document.received: het document ophalen en als inkoopfactuur
     * in het Postvak IN zetten (gegevens al ingevuld, PDF als bijlage).
     * Idempotent: elk Peppol-document hoogstens één keer.
     */
    public function importReceived(string $documentId): ?PurchaseInboxItem
    {
        if (PurchaseInboxItem::withoutGlobalScope('company')->where('peppol_document_id', $documentId)->exists()) {
            return null;
        }

        $doc = $this->client->getDocument($documentId);
        if (($doc['direction'] ?? 'incoming') !== 'incoming') {
            return null;
        }

        $company = Company::where('peppol_company_id', $doc['companyId'] ?? '')->first();
        if (! $company) {
            Log::warning('Recommand: document voor onbekende administratie', ['document' => $documentId, 'companyId' => $doc['companyId'] ?? null]);

            return null;
        }

        $parsed = is_array($doc['parsed'] ?? null) ? $doc['parsed'] : [];
        $isCredit = str_contains(strtolower((string) ($doc['type'] ?? '')), 'credit');
        $scan = $this->scanFromParsed($parsed, $isCredit);

        // PDF-weergave als bijlage; lukt dat niet, dan de UBL-XML zelf.
        $pdf = $this->client->renderPdf($documentId);
        $binary = $pdf ?: (string) ($doc['xml'] ?? $this->client->getDocumentXml($documentId));
        $base = 'peppol-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($scan['supplier_reference'] ?: $documentId));

        $item = PurchaseInboxItem::create([
            'company_id' => $company->id,
            'peppol_document_id' => $documentId,
            'from_email' => null,
            'subject' => trim('Peppol · ' . ($scan['supplier_name'] ?: __('e-factuur')) . ($scan['supplier_reference'] ? " · {$scan['supplier_reference']}" : '')),
            'filename' => $base . ($pdf ? '.pdf' : '.xml'),
            'mime_type' => $pdf ? 'application/pdf' : 'application/xml',
            'size_bytes' => strlen($binary),
            'file_data' => base64_encode($binary),
            'status' => 'pending',
            'received_at' => now(),
            'scan' => $scan,
            'scanned_at' => now(),
        ]);

        $this->client->markAsRead($documentId);

        return $item;
    }

    /** Geparste Peppol-factuur → het scan-formaat dat het Postvak IN al kent. */
    protected function scanFromParsed(array $p, bool $isCredit = false): array
    {
        $sign = $isCredit ? -1 : 1;
        $num = fn ($v) => round($sign * (float) ($v ?? 0), 2);

        $lines = [];
        foreach ((array) ($p['vat']['subtotals'] ?? []) as $sub) {
            $rate = (int) round((float) ($sub['percentage'] ?? 0));
            $rate = in_array($rate, [21, 9, 0], true) ? $rate : 0;
            $lines[$rate] = [
                'rate' => $rate,
                'base' => round(($lines[$rate]['base'] ?? 0) + $num($sub['taxableAmount'] ?? 0), 2),
                'vat' => round(($lines[$rate]['vat'] ?? 0) + $num($sub['vatAmount'] ?? 0), 2),
            ];
        }
        if (! $lines) {
            $excl = $num($p['totals']['taxExclusiveAmount'] ?? 0);
            $incl = $num($p['totals']['taxInclusiveAmount'] ?? $excl);
            $ratio = $excl != 0.0 ? ($incl - $excl) / $excl : 0;
            $lines[] = ['rate' => abs($ratio - 0.21) < 0.02 ? 21 : (abs($ratio - 0.09) < 0.02 ? 9 : 0), 'base' => $excl, 'vat' => round($incl - $excl, 2)];
        }

        $seller = $p['seller'] ?? [];
        $sum = array_sum(array_map(fn ($l) => $l['base'] + $l['vat'], $lines));

        return [
            'is_invoice' => true,
            'supplier_name' => mb_substr((string) ($seller['name'] ?? ''), 0, 190) ?: null,
            'supplier_reference' => mb_substr((string) ($p['invoiceNumber'] ?? ''), 0, 100) ?: null,
            'invoice_date' => $p['issueDate'] ?? null,
            'due_date' => $p['dueDate'] ?? null,
            'category' => null,
            'vat_lines' => array_values($lines),
            'total_incl' => isset($p['totals']['taxInclusiveAmount']) ? $num($p['totals']['taxInclusiveAmount']) : round($sum, 2),
            'deductions' => [],
            'amount_due' => isset($p['totals']['payableAmount']) ? $num($p['totals']['payableAmount']) : null,
            'notes' => mb_substr(trim((string) ($p['note'] ?? '')), 0, 300) ?: ($isCredit ? __('Creditnota ontvangen via Peppol') : __('Ontvangen via Peppol')),
            'warning' => $isCredit ? __('Dit is een creditnota: de bedragen zijn negatief overgenomen.') : null,
        ];
    }
}
