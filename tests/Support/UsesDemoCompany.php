<?php

namespace Tests\Support;

use App\Models\User;
use App\Services\DemoDataBuilder;

/**
 * Een complete, realistische administratie voor tests: de demo-bouwer maakt
 * klanten, producten, facturen (in alle statussen), offertes, uren enz.
 * Standaard zetten we is_demo uit, zodat de app zich als een echte
 * administratie gedraagt (mail via de gewone mailer, geen demo-beperkingen).
 */
trait UsesDemoCompany
{
    protected function demoUser(bool $realCompany = true): User
    {
        $user = app(DemoDataBuilder::class)->build();

        if ($realCompany) {
            $user->company->forceFill(['is_demo' => false])->save();
        }

        return $user->fresh();
    }

    /** Status < 400 = de pagina rendert (200) of stuurt netjes door (3xx). */
    protected function assertRenders(string $url, string $label = ''): void
    {
        $response = $this->get($url);
        $this->assertTrue(
            $response->status() < 400,
            sprintf('%s gaf status %d', $label ?: $url, $response->status())
        );
    }
}
