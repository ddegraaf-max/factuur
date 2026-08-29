<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\VatFiling;
use App\Services\VatService;
use App\Support\Sql;
use App\Support\VatPaymentReference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Btw-aangifte "aangifte-klaar": alle rubrieken per tijdvak in de indeling
 * van Mijn Belastingdienst Zakelijk, betaalgegevens met betalingskenmerk en
 * de status (aangegeven/betaald) per tijdvak. De berekening zit in VatService.
 */
class VatController extends Controller
{
    public function __construct(protected VatService $vat) {}

    public function index(Request $request): Response
    {
        $company = auth()->user()->company;
        $year = (int) $request->input('year', now()->year);

        $allYears = Invoice::regular()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->selectRaw('DISTINCT ' . Sql::year('invoice_date') . ' AS yr')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return Inertia::render('Btw/Index', array_merge($this->vat->overview($company, $year), [
            'year' => $year,
            'allYears' => $allYears,
            'settings' => [
                'vat_period' => VatService::periodType($company),
                'has_ob_number' => filled($company->ob_number),
                'ob_number_hint' => $this->maskObNumber($company->ob_number),
                'vat_reminder_enabled' => (bool) $company->vat_reminder_enabled,
                'reminder_email' => $company->daily_notification_email ?: $company->email ?: auth()->user()->email,
            ],
            'mbz_url' => 'https://mijnzakelijk.belastingdienst.nl',
        ]));
    }

    /** Hetzelfde overzicht als PDF voor de eigen administratie of de boekhouder. */
    public function pdf(Request $request): HttpResponse
    {
        $company = auth()->user()->company;
        $year = (int) $request->input('year', now()->year);

        $pdf = Pdf::loadView('pdf.btw-overzicht', array_merge($this->vat->overview($company, $year, false), [
            'year' => $year,
            'company' => $company,
            'generated_at' => now()->translatedFormat('j F Y, H:i'),
        ]))->setPaper('a4');

        return $pdf->download("btw-aangifte-{$year}.pdf");
    }

    /** Status en aanvullingen van één tijdvak bijwerken. */
    public function updateFiling(Request $request, int $year, string $type, int $period): RedirectResponse
    {
        $company = auth()->user()->company;
        abort_unless(in_array($type, VatService::PERIOD_TYPES, true), 404);
        $max = match ($type) { 'month' => 12, 'year' => 1, default => 4 };
        abort_unless($period >= 1 && $period <= $max && $year >= 2000 && $year <= 2100, 404);

        $data = $request->validate([
            'filed' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'payment_reference' => ['nullable', 'string', 'max:30'],
            'manual' => ['nullable', 'array'],
            'manual.*.base' => ['nullable', 'numeric', 'between:-99999999,99999999'],
            'manual.*.vat' => ['nullable', 'numeric', 'between:-99999999,99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $filing = VatFiling::firstOrNew([
            'company_id' => $company->id,
            'year' => $year,
            'period_type' => $type,
            'period' => $period,
        ]);

        if ($request->has('filed')) {
            $filing->filed_at = $request->boolean('filed') ? ($filing->filed_at ?? now()) : null;
        }
        if ($request->has('paid')) {
            $filing->paid_at = $request->boolean('paid') ? ($filing->paid_at ?? now()) : null;
        }

        if ($request->has('payment_reference')) {
            $raw = trim((string) ($data['payment_reference'] ?? ''));
            if ($raw === '') {
                $filing->payment_reference = null;
            } else {
                $normalized = VatPaymentReference::normalize($raw);
                if (! $normalized || ! VatPaymentReference::isValid($normalized)) {
                    return back()->withErrors([
                        'payment_reference' => 'Dit is geen geldig betalingskenmerk: het bestaat uit 16 cijfers, waarvan het eerste een controlecijfer is. Neem het letterlijk over uit Mijn Belastingdienst Zakelijk.',
                    ]);
                }
                $filing->payment_reference = $normalized;
            }
        }

        if ($request->has('manual')) {
            // Alleen de rubrieken die Easy niet zelf kent; nullen niet bewaren.
            $clean = [];
            foreach (VatService::MANUAL as $key) {
                $base = round((float) ($data['manual'][$key]['base'] ?? 0), 2);
                $vat = round((float) ($data['manual'][$key]['vat'] ?? 0), 2);
                if ($base != 0.0 || $vat != 0.0) {
                    $clean[$key] = ['base' => $base, 'vat' => $vat];
                }
            }
            $filing->manual = $clean ?: null;
        }

        if ($request->has('notes')) {
            $filing->notes = trim((string) ($data['notes'] ?? '')) ?: null;
        }

        $filing->save();

        $flash = match (true) {
            $request->has('filed') && $request->boolean('filed') => 'Gemarkeerd als aangegeven.',
            $request->has('filed') => 'Markering "aangegeven" verwijderd.',
            $request->has('paid') && $request->boolean('paid') => 'Gemarkeerd als betaald.',
            $request->has('paid') => 'Markering "betaald" verwijderd.',
            $request->has('payment_reference') => 'Betalingskenmerk opgeslagen.',
            default => 'Aangifte bijgewerkt.',
        };

        return back()->with('flash', $flash);
    }

    /** Tijdvak, omzetbelastingnummer en herinnering. */
    public function updateSettings(Request $request): RedirectResponse
    {
        $company = auth()->user()->company;

        $data = $request->validate([
            'vat_period' => ['required', 'in:quarter,month,year'],
            'ob_number' => ['nullable', 'string', 'max:30'],
            'ob_number_clear' => ['nullable', 'boolean'],
            'vat_reminder_enabled' => ['nullable', 'boolean'],
        ]);

        $update = [
            'vat_period' => $data['vat_period'],
            'vat_reminder_enabled' => $request->boolean('vat_reminder_enabled'),
        ];

        if ($request->boolean('ob_number_clear')) {
            $update['ob_number'] = null;
        } elseif (filled($data['ob_number'] ?? null)) {
            $parsed = VatPaymentReference::parseObNumber($data['ob_number']);
            if (! $parsed) {
                return back()->withErrors([
                    'ob_number' => 'Vul je omzetbelastingnummer in zoals de Belastingdienst het schrijft, bijvoorbeeld 123456789B01. Let op: bij een eenmanszaak is dat níet het btw-id dat op je facturen staat.',
                ]);
            }
            $update['ob_number'] = $parsed['fiscal'] . 'B' . $parsed['sub'];
        }
        // Leeg veld zonder "wissen" = ongewijzigd laten.

        $company->update($update);

        return back()->with('flash', 'Btw-instellingen opgeslagen.');
    }

    /** Alleen de laatste cijfers tonen: het nummer is BSN-gebaseerd. */
    private function maskObNumber(?string $ob): ?string
    {
        if (! $ob) {
            return null;
        }
        $parsed = VatPaymentReference::parseObNumber($ob);

        return $parsed ? '•••••' . substr($parsed['fiscal'], 5) . 'B' . $parsed['sub'] : '••••••••';
    }
}
