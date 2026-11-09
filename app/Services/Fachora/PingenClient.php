<?php

namespace App\Services\Fachora;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Brieven per post via Pingen (API v2, JSON:API). Drie stappen per brief:
 * 1. een toegangstoken (client credentials, scope "letter"),
 * 2. een uploadadres aanvragen en de PDF daarheen zetten (PUT),
 * 3. de brief aanmaken met auto_send, zodat hij meteen gedrukt en verstuurd wordt.
 *
 * De adresgegevens staan in de PDF zelf (adresvenster links, zie pdf.fachora-letter met
 * $postal); Pingen leest ze uit het venster. Niets hier stuurt e-mail of sms (ConsentPolicy).
 */
class PingenClient
{
    public function configured(): bool
    {
        return filled(config('services.pingen.client_id'))
            && filled(config('services.pingen.client_secret'))
            && filled(config('services.pingen.organisation'));
    }

    /** Verstuurt één PDF als brief; geeft het Pingen-kenmerk (letter id) terug. */
    public function sendLetter(string $pdf, string $filename): string
    {
        $token = $this->token();

        $upload = Http::withToken($token)->acceptJson()->get($this->api('/file-upload'))->throw()->json('data.attributes');
        $url = (string) ($upload['url'] ?? '');
        $signature = (string) ($upload['url_signature'] ?? '');
        if ($url === '' || $signature === '') {
            throw new \RuntimeException('Pingen gaf geen uploadadres terug.');
        }

        Http::withBody($pdf, 'application/pdf')->put($url)->throw();

        $letter = Http::withToken($token)->acceptJson()
            ->withHeaders(['Content-Type' => 'application/vnd.api+json'])
            ->post($this->api('/organisations/' . config('services.pingen.organisation') . '/letters'), [
                'data' => [
                    'type' => 'letters',
                    'attributes' => [
                        'file_original_name' => $filename,
                        'file_url' => $url,
                        'file_url_signature' => $signature,
                        'address_position' => 'left',
                        'auto_send' => true,
                        'delivery_product' => 'cheap',
                        'print_mode' => 'simplex',
                        'print_spectrum' => 'grayscale',
                    ],
                ],
            ])->throw()->json();

        $id = (string) ($letter['data']['id'] ?? '');
        if ($id === '') {
            throw new \RuntimeException('Pingen gaf geen briefkenmerk terug.');
        }

        return $id;
    }

    /** Toegangstoken, gecachet tot vlak voor het verloopt. */
    protected function token(): string
    {
        return Cache::remember('pingen.token', 3000, function () {
            $json = Http::asForm()->post($this->identity('/auth/access-tokens'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.pingen.client_id'),
                'client_secret' => config('services.pingen.client_secret'),
                'scope' => 'letter',
            ])->throw()->json();

            return (string) ($json['access_token'] ?? throw new \RuntimeException('Pingen gaf geen toegangstoken.'));
        });
    }

    protected function api(string $path): string
    {
        return (config('services.pingen.staging') ? 'https://api-staging.pingen.com' : 'https://api.pingen.com') . $path;
    }

    protected function identity(string $path): string
    {
        return (config('services.pingen.staging') ? 'https://identity-staging.pingen.com' : 'https://identity.pingen.com') . $path;
    }
}
