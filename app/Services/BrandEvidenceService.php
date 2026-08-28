<?php

namespace App\Services;

use App\Mail\BrandEvidenceMail;
use App\Mail\InvoiceMail;
use App\Models\BrandDossier;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PageView;
use App\Models\Quote;
use App\Support\OwnerAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Merkgebruik-dossier: elke maand automatisch bewijs dat het merk EasyInvoice
 * normaal wordt gebruikt — gebruikscijfers, een export van verstuurde
 * facturen, de homepage met logo, en een verstuurde klantfactuur (mail + PDF)
 * waarop het merk staat. Bestanden op schijf én per mail naar de eigenaar
 * (de mailbox is het duurzame archief; containeropslag is vluchtig).
 */
class BrandEvidenceService
{
    public const DISK = 'local';
    public const DIR = 'merkbewijs';

    public function buildMonth(Carbon $month, bool $mail = true): BrandDossier
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $key = $start->format('Y-m');
        $dir = self::DIR . '/' . $key;
        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory($dir);

        $stats = $this->stats($start, $end);
        $files = [];

        $files['gebruiksrapport.txt'] = $this->report($key, $stats);
        $files['factuurexport.csv'] = $this->invoiceCsv($start, $end);

        try {
            $files['homepage.html'] = view('landing')->render();
        } catch (\Throwable $e) {
            Log::warning('Merkbewijs: homepage renderen mislukt', ['error' => $e->getMessage()]);
        }
        foreach (['easyinvoice-icon-512.png', 'og-easyinvoice.png'] as $asset) {
            $path = public_path('images/' . $asset);
            if (is_file($path)) {
                $files[$asset] = file_get_contents($path);
            }
        }

        // Eén echte, verstuurde klantfactuur van de eigenaar: mail (met "Verzonden
        // via EasyInvoice namens …") en PDF.
        if ($invoice = $this->ownerInvoice($start, $end)) {
            try {
                $invoice->load('lines');
                $files['voorbeeldmail-factuur.html'] = (new InvoiceMail($invoice, ''))->render();
                $company = $invoice->brandedCompany();
                $files['voorbeeldfactuur.pdf'] = Pdf::loadView('pdf.invoice-' . $company->resolvedInvoiceTemplate(), [
                    'invoice' => $invoice,
                    'company' => $company,
                ])->setPaper('a4')->output();
                $stats['voorbeeldfactuur'] = $invoice->number;
            } catch (\Throwable $e) {
                Log::warning('Merkbewijs: voorbeeldfactuur mislukt', ['error' => $e->getMessage()]);
            }
        }

        $manifest = [];
        foreach ($files as $name => $contents) {
            $disk->put("{$dir}/{$name}", $contents);
            $manifest[] = ['file' => $name, 'bytes' => strlen($contents), 'sha256' => hash('sha256', $contents)];
        }
        $manifestJson = json_encode(['month' => $key, 'generated_at' => now()->toIso8601String(), 'files' => $manifest], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $disk->put("{$dir}/manifest.json", $manifestJson);

        $dossier = BrandDossier::updateOrCreate(['month' => $key], [
            'stats' => $stats,
            'manifest' => $manifest,
            'generated_at' => now(),
        ]);

        if ($mail && ($to = OwnerAccess::emails())) {
            try {
                Mail::to($to)->send(new BrandEvidenceMail($dossier, $files, $manifestJson));
                $dossier->forceFill(['mailed_to' => implode(', ', $to)])->save();
            } catch (\Throwable $e) {
                Log::error('Merkbewijs: dossier mailen mislukt', ['month' => $key, 'error' => $e->getMessage()]);
            }
        }

        return $dossier;
    }

    /** Gebruikscijfers van het platform in de maand (demo-omgevingen tellen niet mee). */
    public function stats(Carbon $start, Carbon $end): array
    {
        $realCompanies = Company::where('is_demo', false);
        $companyIds = (clone $realCompanies)->pluck('id');

        $invoicesSent = Invoice::withoutGlobalScope('company')->whereIn('company_id', $companyIds)
            ->whereBetween('sent_at', [$start, $end])->count();
        $quotesSent = Quote::withoutGlobalScope('company')->whereIn('company_id', $companyIds)
            ->whereBetween('sent_at', [$start, $end])->count();
        $activeCompanies = Invoice::withoutGlobalScope('company')->whereIn('company_id', $companyIds)
            ->whereBetween('sent_at', [$start, $end])->distinct('company_id')->count('company_id');

        $views = class_exists(PageView::class)
            ? PageView::whereBetween('viewed_on', [$start->toDateString(), $end->toDateString()])
            : null;

        return [
            'maand' => $start->format('Y-m'),
            'domein' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'versie' => config('app.version'),
            'administraties_totaal' => (clone $realCompanies)->count(),
            'administraties_nieuw' => (clone $realCompanies)->whereBetween('created_at', [$start, $end])->count(),
            'administraties_actief' => $activeCompanies,
            'facturen_verstuurd' => $invoicesSent,
            'offertes_verstuurd' => $quotesSent,
            'documentmails_met_merk' => $invoicesSent + $quotesSent,
            'bezoekers_website' => $views ? (int) $views->distinct('visitor_hash')->count('visitor_hash') : null,
            'paginaweergaven_website' => $views ? (int) PageView::whereBetween('viewed_on', [$start->toDateString(), $end->toDateString()])->count() : null,
            'homepage_weergaven' => $views ? (int) PageView::whereBetween('viewed_on', [$start->toDateString(), $end->toDateString()])->where('path', '/')->count() : null,
        ];
    }

    protected function report(string $month, array $stats): string
    {
        $lines = [
            'MERKGEBRUIK-DOSSIER EASYINVOICE — ' . $month,
            'Opgesteld op ' . now()->translatedFormat('j F Y, H:i') . ' door de software zelf (' . config('app.version') . ').',
            '',
            'Het merk EasyInvoice wordt gebruikt voor online facturatiesoftware (SaaS) op ' . ($stats['domein'] ?? 'easyinvoice.nl') . '.',
            'Elke factuur- en offertemail die via het platform wordt verstuurd, draagt de vermelding',
            '"Verzonden via EasyInvoice namens <bedrijfsnaam>"; het klantenportaal en alle systeemmails dragen naam en logo.',
            '',
            'CIJFERS OVER ' . strtoupper($month),
            '- Administraties (betalend/proef, excl. demo): ' . $stats['administraties_totaal'] . ' (nieuw deze maand: ' . $stats['administraties_nieuw'] . ')',
            '- Administraties die deze maand facturen verstuurden: ' . $stats['administraties_actief'],
            '- Facturen verstuurd via het platform: ' . $stats['facturen_verstuurd'],
            '- Offertes verstuurd via het platform: ' . $stats['offertes_verstuurd'],
            '- E-mails aan derden met de merkvermelding: ' . $stats['documentmails_met_merk'],
        ];
        if ($stats['bezoekers_website'] !== null) {
            $lines[] = '- Websitebezoekers: ' . $stats['bezoekers_website'] . ' (' . $stats['paginaweergaven_website'] . ' weergaven, waarvan ' . $stats['homepage_weergaven'] . ' van de homepage)';
        }
        $lines[] = '';
        $lines[] = 'BIJLAGEN';
        $lines[] = '- factuurexport.csv: alle in deze maand verstuurde facturen (datum, nummer, administratie-id, bedrag) — zonder klantgegevens.';
        $lines[] = '- homepage.html + logo: de website zoals die deze maand werd getoond, met het merk en logo.';
        $lines[] = '- voorbeeldmail-factuur.html / voorbeeldfactuur.pdf: een echte, verstuurde klantfactuur met de merkvermelding.';
        $lines[] = '- manifest.json: bestandsnamen met SHA-256-hashes voor de integriteit van dit dossier.';

        return implode("\n", $lines) . "\n";
    }

    protected function invoiceCsv(Carbon $start, Carbon $end): string
    {
        $companyIds = Company::where('is_demo', false)->pluck('id');
        $rows = ["verstuurd_op;factuurnummer;administratie;land_klant;totaal_incl_btw;via_peppol"];

        Invoice::withoutGlobalScope('company')->whereIn('company_id', $companyIds)
            ->whereBetween('sent_at', [$start, $end])
            ->orderBy('sent_at')
            ->get(['sent_at', 'number', 'company_id', 'customer_country', 'total', 'peppol_sent_at'])
            ->each(function ($i) use (&$rows) {
                $rows[] = implode(';', [
                    $i->sent_at?->format('Y-m-d H:i'),
                    $i->number,
                    'administratie-' . $i->company_id,
                    $i->customer_country,
                    number_format((float) $i->total, 2, ',', ''),
                    $i->peppol_sent_at ? 'ja' : 'nee',
                ]);
            });

        return implode("\n", $rows) . "\n";
    }

    /** Een verstuurde factuur van de eigenaarsadministratie (liefst uit deze maand, anders de laatste). */
    protected function ownerInvoice(Carbon $start, Carbon $end): ?Invoice
    {
        $owner = \App\Models\User::find(1);
        $companyId = $owner?->company_id;
        if (! $companyId) {
            return null;
        }

        $query = Invoice::withoutGlobalScope('company')->where('company_id', $companyId)
            ->whereNotNull('sent_at')->where('is_credit', false)->orderByDesc('sent_at');

        return (clone $query)->whereBetween('sent_at', [$start, $end])->first() ?? $query->first();
    }
}
