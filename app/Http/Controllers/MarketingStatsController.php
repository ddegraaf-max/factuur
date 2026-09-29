<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Intern marketingdashboard op /marketing-inzichten: bezoekers, herkomst,
 * populaire pagina's en de trechter (bezoek → demo → registratie).
 *
 * Geteld worden mensen: bezoeken waarvan de browser een teken van leven gaf
 * (PageView::confirm). Alles daarbuiten staat er als 'robots en kale verzoeken'
 * naast, zodat het verschil zichtbaar blijft.
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
        $period = fn () => PageView::query()->where('viewed_on', '>=', $from);
        // Een bezoeker is een hash op een dag; dezelfde persoon telt morgen opnieuw.
        $key = $this->visitorKey();
        $visitors = "COUNT(DISTINCT {$key})";

        $perDay = $period()->views()
            ->selectRaw("viewed_on, {$visitors} AS requests, COUNT(DISTINCT CASE WHEN confirmed_at IS NOT NULL THEN {$key} END) AS people")
            ->groupBy('viewed_on')
            ->get()
            ->keyBy(fn ($row) => $row->viewed_on->toDateString());

        // Doorlopende reeks van 30 dagen, ook voor dagen zonder bezoek.
        $series = collect(range($days - 1, 0))->map(function ($ago) use ($perDay) {
            $date = now()->subDays($ago)->toDateString();
            $row = $perDay->get($date);

            return [
                'date' => $date,
                'requests' => (int) ($row->requests ?? 0),
                'people' => (int) ($row->people ?? 0),
            ];
        });

        $topPages = $period()->views()->human()
            ->selectRaw("path, {$visitors} AS visitors, COUNT(*) AS views")
            ->groupBy('path')
            ->orderByDesc('visitors')
            ->limit(15)
            ->get();

        $sources = $period()->views()->human()
            ->selectRaw("COALESCE(utm_source, referrer_host) AS source, {$visitors} AS visitors")
            ->where(fn ($q) => $q->whereNotNull('referrer_host')->orWhereNotNull('utm_source'))
            ->groupByRaw('COALESCE(utm_source, referrer_host)')
            ->orderByDesc('visitors')
            ->limit(12)
            ->get();

        // Zoekmachines sturen hun naam mee; dat is ook zonder seintje een bruikbaar teken.
        $searchPages = $period()->views()->fromSearch()
            ->selectRaw("path, {$visitors} AS visitors")
            ->groupBy('path')
            ->orderByDesc('visitors')
            ->limit(12)
            ->get();

        $count = fn ($query) => (int) $query->selectRaw("{$visitors} AS n")->value('n');
        $events = fn (string $event) => $count($period()->where('event', $event));

        $fromMoment = now()->subDays($days - 1)->startOfDay();
        $people = $count($period()->views()->human());
        $requests = $count($period()->views());

        $signups = $period()->where('event', PageView::EVENT_REGISTERED)
            ->orderByDesc('id')->limit(20)
            ->get(['viewed_on', 'referrer_host', 'utm_source', 'utm_campaign', 'device']);

        return view('marketing.inzichten', [
            'series' => $series,
            'topPages' => $topPages,
            'sources' => $sources,
            'searchPages' => $searchPages,
            'signups' => $signups,
            'totals' => [
                'people' => $people,
                'robots' => max(0, $requests - $people),
                'search' => $count($period()->views()->fromSearch()),
                'registrations' => Company::where('is_demo', false)->where('created_at', '>=', $fromMoment)->count(),
            ],
            'funnel' => [
                ['label' => 'Bezoekers', 'n' => $people],
                ['label' => 'Demopagina bekeken', 'n' => $count($period()->views()->human()->where('path', '/demo'))],
                ['label' => 'Demo gestart', 'n' => $events(PageView::EVENT_DEMO)],
                ['label' => 'Registratiepagina bekeken', 'n' => $count($period()->views()->human()->where('path', '/register'))],
                ['label' => 'Formulier verstuurd', 'n' => $events(PageView::EVENT_REGISTER_TRIED)],
                ['label' => 'Geregistreerd', 'n' => $events(PageView::EVENT_REGISTERED)],
                ['label' => 'Aanmaning gemaakt (zonder account)', 'n' => $events(PageView::EVENT_DEMAND)],
                ['label' => 'Aanmaning bevestigd', 'n' => $events(PageView::EVENT_DEMAND_CONFIRMED)],
            ],
            'measuredSince' => PageView::query()->views()->human()->min('viewed_on'),
            'days' => $days,
        ]);
    }

    /** Hash en dag samen, in de schrijfwijze van de database. */
    private function visitorKey(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "visitor_hash || '-' || viewed_on"
            : "visitor_hash || '-' || viewed_on::text";
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
