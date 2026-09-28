<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Symfony\Component\HttpFoundation\Response;

/**
 * Het bedrijfslogo als afbeelding, voor in e-mails. Openbaar, want een
 * mailprogramma logt niet in; het adres bevat een code die bij het logo hoort,
 * zodat alleen wie de mail heeft het logo kan opvragen.
 */
class CompanyLogoController extends Controller
{
    public function show(int $company, string $hash): Response
    {
        $model = Company::find($company);
        $logo = $model?->logoBinary();

        abort_unless($logo && hash_equals($model->logoHash(), $hash), 404);
        abort_unless(in_array($logo['mime'], ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true), 404);

        return response($logo['data'], 200, [
            'Content-Type' => $logo['mime'],
            'Content-Length' => (string) strlen($logo['data']),
            // Het adres verandert mee met het logo: lang bewaren mag.
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
