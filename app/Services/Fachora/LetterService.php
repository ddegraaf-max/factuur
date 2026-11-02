<?php

namespace App\Services\Fachora;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Support\PaymentQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Brieven per post aan bedrijven die nog niet geclaimd zijn: de enige
 * manier om een bedrijf uit een register te benaderen zonder toestemming
 * voor elektronische berichten. Elke brief heeft een eigen claimcode en een
 * QR-code naar de claimpagina; de code werkt alleen voor dat ene bedrijf.
 */
class LetterService
{
    /**
     * @param  Collection<int, Provider>  $providers
     * @param  bool  $chosen  De brief noemt dat een klant uit de buurt het bedrijf koos (1.94).
     */
    public function pdf(Collection $providers, bool $chosen = false): string
    {
        $letters = $providers->map(function (Provider $provider) {
            if (blank($provider->claim_code)) {
                $provider->forceFill(['claim_code' => Provider::makeClaimCode()])->save();
            }
            $url = route('fachora.claim', ['kod' => $provider->claim_code]);

            return [
                'provider' => $provider,
                'code' => chunk_split($provider->claim_code, 4, ' '),
                'url' => $url,
                'qr' => PaymentQr::render($url),
                'categories' => array_map(fn ($c) => \App\Support\Fachora::categoryName($c, 'pl'), $provider->categories()),
            ];
        })->values()->all();

        return Pdf::loadView('pdf.fachora-letter', [
            'letters' => $letters,
            'chosen' => $chosen,
            'date' => now()->format('d.m.Y'),
            'domain' => brand('domain'),
            'email' => brand('email'),
        ])->setPaper('a4')->output();
    }

    /** Welke bedrijven een brief verdienen: unclaimed, met adres, nog geen brief (of lang geleden). */
    public function candidates(?string $category, ?string $city, int $limit, bool $chosenOnly = false): Collection
    {
        return Provider::query()->where('status', 'unclaimed')
            ->whereNotNull('address_line')->whereNotNull('postal_code')->whereNotNull('city')
            ->when($chosenOnly, fn ($q) => $q->whereIn('id', $this->chosenIds()))
            ->when($category, fn ($q) => $q->inCategory($category))
            ->when($city, fn ($q) => $q->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]))
            ->orderBy('city')->orderBy('display_name')
            ->limit($limit)->get();
    }

    /**
     * Bedrijven die een klant zelf uit de lijst koos (selected_providers op de
     * opdracht) in het afgelopen half jaar: de beste reden voor een brief, want
     * zo'n bedrijf heeft net een klant uit de buurt gemist. Platformonafhankelijk
     * (geen JSON-operatoren): de id's worden in PHP samengevoegd.
     *
     * @return list<int>
     */
    public function chosenIds(): array
    {
        $ids = [];
        ServiceJob::query()->whereNotNull('selected_providers')->where('created_at', '>=', now()->subMonths(6))
            ->orderByDesc('id')->limit(5000)->pluck('selected_providers')
            ->each(function ($selected) use (&$ids) {
                foreach ((array) $selected as $id) {
                    $ids[(int) $id] = true;
                }
            });

        return array_keys($ids);
    }

    /** Hoeveel niet-geclaimde bedrijven met postadres een klant heeft gekozen. */
    public function chosenCount(): int
    {
        return $this->candidates(null, null, 100000, true)->count();
    }
}
