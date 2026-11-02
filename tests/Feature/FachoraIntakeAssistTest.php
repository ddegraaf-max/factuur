<?php

namespace Tests\Feature;

use App\Services\Ai\StructuredClaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * AI-hulp bij de omschrijving in het aanvraagformulier (1.93): zonder AI-sleutel bestaat de
 * knop en de route niet; met sleutel geeft /zlecenie/asystent een voorstel in de eigen taal,
 * een Poolse versie en vragen terug, zonder iets op te slaan.
 */
class FachoraIntakeAssistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.stripe.secret' => null]);
    }

    public function test_without_an_ai_key_there_is_no_assistant(): void
    {
        config(['services.anthropic.key' => null]);

        $this->get('/zlecenie/nowe')->assertOk()->assertInertia(fn ($p) => $p->where('assistant', false));
        $this->postJson('/zlecenie/asystent', ['description' => 'The boiler stopped working this morning'])->assertNotFound();
    }

    public function test_the_assistant_returns_a_proposal_in_the_customers_language_and_a_polish_version(): void
    {
        config(['services.anthropic.key' => 'sk-ant-test']);
        $fake = Mockery::mock(StructuredClaude::class);
        $fake->shouldReceive('enabled')->andReturn(true);
        $fake->shouldReceive('json')->once()
            ->withArgs(fn (string $prompt, array $schema) => str_contains($prompt, 'The boiler stopped working') && str_contains($prompt, 'Ogrzewanie') && in_array('description_pl', $schema['required'], true))
            ->andReturn([
                'language' => 'en',
                'title' => 'Boiler not working',
                'title_pl' => 'Kocioł nie działa',
                'description' => 'The gas boiler in the kitchen stopped working this morning; there is no hot water and the display shows an error.',
                'description_pl' => 'Kocioł gazowy w kuchni przestał działać dziś rano; nie ma ciepłej wody, a wyświetlacz pokazuje błąd.',
                'questions' => ['Which brand and model is the boiler?', 'Does the heating still work?', ''],
            ]);
        $this->app->instance(StructuredClaude::class, $fake);

        $this->get('/zlecenie/nowe')->assertOk()->assertInertia(fn ($p) => $p->where('assistant', true));

        $this->postJson('/zlecenie/asystent', ['description' => 'The boiler stopped working this morning', 'category' => 'ogrzewanie', 'city' => 'Warszawa', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('language', 'en')
            ->assertJsonPath('title', 'Boiler not working')
            ->assertJsonPath('description_pl', 'Kocioł gazowy w kuchni przestał działać dziś rano; nie ma ciepłej wody, a wyświetlacz pokazuje błąd.')
            ->assertJsonCount(2, 'questions');

        $this->assertDatabaseCount('service_jobs', 0);
    }

    public function test_a_too_short_draft_is_rejected_and_an_ai_outage_gives_a_clean_error(): void
    {
        config(['services.anthropic.key' => 'sk-ant-test']);
        $fake = Mockery::mock(StructuredClaude::class);
        $fake->shouldReceive('enabled')->andReturn(true);
        $fake->shouldReceive('json')->andThrow(new \DomainException('De AI-dienst is even druk. Probeer het over een minuut opnieuw.'));
        $this->app->instance(StructuredClaude::class, $fake);

        $this->postJson('/zlecenie/asystent', ['description' => 'kort'])->assertStatus(422)->assertJsonValidationErrors(['description']);
        $this->postJson('/zlecenie/asystent', ['description' => 'Lekkende kraan in de keuken sinds gisteren'])->assertStatus(503)->assertJsonPath('message', 'De AI-dienst is even druk. Probeer het over een minuut opnieuw.');
    }
}
