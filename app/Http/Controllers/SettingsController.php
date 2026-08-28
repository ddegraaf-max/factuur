<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    // ----- KOPPELINGEN (Claude / MCP) -----
    public function integrations()
    {
        $company = auth()->user()->company;

        $peppol = app(\App\Services\PeppolService::class);

        return Inertia::render('Settings/Koppelingen', [
            'mcp' => [
                'active' => $company->mcp_token !== null,
                'url' => $company->mcpUrl(),
            ],
            'has_ai' => $company->hasAiAccess(),
            // Peppol (Recommand): configured = beheerder heeft de teamkey gezet;
            // status = none | pending | verified | rejected | error.
            'peppol' => [
                'configured' => $peppol->configured(),
                'status' => $company->peppol_company_id ? ($company->peppol_verification_status ?: 'pending') : 'none',
                'verification_url' => $company->peppol_verification_url,
                'participant_id' => strlen(preg_replace('/\D/', '', (string) $company->kvk_number)) === 8 ? '0106:' . preg_replace('/\D/', '', $company->kvk_number) : null,
                'registered_at_label' => $company->peppol_registered_at?->translatedFormat('j M Y'),
                'verified_at_label' => $company->peppol_verified_at?->translatedFormat('j M Y'),
                'blockers' => $peppol->registrationBlockers($company),
            ],
            // Mail vanaf eigen domein (Resend Domains): status none | pending | verified | failed.
            'mail_domain' => [
                'configured' => app(\App\Services\MailDomainService::class)->configured(),
                'status' => $company->mail_domain_id ? ($company->mail_domain_status ?: 'pending') : 'none',
                'domain' => $company->mail_domain,
                'from_address' => $company->mail_from_address,
                'records' => $company->mail_domain_records ?: [],
                'checked_at_label' => $company->mail_domain_checked_at?->translatedFormat('j M Y, H:i'),
                'default_from' => config('mail.from.address'),
                'suggested_domain' => filled($company->email) && ! preg_match('/@(gmail|hotmail|outlook|live|icloud|yahoo|ziggo|kpnmail)\./i', $company->email) ? substr(strrchr($company->email, '@'), 1) : '',
                'suggested_local_part' => filled($company->email) ? strstr($company->email, '@', true) : 'facturen',
            ],
        ]);
    }

    /** (Her)activeer de Claude-koppeling — een oude koppel-URL vervalt meteen. */
    public function rotateMcpToken()
    {
        $company = auth()->user()->company;

        if (! $company->hasAiAccess()) {
            return back()->with('error', 'De Claude-koppeling zit in het Slim-abonnement.');
        }

        $wasActive = $company->mcp_token !== null;
        $company->rotateMcpToken();

        return back()->with('flash', $wasActive
            ? 'Nieuwe koppel-URL aangemaakt — de oude werkt niet meer. Werk de connector in Claude bij.'
            : 'Claude-koppeling geactiveerd! Voeg de koppel-URL toe in Claude als custom connector.');
    }

    public function disableMcpToken()
    {
        auth()->user()->company->disableMcpToken();

        return back()->with('flash', 'Claude-koppeling uitgeschakeld — de koppel-URL is ingetrokken.');
    }

    // ----- COMPANY / BEDRIJFSGEGEVENS -----
    public function company()
    {
        return Inertia::render('Settings/Company', [
            'company' => auth()->user()->company,
            // De key zelf blijft geheim (hidden); de interface hoeft alleen te
            // weten óf er een Mollie-koppeling is.
            'mollie_connected' => filled(auth()->user()->company->mollie_api_key),
        ]);
    }

    public function updateCompany(Request $request)
    {
        $company = auth()->user()->company;

        // Normaliseer het BTW-nummer naar hoofdletters voor de unieke-check/opslag.
        if (filled($request->input('vat_number'))) {
            $request->merge(['vat_number' => strtoupper(trim($request->input('vat_number')))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'kvk_number' => ['nullable', 'string', 'max:20', 'unique:companies,kvk_number,' . $company->id],
            'vat_number' => ['nullable', 'string', 'max:20', 'unique:companies,vat_number,' . $company->id],
            'iban' => ['nullable', 'string', 'max:34'],
            'sepa_creditor_id' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z]{2}\d{2}[A-Za-z0-9]{3}\d{6,}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            // New preference fields — all optional so older forms keep working
            'price_mode' => ['nullable', 'in:excl,incl'],
            'fiscal_year_start' => ['nullable', 'integer', 'min:1', 'max:12'],
            'results_per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'copy_email' => ['nullable', 'email'],
            'accountant_email' => ['nullable', 'email'],
            'daily_notification_enabled' => ['nullable', 'boolean'],
            'daily_notification_email' => ['nullable', 'email'],
            'default_payment_terms' => ['required', 'integer', 'min:0', 'max:365'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'default_km_rate' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'mollie_api_key' => ['nullable', 'string', 'max:100', 'regex:/^(test|live)_\w+$/'],
            'mollie_disconnect' => ['nullable', 'boolean'],
            // Legacy invoice fields still accepted from older Company form
            'invoice_footer' => ['nullable', 'string'],
            'invoice_number_format' => ['nullable', 'string', 'max:50'],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'kvk_number.unique' => 'Er bestaat al een account met dit KvK-nummer.',
            'vat_number.unique' => 'Er bestaat al een account met dit BTW-nummer.',
        ]);

        // Drop nulls so we don't overwrite existing values with null
        $data = array_filter($data, fn ($v) => $v !== null);

        // Dit veld moet je juist wél kunnen leegmaken (dan valt de dagmail terug
        // op het bedrijfs-e-mailadres); een leeg veld komt als null binnen.
        if ($request->has('daily_notification_email')) {
            $data['daily_notification_email'] = $request->input('daily_notification_email') ?: null;
        }

        // Mollie: een leeg veld betekent "niet wijzigen" — de bestaande key
        // blijft dan staan. Loskoppelen gaat expliciet via mollie_disconnect.
        if (! empty($data['mollie_disconnect'])) {
            $data['mollie_api_key'] = null;
        } elseif (empty($data['mollie_api_key'])) {
            unset($data['mollie_api_key']);
        }
        unset($data['mollie_disconnect']);

        $company->update($data);
        return back()->with('flash', 'Bedrijfsgegevens opgeslagen.');
    }

    // ----- NUMBERING -----
    public function numbering()
    {
        $company = auth()->user()->company;
        return Inertia::render('Settings/Numbering', [
            'numbering' => $company->resolved_numbering,
        ]);
    }

    public function updateNumbering(Request $request)
    {
        $data = $request->validate([
            'numbering' => 'required|array',
            'numbering.*.prefix' => 'nullable|string|max:10',
            'numbering.*.start' => 'required|integer|min:1',
        ]);
        auth()->user()->company->update(['numbering_settings' => $data['numbering']]);
        return back()->with('flash', 'Nummering opgeslagen.');
    }

    // ----- BRAND / HUISSTIJL -----
    public function brand()
    {
        $company = auth()->user()->company;
        $scanner = app(\App\Services\BrandScanService::class);

        return Inertia::render('Settings/Brand', [
            // logo_data/stationery_data staan standaard op hidden (te zwaar voor
            // elke response); hier zijn ze juist nodig voor de voorvertoning.
            'company' => $company->makeVisible(['logo_data', 'stationery_data']),
            'ai_enabled' => $scanner->availableFor($company),
            'ai_locked' => $scanner->enabled() && ! $company->hasAiAccess(),
        ]);
    }

    public function updateBrand(Request $request)
    {
        $company = auth()->user()->company;

        $data = $request->validate([
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'invoice_template' => ['required', 'in:modern,classic,minimal,stationery'],
            'invoice_font' => ['required', 'in:sans,serif'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'logo_scale' => ['nullable', 'integer', 'min:50', 'max:200'],
            'stationery_margin_top' => ['nullable', 'integer', 'min:10', 'max:150'],
            'stationery_margin_bottom' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        // Optional logo upload — store as base64 data URL in DB (survives Railway redeploys)
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:png,jpg,jpeg,svg,webp|max:2048']);
            $file = $request->file('logo');
            $mime = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $data['logo_data'] = 'data:' . $mime . ';base64,' . $base64;
            $data['logo_path'] = null; // clear old path-based logo
        }

        // Eigen briefpapier (A4-afbeelding als ondergrond voor het
        // "stationery"-template). PNG/JPG — een PDF-briefpapier moet eerst
        // als afbeelding worden geëxporteerd (ontwerp-tools kunnen dat altijd).
        if ($request->hasFile('stationery')) {
            $request->validate([
                'stationery' => 'image|mimes:png,jpg,jpeg,webp|max:4096',
            ], [
                'stationery.mimes' => 'Upload het briefpapier als PNG of JPG (exporteer een PDF eerst als afbeelding).',
                'stationery.max' => 'Het briefpapier mag maximaal 4 MB groot zijn — het gaat met elke factuur-PDF mee.',
            ]);
            $file = $request->file('stationery');
            $data['stationery_data'] = 'data:' . $file->getMimeType() . ';base64,'
                . base64_encode(file_get_contents($file->getRealPath()));
        }

        // Briefpapier-template zonder briefpapier heeft geen zin.
        if (($data['invoice_template'] ?? null) === 'stationery'
            && empty($data['stationery_data'])
            && ! $company->stationery_data) {
            return back()->withErrors(['stationery' => 'Upload eerst je briefpapier voordat je dit sjabloon kiest.']);
        }

        $company->update($data);
        return back()->with('flash', 'Huisstijl opgeslagen.');
    }

    /** Briefpapier verwijderen; het template valt automatisch terug op "modern". */
    public function removeStationery()
    {
        $company = auth()->user()->company;
        $changes = ['stationery_data' => null];
        if ($company->invoice_template === 'stationery') {
            $changes['invoice_template'] = 'modern';
        }
        $company->update($changes);

        return back()->with('flash', 'Briefpapier verwijderd.');
    }

    /**
     * Huisstijl herkennen met AI: upload een huisstijlgids, briefpapier of
     * oude factuur en krijg kleuren, lettertype en template als voorstel
     * terug. Er wordt hier niets opgeslagen — de gebruiker bevestigt zelf.
     */
    public function scanBrand(Request $request, \App\Services\BrandScanService $scanner)
    {
        abort_unless($scanner->enabled(), 404);

        $company = auth()->user()->company;
        if (! $company->hasAiAccess()) {
            return response()->json(['message' => 'Huisstijl herkennen zit in het Slim-abonnement. Upgrade via Instellingen → Abonnement.'], 403);
        }
        if ($company->aiLimitReached()) {
            return response()->json(['message' => 'Het maandelijkse AI-tegoed is opgebruikt (fair use). Volgende maand staat de teller weer op nul.'], 429);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
        ], [
            'file.required' => 'Kies eerst een bestand (PDF of afbeelding).',
            'file.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden kunnen worden gelezen.',
            'file.max' => 'Het bestand mag maximaal 10 MB groot zijn.',
        ]);

        $file = $request->file('file');

        try {
            $result = $scanner->scan(
                file_get_contents($file->getRealPath()),
                $file->getMimeType() ?? 'application/octet-stream'
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        \App\Models\AiUsageEvent::record($company->id, 'brand_scan', 'form');

        return response()->json(['result' => $result]);
    }

    public function removeLogo()
    {
        $company = auth()->user()->company;
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }
        $company->update(['logo_path' => null, 'logo_data' => null]);
        return back()->with('flash', 'Logo verwijderd.');
    }

    // ----- E-MAILTEKSTEN (factuur- en offertemail) -----
    public function emails()
    {
        $company = auth()->user()->company;
        $texts = $company->email_texts ?? [];

        return Inertia::render('Settings/EmailTexts', [
            'texts' => [
                'invoice_subject' => $texts['invoice_subject'] ?? '',
                'invoice_body' => $texts['invoice_body'] ?? '',
                'quote_subject' => $texts['quote_subject'] ?? '',
                'quote_body' => $texts['quote_body'] ?? '',
                'thanks_subject' => $texts['thanks_subject'] ?? '',
                'thanks_body' => $texts['thanks_body'] ?? '',
                'accept_subject' => $texts['accept_subject'] ?? '',
                'accept_body' => $texts['accept_body'] ?? '',
            ],
            'thanks_enabled' => (bool) $company->thanks_mail_enabled,
            'review_url' => $company->review_url ?? '',
            'accept_enabled' => (bool) $company->quote_accept_mail_enabled,
            // De standaardteksten (NL) als voorbeeld/placeholder in het formulier.
            'defaults' => [
                'invoice_subject' => 'Factuur {factuurnummer} — {bedrijf}',
                'invoice_body' => "Beste {klant},\n\nHierbij ontvangt u factuur {factuurnummer} van {factuurdatum} voor een bedrag van {bedrag}. De factuur vindt u als PDF in de bijlage.\n\nWij verzoeken u het bedrag uiterlijk {vervaldatum} te voldoen op {iban} onder vermelding van factuurnummer {factuurnummer}.",
                'quote_subject' => 'Offerte {offertenummer} — {bedrijf}',
                'quote_body' => 'Hierbij ontvang je onze offerte. In de bijlage vind je het volledige overzicht als PDF.',
                'thanks_subject' => 'Bedankt voor uw betaling — factuur {factuurnummer}',
                'thanks_body' => "Beste {klant},\n\nWij hebben uw betaling voor factuur {factuurnummer} in goede orde ontvangen. Hartelijk dank voor de prettige samenwerking.",
                'accept_subject' => 'Bevestiging van uw akkoord — offerte {offertenummer}',
                'accept_body' => "Beste {ondertekenaar},\n\nU heeft offerte {offertenummer} van {bedrijf} op {akkoorddatum} geaccepteerd. Hierbij onze bevestiging.\n\nWij nemen binnenkort contact met u op over de planning en de verdere afspraken. Heeft u in de tussentijd vragen? Beantwoord dan gewoon deze e-mail.",
            ],
        ]);
    }

    public function updateEmails(Request $request)
    {
        $data = $request->validate([
            'invoice_subject' => 'nullable|string|max:200',
            'invoice_body' => 'nullable|string|max:4000',
            'quote_subject' => 'nullable|string|max:200',
            'quote_body' => 'nullable|string|max:4000',
            'thanks_subject' => 'nullable|string|max:200',
            'thanks_body' => 'nullable|string|max:4000',
            'thanks_enabled' => 'nullable|boolean',
            'review_url' => 'nullable|string|max:500',
            'accept_subject' => 'nullable|string|max:200',
            'accept_body' => 'nullable|string|max:4000',
            'accept_enabled' => 'nullable|boolean',
        ]);

        // Reviewlink: "g.page/r/…" zonder schema is ook goed — wij zetten https:// ervoor.
        $reviewUrl = self::normalizeUrl($data['review_url'] ?? null);
        if ($reviewUrl !== null && ! filter_var($reviewUrl, FILTER_VALIDATE_URL)) {
            return back()->withErrors(['review_url' => 'Vul een geldige link in, bijvoorbeeld https://g.page/r/… of je Trustpilot-pagina.']);
        }

        // Alleen ingevulde teksten bewaren; leeg = terug naar de standaard.
        $textKeys = ['invoice_subject', 'invoice_body', 'quote_subject', 'quote_body', 'thanks_subject', 'thanks_body', 'accept_subject', 'accept_body'];
        $texts = array_filter(
            array_map(fn ($v) => trim((string) $v), array_intersect_key($data, array_flip($textKeys))),
            fn ($v) => $v !== ''
        );

        auth()->user()->company->update([
            'email_texts' => $texts ?: null,
            'thanks_mail_enabled' => $request->boolean('thanks_enabled'),
            'review_url' => $reviewUrl,
            'quote_accept_mail_enabled' => $request->boolean('accept_enabled'),
        ]);

        return back()->with('flash', 'E-mailteksten opgeslagen.');
    }

    /**
     * Voorbeeld van de bedankmail in de browser — met de (nog niet opgeslagen)
     * tekst uit het formulier en verzonnen factuurgegevens. Er wordt niets
     * opgeslagen of verstuurd.
     */
    public function previewThanks(Request $request)
    {
        // Kopie in het geheugen: de formulierwaarden eroverheen, zonder op te slaan.
        $company = auth()->user()->company->replicate();
        $texts = $company->email_texts ?? [];
        $texts['thanks_subject'] = trim((string) $request->input('thanks_subject', ''));
        $texts['thanks_body'] = trim((string) $request->input('thanks_body', ''));
        $company->email_texts = $texts;
        if ($request->has('review_url')) {
            $company->review_url = self::normalizeUrl($request->input('review_url'));
        }

        $invoice = new \App\Models\Invoice([
            'number' => date('Y') . '-0042',
            'status' => 'paid',
            'language' => 'nl',
            'invoice_date' => now()->subDays(12),
            'due_date' => now()->addDays(2),
            'paid_at' => now(),
            'customer_name' => 'De Vries Bouw B.V.',
            'customer_email' => 'administratie@devriesbouw.nl',
            'subtotal' => 1000,
            'vat_total' => 210,
            'total' => 1210,
            'paid_total' => 1210,
        ]);
        $invoice->id = 0;
        $invoice->exists = false;
        $invoice->setRelation('company', $company);

        $payment = new \App\Models\Payment([
            'kind' => 'payment',
            'amount' => 1210,
            'paid_on' => now()->toDateString(),
            'method' => 'ideal',
        ]);
        $payment->exists = false;

        $html = \App\Support\DocumentLocale::using('nl', fn () => (new \App\Mail\PaymentThanksMail($invoice, $payment, '', preview: true))->render());

        return response($html)->header('X-Robots-Tag', 'noindex');
    }

    /** Voorbeeld van de bevestiging na akkoord — verzonnen offerte, niets wordt opgeslagen. */
    public function previewAccept(Request $request)
    {
        $company = auth()->user()->company->replicate();
        $texts = $company->email_texts ?? [];
        $texts['accept_subject'] = trim((string) $request->input('accept_subject', ''));
        $texts['accept_body'] = trim((string) $request->input('accept_body', ''));
        $company->email_texts = $texts;

        $quote = new \App\Models\Quote([
            'number' => 'OFF-' . date('Y') . '-0007',
            'status' => 'accepted',
            'language' => 'nl',
            'quote_date' => now()->subDays(6),
            'valid_until' => now()->addDays(24),
            'accepted_at' => now(),
            'signed_at' => now(),
            'signed_name' => 'Sanne de Vries',
            'customer_name' => 'De Vries Bouw B.V.',
            'customer_email' => 'administratie@devriesbouw.nl',
            'subtotal' => 4250,
            'vat_total' => 892.5,
            'total' => 5142.5,
        ]);
        $quote->id = 0;
        $quote->exists = false;
        $quote->setRelation('company', $company);
        $quote->setRelation('installments', collect([
            new \App\Models\QuoteInstallment(['description' => 'Aanbetaling bij opdracht', 'percentage' => 30, 'amount' => 1542.75]),
            new \App\Models\QuoteInstallment(['description' => 'Bij oplevering', 'percentage' => 70, 'amount' => 3599.75]),
        ]));

        $html = \App\Support\DocumentLocale::using('nl', fn () => (new \App\Mail\QuoteAcceptedMail($quote, '', preview: true))->render());

        return response($html)->header('X-Robots-Tag', 'noindex');
    }

    /** Reviewlink netjes maken: spaties weg, zonder schema → https://. Leeg = null. */
    protected static function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    // ----- REMINDERS -----
    public function reminders()
    {
        $company = auth()->user()->company;
        return Inertia::render('Settings/Reminders', [
            'reminders' => $company->resolved_reminders,
            'default_payment_terms' => (int) $company->default_payment_terms,
        ]);
    }

    public function updateReminders(Request $request)
    {
        $data = $request->validate([
            'payment_term_reminder' => 'required|integer|min:0|max:60',
            'payment_term_warning' => 'required|integer|min:0|max:60',
            'num_reminders' => 'required|integer|min:0|max:5',
            'second_reminder_email' => 'required|in:first,custom',
            'negative_outstanding' => 'boolean',
            'reminder_delay' => 'required|integer|min:0|max:30',
            'warning_delay' => 'required|integer|min:0|max:30',
            'reminder_subject' => 'nullable|string|max:200',
            'reminder_body' => 'nullable|string|max:4000',
            'warning_subject' => 'nullable|string|max:200',
            'warning_body' => 'nullable|string|max:4000',
        ]);
        auth()->user()->company->update(['reminder_settings' => $data]);
        return back()->with('flash', 'Herinneringen opgeslagen.');
    }
}
