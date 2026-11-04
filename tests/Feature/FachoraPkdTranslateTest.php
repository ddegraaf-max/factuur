<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ServiceJob;
use App\Services\Ai\StructuredClaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/** 1.100: de Poolse hoofdactiviteit uit CEIDG wordt één keer per unieke naam naar het Engels vertaald en op de Engelse pagina getoond. */
class FachoraPkdTranslateTest extends TestCase
{
    use RefreshDatabase;

    private const PL = 'Wykonywanie instalacji wodno-kanalizacyjnych, cieplnych, gazowych i klimatyzacyjnych';

    private const EN = 'Plumbing, heating, gas and air-conditioning installation';

    private function register(string $name, string $pkdName): Provider
    {
        $slug = Str::slug($name) . '-' . Str::lower(Str::random(4));
        $p = Provider::create([
            'legal_name' => $name, 'display_name' => $name, 'slug' => $slug, 'status' => 'unclaimed', 'source' => 'ceidg',
            'email' => "$slug@example.com", 'city' => 'Płock', 'postal_code' => '09-400', 'lat' => 52.5463, 'lng' => 19.7065,
            'pkd_main' => '43.22.Z', 'pkd_main_name' => $pkdName, 'pkd_checked_at' => now(),
        ]);
        $p->syncCategories(['kominiarz']);

        return $p->refresh();
    }

    public function test_names_are_translated_once_per_unique_name_and_shown_in_english_only_on_the_english_page(): void
    {
        config(['brand.active' => 'fachora', 'services.anthropic.key' => 'sk-ant-test', 'fachora.email_unregistered' => false]);
        $a = $this->register('JJ Instal', self::PL);
        $b = $this->register('GSI Technika', self::PL);
        $c = $this->register('Dach-Pol', 'Wykonywanie konstrukcji i pokryć dachowych');
        $fake = Mockery::mock(StructuredClaude::class);
        $fake->shouldReceive('enabled')->andReturn(true);
        $fake->shouldReceive('json')->once()
            ->withArgs(fn (string $prompt) => str_contains($prompt, self::PL) && str_contains($prompt, 'pokryć dachowych'))
            ->andReturn(['translations' => [['pl' => self::PL, 'en' => self::EN], ['pl' => 'Wykonywanie konstrukcji i pokryć dachowych', 'en' => 'Roofing and roof structures']]]);
        $this->app->instance(StructuredClaude::class, $fake);

        $this->artisan('fachora:pkd-translate')->expectsOutputToContain('voor 3 bedrijven')->assertSuccessful();
        $this->assertSame(self::EN, $a->fresh()->pkd_main_name_en);
        $this->assertSame(self::EN, $b->fresh()->pkd_main_name_en);
        $this->assertSame('Roofing and roof structures', $c->fresh()->pkd_main_name_en);
        $this->artisan('fachora:pkd-translate')->expectsOutputToContain('voor 0 bedrijven')->assertSuccessful(); // niets meer te doen: geen tweede AI-aanroep

        $job = ServiceJob::create([
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(), 'offered_at' => now(),
            'category' => 'kominiarz', 'title' => 'Komin', 'description' => 'Czyszczenie komina.', 'urgency' => 'week', 'language' => 'pl',
            'customer_name' => 'Anna Nowak', 'customer_email' => 'anna@example.com', 'customer_locale' => 'pl', 'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065,
            'selected_providers' => [$a->id],
        ]);
        $this->get(route('fachora.job.show', $job->token))->assertInertia(fn ($page) => $page->where('direct.0.pkd_main_name', self::PL));
        $this->get(route('fachora.job.show', $job->token) . '?lang=en')->assertInertia(fn ($page) => $page->where('direct.0.pkd_main_name', self::EN));
    }

    public function test_without_an_ai_key_nothing_is_translated_and_the_polish_name_stays(): void
    {
        config(['brand.active' => 'fachora', 'services.anthropic.key' => null]);
        $a = $this->register('JJ Instal', self::PL);
        $this->artisan('fachora:pkd-translate')->expectsOutputToContain('voor 0 bedrijven')->assertSuccessful();
        $this->assertNull($a->fresh()->pkd_main_name_en);
        app()->setLocale('en');
        $this->assertSame(self::PL, $a->fresh()->pkdMainName());
    }
}
