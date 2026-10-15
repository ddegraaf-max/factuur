<?php

namespace App\Services\Fachora;

use App\Models\Provider;
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
    /** @param  Collection<int, Provider>  $providers */
    public function pdf(Collection $providers): string
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
            'date' => now()->format('d.m.Y'),
            'domain' => brand('domain'),
        ])->setPaper('a4')->output();
    }

    /** Welke bedrijven een brief verdienen: unclaimed, met adres, nog geen brief (of lang geleden). */
    public function candidates(?string $category, ?string $city, int $limit): Collection
    {
        return Provider::query()->where('status', 'unclaimed')
            ->whereNotNull('address_line')->whereNotNull('postal_code')->whereNotNull('city')
            ->when($category, fn ($q) => $q->inCategory($category))
            ->when($city, fn ($q) => $q->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]))
            ->orderBy('city')->orderBy('display_name')
            ->limit($limit)->get();
    }
}
