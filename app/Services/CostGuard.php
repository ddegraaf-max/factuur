<?php

namespace App\Services;

use App\Support\OwnerAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Kostenbewaker voor betaalde koppelingen: KvK, AI, Peppol, sms op kosten van
 * het platform, post (Pingen) en het CCBR.
 *
 * ── Hoe je hem gebruikt ───────────────────────────────────────────────────
 *
 * Vlak vóór de aanroep naar buiten: `app(CostGuard::class)->spend('kvk', $teamId)`.
 * Is de dienst dicht (noodstop of plafond), dan komt er een CostCapReached met
 * een vriendelijke melding zonder bedragen, en gaat er niets naar buiten. Een
 * nieuwe betaalde functie hoort hier ook doorheen; zie config/kostenplafond.php.
 *
 * ── Hoe hij telt ──────────────────────────────────────────────────────────
 *
 * Per dienst drie tellers: vandaag over het hele platform, vandaag per team en
 * deze maand in eurocenten. Ophogen gebeurt in de database met één UPDATE …
 * WHERE calls < plafond per teller, alle drie in één transactie: twee processen
 * tegelijk gaan zo nooit samen over het plafond, en een geweigerde aanroep telt
 * nergens mee.
 *
 * Valt de database weg, dan telt hij door in een bestand op de server (met een
 * slot, dus gedeeld door alle processen daar), en lukt ook dat niet, dan in het
 * werkgeheugen. De plafonds blijven in alle drie gevallen gelden: een storing
 * heft het plafond nooit op.
 */
class CostGuard
{
    /** @var array<string, array{0: int, 1: int}> Laatste terugval: "dienst|bereik|periode" => [aanroepen, centen]. */
    private static array $memory = [];

    /** @var array<string, int> Minuuttellers als de cache niet werkt. */
    private static array $memoryMinutes = [];

    /** @var array<string, bool> Alarmen die in dit proces al zijn verstuurd (als de database niet werkt). */
    private static array $memoryAlarms = [];

    /** Het team (de administratie) van de ingelogde gebruiker, als die er is. */
    public static function currentTeam(): ?int
    {
        try {
            $id = auth()->user()?->company_id;

            return $id ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Alleen voor tests: vergeet de tellers in het werkgeheugen. */
    public static function flushMemory(): void
    {
        self::$memory = self::$memoryMinutes = self::$memoryAlarms = [];
    }

    /** @return list<string> */
    public function services(): array
    {
        return array_keys((array) config('kostenplafond.diensten', []));
    }

    /**
     * De plafonds van een dienst, na het lezen van env. Null = geen plafond van
     * die soort; 0 = dicht. 'maand' is in eurocenten.
     *
     * @return array{dag: ?int, team: ?int, maand: ?int, minuut: ?int, prijs: int}
     */
    public function limits(string $service): array
    {
        $cfg = $this->config($service);

        return [
            'dag' => $this->number($cfg['dag'] ?? null),
            'team' => $this->number($cfg['team'] ?? null),
            'maand' => $this->number($cfg['maand'] ?? null, euros: true),
            'minuut' => $this->number($cfg['minuut'] ?? null),
            'prijs' => max(0, (int) ($cfg['prijs'] ?? 0)),
        ];
    }

    /** Staat de noodstop aan, voor alles of voor deze dienst? */
    public function stopped(string $service): bool
    {
        return $this->truthy(config('kostenplafond.noodstop'))
            || $this->truthy($this->config($service)['noodstop'] ?? null);
    }

    /** De melding voor de gebruiker als deze dienst dicht is. */
    public function message(string $service): string
    {
        return __((string) ($this->config($service)['melding'] ?? 'Deze functie is nu even niet beschikbaar. Probeer het later opnieuw.'));
    }

    /**
     * Reserveert één aanroep. Gooit CostCapReached als de dienst dicht is.
     *
     * @param  int|string|null  $team  Id van de administratie, of een naam voor een publiek onderdeel ('fachora').
     * @param  int|null  $cents  Wat deze aanroep kost (standaard de prijs uit de config).
     * @return int De gereserveerde eurocenten (voor settle()).
     *
     * @throws CostCapReached
     */
    public function spend(string $service, int|string|null $team = null, ?int $cents = null): int
    {
        $limits = $this->limits($service);
        $cents = max(0, $cents ?? $limits['prijs']);

        if ($this->stopped($service)) {
            $this->refuse($service, 'noodstop', $team);
        }
        foreach (['dag', 'maand', 'minuut'] as $kind) {
            if ($limits[$kind] === 0) {
                $this->refuse($service, 'dicht', $team);
            }
        }
        if ($team !== null && $limits['team'] === 0) {
            $this->refuse($service, 'dicht', $team);
        }

        if ($limits['minuut'] !== null && $this->minuteCount($service) > $limits['minuut']) {
            $this->refuse($service, 'minuut', $team);
        }

        $buckets = $this->buckets($service, $team, $limits);
        [$refused, $dayCalls, $monthCents] = $this->reserve($service, $buckets, $cents);

        if ($refused !== null) {
            if ($refused === 'dag') {
                $this->alarm($service, 'dag100', $limits);
            } elseif ($refused === 'maand') {
                $this->alarm($service, 'maand100', $limits);
            }
            $this->refuse($service, $refused, $team);
        }

        $share = (float) config('kostenplafond.alarm_bij', 0.8);
        if ($limits['dag'] !== null) {
            if ($dayCalls >= $limits['dag']) {
                $this->alarm($service, 'dag100', $limits);
            } elseif ($dayCalls >= (int) ceil($limits['dag'] * $share)) {
                $this->alarm($service, 'dag80', $limits);
            }
        }
        if ($limits['maand'] !== null && $monthCents >= (int) ceil($limits['maand'] * $share)) {
            $this->alarm($service, 'maand80', $limits);
        }

        return $cents;
    }

    /**
     * Verrekent het werkelijke bedrag van een aanroep met wat er is gereserveerd
     * (AI: per token). Mag nooit een fout geven; het plafond is al bewaakt.
     */
    public function settle(string $service, int|string|null $team, int $reserved, int $actual): void
    {
        $delta = max(0, $actual) - $reserved;
        if ($delta === 0) {
            return;
        }
        $day = now()->format('Y-m-d');
        $month = now()->format('Y-m');
        $scopes = [['platform', $day], ['platform', $month]];
        if ($team !== null) {
            $scopes[] = ['team:'.$team, $day];
        }

        try {
            foreach ($scopes as [$scope, $period]) {
                DB::table('cost_counters')->where(['service' => $service, 'scope' => $scope, 'period' => $period])
                    ->update(['cents' => DB::raw('cents + '.(int) $delta), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Kostenplafond: verrekenen mislukt', ['service' => $service, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Verbruik van vandaag en deze maand per dienst, voor het eigenaarsscherm.
     *
     * @return array{storing: bool, noodstop: bool, diensten: list<array<string, mixed>>}
     */
    public function overview(): array
    {
        $day = now()->format('Y-m-d');
        $month = now()->format('Y-m');
        $rows = collect();
        $storing = false;
        try {
            $rows = DB::table('cost_counters')
                ->whereIn('period', [$day, $month])
                ->where(fn ($q) => $q->where('scope', 'platform')->orWhere('scope', 'like', 'team:%'))
                ->get();
        } catch (\Throwable) {
            $storing = true;
        }

        $services = [];
        foreach ($this->services() as $service) {
            $limits = $this->limits($service);
            $mine = $rows->where('service', $service);
            $today = $mine->where('scope', 'platform')->firstWhere('period', $day);
            $thisMonth = $mine->where('scope', 'platform')->firstWhere('period', $month);
            $teams = $mine->where('period', $day)->filter(fn ($r) => str_starts_with($r->scope, 'team:'));

            $services[] = [
                'key' => $service,
                'name' => (string) ($this->config($service)['naam'] ?? $service),
                'stopped' => $this->stopped($service),
                'today_calls' => (int) ($today->calls ?? 0),
                'today_cents' => (int) ($today->cents ?? 0),
                'month_calls' => (int) ($thisMonth->calls ?? 0),
                'month_cents' => (int) ($thisMonth->cents ?? 0),
                'teams_today' => $teams->count(),
                'teams_at_cap' => $limits['team'] !== null ? $teams->filter(fn ($r) => $r->calls >= $limits['team'])->count() : 0,
                'limits' => $limits,
            ];
        }

        return [
            'storing' => $storing,
            'noodstop' => $this->truthy(config('kostenplafond.noodstop')),
            'diensten' => $services,
        ];
    }

    /* ===================== Tellen ===================== */

    /**
     * @return list<array{scope: string, period: string, kind: string, limit: ?int, reason: string}>
     */
    private function buckets(string $service, int|string|null $team, array $limits): array
    {
        $day = now()->format('Y-m-d');
        $buckets = [['scope' => 'platform', 'period' => $day, 'kind' => 'calls', 'limit' => $limits['dag'], 'reason' => 'dag']];
        if ($team !== null) {
            $buckets[] = ['scope' => 'team:'.$team, 'period' => $day, 'kind' => 'calls', 'limit' => $limits['team'], 'reason' => 'team'];
        }
        $buckets[] = ['scope' => 'platform', 'period' => now()->format('Y-m'), 'kind' => 'cents', 'limit' => $limits['maand'], 'reason' => 'maand'];

        return $buckets;
    }

    /**
     * Hoogt alle tellers op, of geen enkele.
     *
     * @return array{0: ?string, 1: int, 2: int} [reden van weigeren of null, aanroepen vandaag, centen deze maand]
     */
    private function reserve(string $service, array $buckets, int $cents): array
    {
        try {
            return $this->reserveInDatabase($service, $buckets, $cents);
        } catch (\Throwable $e) {
            Log::error('Kostenplafond: database-teller werkt niet, terugval op bestand', ['service' => $service, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        }

        try {
            return $this->reserveInFile($service, $buckets, $cents);
        } catch (\Throwable $e) {
            Log::error('Kostenplafond: ook het bestand werkt niet, terugval op werkgeheugen', ['service' => $service, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        }

        return $this->reserveInArray(self::$memory, $service, $buckets, $cents);
    }

    private function reserveInDatabase(string $service, array $buckets, int $cents): array
    {
        $refused = null;
        try {
            $values = DB::transaction(function () use ($service, $buckets, $cents, &$refused) {
                $now = now();
                $out = [];
                foreach ($buckets as $b) {
                    $key = ['service' => $service, 'scope' => $b['scope'], 'period' => $b['period']];
                    DB::table('cost_counters')->insertOrIgnore($key + ['calls' => 0, 'cents' => 0, 'created_at' => $now, 'updated_at' => $now]);

                    $query = DB::table('cost_counters')->where($key);
                    if ($b['limit'] !== null) {
                        $b['kind'] === 'calls'
                            ? $query->where('calls', '<', $b['limit'])
                            : $query->whereRaw('cents + ? <= ?', [$cents, $b['limit']]);
                    }
                    $updated = $query->update(['calls' => DB::raw('calls + 1'), 'cents' => DB::raw('cents + '.$cents), 'updated_at' => $now]);
                    if ($updated === 0) {
                        $refused = $b['reason'];
                        throw new CostGuardRollback;
                    }
                    $out[$b['reason']] = DB::table('cost_counters')->where($key)->first(['calls', 'cents']);
                }

                return $out;
            }, 3);
        } catch (CostGuardRollback) {
            return [$refused, 0, 0];
        }

        return [null, (int) ($values['dag']->calls ?? 0), (int) ($values['maand']->cents ?? 0)];
    }

    private function reserveInFile(string $service, array $buckets, int $cents): array
    {
        $path = (string) config('kostenplafond.bestand');
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            throw new \RuntimeException("Kan {$path} niet openen");
        }
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException("Geen slot op {$path}");
            }
            $raw = stream_get_contents($handle);
            $store = json_decode($raw ?: '[]', true);
            $store = is_array($store) ? $store : [];

            // Alleen vandaag en deze maand bewaren.
            $keep = [now()->format('Y-m-d'), now()->format('Y-m')];
            $store = array_filter($store, fn ($v, $k) => in_array(substr((string) $k, strrpos((string) $k, '|') + 1), $keep, true), ARRAY_FILTER_USE_BOTH);

            $result = $this->reserveInArray($store, $service, $buckets, $cents);

            if (ftruncate($handle, 0) === false || rewind($handle) === false || fwrite($handle, (string) json_encode($store)) === false) {
                throw new \RuntimeException("Kan {$path} niet schrijven");
            }
            fflush($handle);

            return $result;
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /** @param  array<string, array{0: int, 1: int}>  $store */
    private function reserveInArray(array &$store, string $service, array $buckets, int $cents): array
    {
        foreach ($buckets as $b) {
            [$calls, $spent] = $store["{$service}|{$b['scope']}|{$b['period']}"] ?? [0, 0];
            if ($b['limit'] !== null && ($b['kind'] === 'calls' ? $calls >= $b['limit'] : $spent + $cents > $b['limit'])) {
                return [$b['reason'], 0, 0];
            }
        }
        $values = [];
        foreach ($buckets as $b) {
            $key = "{$service}|{$b['scope']}|{$b['period']}";
            [$calls, $spent] = $store[$key] ?? [0, 0];
            $store[$key] = [$calls + 1, $spent + $cents];
            $values[$b['reason']] = $store[$key];
        }

        return [null, (int) ($values['dag'][0] ?? 0), (int) ($values['maand'][1] ?? 0)];
    }

    /** Telt deze aanroep mee in de huidige minuut en geeft het aantal terug. */
    private function minuteCount(string $service): int
    {
        $key = "kostenplafond:{$service}:minuut:".now()->format('YmdHi');
        try {
            Cache::add($key, 0, 120);
            $count = Cache::increment($key);
            if (is_int($count) && $count > 0) {
                return $count;
            }
        } catch (\Throwable $e) {
            Log::warning('Kostenplafond: minuutteller in de cache werkt niet', ['service' => $service, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        }

        self::$memoryMinutes = array_filter(self::$memoryMinutes, fn ($v, $k) => str_ends_with($k, now()->format('YmdHi')), ARRAY_FILTER_USE_BOTH);

        return self::$memoryMinutes[$key] = (self::$memoryMinutes[$key] ?? 0) + 1;
    }

    /* ===================== Weigeren en alarm ===================== */

    /** @throws CostCapReached */
    private function refuse(string $service, string $reason, int|string|null $team): never
    {
        Log::info('Kostenplafond: aanroep geweigerd', ['service' => $service, 'reason' => $reason, 'team' => $team]);

        throw new CostCapReached($service, $reason, $this->message($service));
    }

    /**
     * Eén mail per dienst per soort per dag naar het beheeradres (dezelfde
     * adressen als de foutmeldingen: OwnerAccess::emails()).
     */
    private function alarm(string $service, string $kind, array $limits): void
    {
        $day = now()->format('Y-m-d');
        try {
            $first = DB::table('cost_counters')->insertOrIgnore([
                'service' => $service, 'scope' => 'alarm:'.$kind, 'period' => $day,
                'calls' => 1, 'cents' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]) > 0;
        } catch (\Throwable) {
            $memoryKey = "{$service}|{$kind}|{$day}";
            try {
                $first = Cache::add('kostenplafond:alarm:'.$memoryKey, true, now()->endOfDay());
            } catch (\Throwable) {
                $first = ! isset(self::$memoryAlarms[$memoryKey]);
            }
            self::$memoryAlarms[$memoryKey] = true;
        }
        if (! $first) {
            return;
        }

        try {
            $to = OwnerAccess::emails();
            if (! $to) {
                return;
            }
            $usage = collect($this->overview()['diensten'])->firstWhere('key', $service) ?? [];
            $name = (string) ($this->config($service)['naam'] ?? $service);
            $env = strtoupper($service === 'kvk_zoeken' ? 'kvk' : $service);
            $money = fn (?int $c) => $c === null ? 'geen' : '€ '.number_format($c / 100, 2, ',', '.');

            $subject = match ($kind) {
                'dag80' => "Kostenplafond: {$name} op 80% van het dagplafond",
                'dag100' => "Kostenplafond: {$name} heeft het dagplafond bereikt",
                'maand80' => "Kostenplafond: {$name} op 80% van het maandbudget",
                default => "Kostenplafond: {$name} heeft het maandbudget bereikt",
            };
            $body = implode("\n", [
                $subject.'.',
                '',
                'Vandaag: '.($usage['today_calls'] ?? '?').' aanroepen'.($limits['dag'] !== null ? " van de {$limits['dag']}" : '').', '.$money($usage['today_cents'] ?? 0).'.',
                'Deze maand: '.($usage['month_calls'] ?? '?').' aanroepen, '.$money($usage['month_cents'] ?? 0).' van het budget ('.$money($limits['maand']).').',
                '',
                str_ends_with($kind, '100')
                    ? 'De koppeling staat nu dicht. Gebruikers krijgen een vriendelijke melding en vullen de gegevens zelf in; de rest van de app werkt gewoon door.'
                    : 'Er gebeurt nog niets: de koppeling werkt door tot het plafond.',
                '',
                "Ruimer zetten kan in Railway met {$env}_DAGLIMIET, {$env}_TEAMLIMIET of {$env}_MAANDBUDGET (in euro's). Alles direct dicht: NOODSTOP_{$env}=1.",
                'Overzicht: '.route('owner.companies.index'),
                '',
                'Je krijgt deze melding hooguit één keer per dag.',
            ]);

            foreach ($to as $email) {
                Mail::raw($body, fn ($mail) => $mail->to($email)->subject($subject));
            }
        } catch (\Throwable $e) {
            Log::warning('Kostenplafond: alarmmail niet verstuurd', ['service' => $service, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }

    /* ===================== Config lezen ===================== */

    private function config(string $service): array
    {
        $cfg = config("kostenplafond.diensten.{$service}");
        if (! is_array($cfg)) {
            throw new \InvalidArgumentException("Onbekende betaalde dienst: {$service}");
        }

        return $cfg;
    }

    /**
     * Een plafond uit [env-waarde, standaard]. Leeg of onleesbaar = standaard.
     */
    private function number(mixed $pair, bool $euros = false): ?int
    {
        [$raw, $default] = is_array($pair) ? array_pad(array_values($pair), 2, null) : [$pair, null];

        $value = $default;
        if (is_int($raw) || is_float($raw)) {
            $value = $raw >= 0 ? $raw : $default;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $text = str_replace(',', '.', trim($raw));
            $value = is_numeric($text) && (float) $text >= 0 ? (float) $text : $default;
        }
        if ($value === null) {
            return null;
        }

        return $euros ? (int) round(((float) $value) * 100) : (int) floor((float) $value);
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'ja', 'aan', 'on', 'yes'], true);
    }
}
