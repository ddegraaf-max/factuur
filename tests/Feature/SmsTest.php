<?php

namespace Tests\Feature;

use App\Mail\TenderMail;
use App\Models\ShortLink;
use App\Models\SmsMessage;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use App\Services\SmsService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Sms bij uitvragen (1.69.0): een bedrijf zonder e-mailadres maar met een
 * mobiel nummer krijgt de aanvraag per sms, met een kort adres naar de
 * reactiepagina. Versturen loopt via Smstools en kost geld: alleen voor
 * administraties die daarvoor zijn aangewezen, met een grens per maand.
 */
class SmsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function enableSms(?int $companyId = null): void
    {
        config([
            'services.smstools.client_id' => 'test-id',
            'services.smstools.client_secret' => 'test-secret',
            'services.smstools.companies' => $companyId ? (string) $companyId : '',
        ]);
        Http::fake(['api.smsgatewayapi.com/*' => Http::response(['messageid' => 'h2md1ewkyzjkuyn9ak7pryw1evtyw3x'], 200)]);
    }

    /** @return array{0: WorkPackage, 1: Subcontractor, 2: Subcontractor, 3: Subcontractor} */
    private function pool(): array
    {
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $make = function (string $name, ?string $email, ?string $phone) use ($package) {
            $s = Subcontractor::create(['name' => $name, 'email' => $email, 'phone' => $phone, 'city' => 'Woerden']);
            $s->workPackages()->sync([$package->id]);

            return $s;
        };

        return [
            $package,
            $make('Metselbedrijf Mail', 'info@mail.test', '06-11111111'),
            $make('Metselbedrijf Mobiel', null, '0348-416571 / 06 22221108'),
            $make('Metselbedrijf Vast', null, '0348-416571'),
        ];
    }

    public function test_phone_numbers_are_read_as_mobile_or_not(): void
    {
        $this->assertSame('31612345678', PhoneNumber::mobile('06-12345678'));
        $this->assertSame('31612345678', PhoneNumber::mobile('06 12 34 56 78'));
        $this->assertSame('31612345678', PhoneNumber::mobile('+31 6 12345678'));
        $this->assertSame('31612345678', PhoneNumber::mobile('+31 (0)6 1234 5678'));
        $this->assertSame('31612345678', PhoneNumber::mobile('0031612345678'));
        $this->assertSame('31640993654', PhoneNumber::mobile('030 637 6170 / 06 40993654'), 'Het mobiele nummer uit een veld met twee nummers');
        $this->assertNull(PhoneNumber::mobile('0348-416571'), 'Een vast nummer krijgt geen sms');
        $this->assertNull(PhoneNumber::mobile('+31 348 416571'));
        $this->assertNull(PhoneNumber::mobile('06-1234'));
        $this->assertNull(PhoneNumber::mobile(null));
        $this->assertSame('+31 6 12345678', PhoneNumber::display('31612345678'));
    }

    public function test_the_sender_and_the_text_fit_in_an_sms(): void
    {
        $user = $this->demoUser();
        $sms = app(SmsService::class);

        $user->company->name = 'Creditline B.V.';
        $this->assertSame('Creditline', $sms->sender($user->company));
        $user->company->name = 'Bouwbedrijf Van der Meer & Zn';
        $this->assertSame('Bouwbedrijf', $sms->sender($user->company), 'Hooguit elf letters en cijfers');

        // De é past in een gewone sms en blijft staan; de ó en de gekrulde aanhalingstekens niet.
        $this->assertSame('Reageer voor 1 oktober - "José"', $sms->plain('Reageer vóór 1 oktober – “José”'));
        $this->assertSame(1, $sms->segments(str_repeat('a', 160)));
        $this->assertSame(2, $sms->segments(str_repeat('a', 161)));
        $this->assertSame(2, $sms->segments(str_repeat('€', 81)), 'Het euroteken telt dubbel');
    }

    public function test_without_keys_or_for_another_company_there_is_no_sms(): void
    {
        $user = $this->demoUser();
        $other = $this->demoUser();
        $sms = app(SmsService::class);

        $this->assertFalse($sms->available($user->company), 'Zonder sleutels bestaat de functie niet');
        $this->assertSame('SMSTOOLS_CLIENT_ID', $sms->missing());

        $this->enableSms($user->company_id);
        $sms = new SmsService;
        $this->assertTrue($sms->available($user->company));
        $this->assertFalse($sms->available($other->company), 'Alleen de aangewezen administratie');
        $this->assertNull($sms->missing());

        $other->company->forceFill(['is_demo' => true])->save();
        config(['services.smstools.companies' => '*']);
        $this->assertFalse((new SmsService)->available($other->company->fresh()), 'Een demo verstuurt nooit een sms');

        $this->expectException(\DomainException::class);
        (new SmsService)->send($other->company->fresh(), '06-12345678', 'Hallo');
    }

    public function test_a_company_without_email_gets_the_request_by_sms(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $user->company->update(['name' => 'Creditline BV', 'phone' => '0297 - 12 34 56']);
        $this->enableSms($user->company_id);
        [$package, $mail, $mobile, $landline] = $this->pool();

        // In het keuzevenster: per mail, per sms, of niet te bereiken.
        $this->get(route('tenders.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('packages', fn ($packages) => collect($packages)->firstWhere('id', $package->id)['subcontractors'] == [
                ['id' => $mail->id, 'name' => 'Metselbedrijf Mail', 'city' => 'Woerden', 'has_email' => true, 'by_sms' => false],
                ['id' => $mobile->id, 'name' => 'Metselbedrijf Mobiel', 'city' => 'Woerden', 'has_email' => false, 'by_sms' => true],
                ['id' => $landline->id, 'name' => 'Metselbedrijf Vast', 'city' => 'Woerden', 'has_email' => false, 'by_sms' => false],
            ]));

        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$mail->id, $mobile->id, $landline->id],
            'title' => 'Metselwerk uitbouw',
            'location' => 'Woerden',
            'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $round = TenderRound::firstOrFail();
        $this->assertSame(2, $round->requests()->count(), 'Een vast nummer zonder e-mailadres is niet aan te schrijven');
        $byMail = $round->requests()->where('subcontractor_id', $mail->id)->firstOrFail();
        $bySms = $round->requests()->where('subcontractor_id', $mobile->id)->firstOrFail();

        Mail::assertSent(TenderMail::class, 1);
        Mail::assertSent(TenderMail::class, fn (TenderMail $m) => $m->hasTo('info@mail.test'));
        $this->assertNotNull($bySms->sent_at);
        $this->assertNotNull($bySms->sms_at);
        $this->assertNull($byMail->sms_at);

        // Eén sms, naar het mobiele nummer, met het korte adres en het telefoonnummer om te bellen.
        $link = ShortLink::where('url', $bySms->responseUrl())->firstOrFail();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.smsgatewayapi.com/v1/message/send'
            && $request->hasHeader('X-Client-Id', 'test-id')
            && $request->hasHeader('X-Client-Secret', 'test-secret')
            && $request['to'] === '31622221108'
            && $request['sender'] === 'Creditline'
            && $request['message'] === 'Creditline BV vraagt u om een prijs voor Metselwerk uitbouw (Woerden). Bekijk de aanvraag en reageer: ' . $link->shortUrl() . ' Vragen? Bel 0297 - 12 34 56');

        $message = SmsMessage::firstOrFail();
        $this->assertSame('sent', $message->status);
        $this->assertSame('h2md1ewkyzjkuyn9ak7pryw1evtyw3x', $message->provider_id);
        $this->assertSame($user->company_id, $message->company_id);
        $this->assertSame(1, $message->segments);
        $this->assertTrue($message->subject->is($bySms));

        // Het korte adres leidt naar de reactiepagina; een onbekende code niet.
        $this->assertTrue(ShortLink::isCode($link->code));
        $this->get('/u/' . $link->code)->assertRedirect($bySms->responseUrl());
        $this->assertSame(1, $link->fresh()->hits);
        $this->get('/u/abcdefgh')->assertNotFound();
        $this->get('/u/te-kort')->assertNotFound();

        // De vergelijking toont het mobiele nummer, het moment van de sms en de tekst die klaarstaat.
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->where('sms.available', true)
            ->where('sms.sender', 'Creditline')
            ->where('sms.remaining', 299)
            ->where('sms.missing', null)
            ->where('requests', fn ($requests) => collect($requests)->firstWhere('id', $bySms->id)['mobile'] === '+31 6 22221108'
                && collect($requests)->firstWhere('id', $bySms->id)['sms_at_label'] !== null
                && str_starts_with(collect($requests)->firstWhere('id', $bySms->id)['sms_text'], 'Herinnering van Creditline BV')
                && collect($requests)->firstWhere('id', $byMail->id)['mobile'] === '+31 6 11111111'));
    }

    public function test_the_owner_sends_an_sms_after_the_mail_and_the_link_must_stay(): void
    {
        Mail::fake();
        $user = $this->demoUser();
        $this->actingAs($user);
        $this->enableSms($user->company_id);
        [$package, $mail] = $this->pool();

        $this->post(route('tenders.store'), [
            'work_package_id' => $package->id,
            'subcontractor_ids' => [$mail->id],
            'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $request = $round->requests()->firstOrFail();
        Http::assertNothingSent();

        // Zonder de link kan het bedrijf niet reageren.
        $this->post(route('tenders.sms', [$round, $request]), ['text' => 'Wilt u even reageren?'])->assertSessionHasErrors('sms');
        Http::assertNothingSent();

        $link = app(\App\Services\TenderService::class)->shortUrl($request);
        $this->post(route('tenders.sms', [$round, $request]), ['text' => 'Heeft u onze mail gezien? Reageren kan hier: ' . $link])
            ->assertRedirect()->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => $r['to'] === '31611111111' && str_contains($r['message'], $link));
        $this->assertNotNull($request->fresh()->sms_at);
        $this->assertSame($user->id, SmsMessage::firstOrFail()->user_id);

        // Heeft het bedrijf gereageerd, dan gaat er geen sms meer uit.
        $request->update(['status' => 'responded', 'price' => 1500, 'responded_at' => now()]);
        $this->post(route('tenders.sms', [$round, $request]), [])->assertSessionHasErrors('sms');
        Http::assertSentCount(1);
    }

    public function test_a_refused_sms_is_logged_and_the_monthly_limit_holds(): void
    {
        $user = $this->demoUser();
        config([
            'services.smstools.client_id' => 'test-id',
            'services.smstools.client_secret' => 'test-secret',
            'services.smstools.companies' => (string) $user->company_id,
            'services.smstools.monthly_limit' => 2,
        ]);
        Http::fake(['api.smsgatewayapi.com/*' => Http::sequence()
            ->push(['error' => 108, 'errorMsg' => 'Insufficient credits'], 400)
            ->push(['messageid' => 'a1'], 200)
            ->push(['messageid' => 'a2'], 200)]);
        $sms = app(SmsService::class);

        try {
            $sms->send($user->company, '06-12345678', 'Eerste poging');
            $this->fail('Een geweigerde sms hoort een melding te geven.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Insufficient credits', $e->getMessage());
        }
        $this->assertSame(['failed', 'Insufficient credits'], [SmsMessage::firstOrFail()->status, SmsMessage::firstOrFail()->error]);
        $this->assertSame(2, $sms->remaining($user->company), 'Een mislukte sms telt niet mee');

        $sms->send($user->company, '06-12345678', 'Een');
        $sms->send($user->company, '06-12345678', 'Twee');
        $this->assertSame(0, $sms->remaining($user->company));

        try {
            $sms->send($user->company, '06-12345678', 'Drie');
            $this->fail('Boven de grens gaat er niets meer uit.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('2', $e->getMessage());
        }
        Http::assertSentCount(3);

        try {
            $sms->send($user->company, '0348-416571', 'Naar een vast nummer');
            $this->fail('Een vast nummer krijgt geen sms.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('mobiel', $e->getMessage());
        }
    }
}
