<?php

namespace Tests\Feature;

use App\Models\AiUsageEvent;
use App\Services\Ai\StructuredClaude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Huisstijl ontwerpen met AI: drie voorstellen uit een paar antwoorden, en het logo-voorstel opslaan. */
class BrandDesignTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function fakeClaude(array $payload): void
    {
        $this->app->instance(StructuredClaude::class, new class($payload) extends StructuredClaude {
            public function __construct(private array $payload) {}
            public function enabled(): bool { return true; }
            public function json(string $prompt, array $schema, int $maxTokens = 4000, string $effort = 'medium', array $blocks = [], string $label = 'AI'): array
            {
                return $this->payload;
            }
        });
    }

    private function direction(string $name, string $color, string $style = 'wordmark'): array
    {
        return ['name' => $name, 'brand_color' => $color, 'accent_color' => '#1c1917', 'font' => 'sans', 'template' => 'modern',
            'tagline' => '"Snel geregeld"', 'logo_style' => $style, 'logo_text' => $style === 'monogram' ? 'VD' : 'Vries Design', 'motivation' => 'Past bij een nuchtere vakman.'];
    }

    public function test_owner_gets_three_sanitized_directions_and_usage_is_counted(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeClaude(['directions' => [
            $this->direction('Fris', '#1e40af'),
            $this->direction('Warm', '#ea580c', 'monogram'),
            $this->direction('Kapot', 'geen-kleur'),
            $this->direction('Te veel', '#000000'),
        ]]);

        $response = $this->postJson(route('settings.brand.design'), ['sector' => 'Loodgieter voor particulieren', 'audience' => 'huiseigenaren', 'tone' => 'warm en ambachtelijk'])
            ->assertOk();

        $directions = $response->json('directions');
        $this->assertCount(2, $directions, 'Ongeldige kleur wordt overgeslagen; maximaal drie uit de eerste drie.');
        $this->assertSame('#1E40AF', $directions[0]['brand_color']);
        $this->assertSame('Snel geregeld', $directions[0]['tagline'], 'Aanhalingstekens worden gestript.');
        $this->assertSame('monogram', $directions[1]['logo_style']);
        $this->assertSame(1, AiUsageEvent::where('company_id', $user->company_id)->where('kind', 'brand_design')->count());
    }

    public function test_sector_is_required_and_paid_basis_plan_is_locked_out(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeClaude(['directions' => []]);

        $this->postJson(route('settings.brand.design'), ['sector' => ''])->assertStatus(422)->assertJsonValidationErrors('sector');

        $user->company->forceFill(['trial_ends_at' => now()->subDay(), 'subscription_ends_at' => now()->addMonth(), 'plan' => 'basis'])->save();
        $this->postJson(route('settings.brand.design'), ['sector' => 'Schilder'])->assertStatus(403);
    }

    public function test_generated_logo_can_be_saved_as_data_url(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="#1E40AF"/></svg>');

        $this->post(route('settings.brand.update'), [
            'brand_color' => '#1E40AF', 'accent_color' => '#1C1917', 'invoice_template' => 'modern', 'invoice_font' => 'sans',
            'logo_data_url' => 'data:image/svg+xml;base64,' . $svg,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $user->company->fresh()->makeVisible(['logo_data']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (string) $company->logo_data);
        $this->assertSame('#1E40AF', $company->brand_color);
    }
}
