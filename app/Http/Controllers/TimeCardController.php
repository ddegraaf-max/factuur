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
            'customer_id.required' => 'Kies de klant voor deze strippenkaart.',
            'hours.required' => 'Vul het aantal uren van de bundel in.',
            'hours.min' => 'Een strippenkaart bevat minimaal 1 uur.',
            'price.required' => 'Vul de bundelprijs in.',
            'valid_until.after' => 'De geldigheidsdatum moet in de toekomst liggen.',
        ]);

        $hours = round((float) $data['hours'], 1);

        $card = TimeCard::create([
            'customer_id' => $data['customer_id'],
            'name' => filled($data['name'] ?? null)
                ? trim($data['name'])
                : 'Strippenkaart ' . rtrim(rtrim(number_format($hours, 1, ',', '.'), '0'), ',') . ' uur',
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

        return back()->with('flash', "Strippenkaart aangemaakt — factureer 'm en de uren tellen automatisch af.");
    }

    /** Maak de verkoopfactuur voor de bundel (eenmalig, als concept). */
    public function invoice(Request $request, TimeCard $card, InvoiceManager $manager): RedirectResponse
    {
        if ($card->invoice_id) {
            return back()->with('error', 'Deze strippenkaart is al gefactureerd.');
        }

        $hours = rtrim(rtrim(number_format($card->total_minutes / 60, 1, ',', '.'), '0'), ',');

        $invoice = $manager->create([
            'customer_id' => $card->customer_id,
            'lines' => [[
                'description' => $card->name,
                'details' => trim("Vooraf betaald tegoed: {$hours} uur"
                    . ($card->valid_until ? ', geldig tot ' . $card->valid_until->translatedFormat('j F Y') : '')),
                'quantity' => 1,
                'unit' => 'stuk',
                'unit_price' => (float) $card->price,
                'vat_rate' => 21.0,
            ]],
        ]);

        $card->update(['invoice_id' => $invoice->id]);

        return redirect()->route('invoices.edit', $invoice)
            ->with('flash', "Conceptfactuur voor \"{$card->name}\" aangemaakt — controleer en verstuur 'm.");
    }

    public function destroy(TimeCard $card): RedirectResponse
    {
        // De gedekte uren komen weer vrij als factureerbare uren (nullOnDelete);
        // de eventuele verkoopfactuur blijft gewoon bestaan.
        $card->delete();

        return back()->with('flash', 'Strippenkaart verwijderd — de gedekte uren staan weer als factureerbaar in de lijst.');
    }
}
