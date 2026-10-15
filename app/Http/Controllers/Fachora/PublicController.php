<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ServiceReview;
use App\Services\Fachora\GeocodeService;
use App\Support\Fachora;
use App\Support\Market;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Openbare pagina's met inhoud uit de database: het profiel van een bedrijf
 * (/firma/{slug}) en de overzichten per categorie en per stad (/uslugi/…).
 * Blade, want dit is de SEO-kant van Fachora.
 */
class PublicController extends Controller
{
    public function provider(string $slug): View
    {
        $provider = Provider::query()->where('slug', $slug)->whereNotIn('status', ['blocked'])->firstOrFail();
        $reviews = ServiceReview::query()->where('provider_id', $provider->id)->where('published', true)->with('job')->latest()->limit(20)->get();

        return view(Market::view('fachora.firma'), [
            'provider' => $provider,
            'summary' => $provider->publicSummary(),
            'reviews' => $reviews,
            'categories' => array_map(fn ($c) => ['key' => $c, 'name' => Fachora::categoryName($c)], $provider->categories()),
            'claimUrl' => route('fachora.claim', ['firma' => $provider->slug]),
            'intakeUrl' => route('fachora.intake', ['kategoria' => $provider->categories()[0] ?? null, 'miasto' => $provider->city]),
        ]);
    }

    public function category(string $category, ?string $city = null): View
    {
        abort_unless(Fachora::hasCategory($category), 404);
        $cityName = $city ? Str::title(str_replace('-', ' ', $city)) : null;

        $query = Provider::query()->verified()->inCategory($category)->orderByDesc('rating_avg')->orderByDesc('jobs_done');
        if ($cityName) {
            $norm = GeocodeService::normalise($cityName);
            $query->whereRaw('LOWER(city) = ?', [mb_strtolower($cityName)]);
            // Zonder exacte naamtreffer: alles binnen 30 km van het stadsmiddelpunt.
            if ((clone $query)->doesntExist() && ($center = app(GeocodeService::class)->fallback($norm))) {
                $query = Provider::query()->verified()->inCategory($category)->whereNotNull('lat')
                    ->whereBetween('lat', [$center['lat'] - 0.3, $center['lat'] + 0.3])->whereBetween('lng', [$center['lng'] - 0.45, $center['lng'] + 0.45])
                    ->orderByDesc('rating_avg');
            }
        }
        $providers = $query->limit(60)->get();

        // Steden met Verified bedrijven in deze categorie, voor de interne links.
        $cities = Provider::query()->verified()->inCategory($category)->whereNotNull('city')
            ->selectRaw('city, COUNT(*) as n')->groupBy('city')->orderByDesc('n')->limit(30)->pluck('n', 'city')
            ->map(fn ($n, $name) => ['name' => $name, 'slug' => Str::slug(Str::ascii((string) $name)), 'n' => (int) $n])->values();

        return view(Market::view('fachora.kategoria'), [
            'category' => $category,
            'categoryName' => Fachora::categoryName($category),
            'examples' => (string) config("fachora.categories.{$category}.examples." . (app()->getLocale() === 'en' ? 'en' : 'pl'), ''),
            'city' => $cityName,
            'citySlug' => $city,
            'providers' => $providers,
            'cities' => $cities,
            'allCategories' => Fachora::categoryList(),
            'intakeUrl' => route('fachora.intake', array_filter(['kategoria' => $category, 'miasto' => $cityName])),
        ]);
    }
}
