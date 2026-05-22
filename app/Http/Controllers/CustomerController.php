<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->input('q');
        $type = $request->input('type');

        $customers = Customer::query()
            ->withCount('invoices')
            ->when($q, fn ($qb) => $qb->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('city', 'like', "%{$q}%")
                  ->orWhere('kvk_number', 'like', "%{$q}%");
            }))
            ->when($type && $type !== 'all', fn ($qb) => $qb->where('type', $type))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        // Augment with calculated outstanding (avoids N+1 by fetching once)
        $customers->getCollection()->transform(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'initials' => $c->initials,
                'type' => $c->type,
                'kvk_number' => $c->kvk_number,
                'city' => $c->city,
                'email' => $c->email,
                'invoices_count' => $c->invoices_count,
                'outstanding' => (float) $c->outstanding_total,
            ];
        });

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $q, 'type' => $type],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $customer = Customer::create($data);
        return redirect()->route('customers.index')->with('flash', "Klant {$customer->name} aangemaakt.");
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Form', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));
        return redirect()->route('customers.index')->with('flash', 'Klant bijgewerkt.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->invoices()->exists()) {
            return back()->withErrors(['delete' => 'Kan klant niet verwijderen: er bestaan facturen.']);
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('flash', 'Klant verwijderd.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:business,consumer'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'kvk_number' => ['nullable', 'string', 'max:20'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
