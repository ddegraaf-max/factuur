<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Eén bezochte marketingpagina. Privacyvriendelijk: geen IP, geen cookie —
 * alleen een dagelijks wisselende hash om unieke bezoekers per dag te tellen.
 *
 * 'confirmed_at' is gezet zodra de browser een teken van leven gaf (scrollen,
 * tikken, tien seconden kijken): dat is een mens. Een regel met 'event' is geen
 * paginabezoek maar een mijlpaal van dezelfde bezoeker: demo gestart,
 * registratieformulier verstuurd of geregistreerd, met de herkomst van zijn
 * eerste bezoek die dag.
 */
class PageView extends Model
{
    public const EVENT_DEMO = 'demo_started';

    public const EVENT_REGISTER_TRIED = 'register_tried';

    public const EVENT_REGISTERED = 'registered';

    /** Herkomst die we als zoekmachine of AI-assistent rekenen. */
    public const SEARCH_HOSTS = ['google', 'bing', 'duckduckgo', 'ecosia', 'startpage', 'yahoo', 'qwant', 'brave',
        'chatgpt', 'openai', 'perplexity', 'claude', 'gemini', 'copilot'];

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'viewed_on' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /** De hash van deze bezoeker voor vandaag: elke dag anders, niet terug te rekenen. */
    public static function visitorHash(Request $request): string
    {
        return substr(hash('sha256', implode('|', [
            $request->ip(), (string) $request->userAgent(), now()->toDateString(), config('app.key'),
        ])), 0, 32);
    }

    /** Het seintje uit de browser: de bezoeken van vandaag van deze bezoeker zijn van een mens. */
    public static function confirm(Request $request): int
    {
        return static::query()
            ->whereDate('viewed_on', now()->toDateString())
            ->where('visitor_hash', static::visitorHash($request))
            ->whereNull('confirmed_at')
            ->update(['confirmed_at' => now()]);
    }

    /**
     * Leg een mijlpaal vast bij de bezoeker van vandaag, met de herkomst van zijn
     * eerste bezoek. Mag nooit de eigenlijke handeling (demo, registratie) breken.
     */
    public static function milestone(Request $request, string $event): void
    {
        try {
            $hash = static::visitorHash($request);
            $first = static::query()
                ->whereDate('viewed_on', now()->toDateString())
                ->where('visitor_hash', $hash)
                ->whereNull('event')
                ->where(fn ($q) => $q->whereNotNull('referrer_host')->orWhereNotNull('utm_source'))
                ->orderBy('id')
                ->first();

            static::create([
                'viewed_on' => now()->toDateString(),
                'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 190),
                'referrer_host' => $first?->referrer_host,
                'utm_source' => $first?->utm_source,
                'utm_medium' => $first?->utm_medium,
                'utm_campaign' => $first?->utm_campaign,
                'device' => preg_match('/Mobile|Android|iPhone|iPad/i', (string) $request->userAgent()) ? 'mobile' : 'desktop',
                'visitor_hash' => $hash,
                'confirmed_at' => now(),
                'event' => $event,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Mijlpaal niet geregistreerd', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /** Alleen paginabezoeken, geen mijlpalen. */
    public function scopeViews(Builder $query): Builder
    {
        return $query->whereNull('event');
    }

    /** Alleen bezoeken waarvan de browser een teken van leven gaf. */
    public function scopeHuman(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    /** Bezoeken die via een zoekmachine of AI-assistent binnenkwamen. */
    public function scopeFromSearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            foreach (self::SEARCH_HOSTS as $word) {
                $q->orWhere('referrer_host', 'like', "%{$word}%")->orWhere('utm_source', 'like', "%{$word}%");
            }
        });
    }
}
