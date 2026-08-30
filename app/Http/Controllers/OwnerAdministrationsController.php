<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyPurger;
use App\Support\OwnerAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Eigenaar: overzicht van alle administraties en opruimen van (test)accounts. */
class OwnerAdministrationsController extends Controller
{
    public function index()
    {
        $companies = Company::withoutGlobalScope('company')->orderBy('id')->get()->map(function (Company $c) {
            $owner = $c->users()->orderBy('id')->first();
            $counts = fn ($table) => \Illuminate\Support\Facades\DB::table($table)->where('company_id', $c->id)->count();

            return [
                'id' => $c->id,
                'name' => $c->name,
                'owner_email' => $owner?->email,
                'users' => $c->users()->count(),
                'created_label' => $c->created_at?->translatedFormat('j M Y'),
                'last_seen_label' => $owner?->updated_at?->translatedFormat('j M Y'),
                'access' => $c->accessStatus(),
                'trial_ends_label' => $c->trial_ends_at?->translatedFormat('j M Y'),
                'is_demo' => (bool) $c->is_demo,
                'is_exempt' => (bool) $c->is_exempt,
                'invoices' => $counts('invoices'),
                'quotes' => $counts('quotes'),
                'customers' => $counts('customers'),
                'deletable' => ! $c->is_exempt && $c->id !== auth()->user()->company_id,
            ];
        });

        return Inertia::render('Stats/Administraties', ['companies' => $companies]);
    }

    public function destroy(Request $request, int $company, CompanyPurger $purger)
    {
        $target = Company::withoutGlobalScope('company')->findOrFail($company);

        abort_if($target->is_exempt || $target->id === $request->user()->company_id, 403, __('Deze administratie kan niet worden verwijderd.'));

        $request->validate(['confirm' => ['required', 'string']]);
        if (mb_strtolower(trim($request->input('confirm'))) !== mb_strtolower($target->name)) {
            return back()->with('error', __('De naam komt niet overeen — er is niets verwijderd.'));
        }

        $name = $target->name;
        $purger->purge($target);

        \Illuminate\Support\Facades\Log::warning('Administratie verwijderd door eigenaar', ['company' => $company, 'name' => $name, 'by' => $request->user()->id]);

        \App\Support\Audit::log('purged', null, __('Administratie ":name" (#:id) volledig verwijderd door de eigenaar', ['name' => $name, 'id' => $company]), [], $request->user()->company_id);

        return back()->with('flash', __('Administratie ":name" is volledig verwijderd.', ['name' => $name]));
    }
}
