<?php

namespace Tests\Feature;

use App\Mail\TenderMail;
use App\Models\Project;
use App\Models\ProjectPlanItem;
use App\Models\Quote;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Gunning intrekken (1.75.1): het bedrijf krijgt een mail, de uitvraag gaat
 * weer open, de afgewezen bedrijven doen desgewenst weer mee en het onderdeel
 * op de projectplanning verdwijnt.
 */
class TenderRevokeTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_a_revoked_award_reopens_the_round_and_mails_the_company(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $project = Project::create(['name' => 'Uitbouw']);
        $quote = Quote::where('status', 'accepted')->firstOrFail();
        $quote->update(['project_id' => $project->id]);

        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $a = Subcontractor::create(['name' => 'Metselbedrijf A', 'email' => 'a@metsel.test']);
        $b = Subcontractor::create(['name' => 'Metselbedrijf B', 'email' => 'b@metsel.test']);
        $c = Subcontractor::create(['name' => 'Metselbedrijf C', 'email' => 'c@metsel.test']);
        foreach ([$a, $b, $c] as $s) {
            $s->workPackages()->sync([$package->id]);
        }
        $this->post(route('tenders.from_quote', $quote), [
            'work_package_id' => $package->id, 'subcontractor_ids' => [$a->id, $b->id, $c->id], 'deadline' => now()->addDays(7)->toDateString(), 'start_week' => '2026-W44',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $ra = $round->requests()->where('subcontractor_id', $a->id)->firstOrFail();
        $rb = $round->requests()->where('subcontractor_id', $b->id)->firstOrFail();
        $rc = $round->requests()->where('subcontractor_id', $c->id)->firstOrFail();
        $ra->forceFill(['status' => 'responded', 'price' => 4000, 'responded_at' => now()])->save();
        $rb->forceFill(['status' => 'responded', 'price' => 4400, 'responded_at' => now()])->save();
        // C is al eerder met een eigen bericht afgewezen: die blijft afgewezen.
        $rc->forceFill(['status' => 'responded', 'price' => 5000, 'responded_at' => now()])->save();
        $this->post(route('tenders.requests.reject', [$round, $rc]), ['message' => 'Te duur voor ons.'])->assertRedirect();

        // Niet gegund: intrekken kan niet.
        $this->post(route('tenders.revoke', $round))->assertRedirect()->assertSessionHasErrors('tender');

        $this->post(route('tenders.award', [$round, $ra]))->assertRedirect();
        $round->refresh();
        $this->assertSame('awarded', $round->status);
        $this->assertSame('rejected', $rb->fresh()->status);
        $this->assertSame(1, ProjectPlanItem::where('tender_round_id', $round->id)->count(), 'De gunning staat op de planning');

        // Intrekken met toelichting: mail aan A, uitvraag weer open, B doet weer mee, C blijft afgewezen, planning leeg.
        $this->post(route('tenders.revoke', $round), ['message' => 'De opdrachtgever heeft het werk uitgesteld.', 'reopen' => true])
            ->assertRedirect()->assertSessionHasNoErrors();
        $round->refresh();
        $this->assertSame(['open', null, null], [$round->status, $round->awarded_request_id, $round->awarded_at]);
        $this->assertSame('responded', $ra->fresh()->status);
        $this->assertEqualsWithDelta(4000.0, (float) $ra->fresh()->price, 0.01, 'De prijs blijft bekend');
        $this->assertSame('responded', $rb->fresh()->status);
        $this->assertSame('rejected', $rc->fresh()->status);
        $this->assertSame(0, ProjectPlanItem::where('tender_round_id', $round->id)->count());
        Mail::assertSent(TenderMail::class, fn (TenderMail $m) => $m->kind === 'revoke' && $m->hasTo('a@metsel.test') && $m->note === 'De opdrachtgever heeft het werk uitgesteld.');
        $html = (new TenderMail($ra->fresh(['round', 'subcontractor']), 'revoke', [], 'De opdrachtgever heeft het werk uitgesteld.'))->render();
        $this->assertStringContainsString('intrekken', $html);
        $this->assertStringContainsString('De opdrachtgever heeft het werk uitgesteld.', $html);

        // Opnieuw gunnen aan B werkt gewoon; intrekken zonder de anderen terug te halen laat A afgewezen.
        $this->post(route('tenders.award', [$round, $rb]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['awarded', 'rejected'], [$rb->fresh()->status, $ra->fresh()->status]);
        $this->post(route('tenders.revoke', $round), ['reopen' => false])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['responded', 'rejected', 'open'], [$rb->fresh()->status, $ra->fresh()->status, $round->fresh()->status]);

        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page->where('round.status', 'open'));
    }
}
