<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PeppolService;
use Illuminate\Http\RedirectResponse;

/** Peppol: bereikbaarheid controleren en facturen afleveren via het netwerk. */
class PeppolController extends Controller
{
    public function __construct(protected PeppolService $peppol) {}

    /** Handmatig de Peppol-bereikbaarheid van een klant (opnieuw) controleren. */
    public function check(Customer $customer): RedirectResponse
    {
        $result = $this->peppol->checkCustomer($customer, force: true);

        return back()->with('flash', match ($result) {
            true => "{$customer->name} is bereikbaar via Peppol.",
            false => "{$customer->name} is (nog) niet aangesloten op Peppol.",
            default => 'Geen Peppol-ID afleidbaar — vul een KvK-nummer of Peppol-ID in bij de klant.',
        });
    }

    /** Factuur afleveren via het Peppol-netwerk. */
    public function send(Invoice $invoice): RedirectResponse
    {
        try {
            $reference = $this->peppol->send($invoice);
        } catch (\DomainException $e) {
            return back()->withErrors(['peppol' => $e->getMessage()]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Peppol-verzending onverwacht mislukt', [
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['peppol' => 'Afleveren via Peppol is niet gelukt. Probeer het later opnieuw.']);
        }

        return back()->with('flash', "Factuur {$invoice->number} afgeleverd via Peppol (referentie {$reference}).");
    }
}
