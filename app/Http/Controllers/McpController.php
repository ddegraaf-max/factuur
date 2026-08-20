<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceManager;
use App\Services\QuoteManager;
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
            return $this->rpcError(null, -32000, 'Onbekende of ingetrokken koppeling. Maak in EasyInvoice (Instellingen → Koppelingen) een nieuwe koppel-URL aan.');
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
            default => $this->rpcError($id, -32601, "Methode '{$method}' wordt niet ondersteund."),
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
                'name' => 'EasyInvoice',
                'version' => (string) config('app.version', '1.0'),
            ],
            'instructions' => 'EasyInvoice is de facturatie-administratie van de gebruiker. '
                . 'Gebruik klanten_zoeken om de juiste klant te vinden voordat je een offerte of factuur aanmaakt. '
                . 'Alles wat je aanmaakt is een concept: de gebruiker controleert en verstuurt het zelf in EasyInvoice. '
                . 'Prijzen geef je altijd exclusief btw op, met het btw-tarief (21, 9 of 0) per regel. '
                . 'Heb je een uitgebreid offertedocument of plan van aanpak geschreven? Stuur het mee als bijlage '
                . '(veld "bijlage" met "tekst" in markdown) — EasyInvoice maakt er een nette PDF van die met de mail naar de klant meegaat.',
        ]);
    }

    protected function toolsList(mixed $id): Response
    {
        $lineSchema = [
            'type' => 'object',
            'properties' => [
                'omschrijving' => ['type' => 'string', 'description' => 'Korte omschrijving van de regel.'],
                'toelichting' => ['type' => 'string', 'description' => 'Optionele toelichting onder de regel.'],
                'aantal' => ['type' => 'number', 'description' => 'Aantal (standaard 1).'],
                'eenheid' => ['type' => 'string', 'description' => 'Eenheid, bijv. stuk, uur, dag, maand (standaard "stuk").'],
                'prijs_excl_btw' => ['type' => 'number', 'description' => 'Stuksprijs EXCLUSIEF btw.'],
                'btw_percentage' => ['type' => 'number', 'enum' => [21, 9, 0], 'description' => 'BTW-tarief in procenten.'],
                'korting_pct' => ['type' => 'number', 'description' => 'Optionele korting op deze regel in procenten (0-100).'],
            ],
            'required' => ['omschrijving', 'aantal', 'prijs_excl_btw', 'btw_percentage'],
            'additionalProperties' => false,
        ];

        return $this->rpcResult($id, ['tools' => [
            [
                'name' => 'klanten_zoeken',
                'description' => 'Zoek klanten in de administratie op naam. Gebruik dit om de juiste klant te vinden (en de exacte naam te kennen) voordat je een offerte of factuur aanmaakt. Zonder zoekterm krijg je de eerste 25 klanten.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'zoekterm' => ['type' => 'string', 'description' => 'Deel van de klantnaam (optioneel).'],
                    ],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'offerte_aanmaken',
                'description' => 'Maak een CONCEPT-offerte aan voor een bestaande klant. De gebruiker controleert en verstuurt de offerte zelf in EasyInvoice. Prijzen exclusief btw.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'klant' => ['type' => 'string', 'description' => 'Naam van een bestaande klant (moet eenduidig matchen — gebruik eventueel eerst klanten_zoeken).'],
                        'regels' => ['type' => 'array', 'items' => $lineSchema, 'description' => 'De offerteregels.'],
                        'referentie' => ['type' => 'string', 'description' => 'Referentie of projectnaam (optioneel).'],
                        'intro' => ['type' => 'string', 'description' => 'Inleidende tekst boven de offerte (optioneel).'],
                        'opmerkingen' => ['type' => 'string', 'description' => 'Voorwaarden, planning of aannames onder de offerte (optioneel).'],
                        'geldig_dagen' => ['type' => 'integer', 'description' => 'Geldigheid in dagen (optioneel; standaard van de administratie).'],
                        'bijlage' => $this->attachmentSchema('offerte'),
                    ],
                    'required' => ['klant', 'regels'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'factuur_aanmaken',
                'description' => 'Maak een CONCEPT-factuur aan voor een bestaande klant. De gebruiker controleert en verstuurt de factuur zelf in EasyInvoice (het factuurnummer wordt pas bij versturen toegekend). Prijzen exclusief btw.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'klant' => ['type' => 'string', 'description' => 'Naam van een bestaande klant (moet eenduidig matchen).'],
                        'regels' => ['type' => 'array', 'items' => $lineSchema, 'description' => 'De factuurregels.'],
                        'referentie' => ['type' => 'string', 'description' => 'Referentie (optioneel).'],
                        'opmerkingen' => ['type' => 'string', 'description' => 'Opmerking voor de klant onderaan de factuur (optioneel).'],
                        'betalingstermijn_dagen' => ['type' => 'integer', 'description' => 'Betalingstermijn in dagen (optioneel; standaard van de klant of administratie).'],
                        'bijlage' => $this->attachmentSchema('factuur'),
                    ],
                    'required' => ['klant', 'regels'],
                    'additionalProperties' => false,
                ],
            ],
            [
                'name' => 'openstaande_facturen',
                'description' => 'Overzicht van openstaande (en vervallen) verkoopfacturen: wie moet er nog betalen en hoeveel.',
                'inputSchema' => ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            ],
        ]]);
    }

    /** Schema voor de optionele bijlage bij offerte_aanmaken / factuur_aanmaken. */
    protected function attachmentSchema(string $doc): array
    {
        return [
            'type' => 'object',
            'description' => "Optioneel: een bijlage die met de {$doc} wordt meegestuurd naar de klant. Geef ÓF \"tekst\" (bijv. het volledige offertedocument of een plan van aanpak in markdown — EasyInvoice maakt er een nette PDF van) ÓF \"base64\" met \"bestandsnaam\" voor een echt bestand (PDF/PNG/JPG/WEBP, max 10 MB).",
            'properties' => [
                'titel' => ['type' => 'string', 'description' => 'Titel van het document (wordt ook de bestandsnaam), bijv. "Plan van aanpak".'],
                'tekst' => ['type' => 'string', 'description' => 'De documenttekst in markdown of platte tekst — EasyInvoice zet dit om naar een verzorgde PDF.'],
                'bestandsnaam' => ['type' => 'string', 'description' => 'Bestandsnaam inclusief extensie (alleen samen met base64).'],
                'base64' => ['type' => 'string', 'description' => 'De base64-inhoud van het bestand (alleen voor echte bestanden; gebruik anders "tekst").'],
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
            $title = mb_substr(trim((string) ($bijlage['titel'] ?? '')), 0, 120) ?: 'Bijlage';
            $html = \Illuminate\Support\Str::markdown((string) $bijlage['tekst'], [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
            $binary = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bijlage-tekst', [
                'title' => $title,
                'html' => $html,
                'company' => $company,
                'documentLabel' => $documentLabel,
            ])->setPaper('a4')->output();
            $filename = (\Illuminate\Support\Str::slug($title) ?: 'bijlage') . '.pdf';
            $mime = 'application/pdf';
        } elseif (filled($bijlage['base64'] ?? null)) {
            $binary = base64_decode(preg_replace('/\s+/', '', (string) $bijlage['base64']), true);
            if ($binary === false || strlen($binary) === 0) {
                throw new \DomainException('De bijlage kon niet worden gelezen (ongeldige base64). Gebruik anders "tekst" — dan maakt EasyInvoice er zelf een PDF van.');
            }
            if (strlen($binary) > 10 * 1024 * 1024) {
                throw new \DomainException('De bijlage is groter dan 10 MB.');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary) ?: 'application/octet-stream';
            if (! in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true)) {
                throw new \DomainException('Alleen PDF-, PNG-, JPG- of WEBP-bijlagen zijn toegestaan. Gebruik anders "tekst" — dan maakt EasyInvoice er zelf een PDF van.');
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
            return $this->toolText($id, 'De Claude-koppeling zit in het Slim-abonnement van EasyInvoice. Upgrade via Instellingen → Abonnement.', true);
        }

        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        try {
            $text = match ($name) {
                'klanten_zoeken' => $this->searchCustomers($company, $args),
                'offerte_aanmaken' => $this->createQuote($company, $args),
                'factuur_aanmaken' => $this->createInvoice($company, $args),
                'openstaande_facturen' => $this->openInvoices($company),
                default => throw new \DomainException("Onbekende tool '{$name}'."),
            };

            return $this->toolText($id, $text);
        } catch (\DomainException $e) {
            return $this->toolText($id, $e->getMessage(), true);
        } catch (\Throwable $e) {
            Log::error('MCP-tool mislukt', ['company' => $company->id, 'tool' => $name, 'error' => $e->getMessage()]);

            return $this->toolText($id, 'Er ging iets mis in EasyInvoice. Probeer het opnieuw of maak het document handmatig aan.', true);
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
                ? 'Er staan nog geen klanten in deze administratie. Voeg eerst een klant toe in EasyInvoice (Verkoop → Klanten).'
                : "Geen klanten gevonden voor \"{$term}\". Controleer de spelling of voeg de klant eerst toe in EasyInvoice (Verkoop → Klanten).";
        }

        $lines = $customers->map(fn ($c) => '- ' . $c->name
            . ($c->city ? " ({$c->city})" : '')
            . ($c->email ? " · {$c->email}" : ''));

        return "Gevonden klanten:\n" . $lines->implode("\n");
    }

    /** Eenduidige klantmatch binnen de eigen administratie — anders een nette fout. */
    protected function resolveCustomer(Company $company, string $name): Customer
    {
        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            throw new \DomainException('Geef de naam van de klant op.');
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
            throw new \DomainException("Klant \"{$name}\" staat niet in de administratie. Gebruik klanten_zoeken om de juiste naam te vinden, of voeg de klant eerst toe in EasyInvoice.");
        }

        throw new \DomainException("Meerdere klanten matchen op \"{$name}\": "
            . $matches->pluck('name')->implode(', ')
            . '. Geef de volledige naam op.');
    }

    /** Vertaal MCP-regels naar de regels die de managers verwachten (altijd excl. btw aangeleverd). */
    protected function mapLines(Company $company, array $rows): array
    {
        $incl = $company->price_mode === 'incl';
        $lines = [];

        foreach ($rows as $row) {
            $description = trim((string) ($row['omschrijving'] ?? ''));
            if ($description === '') {
                throw new \DomainException('Elke regel heeft een omschrijving nodig.');
            }
            $rate = (float) ($row['btw_percentage'] ?? 21);
            if (! in_array($rate, [0.0, 9.0, 21.0], true)) {
                throw new \DomainException('Het btw-tarief moet 21, 9 of 0 zijn.');
            }
            $price = round((float) ($row['prijs_excl_btw'] ?? 0), 2);
            if ($price < 0) {
                throw new \DomainException('Prijzen kunnen niet negatief zijn; verwerk een korting via korting_pct.');
            }
            $quantity = (float) ($row['aantal'] ?? 1);

            $lines[] = [
                'description' => mb_substr($description, 0, 500),
                'details' => filled($row['toelichting'] ?? null) ? mb_substr(trim($row['toelichting']), 0, 2000) : null,
                'quantity' => $quantity > 0 ? $quantity : 1.0,
                'unit' => filled($row['eenheid'] ?? null) ? mb_substr(trim($row['eenheid']), 0, 30) : 'stuk',
                // De managers interpreteren prijzen volgens de invoerstand van
                // de administratie; de koppeling levert altijd excl. aan.
                'unit_price' => $incl ? round($price * (1 + $rate / 100), 2) : $price,
                'vat_rate' => $rate,
                'discount_pct' => min(100, max(0, round((float) ($row['korting_pct'] ?? 0), 2))),
            ];
        }

        if ($lines === []) {
            throw new \DomainException('Geef minstens één regel op.');
        }

        return $lines;
    }

    protected function createQuote(Company $company, array $args): string
    {
        $customer = $this->resolveCustomer($company, (string) ($args['klant'] ?? ''));
        $lines = $this->mapLines($company, is_array($args['regels'] ?? null) ? $args['regels'] : []);

        $quote = $this->quotes->create([
            'customer_id' => $customer->id,
            'valid_days' => isset($args['geldig_dagen']) ? max(1, min(365, (int) $args['geldig_dagen'])) : null,
            'reference' => filled($args['referentie'] ?? null) ? mb_substr(trim($args['referentie']), 0, 255) : null,
            'intro' => filled($args['intro'] ?? null) ? mb_substr(trim($args['intro']), 0, 2000) : null,
            'notes' => filled($args['opmerkingen'] ?? null) ? trim($args['opmerkingen']) : null,
            'lines' => $lines,
        ]);

        $attached = $this->attachDocument($quote, $company, $args, 'offerte ' . ($quote->number ?: '(concept)'));

        $eur = fn ($v) => '€ ' . number_format((float) $v, 2, ',', '.');

        return "Concept-offerte aangemaakt voor {$customer->name}.\n"
            . 'Subtotaal ' . $eur($quote->subtotal) . ' · BTW ' . $eur($quote->vat_total) . ' · Totaal ' . $eur($quote->total) . "\n"
            . 'Geldig tot ' . $quote->valid_until->translatedFormat('j F Y') . ".\n"
            . ($attached ? "Bijlage \"{$attached}\" toegevoegd — gaat mee met de offertemail naar de klant.\n" : '')
            . 'Controleren en versturen: ' . route('quotes.show', $quote);
    }

    protected function createInvoice(Company $company, array $args): string
    {
        $customer = $this->resolveCustomer($company, (string) ($args['klant'] ?? ''));
        $lines = $this->mapLines($company, is_array($args['regels'] ?? null) ? $args['regels'] : []);

        $data = [
            'customer_id' => $customer->id,
            'reference' => filled($args['referentie'] ?? null) ? mb_substr(trim($args['referentie']), 0, 255) : null,
            'notes' => filled($args['opmerkingen'] ?? null) ? trim($args['opmerkingen']) : null,
            'lines' => $lines,
        ];
        if (isset($args['betalingstermijn_dagen'])) {
            $data['payment_terms'] = max(0, min(365, (int) $args['betalingstermijn_dagen']));
        }

        $invoice = $this->invoices->create($data);

        $attached = $this->attachDocument($invoice, $company, $args, 'factuur (concept)');

        $eur = fn ($v) => '€ ' . number_format((float) $v, 2, ',', '.');

        return "Concept-factuur aangemaakt voor {$customer->name}.\n"
            . 'Subtotaal ' . $eur($invoice->subtotal) . ' · BTW ' . $eur($invoice->vat_total) . ' · Totaal ' . $eur($invoice->total) . "\n"
            . "Het factuurnummer wordt toegekend bij het versturen.\n"
            . ($attached ? "Bijlage \"{$attached}\" toegevoegd — gaat mee met de factuurmail naar de klant.\n" : '')
            . 'Controleren en versturen: ' . route('invoices.show', $invoice);
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
            return 'Er staan geen facturen open — alles is betaald.';
        }

        $eur = fn ($v) => '€ ' . number_format((float) $v, 2, ',', '.');
        $totalOpen = $open->sum(fn ($i) => $i->remaining_amount);

        $lines = $open->map(function ($i) use ($eur) {
            $late = $i->days_overdue > 0 ? " · {$i->days_overdue} dagen te laat" : '';

            return "- {$i->number} · {$i->customer_name} · open " . $eur($i->remaining_amount)
                . ' · vervaldatum ' . ($i->due_date?->format('d-m-Y') ?? '-') . $late;
        });

        return "Openstaande facturen ({$open->count()} stuks, samen " . $eur($totalOpen) . " open):\n"
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
