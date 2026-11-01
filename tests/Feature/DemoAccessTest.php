<?php

namespace Tests\Feature;

use App\Mail\DemoAccessMail;
use App\Mail\DemoRequestMail;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Demo op aanvraag (1.86.0): de demo start niet meer met één klik. Een
 * bezoeker laat zijn e-mailadres achter, de eigenaar krijgt een mail en
 * keurt goed of wijst af, en alleen een goedgekeurde, niet-verlopen link
 * bouwt een demo-omgeving.
 */
class DemoAccessTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.marketing_stats.emails' => 'eigenaar@example.com']);
    }

    private function pendingRequest(): DemoRequest
    {
        return DemoRequest::create([
            'brand' => Brand::key(), 'email' => 'piet@example.com', 'name' => 'Piet Pietersen', 'locale' => 'nl',
            'token' => DemoRequest::newToken(), 'review_token' => DemoRequest::newToken(), 'status' => DemoRequest::PENDING,
        ]);
    }

    public function test_the_demo_page_asks_for_an_email_and_mails_the_owner(): void
    {
        $this->get('/demo')->assertOk()->assertSee('Vraag toegang tot de demo')->assertDontSee('Start de demo');

        $this->post('/demo', ['name' => 'Piet'])->assertSessionHasErrors('email');
        $this->assertSame(0, DemoRequest::count());

        $this->post('/demo', ['email' => 'Piet@Example.com', 'name' => 'Piet Pietersen', 'company' => 'Pietersen Bouw', 'message' => 'Ik wil de offertes zien.'])
            ->assertRedirect(route('demo'))->assertSessionHas('demo_requested', true);

        $request = DemoRequest::firstOrFail();
        $this->assertSame(DemoRequest::PENDING, $request->status);
        $this->assertSame('piet@example.com', $request->email);
        $this->assertSame(Brand::key(), $request->brand);
        $this->assertNotEmpty($request->token);
        $this->assertNotEmpty($request->review_token);
        $this->assertNotSame($request->token, $request->review_token);
        $this->assertSame(0, Company::query()->where('is_demo', true)->count(), 'Een aanvraag bouwt nog geen demo');

        Mail::assertSent(DemoRequestMail::class, fn (DemoRequestMail $mail) => $mail->hasTo('eigenaar@example.com')
            && $mail->demoRequest->is($request)
            && str_contains($mail->render(), route('demo.review', $request->review_token)));
        Mail::assertNotSent(DemoAccessMail::class);

        $this->withSession(['demo_requested' => true])->get('/demo')->assertOk()->assertSee('Bedankt, je aanvraag is binnen.');
    }

    public function test_only_an_approved_link_starts_the_demo(): void
    {
        $request = $this->pendingRequest();

        $this->get('/demo/start/'.$request->token)->assertRedirect(route('demo'))->assertSessionHas('error');
        $this->post('/demo/start/'.$request->token)->assertRedirect(route('demo'))->assertSessionHas('error');
        $this->assertSame(0, Company::query()->where('is_demo', true)->count());

        // De eigenaar beoordeelt vanuit de mail: eerst de aanvraag zien, dan goedkeuren.
        $this->get('/demo/beoordelen/'.$request->review_token)->assertOk()->assertSee('piet@example.com')->assertSee('Goedkeuren');
        $this->post('/demo/beoordelen/'.$request->review_token.'/approve')->assertOk()->assertSee('Goedgekeurd');

        $request->refresh();
        $this->assertSame(DemoRequest::APPROVED, $request->status);
        $this->assertTrue($request->isUsable());
        $this->assertEqualsWithDelta(DemoRequest::VALID_DAYS, now()->diffInDays($request->expires_at), 1);
        Mail::assertSent(DemoAccessMail::class, fn (DemoAccessMail $mail) => $mail->hasTo('piet@example.com')
            && str_contains($mail->render(), route('demo.landing', $request->token)));

        // De link: eerst de pagina met de startknop, dan de demo zelf.
        $this->get('/demo/start/'.$request->token)->assertOk()->assertSee('Start de demo');
        $this->post('/demo/start/'.$request->token)->assertRedirect(route('dashboard'));
        $this->assertTrue((bool) auth()->user()->company->is_demo);
        $this->assertSame(1, $request->fresh()->starts);

        // Na de geldigheid werkt de link niet meer.
        auth()->logout();
        $this->travel(DemoRequest::VALID_DAYS + 1)->days();
        $this->post('/demo/start/'.$request->token)->assertRedirect(route('demo'))->assertSessionHas('error');
    }

    public function test_denying_sends_no_link(): void
    {
        $request = $this->pendingRequest();

        $this->post('/demo/beoordelen/'.$request->review_token.'/deny')->assertOk()->assertSee('Afgewezen');
        $this->assertSame(DemoRequest::DENIED, $request->fresh()->status);
        Mail::assertNotSent(DemoAccessMail::class);
        $this->post('/demo/start/'.$request->token)->assertRedirect(route('demo'));

        // Een onbekende beoordelingslink bestaat niet, en een verzonnen beslissing ook niet.
        $this->get('/demo/beoordelen/onzin')->assertNotFound();
        $this->post('/demo/beoordelen/'.$request->review_token.'/misschien')->assertNotFound();
    }

    public function test_the_owner_page_lists_requests_and_invites_directly(): void
    {
        $owner = $this->demoUser();
        config(['services.marketing_stats.emails' => $owner->email]);
        $request = $this->pendingRequest();

        $this->actingAs($owner)->get(route('owner.demo.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Stats/DemoAanvragen')
            ->has('requests', 1)
            ->where('requests.0.email', 'piet@example.com')
            ->where('requests.0.status', 'pending'));

        $this->post(route('owner.demo.approve', $request))->assertRedirect();
        $this->assertSame(DemoRequest::APPROVED, $request->fresh()->status);
        Mail::assertSent(DemoAccessMail::class, fn (DemoAccessMail $mail) => $mail->hasTo('piet@example.com'));

        $this->post(route('owner.demo.invite'), ['email' => 'klant@example.com', 'name' => 'Klant', 'locale' => 'en'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $invited = DemoRequest::where('email', 'klant@example.com')->firstOrFail();
        $this->assertTrue($invited->isUsable());
        $this->assertSame('en', $invited->locale);
        Mail::assertSent(DemoAccessMail::class, fn (DemoAccessMail $mail) => $mail->hasTo('klant@example.com'));

        $this->post(route('owner.demo.deny', $request))->assertRedirect();
        $this->assertSame(DemoRequest::DENIED, $request->fresh()->status);
        $this->delete(route('owner.demo.destroy', $request))->assertRedirect();
        $this->assertNull(DemoRequest::find($request->id));
    }

    public function test_the_invite_command_makes_a_working_link(): void
    {
        $this->artisan('demo:invite', ['email' => 'cli@example.com', '--locale' => 'pl'])->assertSuccessful();

        $request = DemoRequest::where('email', 'cli@example.com')->firstOrFail();
        $this->assertTrue($request->isUsable());
        $this->assertSame('pl', $request->locale);
        Mail::assertSent(DemoAccessMail::class, fn (DemoAccessMail $mail) => $mail->hasTo('cli@example.com'));
        $this->post('/demo/start/'.$request->token)->assertRedirect(route('dashboard'));
    }

    public function test_the_owner_page_is_for_the_owner_only(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)->get(route('owner.demo.index'))->assertForbidden();
        $this->post(route('owner.demo.invite'), ['email' => 'x@example.com'])->assertForbidden();
    }
}
