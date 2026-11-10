<?php

namespace App\Services\Ai;

use App\Services\CostCapReached;
use App\Services\CostGuard;

/**
 * Elke aanroep naar Claude gaat hierdoor: eerst een plek reserveren bij de
 * kostenbewaker (dienst 'ai'), daarna het werkelijke bedrag verrekenen op
 * basis van de tokens. Is het plafond bereikt, dan komt er een CostCapReached
 * (een DomainException) en gaat er niets naar Anthropic.
 */
class AiBudget
{
    /** Het team waarvoor aanroepen binnen as() tellen. */
    private static int|string|null $scope = null;

    /**
     * Laat de AI-aanroepen binnen $work meetellen voor dit team, bijvoorbeeld
     * 'fachora' voor het publieke formulier (daar is niemand ingelogd).
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public static function as(int|string $team, callable $work): mixed
    {
        $previous = self::$scope;
        self::$scope = $team;
        try {
            return $work();
        } finally {
            self::$scope = $previous;
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $call  De aanroep naar Anthropic.
     * @param  int|string|null  $team  Administratie, of een naam voor een publiek onderdeel; null = die van de ingelogde gebruiker.
     * @return T
     *
     * @throws CostCapReached
     */
    public static function call(callable $call, int|string|null $team = null): mixed
    {
        $team ??= self::$scope ?? CostGuard::currentTeam();
        $guard = app(CostGuard::class);
        $reserved = $guard->spend('ai', $team);

        $message = $call();

        $guard->settle('ai', $team, $reserved, self::cents($message));

        return $message;
    }

    /** Wat een antwoord ongeveer kostte, in eurocenten (dollar ≈ euro, naar boven afgerond). */
    public static function cents(mixed $message): int
    {
        $usage = is_object($message) ? ($message->usage ?? null) : null;
        if (! is_object($usage)) {
            return (int) config('kostenplafond.diensten.ai.prijs', 8);
        }
        $in = (int) ($usage->inputTokens ?? 0) + (int) ($usage->cacheCreationInputTokens ?? 0) + (int) ($usage->cacheReadInputTokens ?? 0);
        $out = (int) ($usage->outputTokens ?? 0);
        $price = (array) config('kostenplafond.diensten.ai.tokenprijs', ['in' => 5, 'uit' => 25]);

        // Prijs per miljoen tokens in dollars → eurocenten: × 100 / 1.000.000.
        return (int) ceil(($in * (float) ($price['in'] ?? 5) + $out * (float) ($price['uit'] ?? 25)) / 10000);
    }
}
