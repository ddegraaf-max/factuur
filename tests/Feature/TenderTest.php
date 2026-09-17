<?php

namespace Tests\Feature;

use App\Mail\TenderMail;
use App\Models\Quote;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Services\TenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Uitvragen bij onderaannemers (1.57.0): werkpakketten en pool, een ronde
 * vanuit een geaccepteerde offerte met één tokenlink per bedrijf, reageren of
 * afzeggen zonder inlog, herinneren na drie dagen, vergelijken en gunnen.
 */
class TenderTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** @return array{0: WorkPackage, 1: Subcontractor, 2: Subcontractor, 3: Subcontractor} */
    private function pool(): array
    {
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'like', 'Schroefpalen%')->firstOrFail();
        $make = function (string $name, ?string $email) use ($package) {
            $s = Subcontractor::create(['name' => $name, 'email' => $email, 'city' => 'Hilversum']);
            $s->workPackages()->sync([$package->id]);

            return $s;
        };

        return [
            $package,
            $make('Jansen Funderingstechniek', 'info@jansen.test'),
            $make('De Vries Palen', 'offerte@devries.test'),
            $make('Zonder Mail BV', null),
        ];
    }

    /** Verder als bezoeker zonder inlog (de tokenlink van een onderaannemer). */
    private function asGuest(): void
    {
        // Inertia bewaart gedeelde props binnen het testproces; de closure uit het ingelogde
        // verzoek zou anders in het gastverzoek nog op de oude gebruiker wijzen.
        \Inertia\Inertia::flushShared();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function test_default_packages_are_seeded_once(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $this->assertSame(count(TenderService::DEFAULT_PACKAGES), WorkPackage::count());
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $this->assertSame(count(TenderService::DEFAULT_PACKAGES), WorkPackage::count(), 'Nog eens toevoegen verdubbelt niets');

        $this->get(route('tenders.pool'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenders/Pool')->has('packages', count(TenderService::DEFAULT_PACKAGES)));
    }

    public function test_a_round_from_an_accepted_quote_mails_every_company_with_its_own_link(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b, $noMail] = $this->pool();
        $quote = Quote::where('status', 'accepted')->firstOrFail();

        $this->post(route('tenders.from_quote', $quote), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$a->id, $b->id, $noMail->id],
            'title' => 'Fundament ' . $quote->number,
            'description' => 'Zes schroefpalen, sondering aanwezig.',
            'location' => '1402 AT Bussum',
            'start_week' => '2026-W42',
            'deadline' => now()->addDays(7)->toDateString(),
            'budget' => '4500',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $round = TenderRound::firstOrFail();
        $this->assertSame($quote->id, $round->quote_id);
        $this->assertSame('open', $round->status);
        $this->assertSame(2, $round->requests()->count(), 'Zonder e-mailadres geen aanvraag');
        $this->assertCount(2, $round->requests()->pluck('token')->unique());
        $this->assertNotNull($round->requests()->first()->sent_at);
        Mail::assertSent(TenderMail::class, 2);
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'request' && $mail->hasTo('info@jansen.test'));

        // De mail bevat de omschrijving en de locatie, maar nooit de verkoopprijs of de calculatie.
        $html = (new TenderMail($round->requests()->first(), 'request'))->render();
        $this->assertStringContainsString('Zes schroefpalen', $html);
        $this->assertStringContainsString('1402 AT Bussum', $html);
        $this->assertStringContainsString('uitvraag/' . $round->requests()->first()->token, $html);
        $this->assertStringNotContainsString(number_format((float) $quote->total, 2, ',', '.'), $html);
        $this->assertStringNotContainsString('4.500', $html);

        $this->get(route('quotes.show', $quote))->assertOk()
            ->assertInertia(fn ($page) => $page->where('quote.tender_rounds.0.id', $round->id)->has('quote.tender_packages'));
        $this->get(route('tenders.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenders/Index')->where('rounds.0.requested', 2)->where('counts.open', 1));
        $this->get(route('tenders.show', $round))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenders/Show')->has('requests', 2)->where('round.budget', fn ($v) => abs($v - 4500) < 0.001));
        $this->assertDatabaseHas('activity_logs', ['subject_type' => 'uitvraag', 'subject_id' => $round->id, 'action' => 'created']);
    }

    public function test_a_company_responds_or_declines_via_its_link_and_the_owner_awards(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();

        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$a->id, $b->id],
            'deadline' => now()->addDays(5)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $this->assertSame($package->name, $round->title, 'Zonder titel heet de ronde naar het pakket');
        $reqA = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $reqB = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();

        $this->asGuest();

        // Openbare pagina: openen wordt geregistreerd; een onbekend token toont een nette melding.
        $this->get(route('tender.respond.show', $reqA->token))->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenders/Respond')->where('valid', true)->where('round.title', $round->title)->where('request.status', 'sent'));
        $this->assertNotNull($reqA->fresh()->opened_at);
        $this->get(route('tender.respond.show', str_repeat('a', 64)))->assertOk()->assertInertia(fn ($page) => $page->where('valid', false));

        $this->post(route('tender.respond', $reqA->token), [
            'price' => '4250,50',
            'available_week' => '2026-W43',
            'valid_until' => now()->addDays(30)->toDateString(),
            'remarks' => 'Inclusief afvoer',
            'attachment' => UploadedFile::fake()->create('offerte.pdf', 120, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $reqA->refresh();
        $this->assertSame('responded', $reqA->status);
        $this->assertEqualsWithDelta(4250.50, (float) $reqA->price, 0.001);
        $this->assertSame('offerte.pdf', $reqA->attachment_name);
        Storage::disk('local')->assertExists($reqA->attachment_path);

        $this->post(route('tender.decline', $reqB->token), ['reason' => 'Geen capaciteit'])->assertRedirect();
        $this->assertSame('declined', $reqB->fresh()->status);

        // Eigenaar: matrix, bijlage en gunnen.
        $this->actingAs($user);
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->where('round.responded', 1)
            ->where('round.lowest', 4250.5)
            // Rijen op volgorde van aanmaken: de service schrijft bedrijven op naam aan (De Vries vóór Jansen).
            ->where('requests.0.status', 'declined')
            ->where('requests.0.decline_reason', 'Geen capaciteit')
            ->where('requests.1.status', 'responded')
            ->where('requests.1.remarks', 'Inclusief afvoer'));
        $this->get(route('tenders.attachment', [$round, $reqA]))->assertOk();

        $this->post(route('tenders.award', [$round, $reqB]))->assertSessionHasErrors('tender');
        $this->post(route('tenders.award', [$round, $reqA]))->assertRedirect()->assertSessionHasNoErrors();
        $round->refresh();
        $this->assertSame('awarded', $round->status);
        $this->assertSame($reqA->id, $round->awarded_request_id);
        $this->assertSame('awarded', $reqA->fresh()->status);
        $this->assertSame('declined', $reqB->fresh()->status, 'Wie afzegde blijft afgezegd');
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'award' && $mail->hasTo('info@jansen.test'));
        Mail::assertNotSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'reject');
        $this->assertDatabaseHas('activity_logs', ['subject_type' => 'uitvraag', 'subject_id' => $round->id, 'action' => 'awarded']);

        // Gesloten: de link zegt het en neemt niets meer aan.
        $this->asGuest();
        $this->post(route('tender.respond', $reqB->token), ['price' => '1'])->assertSessionHasErrors('tender');
        $this->get(route('tender.respond.show', $reqA->token))
            ->assertInertia(fn ($page) => $page->where('request.status', 'awarded')->where('round.open', false));
    }

    public function test_the_runner_up_gets_a_polite_rejection(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();
        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id], 'deadline' => now()->addDays(5)->toDateString()])->assertRedirect();
        $round = TenderRound::firstOrFail();
        $reqA = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $reqB = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();

        $this->asGuest();
        $this->post(route('tender.respond', $reqA->token), ['price' => '5000'])->assertRedirect();
        $this->post(route('tender.respond', $reqB->token), ['price' => '4800'])->assertRedirect();

        $this->actingAs($user);
        $this->post(route('tenders.award', [$round, $reqB]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('rejected', $reqA->fresh()->status);
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'reject' && $mail->hasTo('info@jansen.test'));
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'award' && $mail->hasTo('offerte@devries.test'));
    }

    public function test_silent_companies_get_one_reminder_after_three_days(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();
        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id], 'deadline' => now()->addDays(10)->toDateString()])->assertRedirect();
        $round = TenderRound::firstOrFail();
        $reqA = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $reqB = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();

        $this->asGuest();
        $this->post(route('tender.respond', $reqB->token), ['price' => '5000'])->assertRedirect();

        $reminders = fn () => collect(Mail::sent(TenderMail::class))->filter(fn (TenderMail $mail) => $mail->kind === 'reminder')->count();

        $this->travel(2)->days();
        $this->artisan('tenders:remind')->assertSuccessful();
        $this->assertSame(0, $reminders(), 'Na twee dagen nog niets');

        $this->travel(2)->days();
        $this->artisan('tenders:remind')->assertSuccessful();
        $this->assertSame(1, $reminders());
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'reminder' && $mail->hasTo('info@jansen.test'));
        $this->assertNotNull($reqA->fresh()->reminded_at);
        $this->assertNull($reqB->fresh()->reminded_at, 'Wie al reageerde krijgt geen herinnering');

        $this->artisan('tenders:remind')->assertSuccessful();
        $this->assertSame(1, $reminders(), 'Eén herinnering, niet elke dag');
    }

    public function test_pool_management_and_bulk_paste(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->post(route('tenders.packages.store'), ['name' => 'Steigerwerk', 'description' => 'm² steiger, aantal weken'])->assertRedirect();
        $package = WorkPackage::where('name', 'Steigerwerk')->firstOrFail();
        $this->patch(route('tenders.packages.update', $package), ['name' => 'Steigers', 'description' => null])->assertRedirect();
        $this->assertSame('Steigers', $package->fresh()->name);

        $this->post(route('tenders.subcontractors.store'), ['name' => 'Bouwsteiger BV', 'email' => 'info@bouwsteiger.test', 'package_ids' => [$package->id]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $s = Subcontractor::where('name', 'Bouwsteiger BV')->firstOrFail();
        $this->assertSame([$package->id], $s->workPackages->pluck('id')->all());
        $this->post(route('tenders.subcontractors.store'), ['name' => 'Fout', 'email' => 'geen-adres'])->assertSessionHasErrors('email');

        $this->post(route('tenders.subcontractors.import'), [
            'lines' => "Alfa Steigers; alfa@test.nl; 035-1; Bussum; Steigers\nBouwsteiger BV; dubbel@test.nl\n; \nBeta; ; ; Naarden; onbekend pakket",
        ])->assertRedirect();
        $this->assertSame(3, Subcontractor::count(), 'Alfa en Beta erbij, Bouwsteiger bestond al');
        $alfa = Subcontractor::where('name', 'Alfa Steigers')->firstOrFail();
        $this->assertSame('alfa@test.nl', $alfa->email);
        $this->assertSame('import', $alfa->source);
        $this->assertSame([$package->id], $alfa->workPackages->pluck('id')->all());
        $this->assertNull(Subcontractor::where('name', 'Beta')->firstOrFail()->email);

        $this->get(route('tenders.pool'))->assertOk()->assertInertia(fn ($page) => $page->has('subcontractors', 3)->where('subcontractors.0.stats.requests', 0));

        $this->delete(route('tenders.subcontractors.destroy', $s))->assertRedirect();
        $this->assertNull(Subcontractor::find($s->id));
        $this->delete(route('tenders.packages.destroy', $package))->assertRedirect();
        $this->assertNull(WorkPackage::find($package->id));
    }
}
