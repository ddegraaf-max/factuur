<?php

namespace App\Http\Controllers;

use App\Models\TimeCard;
use App\Services\InvoiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Strippenkaarten beheren (op de urenpagina): een vooraf betaald urentegoed
 * per klant aanmaken, factureren en verwijderen. Het afschrijven zelf gebeurt
 * automatisch bij het urenschrijven (TimeCard::apply).
 */
class TimeCardController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('company_id', $request->user()->company_id)],
            'hours' => ['required', 'numeric', 'min:1', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'name' => ['nullable', 'string', 'max:190'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ], [
            'customer_id.required' => __('Kies de klant voor deze strippenkaart.'),
            'hours.required' => __('Vul het aantal uren van de bundel in.'),
            'hours.min' => __('Een strippenkaart bevat minimaal 1 uur.'),
            'price.required' => __('Vul de bundelprijs in.'),
            'valid_until.after' => __('De geldigheidsdatum moet in de toekomst liggen.'),
        ]);

        $hours = round((float) $data['hours'], 1);

        $card = TimeCard::create([
            'customer_id' => $data['customer_id'],
            'name' => filled($data['name'] ?? null)
                ? trim($data['name'])
                : __('Strippenkaart :hours uur', ['hours' => rtrim(rtrim(number_format($hours, 1, ',', '.'), '0'), ',')]),
            'total_minutes' => (int) round($hours * 60),
            'price' => round((float) $data['price'], 2),
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        // Al geschreven open uren van deze klant meteen dekken (oudste eerst).
        \App\Models\TimeEntry::billable()
            ->where('customer_id', $card->customer_id)
            ->orderBy('work_date')->orderBy('id')
            ->get()
            ->each(fn ($entry) => TimeCard::apply($entry));

        return back()->with('flash', __("Strippenkaart aangemaakt — factureer 'm en de uren tellen automatisch af."));
    }

    /** Maak de verkoopfactuur voor de bundel (eenmalig, als concept). */
    public function invoice(Request $request, TimeCard $card, InvoiceManager $manager): RedirectResponse
    {
        if ($card->invoice_id) {
            return back()->with('error', __('Deze strippenkaart is al gefactureerd.'));
        }

        $hours = rtrim(rtrim(number_format($card->total_minutes / 60, 1, ',', '.'), '0'), ',');

        $invoice = $manager->create([
            'customer_id' => $card->customer_id,
            'lines' => [[
                'description' => $card->name,
                'details' => trim(__('Vooraf betaald tegoed: :hours uur', ['hours' => $hours])
                    . ($card->valid_until ? __(', geldig tot :date', ['date' => $card->valid_until->translatedFormat('j F Y')]) : '')),
                'quantity' => 1,
                'unit' => __('stuk'),
                'unit_price' => (float) $card->price,
                'vat_rate' => (float) \App\Support\Market::defaultVatRate(),
            ]],
        ]);

        $card->update(['invoice_id' => $invoice->id]);

        return redirect()->route('invoices.edit', $invoice)
            ->with('flash', __("Conceptfactuur voor \":name\" aangemaakt — controleer en verstuur 'm.", ['name' => $card->name]));
    }

    public function destroy(TimeCard $card): RedirectResponse
    {
        // De gedekte uren komen weer vrij als factureerbare uren (nullOnDelete);
        // de eventuele verkoopfactuur blijft gewoon bestaan.
        $card->delete();

        return back()->with('flash', __('Strippenkaart verwijderd — de gedekte uren staan weer als factureerbaar in de lijst.'));
    }
}
