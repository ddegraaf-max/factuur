<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meldt aan VvEMaat dat een abonnementsfactuur is voldaan.
 *
 * ── Waarom dit één bericht is en niet meer ────────────────────────────────
 *
 * VvEMaat weet over geld maar één ding: tot wanneer er betaald is. Staat die
 * datum in de toekomst, dan heeft de vereniging toegang. Al het andere — de
 * factuur, de btw, de herinnering, Mollie — staat hier. Twee systemen die
 * allebei over dezelfde rekening beginnen is erger dan geen van beide.
 *
 * ── Waarom dit nooit een betaling mag breken ──────────────────────────────
 *
 * Dit draait tijdens het opslaan van een betaling. Een storing aan de andere
 * kant mag niet betekenen dat een betaling hier niet wordt vastgelegd: die
 * betaling is een feit, de melding is een gevolg. Alles wordt daarom
 * opgevangen en het resultaat wordt vastgelegd op de factuur, zodat de planner
 * later opnieuw kan proberen wat niet is aangekomen.
 */
class VvemaatService
{
    /** Staat de koppeling aan? Zonder sleutel doen we niets. */
    public function actief(): bool
    {
        return config('vvemaat.sleutel') !== '' && config('vvemaat.url') !== '';
    }

    /**
     * Waar de betaalmelding voor deze factuur heen gaat, of null.
     *
     * Sinds 1.110 kent EasyInvoice zes merken (config/merken.php). Een klant
     * met merk_code + merk_kenmerk meldt aan dat merk, met de sleutel van dat
     * merk. Een klant met alleen het oude vvemaat_slug meldt zoals altijd aan
     * VvEMaat (config/vvemaat.php).
     *
     * @return array{naam: string, url: string, sleutel: string, klant: string}|null
     */
    public function koppelingVoor(Invoice $invoice): ?array
    {
        $klant = $invoice->customer;
        $code = (string) ($klant?->merk_code ?? '');
        $kenmerk = (string) ($klant?->merk_kenmerk ?? '');
        if ($code !== '' && $kenmerk !== '') {
            $merk = config("merken.merken.{$code}");
            if (is_array($merk) && ($merk['sleutel'] ?? '') !== '' && ($merk['url'] ?? '') !== '') {
                return ['naam' => $merk['naam'], 'url' => $merk['url'], 'sleutel' => $merk['sleutel'], 'klant' => $kenmerk];
            }
        }

        $slug = $this->slugVan($invoice);
        if ($slug !== null && $this->actief()) {
            return ['naam' => 'VvEMaat', 'url' => (string) config('vvemaat.url'), 'sleutel' => (string) config('vvemaat.sleutel'), 'klant' => $slug];
        }

        return null;
    }

    /** Staat er voor minstens één merk een koppeling aan? */
    public function ergensActief(): bool
    {
        return $this->actief() || collect(config('merken.merken', []))
            ->contains(fn ($m) => ($m['sleutel'] ?? '') !== '' && ($m['url'] ?? '') !== '');
    }

    /**
     * Hoort deze factuur bij een VvE-omgeving?
     *
     * Alleen als de klant een slug draagt. EasyInvoice factureert veel meer dan
     * verenigingen; zonder dit zou elke betaling van elke klant een verzoek
     * naar VvEMaat sturen.
     */
    public function slugVan(Invoice $invoice): ?string
    {
        $klant = $invoice->customer;
        $slug = $klant?->vvemaat_slug;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * Tot wanneer deze factuur toegang geeft.
     *
     * De periode staat op de factuur, gezet door de terugkerende facturatie die
     * hem exact kent. Ontbreekt hij — bijvoorbeeld bij een handmatige factuur —
     * dan geven we níéts terug in plaats van te gokken. Een verkeerde datum
     * betekent hier dat iemand te vroeg wordt buitengesloten of te lang
     * doorwerkt zonder te betalen; allebei erger dan een melding die niet komt
     * en die je in de log terugvindt.
     */
    public function betaaldTot(Invoice $invoice): ?string
    {
        return $invoice->period_end?->toDateString();
    }

    /**
     * De melding versturen.
     *
     * Geeft terug of het gelukt is. Werpt nooit.
     */
    public function meldBetaling(Invoice $invoice): bool
    {
        $koppeling = $this->koppelingVoor($invoice);
        if ($koppeling === null) {
            return false;
        }
        $slug = $koppeling['klant'];

        $tot = $this->betaaldTot($invoice);
        if ($tot === null) {
            Log::warning('VvEMaat: factuur betaald maar zonder periode, niets gemeld', [
                'invoice' => $invoice->id,
                'number' => $invoice->number,
                'vvemaat_slug' => $slug,
            ]);

            return false;
        }

        try {
            $antwoord = Http::timeout((int) config('vvemaat.timeout'))
                ->withHeaders(['X-Koppelvlak-Sleutel' => $koppeling['sleutel']])
                ->acceptJson()
                ->post($koppeling['url'].'/koppelvlak/abonnement/betaald', [
                    'klant' => $slug,
                    'betaald_tot' => $tot,
                    'bedrag_cent' => (int) round(((float) $invoice->total) * 100),
                    'periode' => $invoice->period_start?->format('Y-m'),
                    'factuurnummer' => $invoice->number,
                ]);
        } catch (\Throwable $e) {
            // Een betaling is een feit; de melding is een gevolg. Nooit hard falen.
            Log::warning('VvEMaat onbereikbaar, melding uitgesteld', [
                'invoice' => $invoice->id,
                'fout' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $antwoord->successful()) {
            Log::warning('VvEMaat wees de melding af', [
                'invoice' => $invoice->id,
                'status' => $antwoord->status(),
                'antwoord' => $antwoord->json() ?? $antwoord->body(),
            ]);

            return false;
        }

        $invoice->forceFill(['vvemaat_notified_at' => Carbon::now()])->saveQuietly();

        Log::info('VvEMaat: betaling gemeld', [
            'merk' => $koppeling['naam'],
            'vvemaat_slug' => $slug,
            'factuur' => $invoice->number,
            'betaald_tot' => $tot,
        ]);

        return true;
    }
}
