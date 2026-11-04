<?php

namespace Tests\Feature;

use App\Http\Controllers\Fachora\CustomerAccessController;
use App\Mail\FachoraMail;
use App\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** 1.99: "Moje zlecenia" — klantlogin zonder wachtwoord (e-mail + code per mail) met de lijst van eigen aanvragen. */
class FachoraCustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora']);
        Mail::fake();
    }

    private function job(array $attrs = []): ServiceJob
    {
        return ServiceJob::create($attrs + [
            'reference' => ServiceJob::nextReference(), 'token' => bin2hex(random_bytes(32)), 'status' => 'open', 'confirmed_at' => now(), 'offered_at' => now(),
            'category' => 'kominiarz', 'title' => 'Czyszczenie komina', 'description' => 'Przegląd i czyszczenie komina.', 'urgency' => 'week', 'language' => 'pl',
            'customer_name' => 'Anna Nowak', 'customer_email' => 'Anna@Example.com', 'customer_locale' => 'pl', 'postal_code' => '09-400', 'city' => 'Płock', 'lat' => 52.5463, 'lng' => 19.7065,
        ]);
    }

    public function test_the_page_asks_for_an_email_and_is_linked_from_the_menu(): void
    {
        $html = (string) $this->get('/moje-zlecenia')->assertOk()->getContent();
        $this->assertStringContainsString('Wyślij kod', $html);
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringContainsString('href="' . route('fachora.my') . '"', $html, 'menu-/voettekstlink');
        $this->assertStringContainsString('Send code', (string) $this->get('/moje-zlecenia?lang=en')->assertOk()->getContent());
    }

    public function test_a_known_address_gets_a_code_by_mail_and_an_unknown_one_gets_the_same_neutral_message(): void
    {
        $this->job();
        $this->post('/moje-zlecenia/kod', ['email' => 'anna@example.com'])->assertRedirect(route('fachora.my'))->assertSessionHas('my_sent');
        Mail::assertSent(FachoraMail::class, fn (FachoraMail $m) => $m->kind === 'login_code' && $m->hasTo('Anna@Example.com') && preg_match('/^\d{6}$/', (string) $m->extra['code']) === 1);
        $html = (string) $this->get('/moje-zlecenia')->assertOk()->getContent();
        $this->assertStringContainsString('kod jest w drodze', $html);
        $this->assertStringContainsString('Kod z e-maila', $html);

        Mail::fake();
        $this->post('/moje-zlecenia/wyloguj');
        $this->post('/moje-zlecenia/kod', ['email' => 'nobody@example.com'])->assertRedirect(route('fachora.my'))->assertSessionHas('my_sent');
        Mail::assertNothingSent();
        $this->assertStringContainsString('kod jest w drodze', (string) $this->get('/moje-zlecenia')->getContent(), 'zelfde melding, geen verklapping');
    }

    public function test_the_right_code_shows_the_own_requests_with_links_and_the_wrong_code_is_refused(): void
    {
        $open = $this->job();
        $pending = $this->job(['status' => 'pending', 'confirmed_at' => null, 'title' => 'Malowanie']);
        $this->job(['customer_email' => 'other@example.com', 'title' => 'Cudze zlecenie']);
        $this->job(['customer_email' => 'demo-1@' . ServiceJob::DEMO_DOMAIN, 'title' => 'Zlecenie demonstracyjne QX']);
        $this->post('/moje-zlecenia/kod', ['email' => 'anna@example.com']);
        $code = null;
        Mail::assertSent(FachoraMail::class, function (FachoraMail $m) use (&$code) { $code = $m->extra['code'] ?? null; return $m->kind === 'login_code'; });

        $this->post('/moje-zlecenia/zaloguj', ['email' => 'anna@example.com', 'code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/moje-zlecenia/zaloguj', ['email' => 'anna@example.com', 'code' => $code])->assertRedirect(route('fachora.my'))->assertSessionHasNoErrors();
        $html = (string) $this->get('/moje-zlecenia')->assertOk()->getContent();
        $this->assertStringContainsString('Zalogowano jako anna@example.com', $html);
        $this->assertStringContainsString($open->url(), $html);
        $this->assertStringContainsString($pending->confirmUrl(), $html, 'onbevestigde aanvraag linkt naar de bevestigingspagina');
        $this->assertStringContainsString('Do potwierdzenia', $html);
        $this->assertStringNotContainsString('Cudze zlecenie', $html);
        $this->assertStringNotContainsString('Zlecenie demonstracyjne QX', $html);

        $this->post('/moje-zlecenia/wyloguj')->assertRedirect(route('fachora.my'));
        $this->assertStringNotContainsString('Zalogowano', (string) $this->get('/moje-zlecenia')->getContent());
        $this->post('/moje-zlecenia/zaloguj', ['email' => 'anna@example.com', 'code' => $code])->assertSessionHasErrors('code'); // code is eenmalig
    }

    public function test_five_wrong_attempts_burn_the_code(): void
    {
        $this->job();
        $this->post('/moje-zlecenia/kod', ['email' => 'anna@example.com']);
        $code = null;
        Mail::assertSent(FachoraMail::class, function (FachoraMail $m) use (&$code) { $code = $m->extra['code'] ?? null; return true; });
        foreach (range(1, 5) as $i) {
            $this->post('/moje-zlecenia/zaloguj', ['email' => 'anna@example.com', 'code' => '111111'])->assertSessionHasErrors('code');
        }
        $this->post('/moje-zlecenia/zaloguj', ['email' => 'anna@example.com', 'code' => $code])->assertSessionHasErrors('code');
        $this->assertNull(session(CustomerAccessController::SESSION_EMAIL));
    }

    public function test_the_code_mail_carries_the_code_and_the_page_address(): void
    {
        $c = (new FachoraMail('login_code', 'customer', $this->job(), null, null, ['code' => '123456']))->compose();
        $this->assertStringContainsString('123456', $c['subject']);
        $this->assertSame('123456', $c['title']);
        $this->assertStringContainsString(route('fachora.my'), implode(' ', $c['paragraphs']));
        $this->assertNull($c['button']);
    }
}
