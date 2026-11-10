<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MerkBundelRegel;
use App\Models\MerkOphaling;
use App\Support\Audit;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extra verbruik van de merken van het VvE/NextPro-pakket factureren.
 *
 * ── Wat dit doet ──────────────────────────────────────────────────────────
 *
 * Per merk (config/merken.php) één vraag aan het koppelvlak:
 * GET /koppelvlak/bundels. Dat geeft elk kantoor of elke vereniging met open
 * regels — bijgekochte bundels (fair use) en brieven per post (Pingen) — met
 * de gegevens om de klant aan te maken. Per klant per maand komt er één
 * conceptfactuur in de administratie van het merk (MERKEN_TEAM_ID: die van
 * NapoleonSoftware B.V.), met per regel omschrijving, aantal, prijs excl. btw
 * en 21% btw. Daarna meldt dit de regels terug met
 * POST /koppelvlak/bundels/gefactureerd, zodat het merk ze niet nog eens geeft.
 *
 * ── Wat dit níét doet ─────────────────────────────────────────────────────
 *
 * Versturen. Het blijft een concept; Daniël controleert en verstuurt zelf,
 * net als bij de concepten die via de MCP-koppeling binnenkomen.
 *
 * ── Waarom het nooit dubbel gaat ──────────────────────────────────────────
 *
 * Elke regel die op een factuur komt, staat in merk_bundel_regels, uniek op
 * merk + kenmerk + bundel-id. Mislukt het terugmelden, dan geeft het merk de
 * regel de volgende keer opnieuw; hier staat hij dan al, dus komt hij niet op
 * een tweede factuur — en wordt het terugmelden opnieuw geprobeerd.
 */
class MerkenService
{
    public function __construct(protected InvoiceManager $invoices) {}

    /** @return array<string, array> alle merken uit de configuratie */
    public function merken(): array
    {
        return (array) config('merken.merken', []);
    }

    public function merk(string $code): ?array
    {
        return $this->merken()[strtolower($code)] ?? null;
    }

    /** De administratie die voor dit merk factureert. */
    public function teamVoor(array $merk): ?int
    {
        return $merk['team_id'] ?? config('merken.team_id');
    }

    /** Wat er nog ontbreekt voordat dit merk kan worden opgehaald (leeg = klaar). */
    public function ontbreekt(array $merk): array
    {
        $mist = [];
        if (($merk['sleutel'] ?? '') === '') {
            $mist[] = __('koppelvlaksleutel (:env)', ['env' => 'MERK_'.strtoupper($merk['code']).'_KOPPELVLAK_SLEUTEL']);
        }
        $team = $this->teamVoor($merk);
        if (! $team) {
            $mist[] = __('administratie (MERKEN_TEAM_ID)');
        } elseif (! Company::withoutGlobalScope('company')->whereKey($team)->exists()) {
            $mist[] = __('administratie #:id bestaat niet', ['id' => $team]);
        }

        return $mist;
    }

    /** Alle merken ophalen. @return list<MerkOphaling> */
    public function haalAllesOp(string $door = 'planner'): array
    {
        return array_values(array_map(fn ($code) => $this->haalOp($code, $door), array_keys($this->merken())));
    }

    /** Eén merk ophalen. Werpt nooit: de uitkomst staat in de teruggegeven ophaling. */
    public function haalOp(string $code, string $door = 'planner'): MerkOphaling
    {
        $merk = $this->merk($code);
        if (! $merk) {
            throw new \InvalidArgumentException("Onbekend merk: {$code}");
        }

        $ophaling = MerkOphaling::create(['merk' => $merk['code'], 'status' => 'bezig', 'door' => mb_substr($door, 0, 80)]);

        try {
            return $this->alsPlanner(fn () => $this->verwerk($merk, $ophaling));
        } catch (\Throwable $e) {
            report($e);

            return $this->klaar($ophaling, 'fout', __('Onverwachte fout: :fout', ['fout' => mb_substr($e->getMessage(), 0, 500)]));
        }
    }

    protected function verwerk(array $merk, MerkOphaling $ophaling): MerkOphaling
    {
        $mist = $this->ontbreekt($merk);
        if ($mist) {
            Log::info("Merken: {$merk['code']} overgeslagen", ['ontbreekt' => $mist]);

            return $this->klaar($ophaling, 'overgeslagen', __('Overgeslagen, ontbreekt: :wat', ['wat' => implode(', ', $mist)]));
        }
        $company = Company::withoutGlobalScope('company')->findOrFail($this->teamVoor($merk));

        // Eerst wat eerder niet kon worden teruggemeld.
        $afmeldfouten = $this->afmeldenOpenstaand($merk);

        try {
            $antwoord = $this->http($merk)->get($merk['url'].'/koppelvlak/bundels');
        } catch (\Throwable $e) {
            return $this->klaar($ophaling, 'fout', __(':merk is niet bereikbaar: :fout', ['merk' => $merk['naam'], 'fout' => mb_substr($e->getMessage(), 0, 300)]), ['afmeldfouten' => $afmeldfouten]);
        }

        if (! $antwoord->successful()) {
            $fout = (string) ($antwoord->json('fout') ?? '');
            $uitleg = match (true) {
                $antwoord->status() === 401 => __('de sleutel klopt niet (MERK_:code_KOPPELVLAK_SLEUTEL moet gelijk zijn aan KOPPELVLAK_SLEUTEL bij het merk)', ['code' => strtoupper($merk['code'])]),
                $antwoord->status() === 503 => __('KOPPELVLAK_SLEUTEL is bij het merk niet ingesteld'),
                $fout === 'stripe_doet_het_geld' => __('het merk rekent af via Stripe'),
                default => $fout !== '' ? $fout : mb_substr($antwoord->body(), 0, 200),
            };

            return $this->klaar($ophaling, 'fout', __(':merk antwoordde met :status: :uitleg', ['merk' => $merk['naam'], 'status' => $antwoord->status(), 'uitleg' => $uitleg]), ['afmeldfouten' => $afmeldfouten]);
        }

        $klanten = $antwoord->json('klanten');
        if (! is_array($klanten)) {
            return $this->klaar($ophaling, 'fout', __('Onverwacht antwoord van :merk (geen lijst "klanten").', ['merk' => $merk['naam']]), ['afmeldfouten' => $afmeldfouten]);
        }

        $periode = now()->format('Y-m');
        $tel = ['klanten' => 0, 'regels' => 0, 'facturen' => 0, 'afmeldfouten' => $afmeldfouten];
        $meldingen = [];

        foreach ($klanten as $klant) {
            if (! is_array($klant)) {
                continue;
            }
            $kenmerk = mb_substr(trim((string) ($klant['kenmerk'] ?? $klant['slug'] ?? '')), 0, 80);
            if ($kenmerk === '') {
                $meldingen[] = __('een klant zonder kenmerk is overgeslagen');

                continue;
            }

            $nieuw = $this->nieuweRegels($merk, $kenmerk, $klant['bundels'] ?? []);
            if ($nieuw->isEmpty()) {
                continue;
            }

            [$invoice, $isNieuw] = DB::transaction(function () use ($company, $merk, $kenmerk, $klant, $periode, $nieuw, $ophaling) {
                $customer = $this->klant($company, $merk, $kenmerk, $klant);
                [$invoice, $isNieuw] = $this->concept($company, $merk, $customer, $kenmerk, $periode, $nieuw);

                foreach ($nieuw as $b) {
                    MerkBundelRegel::create([
                        'merk' => $merk['code'],
                        'klant_kenmerk' => $kenmerk,
                        'bundel_id' => $b['id'],
                        'company_id' => $company->id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'ophaling_id' => $ophaling->id,
                        'periode' => $periode,
                        'omschrijving' => $b['omschrijving'],
                        'bedrag_excl_cent' => $b['cent'],
                        'btw_pct' => $b['btw_pct'],
                    ]);
                }

                return [$invoice, $isNieuw];
            });

            $tel['klanten']++;
            $tel['regels'] += $nieuw->count();
            $tel['facturen'] += $isNieuw ? 1 : 0;

            if (! $this->afmelden($merk, $kenmerk, $invoice, $nieuw->pluck('id')->all())) {
                $tel['afmeldfouten']++;
            }
        }

        $melding = $tel['regels']
            ? __(':regels regel(s) voor :klanten klant(en) klaargezet als concept.', ['regels' => $tel['regels'], 'klanten' => $tel['klanten']])
            : __('Niets nieuws te factureren.');
        if ($tel['afmeldfouten']) {
            $melding .= ' '.__('Terugmelden lukte :n keer niet; dat wordt de volgende keer opnieuw geprobeerd.', ['n' => $tel['afmeldfouten']]);
        }
        if ($meldingen) {
            $melding .= ' '.ucfirst(implode('; ', array_unique($meldingen))).'.';
        }

        return $this->klaar($ophaling, 'ok', $melding, $tel);
    }

    /**
     * De regels van deze klant die nog niet op een factuur staan, genormaliseerd.
     */
    protected function nieuweRegels(array $merk, string $kenmerk, mixed $bundels): Collection
    {
        $regels = collect(is_array($bundels) ? $bundels : [])
            ->filter(fn ($b) => is_array($b) && filled($b['id'] ?? null) && (int) ($b['bedrag_excl_btw_cent'] ?? 0) > 0)
            ->map(fn ($b) => [
                'id' => mb_substr((string) $b['id'], 0, 80),
                'omschrijving' => mb_substr(trim((string) ($b['omschrijving'] ?? '')) ?: __('Extra verbruik :merk', ['merk' => $merk['naam']]), 0, 500),
                'aantal' => max(1, (float) ($b['aantal'] ?? 1)),
                'cent' => (int) $b['bedrag_excl_btw_cent'],
                'btw_pct' => (float) ($b['btw_pct'] ?? 21),
            ])
            ->unique('id')
            ->values();

        if ($regels->isEmpty()) {
            return $regels;
        }

        $al = MerkBundelRegel::where('merk', $merk['code'])
            ->where('klant_kenmerk', $kenmerk)
            ->whereIn('bundel_id', $regels->pluck('id'))
            ->pluck('bundel_id')
            ->all();

        return $regels->reject(fn ($b) => in_array($b['id'], $al, true))->values();
    }

    /**
     * De klant in de administratie van het merk: opzoeken op merk + kenmerk
     * (bij VvEMaat ook op het oude vvemaat_slug), anders aanmaken met de
     * gegevens uit het koppelvlak. Een bestaande klant krijgt alleen lege
     * velden aangevuld — wat Daniël zelf heeft aangepast blijft staan.
     */
    protected function klant(Company $company, array $merk, string $kenmerk, array $gegevens): Customer
    {
        $basis = Customer::withoutGlobalScope('company')->where('company_id', $company->id);

        $klant = (clone $basis)->where('merk_code', $merk['code'])->where('merk_kenmerk', $kenmerk)->first();
        if (! $klant && $merk['code'] === 'vvemaat') {
            $slugs = array_values(array_unique(array_filter([strtolower($kenmerk), strtolower((string) ($gegevens['slug'] ?? ''))])));
            $klant = (clone $basis)->whereIn('vvemaat_slug', $slugs)->first();
        }

        $velden = array_filter([
            'name' => mb_substr(trim((string) ($gegevens['naam'] ?? '')), 0, 255) ?: null,
            'contact_name' => mb_substr(trim((string) ($gegevens['t_a_v'] ?? '')), 0, 255) ?: null,
            'email' => filter_var($gegevens['email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
            'kvk_number' => mb_substr(trim((string) ($gegevens['kvk_nummer'] ?? '')), 0, 20) ?: null,
            'vat_number' => mb_substr(trim((string) ($gegevens['btw_nummer'] ?? '')), 0, 30) ?: null,
            'address_line' => mb_substr(trim((string) ($gegevens['adres'] ?? '')), 0, 255) ?: null,
            'postal_code' => mb_substr(trim((string) ($gegevens['postcode'] ?? '')), 0, 20) ?: null,
            'city' => mb_substr(trim((string) ($gegevens['plaats'] ?? '')), 0, 100) ?: null,
        ]);

        if ($klant) {
            $aanvulling = array_filter($velden, fn ($waarde, $veld) => blank($klant->{$veld}), ARRAY_FILTER_USE_BOTH);
            $klant->forceFill($aanvulling + ['merk_code' => $merk['code'], 'merk_kenmerk' => $kenmerk])->save();

            return $klant;
        }

        $land = strtoupper(mb_substr(trim((string) ($gegevens['land'] ?? 'NL')), 0, 2)) ?: 'NL';

        return Customer::withoutGlobalScope('company')->create($velden + [
            'company_id' => $company->id,
            'name' => $kenmerk,
            'type' => 'business',
            'country' => $land,
            'language' => 'nl',
            'merk_code' => $merk['code'],
            'merk_kenmerk' => $kenmerk,
            'vvemaat_slug' => $merk['code'] === 'vvemaat' ? strtolower((string) ($gegevens['slug'] ?? $kenmerk)) : null,
        ]);
    }

    /**
     * Eén concept per klant per maand: staat er deze maand al een concept
     * voor deze klant (en is het nog niet verstuurd), dan komen de nieuwe
     * regels erbij; anders een nieuw concept.
     *
     * @return array{0: Invoice, 1: bool} de factuur en of hij nieuw is
     */
    protected function concept(Company $company, array $merk, Customer $customer, string $kenmerk, string $periode, Collection $nieuw): array
    {
        $regels = $nieuw->map(fn ($b) => [
            'description' => $b['omschrijving'],
            'quantity' => $b['aantal'],
            'unit' => __('stuk'),
            'unit_price' => round($b['cent'] / 100 / $b['aantal'], 2),
            'vat_rate' => $b['btw_pct'],
        ])->all();

        $vorigeId = MerkBundelRegel::where('merk', $merk['code'])
            ->where('klant_kenmerk', $kenmerk)
            ->where('periode', $periode)
            ->where('company_id', $company->id)
            ->whereNotNull('invoice_id')
            ->orderByDesc('id')
            ->value('invoice_id');
        $vorige = $vorigeId ? Invoice::withoutGlobalScope('company')->find($vorigeId) : null;

        if ($vorige && $vorige->status === 'draft') {
            $bestaand = $vorige->lines()->orderBy('sort_order')->get()->map(fn ($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'details' => $l->details,
                'quantity' => (float) $l->quantity,
                'unit' => $l->unit,
                'unit_price' => (float) $l->unit_price,
                'vat_rate' => (float) $l->vat_rate,
                'discount_pct' => (float) ($l->discount_pct ?? 0),
            ])->all();

            return [$this->invoices->update($vorige, ['lines' => array_merge($bestaand, $regels), 'price_mode' => 'excl']), false];
        }

        $maand = Carbon::createFromFormat('Y-m-d', $periode.'-01')->locale('nl')->translatedFormat('F Y');

        $invoice = $this->invoices->create([
            'customer_id' => $customer->id,
            'brand_profile_id' => $merk['handelsnaam_id'] ?? null,
            'reference' => mb_substr(__(':merk — extra verbruik :maand', ['merk' => $merk['naam'], 'maand' => $maand]), 0, 255),
            'notes' => __('Bijgekochte bundels en brieven per post bij :merk. Klantkenmerk: :kenmerk.', ['merk' => $merk['naam'], 'kenmerk' => $kenmerk]),
            'price_mode' => 'excl',
            'lines' => $regels,
        ]);

        Audit::log('created', $invoice, __('Concept klaargezet voor extra verbruik bij :merk (:kenmerk)', ['merk' => $merk['naam'], 'kenmerk' => $kenmerk]), [], $company->id);

        return [$invoice, true];
    }

    /**
     * Het merk laten weten dat deze regels op een factuur staan.
     *
     * Een concept heeft nog geen factuurnummer (dat komt bij het versturen);
     * dan gaat "EI-concept-<id>" mee, het id waaronder het concept in
     * EasyInvoice te vinden is.
     */
    public function afmelden(array $merk, string $kenmerk, ?Invoice $invoice, array $ids): bool
    {
        if (! $ids) {
            return true;
        }
        $nummer = $invoice?->number ?: ($invoice ? 'EI-concept-'.$invoice->id : 'EI-concept');
        $rijen = MerkBundelRegel::where('merk', $merk['code'])->where('klant_kenmerk', $kenmerk)->whereIn('bundel_id', $ids);

        try {
            $antwoord = $this->http($merk)->post($merk['url'].'/koppelvlak/bundels/gefactureerd', [
                'klant' => $kenmerk,
                'ids' => array_values($ids),
                'factuurnummer' => $nummer,
            ]);
            $fout = $antwoord->successful() ? null : __('status :status: :body', ['status' => $antwoord->status(), 'body' => mb_substr($antwoord->body(), 0, 200)]);
        } catch (\Throwable $e) {
            $fout = mb_substr($e->getMessage(), 0, 300);
        }

        if ($fout !== null) {
            Log::warning("Merken: terugmelden bij {$merk['code']} mislukt", ['klant' => $kenmerk, 'fout' => $fout]);
            (clone $rijen)->update(['afmeld_fout' => $fout]);

            return false;
        }

        (clone $rijen)->update(['afgemeld_op' => now(), 'afmeld_fout' => null]);

        return true;
    }

    /** Eerder mislukte terugmeldingen opnieuw. Geeft het aantal dat wéér mislukte. */
    protected function afmeldenOpenstaand(array $merk): int
    {
        $fouten = 0;
        MerkBundelRegel::where('merk', $merk['code'])->whereNull('afgemeld_op')->get()
            ->groupBy(fn ($r) => $r->klant_kenmerk."\0".$r->invoice_id)
            ->each(function (Collection $groep) use ($merk, &$fouten) {
                $eerste = $groep->first();
                $invoice = $eerste->invoice_id ? Invoice::withoutGlobalScope('company')->find($eerste->invoice_id) : null;
                if (! $this->afmelden($merk, $eerste->klant_kenmerk, $invoice, $groep->pluck('bundel_id')->all())) {
                    $fouten++;
                }
            });

        return $fouten;
    }

    protected function http(array $merk): PendingRequest
    {
        return Http::timeout((int) config('merken.timeout', 20))
            ->withHeaders(['X-Koppelvlak-Sleutel' => (string) $merk['sleutel']])
            ->acceptJson();
    }

    protected function klaar(MerkOphaling $ophaling, string $status, string $melding, array $tel = []): MerkOphaling
    {
        $ophaling->forceFill($tel + ['status' => $status, 'melding' => $melding, 'klaar_op' => now()])->save();

        return $ophaling;
    }

    /**
     * Draaien zoals de planner: zonder ingelogde gebruiker.
     *
     * Klanten en facturen hebben een bedrijfsscope op de administratie van wie
     * is ingelogd. Start de eigenaar het ophalen vanuit zijn eigen administratie,
     * dan zou InvoiceManager de klant in de administratie van het merk niet
     * vinden. Voor de duur van het ophalen is er dus niemand ingelogd; daarna
     * is de eigenaar het weer, zonder dat de sessie wordt aangeraakt.
     */
    protected function alsPlanner(callable $werk): mixed
    {
        $guard = auth()->guard();
        $gebruiker = $guard->user();
        if ($gebruiker && method_exists($guard, 'forgetUser')) {
            $guard->forgetUser();
        }

        try {
            return $werk();
        } finally {
            if ($gebruiker) {
                $guard->setUser($gebruiker);
            }
        }
    }
}
