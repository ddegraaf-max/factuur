<?php

namespace Tests\Feature;

use App\Mail\IncassoDossierMail;
use App\Mail\PaymentDemandMail;
use App\Mail\PaymentDemandNoticeMail;
use App\Mail\PublicDemandMail;
use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Services\PaymentDemandService;
use App\Services\PublicDemandService;
use App\Support\LegalInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Online aanmaning zonder account (/online-aanmaning, 1.67.0): formulier,
 * bevestigen via de link in de mail, en pas dan staat de pagina online en
 * gaat de mail naar de klant. De schuldeiser volgt alles via zijn eigen link;
 * zijn eigen bezoeken en automaten tellen niet als geopend.
 */
class PublicDemandTest extends TestCase
{
    use RefreshDatabase;

    private const CREDITOR_IP = '198.51.100.7';

    private const DEBTOR_IP = '203.0.113.9';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::fake();
        $this->travelTo(Carbon::parse('2026-10-05 10:00'));
        $this->visitor(self::CREDITOR_IP);
    }

    private function visitor(string $ip, string $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/130.0'): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $agent]);
    }

    /** @return array<string, mixed> */
    private function form(array $override = []): array
    {
        return $override + [
            'van_bedrijf' => 'Jansen Timmerwerk',
            'van_email' => 'jan@jansentimmerwerk.test',
            'van_adres' => "Dorpsstraat 1\n1431 AB Aalsmeer",
            'van_iban' => 'NL91 ABNA 0417 1643 00',
            'van_kvk' => '12345678',
            'geen_btw_aftrek' => '0',
            'aan_naam' => 'Bakkerij Het Stoepje',
            'aan_email' => 'jan@hetstoepje.test',
            'aan_adres' => "Kerkstraat 3\n1211 CK Hilversum",
            'klant' => 'zakelijk',
            'factuurnummer' => '2026-017',
            'factuurdatum' => '2026-08-01',
            'vervaldatum' => '2026-08-31',
            'bedrag' => '1.250,50',
            'rente' => '1',
        ];
    }

    /** Maakt een aanmaning en bevestigt haar, zoals de schuldeiser dat doet. */
    private function confirmed(array $override = []): PaymentDemand
    {
        $this->visitor(self::CREDITOR_IP)->post(route('aanmaning.store'), $this->form($override))->assertSessionHasNoErrors();
        $demand = PaymentDemand::latest('id')->firstOrFail();
        $this->post(route('demand.confirm.store', $demand->token), ['k' => $demand->creditor_key])->assertRedirect();

        return $demand->fresh();
    }

    public function test_the_page_shows_the_form_the_example_and_the_old_tool_redirects(): void
    {
        $this->get('/online-aanmaning')->assertOk()
            ->assertSee('Gratis aanmaning die zelf')
            ->assertSee('Maak de aanmaning, gratis')
            ->assertSee('Bekijk het voorbeeld');
        $this->get('/aanmaning-maken')->assertRedirect('/online-aanmaning')->assertStatus(301);

        // Het voorbeeld: de pagina van de klant met verzonnen gegevens en de drie mails; er wordt niets bewaard.
        $this->get(route('aanmaning.example'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('Demands/Show')
            ->where('valid', true)
            ->where('invoice.customer_name', 'Voorbeeld Klant B.V.')
            ->where('claim.principal', 12400)
            ->where('demand.pdf_url', null)
            ->has('demo.mails', 3)
            ->where('demo.mails.0.to', 'facturen@jouwbedrijf.nl')
            ->where('demo.mails.1.to', 'administratie@voorbeeldklant.nl')
            ->where('demo.mails.1.html', fn ($html) => str_contains($html, 'Laatste aanmaning') && ! str_contains($html, '/aanmaning/voorbeeld')));
        $this->assertSame(0, PaymentDemand::count());
        Mail::assertNothingSent();
    }

    public function test_nothing_reaches_the_customer_until_the_creditor_confirms_his_address(): void
    {
        $this->post(route('aanmaning.store'), $this->form(['factuur' => UploadedFile::fake()->createWithContent('factuur-2026-017.pdf', '%PDF-1.4 factuur')]))
            ->assertSessionHasNoErrors();
        $demand = PaymentDemand::firstOrFail();

        $this->assertNull($demand->invoice_id);
        $this->assertNull($demand->company_id);
        $this->assertSame('pending', $demand->status);
        $this->assertSame(64, strlen($demand->token));
        $this->assertSame(48, strlen($demand->creditor_key));
        $this->assertSame(self::CREDITOR_IP, $demand->creator_ip);
        $this->assertEqualsWithDelta(1250.50, (float) $demand->amount, 0.001);
        $this->assertSame('factuur-2026-017.pdf', $demand->fileInfo()->filename);
        $this->assertSame(0, Invoice::count(), 'Een aanmaning zonder account maakt geen factuur of administratie');

        // Alleen de schuldeiser krijgt post: de link om te bevestigen.
        Mail::assertSent(PublicDemandMail::class, fn (PublicDemandMail $mail) => $mail->kind === 'confirm' && $mail->hasTo('jan@jansentimmerwerk.test'));
        Mail::assertNotSent(PaymentDemandMail::class);
        $html = (new PublicDemandMail($demand, 'confirm', app(PaymentDemandService::class)->claim($demand)))->render();
        $this->assertStringContainsString('aanmaning/' . $demand->token . '/bevestigen?k=' . $demand->creditor_key, $html);
        $this->assertStringContainsString('Was jij dit niet?', $html);

        // Wachtpagina: geen sleutel in de link, dus wie hem ziet kan niets bevestigen.
        $this->get(route('aanmaning', ['wacht' => $demand->token]))->assertOk()
            ->assertSee('Bevestig je e-mailadres')
            ->assertSee('jan@jansentimmerwerk.test')
            ->assertDontSee($demand->creditor_key);

        // Voor de buitenwereld bestaat de aanmaning nog niet.
        $this->visitor(self::DEBTOR_IP)->get(route('demand.show', $demand->token))->assertOk()->assertInertia(fn ($page) => $page->where('valid', false));
        $this->get(route('demand.pdf', $demand->token))->assertNotFound();
        $this->get(route('demand.file', $demand->token))->assertNotFound();
        $this->post(route('demand.respond', $demand->token), ['response' => 'paid'])->assertNotFound();
        $this->get(route('demand.confirm', ['token' => $demand->token, 'k' => 'verkeerd']))->assertNotFound();
        $this->post(route('demand.confirm.store', $demand->token), ['k' => 'verkeerd'])->assertNotFound();

        // De link uit de mail toont de gegevens; openen alleen bevestigt niets (scanners van mailservers).
        $this->visitor(self::CREDITOR_IP)->get($demand->confirmUrl())->assertOk()
            ->assertSee('Bevestig je aanmaning')
            ->assertSee('Ik bevestig: verstuur de aanmaning')
            ->assertSee('Bakkerij Het Stoepje');
        $this->assertSame('pending', $demand->fresh()->status);
        Mail::assertNotSent(PaymentDemandMail::class);
    }

    public function test_confirming_puts_the_demand_online_and_mails_the_customer(): void
    {
        $this->post(route('aanmaning.store'), $this->form(['factuur' => UploadedFile::fake()->createWithContent('factuur-2026-017.pdf', '%PDF-1.4 factuur')]));
        $demand = PaymentDemand::firstOrFail();

        $this->travel(2)->days();
        $this->post(route('demand.confirm.store', $demand->token), ['k' => $demand->creditor_key])
            ->assertRedirect(route('aanmaning', ['ok' => $demand->token, 'k' => $demand->creditor_key]));
        $demand->refresh();

        $this->assertSame('sent', $demand->status);
        $this->assertNotNull($demand->confirmed_at);
        $this->assertSame(self::CREDITOR_IP, $demand->confirm_ip);
        $this->assertSame('2026-10-14', $demand->deadline->toDateString(), 'De termijn van zeven dagen begint op de dag van bevestigen');
        $this->assertSame(['created', 'confirmed', 'sent'], $demand->events()->pluck('event')->all());

        // De klant krijgt de aanmaning, met de brief en de kopie van de factuur; antwoorden gaan naar de schuldeiser.
        Mail::assertSent(PaymentDemandMail::class, function (PaymentDemandMail $mail) {
            $envelope = $mail->envelope();

            return $mail->hasTo('jan@hetstoepje.test')
                && count($mail->attachments()) === 2
                && $mail->copy['name'] === 'factuur-2026-017.pdf'
                && $envelope->replyTo[0]->address === 'jan@jansentimmerwerk.test'
                && str_contains($envelope->from->name, 'Jansen Timmerwerk via');
        });
        Mail::assertSent(PublicDemandMail::class, fn (PublicDemandMail $mail) => $mail->kind === 'ready' && $mail->hasTo('jan@jansentimmerwerk.test'));

        $service = app(PaymentDemandService::class);
        $html = (new PaymentDemandMail($demand, $service->claimAsSent($demand), '', ''))->render();
        $this->assertStringContainsString('aanmaning/' . $demand->token, $html);
        $this->assertStringNotContainsString($demand->creditor_key, $html);
        $this->assertStringContainsString(money(LegalInterest::collectionCosts(1250.50)), $html);

        // Nog eens klikken verstuurt niets nog een keer.
        $this->post(route('demand.confirm.store', $demand->token), ['k' => $demand->creditor_key])->assertRedirect();
        $this->assertSame(1, collect(Mail::sent(PaymentDemandMail::class))->count());

        $this->get(route('aanmaning', ['ok' => $demand->token, 'k' => $demand->creditor_key]))->assertOk()
            ->assertSee('Je aanmaning staat online')
            ->assertSee('verstuurd naar jan@hetstoepje.test')
            ->assertSee('14 oktober 2026');
        $this->get(route('aanmaning', ['ok' => $demand->token, 'k' => 'verkeerd']))->assertOk()->assertDontSee('Je aanmaning staat online');
        $this->get(route('demand.file', $demand->token))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_the_confirmation_link_expires_and_the_mail_can_be_resent_a_few_times(): void
    {
        $this->post(route('aanmaning.store'), $this->form());
        $demand = PaymentDemand::firstOrFail();
        $confirms = fn () => collect(Mail::sent(PublicDemandMail::class))->filter(fn ($mail) => $mail->kind === 'confirm')->count();
        $this->assertSame(1, $confirms());

        $this->post(route('aanmaning.resend'), ['token' => $demand->token])->assertRedirect(route('aanmaning', ['wacht' => $demand->token, 'opnieuw' => 1]));
        $this->assertSame(2, $confirms());
        // Hooguit één keer per minuut.
        $this->post(route('aanmaning.resend'), ['token' => $demand->token])->assertRedirect(route('aanmaning', ['wacht' => $demand->token, 'opnieuw' => 'limiet']));
        $this->assertSame(2, $confirms());
        $this->travel(2)->minutes();
        $this->post(route('aanmaning.resend'), ['token' => $demand->token]);
        $this->travel(2)->minutes();
        $this->post(route('aanmaning.resend'), ['token' => $demand->token]);
        $this->assertSame(4, $confirms());
        // Na drie keer opnieuw is het op.
        $this->travel(2)->minutes();
        $this->post(route('aanmaning.resend'), ['token' => $demand->token])->assertRedirect(route('aanmaning', ['wacht' => $demand->token, 'opnieuw' => 'limiet']));
        $this->assertSame(4, $confirms());

        $this->travel(8)->days();
        $this->post(route('demand.confirm.store', $demand->token), ['k' => $demand->creditor_key])->assertStatus(410);
        $this->assertSame('pending', $demand->fresh()->status);
        Mail::assertNotSent(PaymentDemandMail::class);

        // Na dertig dagen verdwijnt wat nooit is bevestigd.
        $this->travel(25)->days();
        $this->artisan('demands:notify')->assertSuccessful();
        $this->assertSame(0, PaymentDemand::count());
    }

    public function test_only_the_customer_counts_as_opened_and_he_answers_once(): void
    {
        $demand = $this->confirmed(['klant' => 'particulier', 'bedrag' => '400', 'geen_btw_aftrek' => '1']);
        $this->assertSame(14, $demand->term_days);
        $this->assertSame('2026-10-20', $demand->deadline->toDateString(), 'Veertien dagen vanaf de dag na ontvangst, met een dag marge');
        $this->assertEqualsWithDelta(12.6, (float) $demand->costs_vat, 0.001);

        // De schuldeiser: met zijn sleutel, of vanaf het adres waarmee hij de aanmaning maakte.
        $this->visitor(self::CREDITOR_IP)->get($demand->creditorUrl())->assertOk()->assertInertia(fn ($page) => $page
            ->where('creditor.opens', 0)
            ->where('creditor.first_open_label', null)
            ->where('creditor.debtor_url', $demand->url())
            ->where('creditor.key', $demand->creditor_key)
            ->where('creditor.can_close', true)
            ->where('creditor.can_transfer', false)
            ->where('demand.standalone', true)
            ->where('company.name', 'Jansen Timmerwerk')
            ->where('company.iban', 'NL91 ABNA 0417 1643 00')
            ->where('invoice.customer_name', 'Bakkerij Het Stoepje')
            ->where('claim.principal', 400));
        // Zonder sleutel geen overzicht, ook niet vanaf zijn eigen adres; het bezoek telt dan wel als het zijne.
        $this->visitor(self::CREDITOR_IP)->get($demand->url())->assertOk()->assertInertia(fn ($page) => $page->where('creditor', null));
        // Een scanner van een mailserver.
        $this->visitor('192.0.2.44', 'Mozilla/5.0 (compatible; Barracuda Sentinel/1.0)')->get($demand->url())->assertOk();
        $demand->refresh();
        $this->assertNull($demand->first_opened_at);
        $this->assertSame(['bot'], $demand->events()->where('event', 'opened')->pluck('actor')->all());

        // De klant.
        $this->flushSession();
        $this->visitor(self::DEBTOR_IP)->get($demand->url())->assertOk()->assertInertia(fn ($page) => $page
            ->where('creditor', null)
            ->where('demand.status', 'sent')
            ->where('demand.opened', true)
            ->where('t.footer', fn ($text) => str_contains($text, 'in opdracht van Jansen Timmerwerk')));
        $this->assertNotNull($demand->fresh()->first_opened_at);

        // De schuldeiser kan niet namens zijn klant antwoorden.
        $this->visitor(self::DEBTOR_IP)->post(route('demand.respond', $demand->token), ['response' => 'paid', 'k' => $demand->creditor_key])->assertSessionHasErrors('demand');
        $this->assertNull($demand->fresh()->response);

        $this->visitor(self::DEBTOR_IP)->post(route('demand.respond', $demand->token), ['response' => 'promise', 'date' => '2026-10-12', 'note' => 'Na mijn salaris'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('promise', $demand->fresh()->response);
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'response' && $mail->hasTo('jan@jansentimmerwerk.test'));
        $notice = Mail::sent(PaymentDemandNoticeMail::class)->first()->render();
        $this->assertStringContainsString(e($demand->creditorUrl()), $notice);
        $this->assertStringContainsString('Na mijn salaris', $notice);

        // Een tweede reactie wordt geweigerd; de eerste blijft staan.
        $this->visitor(self::DEBTOR_IP)->post(route('demand.respond', $demand->token), ['response' => 'dispute', 'note' => 'Toch niet'])->assertSessionHasErrors('demand');
        $demand->refresh();
        $this->assertSame('promise', $demand->response);
        $this->assertSame('Na mijn salaris', $demand->response_note);
        $this->assertSame(1, $demand->events()->where('event', 'rejected')->count());

        $this->visitor(self::CREDITOR_IP)->get($demand->creditorUrl())->assertOk()->assertInertia(fn ($page) => $page
            ->where('creditor.opens', 1)
            ->where('demand.response', 'promise')
            ->where('demand.response_note', 'Na mijn salaris'));
    }

    public function test_after_the_term_the_creditor_hands_the_file_to_the_bailiff_with_one_click(): void
    {
        $demand = $this->confirmed(['factuur' => UploadedFile::fake()->createWithContent('factuur.pdf', '%PDF-1.4 factuur')]);
        $this->visitor(self::DEBTOR_IP)->get($demand->url())->assertOk();

        // Zonder sleutel gebeurt er niets; zolang de termijn loopt kan het nog niet.
        $this->post(route('demand.transfer', $demand->token), ['k' => 'verkeerd'])->assertNotFound();
        $this->visitor(self::CREDITOR_IP)->post(route('demand.transfer', $demand->token), ['k' => $demand->creditor_key])
            ->assertRedirect($demand->creditorUrl())->assertSessionHasErrors('demand');
        $this->assertSame('sent', $demand->fresh()->status);

        $this->travel(8)->days();
        $this->artisan('demands:notify')->assertSuccessful();
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'expired' && $mail->hasTo('jan@jansentimmerwerk.test') && $mail->autoDate === null);
        $this->get($demand->creditorUrl())->assertOk()->assertInertia(fn ($page) => $page->where('creditor.can_transfer', true)->where('claim.costs_due', true));

        $this->post(route('demand.transfer', $demand->token), ['k' => $demand->creditor_key])->assertRedirect($demand->creditorUrl())->assertSessionHasNoErrors();
        $demand->refresh();
        $this->assertSame('transferred', $demand->status);
        $this->assertSame(0, Invoice::count(), 'Ook bij de overdracht ontstaat er geen factuur in een administratie');

        Mail::assertSent(IncassoDossierMail::class, function (IncassoDossierMail $mail) use ($demand) {
            $names = array_column($mail->files, 'name');

            return $mail->hasTo(\App\Support\Market::incasso('claims_email'))
                && $mail->pdf === ''
                && $names === ['aanmaning-2026-017.pdf', 'factuur.pdf']
                && count($mail->attachments()) === 2
                && $mail->demand->id === $demand->id
                && $mail->invoice->incasso_reference === sprintf('ARM-2026-W%05d', $demand->id);
        });
        $dossier = Mail::sent(IncassoDossierMail::class)->first()->render();
        $this->assertStringContainsString('Jansen Timmerwerk', $dossier);
        $this->assertStringContainsString('Bakkerij Het Stoepje', $dossier);
        $this->assertStringContainsString('Gemaakt zonder account', $dossier);
        $this->assertStringContainsString('Pagina van de aanmaning geopend', $dossier);
        $this->assertStringContainsString('De kopie van de factuur die de schuldeiser meestuurde', $dossier);
        Mail::assertSent(PaymentDemandNoticeMail::class, fn (PaymentDemandNoticeMail $mail) => $mail->kind === 'handed' && $mail->hasTo('jan@jansentimmerwerk.test'));

        $this->visitor(self::DEBTOR_IP)->get($demand->url())->assertOk()->assertInertia(fn ($page) => $page->where('demand.status', 'transferred')->where('qr', null));
        $this->post(route('demand.respond', $demand->token), ['response' => 'paid'])->assertSessionHasErrors('demand');
    }

    public function test_the_creditor_closes_a_demand_as_paid_or_withdraws_it(): void
    {
        $paid = $this->confirmed();
        $this->post(route('demand.paid', $paid->token), ['k' => 'verkeerd'])->assertNotFound();
        $this->post(route('demand.paid', $paid->token), ['k' => $paid->creditor_key])->assertRedirect($paid->creditorUrl())->assertSessionHasNoErrors();
        $this->assertSame('paid', $paid->fresh()->status);
        $this->visitor(self::DEBTOR_IP)->get($paid->url())->assertOk()->assertInertia(fn ($page) => $page->where('demand.status', 'paid')->where('claim.principal', 0));

        $withdrawn = $this->confirmed(['factuurnummer' => '2026-018', 'aan_email' => 'ander@klant.test']);
        $this->visitor(self::CREDITOR_IP)->post(route('demand.withdraw', $withdrawn->token), ['k' => $withdrawn->creditor_key])->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $withdrawn->fresh()->status);
        $this->post(route('demand.withdraw', $withdrawn->token), ['k' => $withdrawn->creditor_key])->assertSessionHasErrors('demand');

        // Een gesloten aanmaning krijgt geen melding over een verstreken termijn.
        $this->travel(20)->days();
        $this->artisan('demands:notify')->assertSuccessful();
        Mail::assertNotSent(PaymentDemandNoticeMail::class);
    }

    public function test_the_form_checks_the_input_and_puts_a_brake_on_abuse(): void
    {
        $this->post(route('aanmaning.store'), $this->form(['van_email' => '']))->assertSessionHasErrors('van_email');
        $this->post(route('aanmaning.store'), $this->form(['aan_email' => 'jan@jansentimmerwerk.test']))->assertSessionHasErrors('aan_email');
        $this->post(route('aanmaning.store'), $this->form(['vervaldatum' => '2026-10-20']))->assertSessionHasErrors('vervaldatum');
        $this->post(route('aanmaning.store'), $this->form(['bedrag' => '0']))->assertSessionHasErrors('bedrag');
        $this->post(route('aanmaning.store'), $this->form(['klant' => 'particulier', 'termijn' => '7']))->assertSessionHasErrors('termijn');
        $this->post(route('aanmaning.store'), $this->form(['van_iban' => 'geen iban']))->assertSessionHasErrors('van_iban');
        $this->post(route('aanmaning.store'), $this->form(['factuur' => UploadedFile::fake()->create('factuur.exe', 20, 'application/x-msdownload')]))->assertSessionHasErrors('factuur');
        // Het lokveld: ingevuld door een robot.
        $this->post(route('aanmaning.store'), $this->form(['website' => 'https://spam.test']))->assertRedirect(route('aanmaning'));
        $this->assertSame(0, PaymentDemand::count());
        Mail::assertNothingSent();

        // Dezelfde klant krijgt hoogstens drie aanmaningen per dag, van wie dan ook.
        foreach (['2026-021', '2026-022', '2026-023'] as $i => $number) {
            $this->visitor('198.51.100.' . (20 + $i));
            $this->confirmed(['factuurnummer' => $number, 'van_email' => "afzender{$i}@bedrijf.test"]);
        }
        $this->visitor('198.51.100.30')->post(route('aanmaning.store'), $this->form(['factuurnummer' => '2026-024']))->assertSessionHasErrors('aan_email');
        // Zonder adres van de klant kan het wel: dan stuurt de schuldeiser de link zelf.
        $this->post(route('aanmaning.store'), $this->form(['factuurnummer' => '2026-024', 'aan_email' => '']))->assertSessionHasNoErrors();
        $own = PaymentDemand::latest('id')->firstOrFail();
        $this->post(route('demand.confirm.store', $own->token), ['k' => $own->creditor_key])->assertRedirect();
        $this->assertSame('sent', $own->fresh()->status);
        $this->assertSame(3, collect(Mail::sent(PaymentDemandMail::class))->count(), 'Zonder adres gaat er geen mail naar de klant');
        $this->get(route('aanmaning', ['ok' => $own->token, 'k' => $own->creditor_key]))->assertOk()->assertSee('geen adres ingevuld');

        // Vanaf één verbinding hoogstens vijf per uur.
        $this->visitor('198.51.100.99');
        foreach (range(1, PublicDemandService::LIMIT['hour']) as $n) {
            $this->post(route('aanmaning.store'), $this->form(['factuurnummer' => "2026-1{$n}", 'aan_email' => '']))->assertSessionHasNoErrors();
        }
        $this->post(route('aanmaning.store'), $this->form(['factuurnummer' => '2026-199', 'aan_email' => '']))->assertSessionHasErrors('limiet');
    }

    public function test_the_calculation_needs_only_amounts_and_dates(): void
    {
        $this->getJson(route('aanmaning.calculation', ['bedrag' => '1.000,00', 'vervaldatum' => '2026-08-31', 'klant' => 'zakelijk', 'rente' => 1, 'aan_email' => 1]))
            ->assertOk()
            ->assertJson(['with_interest' => true, 'principal' => money(1000), 'costs' => money(150), 'term_days' => 7])
            ->assertJsonPath('note', fn ($note) => str_contains($note, '12 oktober 2026'));

        // Particulier zonder adres: twee dagen voor de post, dan veertien dagen vanaf de dag na ontvangst.
        $this->getJson(route('aanmaning.calculation', ['bedrag' => '1000', 'vervaldatum' => '2026-08-31', 'klant' => 'particulier', 'rente' => 0, 'geen_btw_aftrek' => 1, 'aan_email' => 0]))
            ->assertOk()
            ->assertJson(['with_interest' => false, 'total' => money(1000), 'costs' => money(181.5), 'term_days' => 14])
            ->assertJsonPath('note', fn ($note) => str_contains($note, '22 oktober 2026'));

        $this->getJson(route('aanmaning.calculation', ['bedrag' => 'veel', 'vervaldatum' => '2026-08-31', 'klant' => 'zakelijk']))->assertStatus(422);
        $this->assertSame(0, PaymentDemand::count());
    }
}
