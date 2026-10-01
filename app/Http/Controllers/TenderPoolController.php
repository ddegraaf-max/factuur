<?php

namespace App\Http\Controllers;

use App\Models\Subcontractor;
use App\Models\WorkPackage;
use App\Services\TenderService;
use App\Support\OwnerAccess;
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
        $row = fn (Subcontractor $s) => [
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
            'archived_at_label' => $s->archived_at?->translatedFormat('j M Y'),
        ];

        return Inertia::render('Tenders/Pool', [
            'packages' => $packages->map(fn (WorkPackage $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'subcontractors_count' => $p->subcontractors_count,
            ])->values(),
            'subcontractors' => $subcontractors->reject(fn (Subcontractor $s) => $s->isArchived())->map($row)->values(),
            // Uit de pool gehaald, maar met geschiedenis: apart, en terug te zetten.
            'archived' => $subcontractors->filter(fn (Subcontractor $s) => $s->isArchived())->map($row)->values(),
            // Kant-en-klare startlijsten per werkpakket; alleen de eigenaar van het platform ziet ze.
            'startlists' => OwnerAccess::allows(auth()->user()) ? $this->service->startlists() : [],
        ]);
    }

    /** Een startlijst in de pool zetten (werkpakket erbij als dat nog niet bestaat). */
    public function startlist(Request $request): RedirectResponse
    {
        abort_unless(OwnerAccess::allows(auth()->user()), 403);
        $data = $request->validate(['key' => ['required', 'string', 'max:60']]);

        try {
            [$added, $skipped] = $this->service->applyStartlist(auth()->user()->company, $data['key']);
        } catch (\DomainException $e) {
            return back()->withErrors(['subcontractor' => $e->getMessage()]);
        }

        return back()->with('flash', __(':added bedrijven toegevoegd, :skipped overgeslagen (bestonden al).', ['added' => $added, 'skipped' => $skipped]));
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
        // Met prijsaanvragen in de geschiedenis gaat het bedrijf uit de pool, maar niet uit de uitvragen.
        if ($subcontractor->requests()->exists()) {
            $subcontractor->forceFill(['archived_at' => now()])->save();

            return back()->with('flash', __(':name is uit de pool gehaald. De eerdere uitvragen blijven bewaard; terugzetten kan onderaan de lijst.', ['name' => $subcontractor->name]));
        }
        $subcontractor->delete();

        return back()->with('flash', __('Bedrijf uit de pool verwijderd.'));
    }

    /** Een uit de pool gehaald bedrijf weer meenemen. */
    public function restoreSubcontractor(Subcontractor $subcontractor): RedirectResponse
    {
        $subcontractor->forceFill(['archived_at' => null])->save();

        return back()->with('flash', __(':name staat weer in de pool.', ['name' => $subcontractor->name]));
    }

    /**
     * Meerdere bedrijven tegelijk: één per regel, velden gescheiden door een
     * puntkomma — naam; e-mail; telefoon; plaats; werkpakketten (komma's).
     */
    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate(['lines' => ['required', 'string', 'max:20000']]);
        [$added, $skipped] = $this->service->importLines(auth()->user()->company, $data['lines']);

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
