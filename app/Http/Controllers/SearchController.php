<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use Illuminate\Http\Request;

/** Globaal zoeken (Ctrl/⌘-K): facturen, offertes, klanten, producten, inkoop en snelle acties. */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => [], 'actions' => $this->actions('')]);
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($q)) . '%';
        $money = fn ($v) => money($v);
        $groups = [];

        $invoices = Invoice::query()
            ->where(fn ($w) => $w->whereRaw('LOWER(number) LIKE ?', [$like])->orWhereRaw('LOWER(customer_name) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(reference, \'\')) LIKE ?', [$like]))
            ->orderByDesc('invoice_date')->limit(6)->get();
        if ($invoices->isNotEmpty()) {
            $groups[] = ['title' => __('Facturen'), 'items' => $invoices->map(fn ($i) => [
                'title' => ($i->is_credit ? __('Creditnota') : __('Factuur')) . ' ' . ($i->number ?: __('concept')),
                'subtitle' => $i->customer_name . ' · ' . $money($i->total) . ' · ' . $this->status($i->status),
                'url' => route('invoices.show', $i), 'icon' => 'invoice',
            ])->all()];
        }

        $quotes = Quote::query()
            ->where(fn ($w) => $w->whereRaw('LOWER(COALESCE(number, \'\')) LIKE ?', [$like])->orWhereRaw('LOWER(customer_name) LIKE ?', [$like]))
            ->orderByDesc('quote_date')->limit(4)->get();
        if ($quotes->isNotEmpty()) {
            $groups[] = ['title' => __('Offertes'), 'items' => $quotes->map(fn ($qt) => [
                'title' => __('Offerte') . ' ' . ($qt->number ?: __('concept')),
                'subtitle' => $qt->customer_name . ' · ' . $money($qt->total) . ' · ' . $this->status($qt->status),
                'url' => route('quotes.show', $qt), 'icon' => 'quote',
            ])->all()];
        }

        $customers = Customer::query()
            ->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(contact_name, \'\')) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(city, \'\')) LIKE ?', [$like]))
            ->orderBy('name')->limit(5)->get();
        if ($customers->isNotEmpty()) {
            $groups[] = ['title' => __('Klanten'), 'items' => $customers->map(fn ($c) => [
                'title' => $c->name,
                'subtitle' => trim(($c->email ?: '') . ($c->city ? ' · ' . $c->city : '')) ?: __('Klant'),
                'url' => route('customers.show', $c), 'icon' => 'users',
            ])->all()];
        }

        $products = Product::query()->whereRaw('LOWER(name) LIKE ?', [$like])->orderBy('name')->limit(4)->get();
        if ($products->isNotEmpty()) {
            $groups[] = ['title' => __('Producten'), 'items' => $products->map(fn ($p) => [
                'title' => $p->name,
                'subtitle' => $money($p->price) . ' · ' . __(':rate% btw', ['rate' => (int) $p->vat_rate]),
                'url' => route('products.edit', $p), 'icon' => 'box',
            ])->all()];
        }

        $purchases = PurchaseInvoice::query()
            ->where(fn ($w) => $w->whereRaw('LOWER(supplier_name) LIKE ?', [$like])->orWhereRaw('LOWER(COALESCE(supplier_reference, \'\')) LIKE ?', [$like]))
            ->orderByDesc('invoice_date')->limit(4)->get();
        if ($purchases->isNotEmpty()) {
            $groups[] = ['title' => __('Inkoop'), 'items' => $purchases->map(fn ($p) => [
                'title' => $p->supplier_name . ($p->supplier_reference ? ' · ' . $p->supplier_reference : ''),
                'subtitle' => $money($p->total) . ' · ' . $p->invoice_date?->translatedFormat('j M Y'),
                'url' => route('purchases.show', $p), 'icon' => 'receipt',
            ])->all()];
        }

        return response()->json(['groups' => $groups, 'actions' => $this->actions($q)]);
    }

    /** Snelle acties en pagina's die op de zoekterm passen. */
    private function actions(string $q): array
    {
        // Titels en trefwoorden zijn vertaalbaar; de Nederlandse trefwoorden
        // blijven altijd meezoeken, zodat een bekende term nooit verdwijnt.
        $all = [
            ['title' => __('Nieuwe factuur'), 'url' => route('invoices.create'), 'keywords' => __('factuur nieuw maken aanmaken') . ' factuur nieuw maken aanmaken'],
            ['title' => __('Nieuwe offerte'), 'url' => route('quotes.create'), 'keywords' => __('offerte nieuw maken aanmaken') . ' offerte nieuw maken aanmaken'],
            ['title' => __('Nieuwe klant'), 'url' => route('customers.create'), 'keywords' => __('klant nieuw toevoegen relatie') . ' klant nieuw toevoegen relatie'],
            ['title' => __('Banktransacties importeren'), 'url' => route('bank.index'), 'keywords' => __('bank afschrift importeren camt mt940 transacties') . ' bank afschrift importeren camt mt940 transacties'],
            ['title' => __('Btw-aangifte'), 'url' => route('vat.index'), 'keywords' => __('btw aangifte omzetbelasting kwartaal') . ' btw aangifte omzetbelasting kwartaal'],
            ['title' => __('Export naar boekhouder / auditfile'), 'url' => route('export.index'), 'keywords' => __('export boekhouder accountant xaf auditfile csv') . ' export boekhouder accountant xaf auditfile csv'],
            ['title' => __('Automatische incasso'), 'url' => route('direct-debit.index'), 'keywords' => __('incasso sepa machtiging batch') . ' incasso sepa machtiging batch'],
            ['title' => __('Bedrijfsgegevens'), 'url' => route('settings.company'), 'keywords' => __('instellingen bedrijf gegevens logo iban mollie') . ' instellingen bedrijf gegevens logo iban mollie'],
            ['title' => __('Koppelingen'), 'url' => route('settings.integrations'), 'keywords' => __('koppelingen peppol domein mail claude') . ' koppelingen peppol domein mail claude'],
            ['title' => __('Logboek'), 'url' => route('settings.activity'), 'keywords' => __('logboek wie deed wat audit') . ' logboek wie deed wat audit'],
            ['title' => __('Overstappen / importeren'), 'url' => route('import.index'), 'keywords' => __('import overstappen csv wefact moneybird') . ' import overstappen csv wefact moneybird'],
        ];
        $needle = mb_strtolower($q);

        return array_values(array_filter($all, fn ($a) => $needle === '' || str_contains(mb_strtolower($a['title'] . ' ' . $a['keywords']), $needle)));
    }

    private function status(string $status): string
    {
        $label = ['draft' => 'concept', 'sent' => 'verstuurd', 'partial' => 'deels betaald', 'overdue' => 'vervallen', 'paid' => 'betaald', 'incasso' => 'incasso', 'cancelled' => 'geannuleerd', 'accepted' => 'geaccepteerd', 'rejected' => 'afgewezen', 'expired' => 'verlopen'][$status] ?? null;

        return $label === null ? $status : (string) __($label);
    }
}
