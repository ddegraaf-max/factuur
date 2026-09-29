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
            'attachment' => UploadedFile::fake()->createWithContent('offerte.pdf', '%PDF-1.4 offerte'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $reqA->refresh();
        $this->assertSame('responded', $reqA->status);
        $this->assertEqualsWithDelta(4250.50, (float) $reqA->price, 0.001);
        $this->assertSame('offerte.pdf', $reqA->attachment_name);
        // In de database, niet op schijf: daar verdwijnt alles bij een deploy.
        $this->assertNull($reqA->attachment_path);
        $this->assertSame(1, $reqA->attachments()->count());
        $this->assertSame($user->company_id, $reqA->attachments()->first()->company_id);

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

    public function test_attachments_go_out_with_the_request_and_are_on_the_response_page(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();

        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$a->id, $b->id],
            'start_week' => '2026-W44',
            'deadline' => now()->addDays(5)->toDateString(),
            'files' => [
                UploadedFile::fake()->createWithContent('tekening.pdf', '%PDF-1.4 tekening'),
                UploadedFile::fake()->image('situatie.jpg', 40, 30),
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $round = TenderRound::firstOrFail();
        $this->assertSame(['tekening.pdf', 'situatie.jpg'], $round->attachments()->pluck('filename')->all());
        $this->assertSame($user->company_id, $round->attachments()->first()->company_id);
        $request = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();

        // De mail: beide bestanden als bijlage, met naam in de tekst en de week met datums.
        $mail = new TenderMail($request, 'request');
        $this->assertCount(2, $mail->attachments());
        $html = $mail->render();
        $this->assertStringContainsString('tekening.pdf', $html);
        $this->assertStringContainsString('week 44 (26 okt. – 1 nov. 2026)', $html);
        $this->assertCount(2, (new TenderMail($request, 'reminder'))->attachments());
        $this->assertCount(0, (new TenderMail($request, 'reject'))->attachments(), 'Bij een afwijzing gaat er niets mee');

        // Eigenaar: lijst op de uitvraagpagina en achteraf een bestand erbij.
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->has('round.attachments', 2)
            ->where('round.attachments.0.filename', 'tekening.pdf')
            ->where('round.start_week', '44 (26 okt. – 1 nov. 2026)'));
        $this->post(route('tenders.attachments.store', $round), [
            'files' => [UploadedFile::fake()->createWithContent('bestek.pdf', '%PDF-1.4 bestek')],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, $round->attachments()->count());
        $this->post(route('tenders.attachments.store', $round), [
            'files' => [UploadedFile::fake()->create('calculatie.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
        ])->assertSessionHasErrors('files.0');

        // Het bedrijf: ziet en opent de bijlagen via zijn eigen link, zonder inlog.
        $file = $round->attachments()->first();
        $this->asGuest();
        $this->get(route('tender.respond.show', $request->token))->assertOk()->assertInertia(fn ($page) => $page
            ->has('round.attachments', 3)
            ->where('round.attachments.0.filename', 'tekening.pdf')
            ->where('round.start_week_label', '44 (26 okt. – 1 nov. 2026)'));
        $this->get(route('tender.attachment', [$request->token, $file->id]))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tender.attachment', [str_repeat('b', 64), $file->id]))->assertNotFound();

        // Een bijlage van een andere uitvraag is met deze link niet te openen.
        $this->actingAs($user);
        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$b->id],
            'deadline' => now()->addDays(5)->toDateString(),
            'files' => [UploadedFile::fake()->createWithContent('ander-project.pdf', '%PDF-1.4 ander')],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $other = TenderRound::latest('id')->firstOrFail()->attachments()->firstOrFail();
        $this->asGuest();
        $this->get(route('tender.attachment', [$request->token, $other->id]))->assertNotFound();
    }

    public function test_the_owner_records_a_decline_removes_a_company_and_invites_more(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();
        $c = Subcontractor::create(['name' => 'Klein Timmerwerk', 'email' => 'info@klein.test', 'city' => 'Alphen aan den Rijn']);
        $c->workPackages()->sync([$package->id]);

        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id], 'deadline' => now()->addDays(5)->toDateString()])->assertRedirect();
        $round = TenderRound::firstOrFail();
        $reqA = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $reqB = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();
        Mail::assertSent(TenderMail::class, 2);

        // Afgezegd per telefoon: vastleggen zonder mail, en daarna geen herinnering meer.
        $this->post(route('tenders.requests.decline', [$round, $reqA]), ['reason' => 'Geen tijd'])->assertSessionHasNoErrors();
        $this->assertSame('declined', $reqA->fresh()->status);
        $this->assertSame('Geen tijd', $reqA->fresh()->decline_reason);
        $this->travel(4)->days();
        $this->assertSame(1, app(TenderService::class)->remindDue(), 'Alleen het bedrijf dat nog meedoet krijgt een herinnering');
        Mail::assertNotSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'reminder' && $mail->hasTo('info@jansen.test'));

        // Per vergissing aangeschreven: uit de ronde halen.
        $this->delete(route('tenders.requests.destroy', [$round, $reqB]))->assertSessionHasNoErrors();
        $this->assertSame(1, $round->requests()->count());

        // De pagina biedt de bedrijven aan die nog niet meedoen; toevoegen mailt alleen de nieuwe.
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->has('candidates', 3)
            ->where('requests.0.status', 'declined'));
        $sent = Mail::sent(TenderMail::class)->count();
        $this->post(route('tenders.requests.store', $round), ['subcontractor_ids' => [$c->id, $a->id]])->assertSessionHasNoErrors();
        $this->assertSame(2, $round->requests()->count(), 'Wie al in de ronde zit, wordt niet nog eens aangeschreven');
        $this->assertSame($sent + 1, Mail::sent(TenderMail::class)->count());
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'request' && $mail->hasTo('info@klein.test'));

        // Na sluiten kan er niets meer bij of af.
        $this->post(route('tenders.close', $round))->assertRedirect();
        $this->post(route('tenders.requests.store', $round), ['subcontractor_ids' => [$b->id]])->assertSessionHasErrors('tender');
        $this->delete(route('tenders.requests.destroy', [$round, $reqA]))->assertSessionHasErrors('tender');
    }

    public function test_the_mail_keeps_a_pasted_signature_out_of_the_description(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a] = $this->pool();

        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$a->id],
            'deadline' => now()->addDays(5)->toDateString(),
            'description' => "Uitbouw 5,50 x 1,80 m.\n\nGevraagd:\n- HSB-wanden leveren\n- Balklaag plat dak\n\n--\u{00A0}\n\nMet vriendelijke groet,\nJan Jansen\nDe inhoud van dit bericht is vertrouwelijk.",
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $request = $round->requests()->firstOrFail();

        $text = \App\Support\TenderText::split($round->description);
        $this->assertStringEndsWith('- Balklaag plat dak', $text['body']);
        $this->assertStringStartsWith('Met vriendelijke groet,', $text['signature']);
        $this->assertSame(['p', 'p', 'ul'], array_column(\App\Support\TenderText::blocks($round->description), 'type'));

        $html = (new TenderMail($request, 'request'))->render();
        $this->assertStringContainsString('HSB-wanden leveren', $html);
        $this->assertLessThan(strpos($html, 'De inhoud van dit bericht is vertrouwelijk'), strpos($html, 'Prijs en beschikbaarheid doorgeven'), 'De ondertekening staat onder de knop, niet in de omschrijving');

        // Het bedrijf en de eigenaar zien de omschrijving zonder ondertekening.
        $this->get(route('tenders.show', $round))->assertInertia(fn ($page) => $page
            ->where('round.description', $text['body'])
            ->where('round.signature', $text['signature']));
        $this->asGuest();
        $this->get(route('tender.respond.show', $request->token))->assertInertia(fn ($page) => $page->where('round.description', $text['body']));
    }

    public function test_the_mail_shows_the_logo_from_an_address_that_survives_a_reply(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a] = $this->pool();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $user->company->forceFill(['logo_data' => 'data:image/png;base64,' . base64_encode($png)])->save();

        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id], 'deadline' => now()->addDays(5)->toDateString()])->assertRedirect();
        $request = TenderRound::firstOrFail()->requests()->firstOrFail();

        $url = $user->company->fresh()->logoUrl();
        $this->assertNotNull($url);
        $html = (new TenderMail($request, 'request'))->render();
        $this->assertStringContainsString($url, $html);
        $this->assertStringNotContainsString('cid:', $html);

        // Het mailprogramma van het bedrijf haalt het logo op zonder inlog.
        $this->asGuest();
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('company.logo', [$user->company_id, str_repeat('0', 16)]))->assertNotFound();

        // Zonder logo staat de bedrijfsnaam in de kop.
        $user->company->forceFill(['logo_data' => null])->save();
        $this->assertNull($user->company->fresh()->logoUrl());
    }

    public function test_closed_rounds_take_no_more_attachments(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a] = $this->pool();
        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id], 'deadline' => now()->addDays(5)->toDateString()])->assertRedirect();
        $round = TenderRound::firstOrFail();
        $this->post(route('tenders.close', $round))->assertRedirect();

        $this->post(route('tenders.attachments.store', $round), [
            'files' => [UploadedFile::fake()->create('tekening.pdf', 50, 'application/pdf')],
        ])->assertSessionHasErrors('files');
        $this->assertSame(0, $round->attachments()->count());
    }

    public function test_weeks_are_shown_with_their_dates(): void
    {
        $this->assertSame('44 (26 okt. – 1 nov. 2026)', \App\Support\IsoWeek::label('2026-W44'));
        $this->assertSame('1 (29 dec. – 4 jan. 2026)', \App\Support\IsoWeek::label('2026-W01'));
        $this->assertSame('week 42 of later', \App\Support\IsoWeek::label(' week 42 of later '), 'Vrije tekst van vóór de kalender blijft staan');
        $this->assertNull(\App\Support\IsoWeek::label(null));
        $this->assertNull(\App\Support\IsoWeek::monday('2026-W60'));
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

    public function test_the_owner_rejects_one_quote_with_a_message_and_the_round_stays_open(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        [$package, $a, $b] = $this->pool();
        $c = Subcontractor::create(['name' => 'Klein Timmerwerk', 'email' => 'info@klein.test', 'city' => 'Alphen aan den Rijn']);
        $c->workPackages()->sync([$package->id]);
        $this->post(route('tenders.store'), ['work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id, $c->id], 'deadline' => now()->addDays(5)->toDateString()])->assertRedirect();
        $round = TenderRound::firstOrFail();
        $reqA = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $reqB = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();
        $reqC = $round->requests()->where('subcontractor_id', $c->id)->firstOrFail();
        $rejections = fn () => collect(Mail::sent(TenderMail::class))->filter(fn (TenderMail $mail) => $mail->kind === 'reject')->count();

        // Zonder prijs valt er niets af te wijzen.
        $this->post(route('tenders.requests.reject', [$round, $reqA]), ['message' => 'Helaas'])->assertSessionHasErrors('tender');
        $this->assertSame('sent', $reqA->fresh()->status);

        $this->asGuest();
        $this->post(route('tender.respond', $reqA->token), ['price' => '5000'])->assertRedirect();
        $this->post(route('tender.respond', $reqB->token), ['price' => '4800'])->assertRedirect();
        $this->post(route('tender.respond', $reqC->token), ['price' => '5200'])->assertRedirect();

        // Het venster begint met een vriendelijk bericht dat de ondernemer kan aanpassen.
        $this->actingAs($user);
        $default = app(TenderService::class)->defaultRejection();
        $this->assertStringStartsWith('Bedankt voor uw prijsopgave', $default);
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->where('rejectDefault', $default));

        $message = "Bedankt voor uw offerte.\n\nDe prijs ligt boven ons budget:\n- wij zoeken nog verder\n- bij een volgend project hoort u van ons";
        $this->post(route('tenders.requests.reject', [$round, $reqA]), ['message' => $message])->assertRedirect()->assertSessionHasNoErrors();
        $reqA->refresh();
        $this->assertSame('rejected', $reqA->status);
        $this->assertSame($message, $reqA->reject_message);
        $this->assertNotNull($reqA->rejected_at);
        $this->assertSame('open', $round->fresh()->status, 'De uitvraag blijft open voor de andere bedrijven');
        $this->assertSame('responded', $reqB->fresh()->status);
        $this->assertSame(1, $rejections());
        Mail::assertSent(TenderMail::class, fn (TenderMail $mail) => $mail->kind === 'reject' && $mail->hasTo('info@jansen.test'));
        $this->assertDatabaseHas('activity_logs', ['subject_type' => 'uitvraag', 'subject_id' => $round->id, 'action' => 'updated']);

        // De mail: het eigen bericht in alinea's en opsommingen, niet de standaardtekst van het gunnen.
        $mail = new TenderMail($reqA, 'reject');
        $this->assertStringStartsWith('Uw prijsopgave voor', $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('De prijs ligt boven ons budget', $html);
        $this->assertStringContainsString('wij zoeken nog verder', $html);
        $this->assertStringNotContainsString('hebben wij een andere partij gekozen', $html);
        $this->assertStringNotContainsString('uitvraag/' . $reqA->token, $html, 'Geen knop meer om te reageren');

        // Zonder eigen tekst gaat het standaardbericht uit.
        $this->post(route('tenders.requests.reject', [$round, $reqC]), ['message' => '  '])->assertSessionHasNoErrors();
        $this->assertSame($default, $reqC->fresh()->reject_message);
        $this->assertSame(2, $rejections());

        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->where('round.responded', 3)
            ->where('requests', fn ($rows) => collect($rows)->firstWhere('id', $reqA->id)['reject_message'] === $message
                && collect($rows)->firstWhere('id', $reqA->id)['status'] === 'rejected'));

        // Afgewezen is definitief: geen tweede mail, en via de link komt er geen nieuwe prijs binnen.
        $this->post(route('tenders.requests.reject', [$round, $reqA]), ['message' => 'Nog eens'])->assertSessionHasErrors('tender');
        $this->assertSame(2, $rejections());
        $this->asGuest();
        $this->get(route('tender.respond.show', $reqA->token))->assertOk()->assertInertia(fn ($page) => $page
            ->where('request.status', 'rejected')
            ->where('request.reject_message', $message)
            ->where('round.open', true)
            ->where('round.awarded', false));
        $this->post(route('tender.respond', $reqA->token), ['price' => '4000'])->assertSessionHasErrors('tender');
        $this->post(route('tender.decline', $reqA->token), ['reason' => 'Dan niet'])->assertSessionHasErrors('tender');
        $this->assertSame('rejected', $reqA->fresh()->status);

        // Bij het gunnen krijgt wie al is afgewezen niet nog een afwijzing.
        $this->actingAs($user);
        $this->post(route('tenders.award', [$round, $reqA]))->assertSessionHasErrors('tender');
        $this->post(route('tenders.award', [$round, $reqB]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $rejections());
        $this->assertSame('awarded', $reqB->fresh()->status);
        $this->assertSame($message, $reqA->fresh()->reject_message);
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
