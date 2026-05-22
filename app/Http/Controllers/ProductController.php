<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\VatCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->input('q');
        $products = Product::query()
            ->when($q, fn ($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => ['q' => $q],
            'vat_rates' => VatCalculator::availableRates(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'product' => null,
            'vat_rates' => VatCalculator::availableRates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));
        return redirect()->route('products.index')->with('flash', 'Product aangemaakt.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'product' => $product,
            'vat_rates' => VatCalculator::availableRates(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));
        return redirect()->route('products.index')->with('flash', 'Product bijgewerkt.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('products.index')->with('flash', 'Product verwijderd.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'in:0,9,21'],
            'is_active' => ['boolean'],
        ]);
    }
}
