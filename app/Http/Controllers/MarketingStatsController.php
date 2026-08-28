<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PageView;
use Illuminate\Http\Request;

/**
 * Intern marketingdashboard op /marketing-inzichten: bezoekers, herkomst,
 * populaire pagina's en de funnel (bezoek → demo → registratie).
 *
 * Alleen zichtbaar voor de eigenaar: e-mailadressen uit MARKETING_STATS_EMAILS
 * of — zolang die variabele leeg is — de gebruiker met id 1.
 */
class MarketingStatsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($this->mayView($user), 403);

        $days = 30;
        $from = now()->subDays($days - 1)->toDateString();

        $perDay = PageView::query()
            ->where('viewed_on', '>=', $from)
            ->selectRaw('viewed_on, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors')
            ->groupBy('viewed_on')
            ->get()
            ->keyBy(fn ($row) => $row->viewed_on->toDateString());

        // Doorlopende reeks van 30 dagen, ook voor dagen zonder bezoek.
        $series = collect(range($days - 1, 0))->map(function ($ago) use ($perDay) {
            $date = now()->subDays($ago)->toDateString();
            $row = $perDay->get($date);

            return [
                'date' => $date,
                'views' => (int) ($row->views ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ];
        });

        $topPages = PageView::query()
            ->where('viewed_on', '>=', $from)
            ->selectRaw('path, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(12)
            ->get();

        $topReferrers = PageView::query()
            ->where('viewed_on', '>=', $from)
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host, COUNT(*) AS views')
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $topSources = PageView::query()
            ->where('viewed_on', '>=', $from)
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, COUNT(*) AS views')
            ->groupBy('utm_source')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $fromMoment = now()->subDays($days - 1)->startOfDay();
        $registrations = Company::where('is_demo', false)->where('created_at', '>=', $fromMoment)->count();
        $demoStarts = Company::where('is_demo', true)->where('created_at', '>=', $fromMoment)->count();

        return view('marketing.inzichten', [
            'series' => $series,
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
            'topSources' => $topSources,
            'totals' => [
                'views' => $series->sum('views'),
                'visitors' => $series->sum('visitors'),
                'registrations' => $registrations,
                'demo_starts' => $demoStarts,
            ],
            'days' => $days,
        ]);
    }

    private function mayView(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        $allowed = collect(explode(',', (string) config('services.marketing_stats.emails')))
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter();

        if ($allowed->isNotEmpty()) {
            return $allowed->contains(mb_strtolower($user->email));
        }

        return $user->id === \App\Support\OwnerAccess::owner()?->id;
    }
}
