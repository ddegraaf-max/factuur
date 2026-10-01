<?php

namespace App\Http\Controllers;

use App\Models\CcbrCheck;
use App\Models\Customer;
use App\Services\CcbrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Een debiteur opzoeken in het Centraal Curatele- en Bewindregister.
 *
 * ── Waarom dit niet met één klik gaat ─────────────────────────────────────
 *
 * Het register zoekt op achternaam én geboortedatum (of op zijn minst het
 * geboortejaar). Een klant in dit pakket heeft geen geboortedatum — het is een
 * facturatiepakket, niet een persoonsadministratie. De gebruiker vult die dus
 * zelf in. Dat is geen omissie maar de werkelijkheid van het register: zonder
 * geboortegegeven kun je niet zoeken, en een achternaam alleen zou een lijst
 * met vreemden opleveren.
 *
 * Bij een bedrijf heeft de controle geen zin: curatele en bewind gelden voor
 * mensen van 18 jaar en ouder.
 */
class CcbrController extends Controller
{
    public function __construct(private CcbrService $ccbr) {}

    public function check(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($this->ccbr->enabled(), 404);
        abort_if($customer->type !== 'consumer', 422, __('Curatele en bewind gelden voor particulieren.'));

        $data = $request->validate([
            'achternaam' => ['required', 'string', 'max:120'],
            'voorvoegsel' => ['nullable', 'string', 'max:40'],
            'geboortedatum' => ['nullable', 'date', 'before:today'],
            'geboortejaar' => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
        ], [
            'achternaam.required' => __('Vul de achternaam in zoals die in het register staat.'),
            'geboortedatum.before' => __('Een geboortedatum ligt in het verleden.'),
        ]);

        if (blank($data['geboortedatum'] ?? null) && blank($data['geboortejaar'] ?? null)) {
            return back()->withInput()->withErrors([
                'geboortedatum' => __('Het register zoekt op achternaam én geboortedatum. Weet je de datum niet, vul dan het geboortejaar in.'),
            ]);
        }

        try {
            $uit = $this->ccbr->zoek(
                $data['achternaam'],
                $data['geboortedatum'] ?? null,
                isset($data['geboortejaar']) ? (int) $data['geboortejaar'] : null,
                $data['voorvoegsel'] ?? ''
            );
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['achternaam' => $e->getMessage()]);
        }

        /*
         * Een eerdere uitkomst voor deze klant vervalt: twee antwoorden naast
         * elkaar bewaren levert alleen de vraag op welke nu geldt, en het is
         * extra persoonsgegeven zonder doel.
         */
        CcbrCheck::where('customer_id', $customer->id)->delete();

        $basis = [
            'customer_id' => $customer->id,
            'checked_by' => $request->user()?->id,
            'checked_at' => now(),
            'achternaam' => $data['achternaam'],
            'voorvoegsel' => $data['voorvoegsel'] ?? null,
            'geboortedatum' => $data['geboortedatum'] ?? null,
            'geboortejaar' => $data['geboortejaar'] ?? null,
        ];

        if (! $uit['treffers']) {
            CcbrCheck::create($basis + ['gevonden' => false]);

            return back()->with('flash', __('Niet gevonden in het curatele- en bewindregister.'));
        }

        /*
         * Meer treffers kan: dezelfde achternaam en hetzelfde geboortejaar. De
         * kaart met een volledige match gaat voor; is die er niet, dan de
         * eerste. Welke het is blijft zichtbaar op het scherm, met de naam en
         * geboortedatum uit het register erbij, zodat de gebruiker zelf kan
         * zien of het om zijn klant gaat.
         */
        $treffer = collect($uit['treffers'])->firstWhere('volledige_match', true) ?? $uit['treffers'][0];

        try {
            $kaart = $this->ccbr->raadpleeg($treffer['aanduiding']);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['achternaam' => $e->getMessage()]);
        }

        CcbrCheck::create($basis + [
            'gevonden' => true,
            'volledige_match' => (bool) $treffer['volledige_match'],
            'maatregel' => $kaart['maatregel'],
            'grond' => $kaart['grond'] ?: null,
            'grond_tekst' => $kaart['grond_tekst'],
            'kaartnummer' => $kaart['kaartnummer'] ?: null,
            'ingangsdatum' => $kaart['ingangsdatum'],
            'einddatum' => $kaart['einddatum'],
            'rechtbank' => $kaart['rechtbank'] ?: null,
            'beperkt_bewind' => (bool) $kaart['beperkt_bewind'],
            'vertegenwoordigers' => $kaart['vertegenwoordigers'],
        ]);

        return back()->with('flash', $kaart['maatregel'] === 'curatele'
            ? __('Deze persoon staat onder curatele. Neem contact op met de curator.')
            : __('Deze persoon staat in het register. Neem contact op met de bewindvoerder.'));
    }

    /** De uitkomst weghalen, bijvoorbeeld als de gebruiker ziet dat het een naamgenoot is. */
    public function forget(Customer $customer): RedirectResponse
    {
        CcbrCheck::where('customer_id', $customer->id)->delete();

        return back()->with('flash', __('De uitkomst is verwijderd.'));
    }
}
