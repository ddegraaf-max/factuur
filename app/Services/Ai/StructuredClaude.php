<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;

/**
 * Gedeelde aanroep van Claude met een JSON-schema als uitvoer (structured
 * output). Vertaalt API-fouten naar nette Nederlandse meldingen
 * (DomainException) — dezelfde afhandeling als de bon- en huisstijlherkenning.
 */
class StructuredClaude
{
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks  Content-blokken vóór de prompt (bijv. een afbeelding).
     * @return array<string, mixed>
     *
     * @throws \DomainException
     */
    public function json(string $prompt, array $schema, int $maxTokens = 4000, string $effort = 'medium', array $blocks = [], string $label = 'AI'): array
    {
        if (! $this->enabled()) {
            throw new \DomainException('De AI-koppeling is niet geconfigureerd.');
        }
        set_time_limit(180);

        $client = new Client(apiKey: config('services.anthropic.key'));
        $model = (string) config('services.anthropic.model');
        $params = [
            'model' => $model,
            'maxTokens' => $maxTokens,
            'outputConfig' => ['format' => ['type' => 'json_schema', 'schema' => $schema], 'effort' => $effort],
            'messages' => [['role' => 'user', 'content' => array_merge($blocks, [['type' => 'text', 'text' => $prompt]])]],
            'requestOptions' => ['timeout' => 150],
        ];
        // De fallbacks-parameter bestaat alleen op Opus 5/Fable — andere modellen geven er een 400 op.
        if (str_contains($model, 'opus-5') || str_contains($model, 'fable') || str_contains($model, 'mythos')) {
            $params['fallbacks'] = 'default';
            $params['betas'] = ['server-side-fallback-2026-07-01'];
        }

        try {
            $message = $client->beta->messages->create(...$params);
        } catch (\Anthropic\Core\Exceptions\AuthenticationException $e) {
            Log::error("{$label}: ongeldige Anthropic API-key", ['error' => $e->getMessage()]);
            throw new \DomainException('De AI-koppeling is verkeerd geconfigureerd (ongeldige API-key).');
        } catch (\Anthropic\Core\Exceptions\RateLimitException) {
            throw new \DomainException('De AI-dienst is even druk. Probeer het over een minuut opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            Log::warning("{$label}: API-fout", ['type' => $e->type?->value, 'error' => mb_substr($e->getMessage(), 0, 300)]);
            throw new \DomainException('Het lukte niet door een storing bij de AI-dienst. Probeer het zo opnieuw.');
        } catch (\Anthropic\Core\Exceptions\APIConnectionException) {
            throw new \DomainException('Geen verbinding met de AI-dienst. Controleer de internetverbinding en probeer het opnieuw.');
        }

        if ($message->stopReason === 'refusal') {
            throw new \DomainException('De AI wilde deze opdracht niet uitvoeren. Pas je antwoorden aan en probeer het opnieuw.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new \DomainException('Het antwoord werd te lang. Probeer het opnieuw met kortere antwoorden.');
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                if (is_array($json)) {
                    return $json;
                }
            }
        }
        Log::warning("{$label}: onleesbaar antwoord van het model");
        throw new \DomainException('Het antwoord van de AI was onleesbaar. Probeer het opnieuw.');
    }
}
