<?php

namespace Tests\Feature;

use App\Mail\PlanDigestMail;
use App\Mail\PlanMail;
use App\Mail\PlanNoticeMail;
use App\Models\Project;
use App\Models\ProjectPlanItem;
use App\Models\Quote;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Services\ProjectPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Projectplanning (1.74.0): gegund werk op de tijdslijn, de automatische
 * mails aan de onderaannemer, en het verzoek om eerder te beginnen.
 */
class ProjectPlanTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** Verder als bezoeker zonder inlog (de tokenlink van een onderaannemer); zie TenderTest. */
    private function asGuest(): void
    {
        \Inertia\Inertia::flushShared();
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_an_awarded_round_lands_on_the_timeline_and_the_subcontractor_is_reminded(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-10-05 09:00:00'); // maandag, week 41
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = Project::create(['name' => 'Uitbouw', 'location' => 'Woerden']);

        // Een uitvraag vanuit een offerte van het project, gewenste start week 43; gegund.
        $quote = Quote::where('status', 'accepted')->firstOrFail();
        $quote->update(['project_id' => $project->id]);
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $sub = Subcontractor::create(['name' => 'Metselbedrijf Test', 'contact_name' => 'Kees', 'email' => 'kees@metsel.test', 'city' => 'Woerden']);
        $sub->workPackages()->sync([$package->id]);
        $this->post(route('tenders.from_quote', $quote), [
            'work_package_id' => $package->id, 'subcontractor_ids' => [$sub->id], 'deadline' => '2026-10-12', 'start_week' => '2026-W43',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $request = $round->requests()->firstOrFail();
        $request->update(['status' => 'responded', 'price' => 4000, 'responded_at' => now()]);
        $this->post(route('tenders.award', [$round, $request]))->assertRedirect();

        $item = ProjectPlanItem::firstOrFail();
        $this->assertSame([$project->id, $round->id, $sub->id, 'Metselwerk'], [$item->project_id, $item->tender_round_id, $item->subcontractor_id, $item->title]);
        $this->assertSame('2026-10-19', $item->starts_on->toDateString(), 'Maandag van week 43');
        $this->assertSame('2026-10-23', $item->ends_on->toDateString(), 'Eén werkweek');

        // De projectpagina toont de tijdslijn; nog eens laden maakt geen tweede onderdeel.
        $this->get(route('projects.show', $project))->assertOk()->assertInertia(fn ($page) => $page->component('Projects/Show')
            ->has('plan.items', 1)->where('plan.items.0.title', 'Metselwerk')->where('plan.items.0.week', 43)->has('plan.weeks'));
        $this->assertSame(1, ProjectPlanItem::count());

        // De dagelijkse ronde: te vroeg → niets; een week vooraf → vooraankondiging; maandag van de week → herinnering.
        $service = app(ProjectPlanService::class);
        $this->assertSame(['headsup' => 0, 'reminders' => 0, 'digests' => 0], $service->runDaily(Carbon::parse('2026-10-08')));
        Mail::assertNotSent(PlanMail::class);

        // 12 oktober is ook een maandag: naast de vooraankondiging gaat het weekoverzicht uit, met Metselwerk bij 'volgende week'.
        $count = $service->runDaily(Carbon::parse('2026-10-12'));
        $this->assertSame(['headsup' => 1, 'reminders' => 0, 'digests' => 1], $count);
        Mail::assertSent(PlanDigestMail::class, fn (PlanDigestMail $m) => count($m->data['next_week']) === 1 && count($m->data['this_week']) === 0);

        Mail::assertSent(PlanMail::class, fn (PlanMail $m) => $m->kind === 'headsup' && $m->hasTo('kees@metsel.test'));
        $this->assertSame(0, $service->runDaily(Carbon::parse('2026-10-13'))['headsup'], 'Niet nog eens');

        $count = $service->runDaily(Carbon::parse('2026-10-19'));
        $this->assertSame(1, $count['reminders']);
        $this->assertSame(1, $count['digests'], 'Maandag: het weekoverzicht');
        Mail::assertSent(PlanMail::class, fn (PlanMail $m) => $m->kind === 'reminder' && $m->hasTo('kees@metsel.test'));
        Mail::assertSent(PlanDigestMail::class, fn (PlanDigestMail $m) => count($m->data['this_week']) === 1 && $m->data['this_week'][0]['title'] === 'Metselwerk');
        $this->assertSame(0, $service->runDaily(Carbon::parse('2026-10-20'))['reminders']);

        // De onderaannemer bevestigt via zijn link; de mail rendert.
        $item->refresh();
        $this->assertNotNull($item->reminder_sent_at);
        $html = (new PlanMail($item, 'reminder'))->render();
        $this->assertStringContainsString('Metselwerk', $html);
        $this->assertStringContainsString($item->token, $html);

        $this->asGuest();
        $this->get(route('plan.show', $item->token))->assertOk()->assertInertia(fn ($page) => $page->component('Projects/PlanRespond')
            ->where('valid', true)->where('item.title', 'Metselwerk')->where('item.project', 'Uitbouw'));
        $this->post(route('plan.respond', $item->token), ['action' => 'confirm'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($item->fresh()->confirmed_at);
        $this->post(route('plan.respond', $item->token), ['action' => 'problem', 'message' => 'Stenen komen een dag later'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Stenen komen een dag later', $item->fresh()->problem);
        Mail::assertSent(PlanNoticeMail::class, fn (PlanNoticeMail $m) => $m->kind === 'problem');
        $this->get(route('plan.show', 'nietbestaandtoken-nietbestaandtoken'))->assertOk()->assertInertia(fn ($page) => $page->where('valid', false));
    }

    public function test_finishing_early_asks_the_next_party_and_the_answer_moves_the_plan(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-10-14 09:00:00');
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = Project::create(['name' => 'Dakkapel']);
        $dakdekker = Subcontractor::create(['name' => 'Dakdekker Test', 'email' => 'dak@test.test']);
        $stukadoor = Subcontractor::create(['name' => 'Stukadoor Test', 'email' => 'stuc@test.test']);
        $zonderMail = Subcontractor::create(['name' => 'Zonder Mail']);

        // Eigen planning: ruwbouw (eigen werk), dan dakdekker, dan stukadoor, en één partij zonder e-mail.
        $this->post(route('projects.plan.store', $project), ['title' => 'Ruwbouw', 'starts_on' => '2026-10-12', 'ends_on' => '2026-10-23'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('projects.plan.store', $project), ['title' => 'Dak', 'starts_on' => '2026-10-26', 'ends_on' => '2026-10-30', 'subcontractor_id' => $dakdekker->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('projects.plan.store', $project), ['title' => 'Stucwerk', 'starts_on' => '2026-11-02', 'subcontractor_id' => $stukadoor->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('projects.plan.store', $project), ['title' => 'Schilder', 'starts_on' => '2026-11-09', 'subcontractor_id' => $zonderMail->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('projects.plan.store', $project), ['title' => 'Fout', 'starts_on' => '2026-11-09', 'ends_on' => '2026-11-01'])->assertRedirect()->assertSessionHasErrors('plan');
        $this->assertSame(4, $project->planItems()->count());
        $ruwbouw = $project->planItems()->where('title', 'Ruwbouw')->firstOrFail();
        $dak = $project->planItems()->where('title', 'Dak')->firstOrFail();
        $stuc = $project->planItems()->where('title', 'Stucwerk')->firstOrFail();
        $this->assertSame('2026-11-06', $stuc->ends_on->toDateString(), 'Zonder einddatum: één werkweek');

        // Ruwbouw is op 16 oktober klaar: 7 dagen eerder dan gepland. Dak (26 okt) → 19 okt; stucwerk (2 nov) → 26 okt; schilder heeft geen e-mail.
        $this->patch(route('projects.plan.status', [$project, $ruwbouw]), ['status' => 'done', 'done_on' => '2026-10-16'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('done', $ruwbouw->fresh()->status);
        Mail::assertSent(PlanMail::class, 2);
        Mail::assertSent(PlanMail::class, fn (PlanMail $m) => $m->kind === 'earlier' && $m->hasTo('dak@test.test') && $m->item->request_start->toDateString() === '2026-10-19');
        Mail::assertSent(PlanMail::class, fn (PlanMail $m) => $m->kind === 'earlier' && $m->hasTo('stuc@test.test') && $m->item->request_start->toDateString() === '2026-10-26');
        $this->assertTrue($dak->fresh()->requestPending());
        $this->assertStringContainsString('7 dagen eerder', (string) $dak->fresh()->request_message);

        // Dakdekker zegt ja: de planning schuift naar 19 oktober, even lang; de ondernemer krijgt bericht.
        $this->asGuest();
        $this->get(route('plan.show', $dak->token))->assertOk()->assertInertia(fn ($page) => $page->where('item.request.pending', true)->where('item.request.start', '2026-10-19'));
        $this->post(route('plan.respond', $dak->token), ['action' => 'accepted'])->assertRedirect()->assertSessionHasNoErrors();
        $dak->refresh();
        $this->assertSame(['2026-10-19', '2026-10-23', 'accepted'], [$dak->starts_on->toDateString(), $dak->ends_on->toDateString(), $dak->request_answer]);
        $this->assertNotNull($dak->confirmed_at);
        Mail::assertSent(PlanNoticeMail::class, fn (PlanNoticeMail $m) => $m->item->id === $dak->id && $m->item->request_answer === 'accepted');
        $this->post(route('plan.respond', $dak->token), ['action' => 'accepted'])->assertRedirect()->assertSessionHasErrors('plan');

        // Stukadoor stelt een andere dag voor: 28 oktober.
        $this->post(route('plan.respond', $stuc->token), ['action' => 'counter', 'start' => '2026-10-28', 'message' => 'Woensdag lukt'])->assertRedirect()->assertSessionHasNoErrors();
        $stuc->refresh();
        $this->assertSame(['2026-10-28', '2026-11-01', 'counter', 'Woensdag lukt'], [$stuc->starts_on->toDateString(), $stuc->ends_on->toDateString(), $stuc->request_answer, $stuc->request_message]);

        // Handmatig eerder vragen, en een nee laat alles staan.
        $this->actingAs($user);
        $this->post(route('projects.plan.earlier', [$project, $stuc]), ['start' => '2026-10-30'])->assertRedirect()->assertSessionHasErrors('plan', 'Niet later dan gepland');
        $this->post(route('projects.plan.earlier', [$project, $stuc]), ['start' => '2026-10-26', 'reason' => 'Kan het toch maandag?'])->assertRedirect()->assertSessionHasNoErrors();
        $this->asGuest();
        $this->post(route('plan.respond', $stuc->token), ['action' => 'declined'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['2026-10-28', 'declined'], [$stuc->fresh()->starts_on->toDateString(), $stuc->fresh()->request_answer]);

        // Automatisch uit: klaar melden vraagt niets meer.
        $this->actingAs($user);
        $this->patch(route('projects.plan.auto', $project), ['auto_earlier' => false])->assertRedirect();
        Mail::fake();
        $this->patch(route('projects.plan.status', [$project, $dak]), ['status' => 'done', 'done_on' => '2026-10-20'])->assertRedirect();
        Mail::assertNothingSent();

        // Bewerken verschuift en wist de herinneringen; verwijderen; gesloten project blokkeert.
        $stuc->forceFill(['headsup_sent_at' => now()])->save();
        $this->patch(route('projects.plan.update', [$project, $stuc]), ['title' => 'Stucwerk binnen', 'starts_on' => '2026-11-02', 'ends_on' => '2026-11-04'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($stuc->fresh()->headsup_sent_at);
        $this->assertSame('Stucwerk binnen', $stuc->fresh()->title);
        $this->delete(route('projects.plan.destroy', [$project, $stuc]))->assertRedirect();
        $this->assertNull(ProjectPlanItem::find($stuc->id));
        $this->patch(route('projects.status', $project), ['status' => 'closed'])->assertRedirect();
        $this->post(route('projects.plan.store', $project), ['title' => 'Nee'])->assertForbidden();

        // Een ander project mag niet bij dit onderdeel.
        $this->patch(route('projects.status', $project), ['status' => 'open'])->assertRedirect();
        $other = Project::create(['name' => 'Ander']);
        $this->patch(route('projects.plan.status', [$other, $dak]), ['status' => 'started'])->assertNotFound();
    }
}
