<?php

namespace App\Http\Controllers;

use App\Models\Subcontractor;
use App\Models\WorkPackage;
use App\Services\TenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Werkpakketten en de pool van onderaannemers (Inkoop → Onderaannemers). */
class TenderPoolController extends Controller
{
    public function __construct(private TenderService $service) {}

    public function index(): Response
    {
        $packages = WorkPackage::withCount('subcontractors')->orderBy('sort_order')->orderBy('name')->get();
        $subcontractors = Subcontractor::with('workPackages')->orderBy('name')->get();

        return Inertia::render('Tenders/Pool', [
            'packages' => $packages->map(fn (WorkPackage $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'subcontractors_count' => $p->subcontractors_count,
            ])->values(),
            'subcontractors' => $subcontractors->map(fn (Subcontractor $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'contact_name' => $s->contact_name,
                'email' => $s->email,
                'phone' => $s->phone,
                'city' => $s->city,
                'website' => $s->website,
                'notes' => $s->notes,
                'source' => $s->source,
                'package_ids' => $s->workPackages->pluck('id')->all(),
                'stats' => $this->service->stats($s),
            ])->values(),
        ]);
    }

    public function seedPackages(): RedirectResponse
    {
        $added = $this->service->seedDefaultPackages(auth()->user()->company);

        return back()->with('flash', $added
            ? __(':n standaardpakketten toegevoegd. Pas ze gerust aan.', ['n' => $added])
            : __('Alle standaardpakketten staan er al.'));
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $data = $this->packageData($request);
        $data['sort_order'] = (int) WorkPackage::max('sort_order') + 1;
        WorkPackage::create($data);

        return back()->with('flash', __('Werkpakket toegevoegd.'));
    }

    public function updatePackage(Request $request, WorkPackage $package): RedirectResponse
    {
        $package->update($this->packageData($request));

        return back()->with('flash', __('Werkpakket bijgewerkt.'));
    }

    public function destroyPackage(WorkPackage $package): RedirectResponse
    {
        if ($package->rounds()->exists()) {
            return back()->withErrors(['package' => __('Dit werkpakket is al gebruikt in een uitvraag en kan niet worden verwijderd.')]);
        }
        $package->delete();

        return back()->with('flash', __('Werkpakket verwijderd.'));
    }

    public function storeSubcontractor(Request $request): RedirectResponse
    {
        [$data, $packageIds] = $this->subcontractorData($request);
        $subcontractor = Subcontractor::create($data);
        $subcontractor->workPackages()->sync($packageIds);

        return back()->with('flash', __(':name toegevoegd aan de pool.', ['name' => $subcontractor->name]));
    }

    public function updateSubcontractor(Request $request, Subcontractor $subcontractor): RedirectResponse
    {
        [$data, $packageIds] = $this->subcontractorData($request);
        $subcontractor->update($data);
        $subcontractor->workPackages()->sync($packageIds);

        return back()->with('flash', __('Bedrijf bijgewerkt.'));
    }

    public function destroySubcontractor(Subcontractor $subcontractor): RedirectResponse
    {
        if ($subcontractor->requests()->exists()) {
            return back()->withErrors(['subcontractor' => __('Dit bedrijf heeft al prijsaanvragen gehad; verwijderen zou die geschiedenis wissen. Haal het in plaats daarvan bij de werkpakketten weg.')]);
        }
        $subcontractor->delete();

        return back()->with('flash', __('Bedrijf uit de pool verwijderd.'));
    }

    /**
     * Meerdere bedrijven tegelijk: één per regel, velden gescheiden door een
     * puntkomma — naam; e-mail; telefoon; plaats; werkpakketten (komma's).
     */
    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate(['lines' => ['required', 'string', 'max:20000']]);

        $packages = WorkPackage::orderBy('sort_order')->get();
        $findPackage = function (string $name) use ($packages) {
            $needle = mb_strtolower(trim($name));

            return $packages->first(fn (WorkPackage $p) => mb_strtolower($p->name) === $needle
                || str_starts_with(mb_strtolower($p->name), $needle));
        };

        $added = 0;
        $skipped = 0;
        foreach (preg_split('/\r?\n/', $data['lines']) as $line) {
            $parts = array_map('trim', explode(';', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }
            if (Subcontractor::whereRaw('lower(name) = ?', [mb_strtolower($name)])->exists()) {
                $skipped++;
                continue;
            }
            $email = filter_var($parts[1] ?? '', FILTER_VALIDATE_EMAIL) ? $parts[1] : null;
            $subcontractor = Subcontractor::create([
                'name' => mb_substr($name, 0, 160),
                'email' => $email,
                'phone' => mb_substr($parts[2] ?? '', 0, 40) ?: null,
                'city' => mb_substr($parts[3] ?? '', 0, 120) ?: null,
                'source' => 'import',
            ]);
            $ids = collect(explode(',', $parts[4] ?? ''))->map(fn ($n) => $findPackage($n)?->id)->filter()->unique()->values()->all();
            $subcontractor->workPackages()->sync($ids);
            $added++;
        }

        return back()->with('flash', __(':added bedrijven toegevoegd, :skipped overgeslagen (bestonden al).', ['added' => $added, 'skipped' => $skipped]));
    }

    private function packageData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => __('Geef het werkpakket een naam.'),
        ]);
    }

    /** @return array{0: array, 1: array<int>} */
    private function subcontractorData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer'],
        ], [
            'name.required' => __('Vul de bedrijfsnaam in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
        ]);

        // Alleen pakketten van dit bedrijf (de scope regelt dat).
        $packageIds = WorkPackage::whereIn('id', $data['package_ids'] ?? [])->pluck('id')->all();
        unset($data['package_ids']);

        foreach (['contact_name', 'email', 'phone', 'city', 'website', 'notes'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? $data[$field] : null;
        }

        return [$data, $packageIds];
    }
}
