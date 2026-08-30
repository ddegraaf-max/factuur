<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceManager;
use App\Services\QuoteManager;
use App\Support\Brand;
use App\Support\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Claude-koppeling: een minimale MCP-server (Model Context Protocol) zodat
 * Claude — via een custom connector in claude.ai — rechtstreeks in
 * EasyInvoice kan werken: klanten zoeken, concept-offertes en -facturen
 * aanmaken en openstaande facturen opvragen.
 *
 * Beveiliging: het geheime token in de URL bepaalt de administratie (zoals
 * bij het inboek-adres). Alles wat Claude aanmaakt is een CONCEPT — er wordt
 * nooit iets verstuurd zonder dat de gebruiker het in de app bevestigt.
 *
 * Protocol: JSON-RPC 2.0 over HTTP POST (MCP "Streamable HTTP", zonder
 * streaming — elk antwoord is één JSON-response, wat de spec toestaat).
 */
class McpController extends Controller
{
    protected const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public function __construct(
        protected QuoteManager $quotes,
        protected InvoiceManager $invoices,
    ) {}

    public function handle(Request $request, string $token): Response
    {
        // GET is voor server-initiated streams (gebruiken we niet); DELETE
        // beëindigt een sessie (we zijn stateless).
        if ($request->isMethod('get')) {
            return response('', 405, ['Allow' => 'POST']);
        }
        if ($request->isMethod('delete')) {
            return response('', 204);
        }

        $company = Company::where('mcp_token', $token)->first();
        if (! $company) {
            return $this->rpcError(null, -32000, __('Onbekende of ingetrokken koppeling. Maak in :brand (Instellingen → Koppelingen) een nieuwe koppel-URL aan.', ['brand' => Brand::name()]));
        }

        $message = $request->json()->all();
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? '';
        $params = $message['params'] ?? [];

        // Notificaties (geen id) beantwoorden we met 202 zonder body.
        if ($id === null && str_starts_with($method, 'notifications/')) {
            return response('', 202);
        }

        return match ($method) {
            'initialize' => $this->initialize($id, $params),
            'ping' => $this->rpcResult($id, new \stdClass()),
            'tools/list' => $this->toolsList($id),
            'tools/call' => $this->toolsCall($id, $params, $company),
            default => $this->rpcError($id, -32601, __("Methode ':method' wordt niet ondersteund.", ['method' => $method])),
        };
    }

    /* ===================== Protocol ===================== */

    protected function initialize(mixed $id, array $params): Response
    {
        $requested = $params['protocolVersion'] ?? self::PROTOCOL_VERSIONS[0];
        $version = in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSIONS[0];

        return $this->rpcResult($id, [
            'protocolVersion' => $version,
            'capabilities' => ['tools' => new \stdClass()],
            'serverInfo' => [
                'name' => Brand::name(),
                'version' => (string) config('app.version', '1.0'),
            ],
            'instructions' => __(':brand is de facturatie-administratie van de gebruiker.', ['brand' => Brand::name()]) . ' '
                . __('Gebruik klanten_zoeken om de juiste klant te vinden voordat je een offerte of factuur aanmaakt.') . ' '
                . __('Alles wat je aanmaakt is een concept: de gebruiker controleert en verstuurt het zelf in :brand.', ['brand' => Brand::name()]) . ' '
                . __('Prijzen geef je altijd exclusief btw op, met het btw-tarief (:rates of :last) per regel.', $this->rateList()) . ' '
                . __('Heb je een uitgebreid offertedocument of plan van aanpak geschreven? Stuur het mee als bijlage (veld "bijlage" met "tekst" in markdown) — :brand maakt er een nette PDF van die met de mail naar de klant meegaat.', ['brand' => Brand::name()]),
        ]);
    }

    /** Btw-tarieven van de markt als tekst: nl "21, 9 of 0", pl "23, 8, 5 lub 0" (placeholders :rates en :last). */
    protected function rateList(): array
    {
        $rates = Market::vatRates();
        $last = array_pop($rates);

        return ['rates' => implode(', ', $rates), 'last' => $last];
    }

    protected function toolsList(mixed $id): Response
    {
        $lineSchema = [
            'type' => 'object',
            'properties' => [
                'omschrijving' => ['type' => 'string', 'description' => __('Korte omschrijving van de regel.')],
                'toelichting' => ['type' => 'string', 'description' => __('Optionele toelichting onder de regel.')],
                'aantal' => ['type' => 'number', 'description' => __('Aantal (standaard 1).')],
                'eenheid' => ['type' => 'string', 'description' => __('Eenheid, bijv. stuk, uur, dag, maand (standaard "stuk").')],
                'prijs_excl_btw' => ['type' => 'number', 'description' => __('Stuksprijs EXCLUSIEF btw.')],
                'btw_percentage' => ['type' => 'number', 'enum' => Market::vatRates(), 'description' => __('BTW-tarief in procenten.')],
                'korting_pct' => ['type' => 'number', 'description' => __('Optionele korting op deze regel in procenten (0-100).')],
            ],
            'required' => ['omschrijving', 'aantal', 'prijs_excl_btw', 'btw_percentage'],
            'additionalProperties' => false,
        ];

        return $this->rpcResult($id, ['tools' => [
            [
                'name' => 'klanten_zoeken',
                'description' => __('Zoek klanten in de administratie op naam. Gebruik dit om de juiste klant te vinden (en de exacte naam te kennen) voordat je een offerte of factuur aanmaakt. Zonder zoekterm krijg je de eerste 25 klanten.'),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'zoekterm' => ['type' => 'string', 'description' => __('Deel van de klantnaam (optioneel).')],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'offerte_aanmaken',
                'description' => __('Maak een CONCEPT-offerte aan voor een bestaande klant. Prijzen exclusief btw. Kan ook een geschreven document meesturen als bijlage (veld "bijlage" met "tekst" in markdown — bijv. het volledige offertedocument of plan van aanpak): :brand maakt er een PDF in de eigen huisstijl van die met de offertemail meegaat. Ook een handelsnaam kiezen kan. De gebruiker controleert en verstuurt alles zelf in :brand.', ['brand' => Brand::name()]),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'klant' => ['type' => 'string', 'description' => __('Naam van een bestaande klant (moet eenduidig matchen — gebruik eventueel eerst klanten_zoeken).')],
                        'regels' => ['type' => 'array', 'items' => $lineSchema, 'description' => __('De offerteregels.')],
                        'referentie' => ['type' => 'string', 'description' => __('Referentie of projectnaam (optioneel).')],
                        'intro' => ['type' => 'string', 'description' => __('Inleidende tekst boven de offerte (optioneel).')],
                        'opmerkingen' => ['type' => 'string', 'description' => __('Voorwaarden, planning of aannames onder de offerte (optioneel).')],
                        'geldig_dagen' => ['type' => 'integer', 'description' => __('Geldigheid in dagen (optioneel; standaard van de administratie).')],
                        'handelsnaam' => ['type' => 'string', 'description' => __('Optioneel: de handelsnaam (huisstijl) waaronder de offerte wordt gemaakt, als de administratie meerdere handelsnamen heeft. Weglaten = standaard huisstijl.')],
                        'bijlage' => $this->attachmentSchema('offerte'),
                    ],
                    'required' => ['klant', 'regels'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'factuur_aanmaken',
                'description' => __('Maak een CONCEPT-factuur aan voor een bestaande klant (het factuurnummer wordt pas bij versturen toegekend). Prijzen exclusief btw. Kan ook een geschreven document meesturen als bijlage (veld "bijlage" met "tekst" in markdown — bijv. een urenspecificatie): :brand maakt er een PDF in de eigen huisstijl van die met de factuurmail meegaat. De gebruiker controleert en verstuurt alles zelf in :brand.', ['brand' => Brand::name()]),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'klant' => ['type' => 'string', 'description' => __('Naam van een bestaande klant (moet eenduidig matchen).')],
                        'regels' => ['type' => 'array', 'items' => $lineSchema, 'description' => __('De factuurregels.')],
                        'referentie' => ['type' => 'string', 'description' => __('Referentie (optioneel).')],
                        'opmerkingen' => ['type' => 'string', 'description' => __('Opmerking voor de klant onderaan de factuur (optioneel).')],
                        'betalingstermijn_dagen' => ['type' => 'integer', 'description' => __('Betalingstermijn in dagen (optioneel; standaard van de klant of administratie).')],
                        'handelsnaam' => ['type' => 'string', 'description' => __('Optioneel: de handelsnaam (huisstijl) waaronder de factuur wordt gemaakt, als de administratie meerdere handelsnamen heeft. Weglaten = standaard huisstijl.')],
                        'bijlage' => $this->attachmentSchema('factuur'),
                    ],
                    'required' => ['klant', 'regels'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'openstaande_facturen',
                'description' => __('Overzicht van openstaande (en vervallen) verkoopfacturen: wie moet er nog betalen en hoeveel.'),
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            ],
        ]]);
    }

    /** Schema voor de optionele bijlage bij offerte_aanmaken / factuur_aanmaken. */
    protected function attachmentSchema(string $doc): array
    {
        $brand = Brand::name();

        return [
            'type' => 'object',
            'description' => __('Optioneel: een bijlage die met de :doc wordt meegestuurd naar de klant. Geef ÓF "tekst" (bijv. het volledige offertedocument of een plan van aanpak in markdown — :brand maakt er een nette PDF van) ÓF "base64" met "bestandsnaam" voor een echt bestand (PDF/PNG/JPG/WEBP, max 10 MB).', ['doc' => __($doc), 'brand' => $brand]),
            'properties' => [
                'titel' => ['type' => 'string', 'description' => __('Titel van het document (wordt ook de bestandsnaam), bijv. "Plan van aanpak".')],
                'tekst' => ['type' => 'string', 'description' => __('De documenttekst in markdown of platte tekst — :brand zet dit om naar een verzorgde PDF.', ['brand' => $brand])],
                'bestandsnaam' => ['type' => 'string', 'description' => __('Bestandsnaam inclusief extensie (alleen samen met base64).')],
                'base64' => ['type' => 'string', 'description' => __('De base64-inhoud van het bestand (alleen voor echte bestanden; gebruik anders "tekst").')],
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * Maak van de meegegeven bijlage een Attachment bij het document.
     * Retourneert de bestandsnaam, of null als er geen bijlage was.
     */
    protected function attachDocument(object $model, Company $company, array $args, string $documentLabel): ?string
    {
        $bijlage = $args['bijlage'] ?? null;
        if (! is_array($bijlage)) {
            return null;
        }

        if (filled($bijlage['tekst'] ?? null)) {
            $title = mb_substr(trim((string) ($bijlage['titel'] ?? '')), 0, 120) ?: __('Bijlage');
            $html = \Illuminate\Support\Str::markdown((string) $bijlage['tekst'], [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
            // Huisstijl van de gekozen handelsnaam (of gewoon het bedrijf):
            // logo, merkkleur en lettertype — zelfde look als de offerte zelf.
            $branded = method_exists($model, 'brandedCompany')
                ? ($model->brandedCompany() ?? $company)
                : $company;
            $binary = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bijlage-tekst', [
                'title' => $title,
                'html' => $html,
                'company' => $branded,
                'documentLabel' => $documentLabel,
            ])->setPaper('a4')->output();
            $filename = (\Illuminate\Support\Str::slug($title) ?: 'bijlage') . '.pdf';
            $mime = 'application/pdf';
        } elseif (filled($bijlage['base64'] ?? null)) {
            $binary = base64_decode(preg_replace('/\s+/', '', (string) $bijlage['base64']), true);
            if ($binary === false || strlen($binary) === 0) {
                throw new \DomainException(__('De bijlage kon niet worden gelezen (ongeldige base64). Gebruik anders "tekst" — dan maakt :brand er zelf een PDF van.', ['brand' => Brand::name()]));
            }
            if (strlen($binary) > 10 * 1024 * 1024) {
                throw new \DomainException(__('De bijlage is groter dan 10 MB.'));
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'application/octet-stream';
            if (! in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true)) {
                throw new \DomainException(__('Alleen PDF-, PNG-, JPG- of WEBP-bijlagen zijn toegestaan. Gebruik anders "tekst" — dan maakt :brand er zelf een PDF van.', ['brand' => Brand::name()]));
            }
            $filename = mb_substr(trim((string) ($bijlage['bestandsnaam'] ?? 'bijlage.pdf')), 0, 255) ?: 'bijlage.pdf';
        } else {
            return null;
        }

        \App\Models\Attachment::create([
            // Expliciet: zonder ingelogde gebruiker vult het model dit niet zelf in.
            'company_id' => $company->id,
            'attachable_type' => get_class($model),
            'attachable_id' => $model->id,
            'filename' => $filename,
            'mime_type' => $mime,
            'size_bytes' => strlen($binary),
            'file_data' => base64_encode($binary),
            'for_customer' => true,
        ]);

        return $filename;
    }

    protected function toolsCall(mixed $id, array $params, Company $company): Response
    {
        // De Claude-koppeling hoort bij de AI-functies (Slim-abonnement).
        if (! $company->hasAiAccess()) {
            return $this->toolText($id, __('De Claude-koppeling zit in het Slim-abonnement van :brand. Upgrade via Instellingen → Abonnement.', ['brand' => Brand::name()]), true);
        }

        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            $text = match ($name) {
                'klanten_zoeken' => $this->searchCustomers($company, $args),
                'offerte_aanmaken' => $this->createQuote($company, $args),
                'factuur_aanmaken' => $this->createInvoice($company, $args),
                'openstaande_facturen' => $this->openInvoices($company),
                default => throw new \DomainException(__("Onbekende tool ':name'.", ['name' => $name])),
            };

            return $this->toolText($id, $text);
        } catch (\DomainException $e) {
            return $this->toolText($id, $e->getMessage(), true);
        } catch (\Throwable $e) {
            Log::error('MCP-tool mislukt', ['company' => $company->id, 'tool' => $name, 'error' => $e->getMessage()]);

            return $this->toolText($id, __('Er ging iets mis in :brand. Probeer het opnieuw of maak het document handmatig aan.', ['brand' => Brand::name()]), true);
        }
    }

    /* ===================== Tools ===================== */

    protected function searchCustomers(Company $company, array $args): string
    {
        $term = trim((string) ($args['zoekterm'] ?? ''));

        $customers = Customer::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->when($term !== '', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($term) . '%']))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'city', 'email']);

        if ($customers->isEmpty()) {
            return $term === ''
                ? __('Er staan nog geen klanten in deze administratie. Voeg eerst een klant toe in :brand (Verkoop → Klanten).', ['brand' => Brand::name()])
                : __('Geen klanten gevonden voor ":term". Controleer de spelling of voeg de klant eerst toe in :brand (Verkoop → Klanten).', ['term' => $term, 'brand' => Brand::name()]);
        }

        $lines = $customers->map(fn ($c) => '- ' . $c->name
            . ($c->city ? " ({$c->city})" : '')
            . ($c->email ? " · {$c->email}" : ''));

        return __('Gevonden klanten:') . "\n" . $lines->implode("\n");
    }

    /** Eenduidige handelsnaam-match binnen de eigen administratie (of null). */
    protected function resolveBrandProfile(Company $company, array $args): ?int
    {
        $name = trim((string) ($args['handelsnaam'] ?? ''));
        if ($name === '') {
            return null;
        }

        $profiles = \App\Models\BrandProfile::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->get(['id', 'name']);

        if ($profiles->isEmpty()) {
            throw new \DomainException(__('Deze administratie heeft geen handelsnamen ingericht — laat "handelsnaam" weg voor de standaard huisstijl.'));
        }

        $needle = mb_strtolower($name);
        $matches = $profiles->filter(fn ($p) => str_contains(mb_strtolower($p->name), $needle));
        if ($matches->count() === 1) {
            return $matches->first()->id;
        }

        throw new \DomainException(($matches->isEmpty()
            ? __('Handelsnaam ":name" bestaat niet.', ['name' => $name])
            : __('Meerdere handelsnamen matchen op ":name".', ['name' => $name]))
            . ' ' . __('Beschikbaar: :names.', ['names' => $profiles->pluck('name')->implode(', ')])
            . ' ' . __('Of laat "handelsnaam" weg voor de standaard huisstijl.'));
    }

    /** Eenduidige klantmatch binnen de eigen administratie — anders een nette fout. */
    protected function resolveCustomer(Company $company, string $name): Customer
    {
        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            throw new \DomainException(__('Geef de naam van de klant op.'));
        }

        $candidates = Customer::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->get(['id', 'name', 'city', 'email', 'payment_terms', 'company_id']);

        $exact = $candidates->filter(fn ($c) => mb_strtolower($c->name) === $needle);
        if ($exact->count() === 1) {
            return Customer::withoutGlobalScope('company')->findOrFail($exact->first()->id);
        }

        $matches = $candidates->filter(fn ($c) => str_contains(mb_strtolower($c->name), $needle));
        if ($matches->count() === 1) {
            return Customer::withoutGlobalScope('company')->findOrFail($matches->first()->id);
        }
        if ($matches->isEmpty()) {
            throw new \DomainException(__('Klant ":name" staat niet in de administratie. Gebruik klanten_zoeken om de juiste naam te vinden, of voeg de klant eerst toe in :brand.', ['name' => $name, 'brand' => Brand::name()]));
        }

        throw new \DomainException(__('Meerdere klanten matchen op ":name": :names. Geef de volledige naam op.', ['name' => $name, 'names' => $matches->pluck('name')->implode(', ')]));
    }

    /** Vertaal MCP-regels naar de regels die de managers verwachten (altijd excl. btw aangeleverd). */
    protected function mapLines(Company $company, array $rows): array
    {
        $incl = $company->price_mode === 'incl';
        $lines = [];

        foreach ($rows as $row) {
            $description = trim((string) ($row['omschrijving'] ?? ''));
            if ($description === '') {
                throw new \DomainException(__('Elke regel heeft een omschrijving nodig.'));
            }
            $rate = (float) ($row['btw_percentage'] ?? Market::defaultVatRate());
            if (! in_array($rate, array_map('floatval', Market::vatRates()), true)) {
                throw new \DomainException(__('Het btw-tarief moet :rates of :last zijn.', $this->rateList()));
            }
            $price = round((float) ($row['prijs_excl_btw'] ?? 0), 2);
            if ($price < 0) {
                throw new \DomainException(__('Prijzen kunnen niet negatief zijn; verwerk een korting via korting_pct.'));
            }
            $quantity = (float) ($row['aantal'] ?? 1);

            $lines[] = [
                'description' => mb_substr($description, 0, 500),
                'details' => filled($row['toelichting'] ?? null) ? mb_substr(trim($row['toelichting']), 0, 2000) : null,
                'quantity' => $quantity > 0 ? $quantity : 1.0,
                'unit' => filled($row['eenheid'] ?? null) ? mb_substr(trim($row['eenheid']), 0, 30) : __('stuk'),
                // De managers interpreteren prijzen volgens de invoerstand van
                // de administratie; de koppeling levert altijd excl. aan.
                'unit_price' => $incl ? round($price * (1 + $rate / 100), 2) : $price,
                'vat_rate' => $rate,
                'discount_pct' => min(100, max(0, round((float) ($row['korting_pct'] ?? 0), 2))),
            ];
        }

        if ($lines === []) {
            throw new \DomainException(__('Geef minstens één regel op.'));
        }

        return $lines;
    }

    protected function createQuote(Company $company, array $args): string
    {
        $customer = $this->resolveCustomer($company, (string) ($args['klant'] ?? ''));
        $lines = $this->mapLines($company, is_array($args['regels'] ?? null) ? $args['regels'] : []);

        $quote = $this->quotes->create([
            'customer_id' => $customer->id,
            'brand_profile_id' => $this->resolveBrandProfile($company, $args),
            'valid_days' => isset($args['geldig_dagen']) ? max(1, min(365, (int) $args['geldig_dagen'])) : null,
            'reference' => filled($args['referentie'] ?? null) ? mb_substr(trim($args['referentie']), 0, 255) : null,
            'intro' => filled($args['intro'] ?? null) ? mb_substr(trim($args['intro']), 0, 2000) : null,
            'notes' => filled($args['opmerkingen'] ?? null) ? trim($args['opmerkingen']) : null,
            'lines' => $lines,
        ]);

        $attached = $this->attachDocument($quote, $company, $args, __('offerte :number', ['number' => $quote->number ?: __('(concept)')]));

        $eur = fn ($v) => money($v);
        $brand = $quote->brandProfile?->name;

        return __('Concept-offerte aangemaakt voor :customer', ['customer' => $customer->name]) . ($brand ? ' ' . __('onder handelsnaam :brand', ['brand' => $brand]) : '') . ".\n"
            . __('Subtotaal :subtotal · BTW :vat · Totaal :total', ['subtotal' => $eur($quote->subtotal), 'vat' => $eur($quote->vat_total), 'total' => $eur($quote->total)]) . "\n"
            . __('Geldig tot :date.', ['date' => $quote->valid_until->translatedFormat('j F Y')]) . "\n"
            . ($attached ? __('Bijlage ":filename" toegevoegd — gaat mee met de offertemail naar de klant.', ['filename' => $attached]) . "\n" : '')
            . __('Controleren en versturen: :url', ['url' => route('quotes.show', $quote)]);
    }

    protected function createInvoice(Company $company, array $args): string
    {
        $customer = $this->resolveCustomer($company, (string) ($args['klant'] ?? ''));
        $lines = $this->mapLines($company, is_array($args['regels'] ?? null) ? $args['regels'] : []);

        $data = [
            'customer_id' => $customer->id,
            'brand_profile_id' => $this->resolveBrandProfile($company, $args),
            'reference' => filled($args['referentie'] ?? null) ? mb_substr(trim($args['referentie']), 0, 255) : null,
            'notes' => filled($args['opmerkingen'] ?? null) ? trim($args['opmerkingen']) : null,
            'lines' => $lines,
        ];
        if (isset($args['betalingstermijn_dagen'])) {
            $data['payment_terms'] = max(0, min(365, (int) $args['betalingstermijn_dagen']));
        }

        $invoice = $this->invoices->create($data);

        $attached = $this->attachDocument($invoice, $company, $args, __('factuur (concept)'));

        $eur = fn ($v) => money($v);
        $brand = $invoice->brandProfile?->name;

        return __('Concept-factuur aangemaakt voor :customer', ['customer' => $customer->name]) . ($brand ? ' ' . __('onder handelsnaam :brand', ['brand' => $brand]) : '') . ".\n"
            . __('Subtotaal :subtotal · BTW :vat · Totaal :total', ['subtotal' => $eur($invoice->subtotal), 'vat' => $eur($invoice->vat_total), 'total' => $eur($invoice->total)]) . "\n"
            . __('Het factuurnummer wordt toegekend bij het versturen.') . "\n"
            . ($attached ? __('Bijlage ":filename" toegevoegd — gaat mee met de factuurmail naar de klant.', ['filename' => $attached]) . "\n" : '')
            . __('Controleren en versturen: :url', ['url' => route('invoices.show', $invoice)]);
    }

    protected function openInvoices(Company $company): string
    {
        $open = Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('is_credit', false)
            ->whereIn('status', ['sent', 'partial', 'overdue', 'incasso'])
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        if ($open->isEmpty()) {
            return __('Er staan geen facturen open — alles is betaald.');
        }

        $eur = fn ($v) => money($v);
        $totalOpen = $open->sum(fn ($i) => $i->remaining_amount);

        $dateFormat = (string) Market::get('date_format', 'd-m-Y');
        $lines = $open->map(function ($i) use ($eur, $dateFormat) {
            $late = $i->days_overdue > 0 ? ' · ' . __(':days dagen te laat', ['days' => $i->days_overdue]) : '';

            return '- ' . __(':number · :customer · open :amount · vervaldatum :date', [
                'number' => $i->number, 'customer' => $i->customer_name,
                'amount' => $eur($i->remaining_amount), 'date' => $i->due_date?->format($dateFormat) ?? '-',
            ]) . $late;
        });

        return __('Openstaande facturen (:count stuks, samen :total open):', ['count' => $open->count(), 'total' => $eur($totalOpen)]) . "\n"
            . $lines->implode("\n");
    }

    /* ===================== JSON-RPC-helpers ===================== */

    protected function toolText(mixed $id, string $text, bool $isError = false): Response
    {
        return $this->rpcResult($id, [
            'content' => [['type' => 'text', 'text' => $text]],
            'isError' => $isError,
        ]);
    }

    protected function rpcResult(mixed $id, mixed $result): Response
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
    }

    protected function rpcError(mixed $id, int $code, string $message): Response
    {
        return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]]);
    }
}
