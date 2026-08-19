<?php

namespace App\Http\Controllers;

use App\Models\BrandProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handelsnamen beheren (Instellingen → Handelsnamen): meerdere huisstijlen
 * onder één administratie. Per handelsnaam een eigen naam, logo, kleur,
 * sjabloon en voetnoot; bij het maken van een factuur kies je onder welke
 * naam die de deur uitgaat. KvK, BTW-nummer, IBAN en de factuurnummering
 * blijven van het bedrijf zelf — het is en blijft één administratie.
 */
class BrandProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Handelsnamen', [
            'profiles' => BrandProfile::withCount('invoices')
                ->orderBy('name')
                ->get()
                ->map(fn ($p) => array_merge(
                    $p->makeVisible('logo_data')->toArray(),
                    ['invoices_count' => $p->invoices_count],
                )),
            'company' => [
                'name' => auth()->user()->company->name,
                'brand_color' => auth()->user()->company->brand_color,
                'invoice_template' => auth()->user()->company->invoice_template ?? 'modern',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        BrandProfile::create($data);

        return back()->with('flash', 'Handelsnaam toegevoegd.');
    }

    public function update(Request $request, BrandProfile $profile): RedirectResponse
    {
        $data = $this->validated($request, $profile);

        // Logo verwijderen zonder nieuw logo te uploaden.
        if ($request->boolean('remove_logo') && ! isset($data['logo_data'])) {
            $data['logo_data'] = null;
        }

        $profile->update($data);

        return back()->with('flash', 'Handelsnaam bijgewerkt.');
    }

    public function destroy(BrandProfile $profile): RedirectResponse
    {
        // Facturen die onder deze naam zijn gemaakt vallen terug op de
        // standaard huisstijl (database: nullOnDelete).
        $profile->delete();

        return back()->with('flash', 'Handelsnaam verwijderd. Bestaande facturen gebruiken weer de standaard huisstijl.');
    }

    protected function validated(Request $request, ?BrandProfile $profile = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:190',
                \Illuminate\Validation\Rule::unique('brand_profiles', 'name')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($profile?->id),
            ],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'invoice_template' => ['nullable', 'in:modern,classic,minimal'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
            'logo_scale' => ['nullable', 'integer', 'min:50', 'max:200'],
        ], [
            'name.required' => 'Vul de handelsnaam in.',
            'name.unique' => 'Deze handelsnaam bestaat al.',
        ]);

        // Eigen logo, als base64 in de database (overleeft elke deploy).
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:png,jpg,jpeg,svg,webp|max:2048']);
            $file = $request->file('logo');
            $data['logo_data'] = 'data:' . $file->getMimeType() . ';base64,'
                . base64_encode(file_get_contents($file->getRealPath()));
        }

        $data['name'] = trim($data['name']);
        $data['logo_scale'] = $data['logo_scale'] ?? 100;

        return $data;
    }
}
