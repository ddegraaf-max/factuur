<?php

namespace Tests\Feature;

use App\Mail\TenderMail;
use App\Mail\TenderReviewMail;
use App\Models\AiUsageEvent;
use App\Models\Subcontractor;
use App\Models\TenderRequest;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Services\Ai\StructuredClaude;
use App\Services\TenderReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Offertecheck (1.75.0): elke binnengekomen prijsopgave wordt beoordeeld —
 * automatisch met een mail aan de ondernemer, of met de knop — en de vragen
 * uit de check gaan met één klik naar het bedrijf.
 */
class TenderReviewTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** @var array<int, string> De prompts die naar het (nagebootste) model gingen. */
    private array $prompts = [];

    private function fakeClaude(array $payload, bool $enabled = true): void
    {
        $test = $this;
        $this->app->instance(StructuredClaude::class, new class($payload, $enabled, $test) extends StructuredClaude {
            public function __construct(private array $payload, private bool $on, private TenderReviewTest $test) {}
            public function enabled(): bool { return $this->on; }
            public function json(string $prompt, array $schema, int $maxTokens = 4000, string $effort = 'medium', array $blocks = [], string $label = 'AI'): array
            {
                $this->test->remember($prompt, $blocks);
                return $this->payload;
            }
        });
    }

    /** Verder als bezoeker zonder inlog (de tokenlink van een onderaannemer); zie TenderTest. */
    private function asGuest(): void
    {
        \Inertia\Inertia::flushShared();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function remember(string $prompt, array $blocks): void
    {
        $this->prompts[] = $prompt . "\n[blokken: " . count($blocks) . ']';
    }

    private function payload(string $verdict = 'high'): array
    {
        return [
            'verdict' => $verdict, 'confidence' => 'medium',
            'headline' => 'Aan de hoge kant: 12% boven je calculatie, steiger niet inbegrepen',
            'summary' => 'De prijs ligt boven je calculatie en boven de andere prijs. De steiger zit er niet in.',
            'price_basis' => 'Alleen een totaalbedrag.', 'comparison' => '12% boven de calculatie, 8% boven de laagste prijs.',
            'included' => ['Metselwerk gevel', 'Materiaal'], 'excluded' => ['Steiger', 'Afvoer puin'], 'scope_gaps' => ['Rollagen boven de kozijnen'],
            'market_estimate' => ['low' => 3800, 'high' => 4600, 'basis' => 'Circa 90 m² gevelmetselwerk tegen € 42–51 per m² inclusief materiaal.'],
            'questions' => ['Is de steiger inbegrepen?', 'Zitten de rollagen boven de kozijnen in de prijs?', str_repeat('x', 400)],
            'advice' => 'Vraag eerst of de steiger erin zit; anders onderhandelen richting € 4.400.',
        ];
    }

    /** @return array{0: TenderRound, 1: TenderRequest, 2: TenderRequest} */
    private function roundWithTwoPrices(): array
    {
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $a = Subcontractor::create(['name' => 'Metselbedrijf A', 'email' => 'a@metsel.test', 'city' => 'Woerden']);
        $b = Subcontractor::create(['name' => 'Metselbedrijf B', 'email' => 'b@metsel.test']);
        $a->workPackages()->sync([$package->id]);
        $b->workPackages()->sync([$package->id]);
        $this->post(route('tenders.store'), [
            'title' => 'Metselwerk gevel', 'work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id],
            'deadline' => now()->addDays(7)->toDateString(), 'description' => "90 m² gevelmetselwerk\nInclusief rollagen boven de kozijnen", 'budget' => 4200,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        [$ra, $rb] = [$round->requests()->where('subcontractor_id', $a->id)->firstOrFail(), $round->requests()->where('subcontractor_id', $b->id)->firstOrFail()];
        $rb->forceFill(['status' => 'responded', 'price' => 4350, 'responded_at' => now()->subHour()])->save();

        return [$round, $ra, $rb];
    }

    public function test_a_new_price_is_reviewed_automatically_and_the_owner_gets_the_advice(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeClaude($this->payload('high'));
        [$round, $ra, $rb] = $this->roundWithTwoPrices();

        // Bedrijf A geeft een prijs door via zijn link, met opmerkingen.
        $this->asGuest();
        $this->post(route('tender.respond', $ra->token), ['price' => '4.700,00', 'remarks' => 'Exclusief steiger', 'available_week' => '2026-W44'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($ra->fresh()->reviewed_at, 'De beoordeling komt uit de automatische ronde');

        // De ronde: beide prijsopgaven worden beoordeeld en gemaild; een tweede ronde doet niets meer.
        $done = app(TenderReviewService::class)->reviewPending();
        $this->assertSame(2, $done);
        $ra->refresh();
        $this->assertTrue($ra->hasReview());
        $this->assertSame('high', $ra->review['verdict']);
        $this->assertSame(3, count($ra->review['questions']));
        $this->assertSame(300, mb_strlen($ra->review['questions'][2]), 'Een te lange vraag wordt ingekort');
        $this->assertEqualsWithDelta(11.9, $ra->review['facts']['vs_budget_pct'], 0.1);
        $this->assertSame([2, 2], [$ra->review['facts']['rank'], $ra->review['facts']['of']]);
        $this->assertEqualsWithDelta(8.0, $ra->review['facts']['vs_lowest_pct'], 0.1);
        Mail::assertSent(TenderReviewMail::class, 2);
        Mail::assertSent(TenderReviewMail::class, fn (TenderReviewMail $m) => $m->request->id === $ra->id && str_contains($m->envelope()->subject, 'aan de hoge kant'));
        $this->assertSame(2, AiUsageEvent::where('kind', 'tender_review')->count());
        $this->assertSame(0, app(TenderReviewService::class)->reviewPending());

        // De prompt bevat de aanvraag, de calculatie, de opmerkingen en de andere prijs.
        $prompt = collect($this->prompts)->first(fn ($p) => str_contains($p, 'Bedrijf: Metselbedrijf A'));
        $this->assertNotNull($prompt);
        foreach (['90 m² gevelmetselwerk', '4.200,00', 'Exclusief steiger', 'Metselbedrijf B: 4.350,00', 'Metselwerk gevel'] as $needle) {
            $this->assertStringContainsString($needle, $prompt);
        }
        $this->assertStringContainsString('[blokken: 0]', $prompt, 'Zonder eigen offerte gaat er geen document mee');

        // De mail rendert met het oordeel en de vragen.
        $html = (new TenderReviewMail($ra->fresh(['round.company', 'subcontractor']), $ra->review))->render();
        $this->assertStringContainsString('aan de hoge kant', $html);
        $this->assertStringContainsString('Is de steiger inbegrepen?', $html);
        $this->assertStringContainsString('4.400', $html);

        // Op de uitvraagpagina staat het advies bij de prijs.
        $this->actingAs($user);
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->component('Tenders/Show')
            ->where('ai.available', true)
            ->where('requests.0.review.verdict', 'high')
            ->where('requests.0.review.verdict_label', 'aan de hoge kant')
            ->where('requests.0.review.facts.rank', 2));

        // Een aangepaste prijs wist de beoordeling; de knop beoordeelt direct opnieuw.
        $this->asGuest();
        $this->post(route('tender.respond', $ra->token), ['price' => '4400', 'remarks' => 'Inclusief steiger'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($ra->fresh()->hasReview());
        $this->actingAs($user);
        $this->fakeClaude($this->payload('reasonable'));
        $this->post(route('tenders.requests.review', [$round, $ra]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('reasonable', $ra->fresh()->review['verdict']);
        $this->assertSame(3, AiUsageEvent::where('kind', 'tender_review')->count());

        // De vragen gaan met één klik naar het bedrijf; lege regels vallen weg.
        $this->post(route('tenders.requests.questions', [$round, $ra]), ['questions' => ['Is de steiger inbegrepen?', '', 'Wanneer kunt u beginnen?']])
            ->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertSent(TenderMail::class, fn (TenderMail $m) => $m->kind === 'questions' && $m->hasTo('a@metsel.test') && count($m->questions) === 2);
        $this->assertSame(2, count($ra->fresh()->review['questions_sent']['questions']));
        $mail = new TenderMail($ra->fresh(['round', 'subcontractor']), 'questions', ['Is de steiger inbegrepen?']);
        $this->assertStringContainsString('Is de steiger inbegrepen?', $mail->render());
        $this->assertStringContainsString('Prijsopgave aanvullen', $mail->render());
        $this->post(route('tenders.requests.questions', [$round, $ra]), ['questions' => ['', '']])->assertRedirect()->assertSessionHasErrors('tender');

        // Zonder AI-toegang (betaald Basis) geen check, en zonder prijs ook niet.
        $user->company->forceFill(['trial_ends_at' => now()->subDay(), 'subscription_ends_at' => now()->addMonth(), 'plan' => 'basis'])->save();
        $this->post(route('tenders.requests.review', [$round, $ra]))->assertRedirect()->assertSessionHasErrors('tender');
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->where('ai.available', false));
    }

    public function test_a_demo_and_a_disabled_ai_are_skipped_and_failures_stop_after_three_tries(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->fakeClaude([], false);
        [$round, $ra, $rb] = $this->roundWithTwoPrices();
        $this->assertSame(0, app(TenderReviewService::class)->reviewPending(), 'Zonder API-key gebeurt er niets');

        // Met een model dat onzin teruggeeft: het oordeel wordt "onduidelijk", geen crash.
        $this->fakeClaude(['verdict' => 'nonsense', 'questions' => 'geen lijst']);
        $this->post(route('tenders.requests.review', [$round, $rb]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['unclear', [], 'low'], [$rb->fresh()->review['verdict'], $rb->fresh()->review['questions'], $rb->fresh()->review['confidence']]);

        // Een demo-administratie kost geen AI-tegoed in de automatische ronde.
        TenderRequest::whereKey($rb->id)->update(['review' => null, 'reviewed_at' => null]);
        $user->company->forceFill(['is_demo' => true])->save();
        $this->assertSame(0, app(TenderReviewService::class)->reviewPending());
        $user->company->forceFill(['is_demo' => false])->save();

        // Een storing bij de AI: na drie pogingen laat de ronde de prijsopgave met rust.
        $this->app->instance(StructuredClaude::class, new class extends StructuredClaude {
            public function enabled(): bool { return true; }
            public function json(string $prompt, array $schema, int $maxTokens = 4000, string $effort = 'medium', array $blocks = [], string $label = 'AI'): array
            {
                throw new \DomainException('De AI-dienst is even druk.');
            }
        });
        foreach ([2, 3, 3, 3] as $expected) {
            app(TenderReviewService::class)->reviewPending();
            $this->assertSame($expected, (int) $rb->fresh()->review_attempts, 'De knop telde al één poging');
        }
        $this->assertSame('De AI-dienst is even druk.', $rb->fresh()->review_error);
        Mail::assertNotSent(TenderReviewMail::class);
    }
}
