<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PeppolService;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/** Peppol: koppeling van de administratie, bereikbaarheid van klanten en afleveren van facturen. */
class PeppolController extends Controller
{
    public function __construct(protected PeppolService $peppol) {}

    /** Administratie registreren als Peppol-deelnemer (daarna: identiteitscontrole). */
    public function activate(): RedirectResponse
    {
        $company = auth()->user()->company;

        try {
            $url = $this->peppol->register($company);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Peppol activeren mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Activeren is niet gelukt. Probeer het later opnieuw.');
        }

        return back()->with('flash', $url
            ? 'Geregistreerd op het Peppol-netwerk. Rond nu de identiteitscontrole af via de knop hieronder.'
            : 'Geregistreerd op het Peppol-netwerk.');
    }

    /** Verificatiestatus opnieuw ophalen. */
    public function refresh(): RedirectResponse
    {
        $company = auth()->user()->company;

        try {
            $status = $this->peppol->refreshStatus($company);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('flash', match ($status) {
            'verified' => 'Je administratie is geverifieerd: je kunt nu via Peppol verzenden en ontvangen.',
            'rejected' => 'De identiteitscontrole is afgewezen. Neem contact op met ' . Brand::name() . '.',
            'none' => 'Peppol is nog niet geactiveerd.',
            default => 'De identiteitscontrole is nog niet afgerond.',
        });
    }

    /** Peppol uitschakelen voor deze administratie. */
    public function disable(): RedirectResponse
    {
        $this->peppol->deregister(auth()->user()->company);

        return back()->with('flash', 'Peppol is uitgeschakeld voor deze administratie.');
    }

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
            Log::error('Peppol-verzending onverwacht mislukt', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['peppol' => 'Afleveren via Peppol is niet gelukt. Probeer het later opnieuw.']);
        }

        return back()->with('flash', "Factuur {$invoice->number} afgeleverd via Peppol (referentie {$reference}).");
    }
}
