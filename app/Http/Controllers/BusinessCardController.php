<?php

namespace App\Http\Controllers;

use App\Models\BusinessCard;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Digitaal visitekaartje: publieke pagina in de huisstijl van de administratie
 * (easyinvoice.nl/k/{slug}) met contactknoppen, vCard en een QR-code voor op
 * de fysieke kaart of in de e-mailhandtekening.
 */
class BusinessCardController extends Controller
{
    public function edit(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $company = $user->company;
        $slug = $company->ensurePublicSlug();
        $card = $company->businessCard ?? new BusinessCard([
            'contact_name' => $user->name, 'show_kvk' => true, 'show_vat' => false, 'show_address' => true, 'published' => false,
        ]);

        return Inertia::render('Settings/BusinessCard', [
            'card' => $card->only(['published', 'contact_name', 'job_title', 'tagline', 'whatsapp', 'linkedin_url', 'show_kvk', 'show_vat', 'show_address']),
            'slug' => $slug,
            'public_url' => route('card.show', $slug),
            'site_published' => (bool) $company->site?->published,
            'allowed' => $company->publicPagesAllowed(),
            'company' => [
                'name' => $company->publicName(), 'email' => $company->email, 'phone' => $company->phone, 'website' => $company->website,
                'address' => $company->full_address, 'kvk_number' => $company->kvk_number, 'vat_number' => $company->vat_number,
                'brand_color' => $company->brand_color ?: '#E8231F', 'accent_color' => $company->accent_color ?: '#1C1917',
                'logo' => $company->logo_data,
            ],
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $company = $user->company;

        $data = $request->validate([
            'published' => ['boolean'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+ ()-]+$/'],
            'linkedin_url' => ['nullable', 'url', 'max:200'],
            'show_kvk' => ['boolean'],
            'show_vat' => ['boolean'],
            'show_address' => ['boolean'],
            'public_slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,public_slug,' . $company->id],
        ], [
            'public_slug.regex' => 'Gebruik alleen kleine letters, cijfers en koppeltekens.',
            'public_slug.unique' => 'Dit adres is al in gebruik door een andere administratie.',
            'whatsapp.regex' => 'Vul een telefoonnummer in, bijvoorbeeld 06 12345678.',
        ]);

        $company->forceFill(['public_slug' => $data['public_slug']])->save();
        unset($data['public_slug']);
        BusinessCard::updateOrCreate(['company_id' => $company->id], $data);

        return back()->with('flash', ! empty($data['published']) ? 'Visitekaartje opgeslagen en online.' : 'Visitekaartje opgeslagen (nog niet online).');
    }

    /* ===================== Publiek ===================== */

    public function show(string $slug)
    {
        [$company, $card] = $this->resolve($slug);

        return response()->view('public.card', [
            'company' => $company,
            'card' => $card,
            'whatsapp_url' => $card->whatsappUrl(),
            'site_url' => $company->site?->published ? route('site.show', $slug) : null,
            'website_url' => $company->website ? (str_starts_with($company->website, 'http') ? $company->website : 'https://' . $company->website) : null,
            'phone_url' => $company->phone ? 'tel:' . preg_replace('/[^0-9+]/', '', $company->phone) : null,
        ]);
    }

    public function vcard(string $slug)
    {
        [$company, $card] = $this->resolve($slug);
        $person = trim((string) $card->contact_name) ?: $company->publicName();

        $lines = ['BEGIN:VCARD', 'VERSION:3.0', 'N:' . self::v($person), 'FN:' . self::v($person), 'ORG:' . self::v($company->publicName())];
        if ($card->job_title) { $lines[] = 'TITLE:' . self::v($card->job_title); }
        if ($company->phone) { $lines[] = 'TEL;TYPE=WORK,VOICE:' . self::v($company->phone); }
        if ($card->whatsapp) { $lines[] = 'TEL;TYPE=CELL:' . self::v($card->whatsapp); }
        if ($company->email) { $lines[] = 'EMAIL;TYPE=WORK:' . self::v($company->email); }
        if ($company->website) { $lines[] = 'URL:' . self::v($company->website); }
        if ($card->show_address && ($company->address_line || $company->city)) {
            $lines[] = 'ADR;TYPE=WORK:;;' . self::v($company->address_line) . ';' . self::v($company->city) . ';;' . self::v($company->postal_code) . ';' . self::v($company->country ?: 'Nederland');
        }
        $lines[] = 'NOTE:' . self::v('Digitaal visitekaartje: ' . route('card.show', $slug));
        $lines[] = 'END:VCARD';

        return response(implode("\r\n", $lines) . "\r\n", 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $slug . '.vcf"',
        ]);
    }

    /** @return array{0: Company, 1: BusinessCard} */
    private function resolve(string $slug): array
    {
        $company = Company::where('public_slug', $slug)->first();
        $card = $company?->businessCard;
        abort_unless($company && $card && $card->published && $company->publicPagesAllowed(), 404);

        return [$company, $card];
    }

    private static function v(?string $value): string
    {
        return str_replace([';', ',', "\r", "\n"], ['\;', '\,', '', ' '], (string) $value);
    }
}
