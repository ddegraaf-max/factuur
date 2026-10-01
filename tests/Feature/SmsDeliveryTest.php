<?php

namespace Tests\Feature;

use App\Models\SmsMessage;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Models\WorkPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Afleverstatus van sms'en (1.76.0): de webhook van Smstools zet
 * afgeleverd / niet afgeleverd bij het bericht, met handtekeningcontrole als
 * er een secret is; de uitvraagpagina en de sms-instellingen tonen het.
 */
class SmsDeliveryTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    private function webhook(array $message, ?string $secret = null, array $headers = []): \Illuminate\Testing\TestResponse
    {
        $body = json_encode(['webhook_id' => 1, 'webhook_type' => 'delivery_report', 'username' => 'creditline', 'message' => $message]);
        if ($secret !== null) {
            $t = (string) time();
            $headers['X-Smstools-Signature'] = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $body, $secret);
        }

        return $this->call('POST', route('webhooks.smstools'), [], [], [], array_merge(['CONTENT_TYPE' => 'application/json'], collect($headers)->mapWithKeys(fn ($v, $k) => ['HTTP_' . strtoupper(str_replace('-', '_', $k)) => $v])->all()), $body);
    }

    public function test_the_webhook_records_delivery_and_the_pages_show_it(): void
    {
        Mail::fake();
        config(['services.smstools.client_id' => 'id', 'services.smstools.client_secret' => 'secret', 'services.smstools.companies' => '*', 'services.smstools.webhook_secret' => null]);
        Http::fake(['*smsgatewayapi*' => Http::response(['messageid' => 'msg-123'], 200), '*' => Http::response([], 200)]);
        $user = $this->demoUser();
        $user->company->forceFill(['is_exempt' => true])->save();
        $this->actingAs($user);

        // Een uitvraag per sms naar een bedrijf met alleen een mobiel nummer.
        $this->post(route('tenders.packages.seed'))->assertRedirect();
        $package = WorkPackage::where('name', 'Metselwerk')->firstOrFail();
        $sub = Subcontractor::create(['name' => 'Mobiel Metselaar', 'phone' => '06 12345678']);
        $sub->workPackages()->sync([$package->id]);
        $this->post(route('tenders.store'), [
            'title' => 'Gevel', 'work_package_id' => $package->id, 'subcontractor_ids' => [$sub->id], 'deadline' => now()->addDays(7)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $round = TenderRound::firstOrFail();
        $message = SmsMessage::firstOrFail();
        $this->assertSame(['sent', 'msg-123', null], [$message->status, $message->provider_id, $message->delivery_status]);

        // Onderweg, dan afgeleverd; een latere 'submitted' overschrijft 'afgeleverd' niet.
        $this->webhook(['messageid' => 'msg-123', 'delivery_code' => '0', 'delivery_status' => 'submitted'])->assertOk();
        $this->assertSame('pending', $message->fresh()->delivery_status);
        $this->webhook(['messageid' => 'msg-123', 'delivery_code' => '1', 'delivery_status' => 'delivered', 'delivery_status_datetime' => '2026-10-01 16:00:00'])->assertOk();
        $message->refresh();
        $this->assertSame('delivered', $message->delivery_status);
        $this->assertNotNull($message->delivered_at);
        $this->webhook(['messageid' => 'msg-123', 'delivery_code' => '0', 'delivery_status' => 'submitted'])->assertOk();
        $this->assertSame('delivered', $message->fresh()->delivery_status);

        // Onbekend messageid: genegeerd, geen fout.
        $this->webhook(['messageid' => 'bestaat-niet', 'delivery_code' => '1'])->assertOk();
        $this->assertSame(1, SmsMessage::count());

        // De uitvraagpagina en de sms-instellingen tonen het.
        $this->get(route('tenders.show', $round))->assertOk()->assertInertia(fn ($page) => $page
            ->where('requests.0.sms_delivery', 'delivered')->where('requests.0.sms_delivery_label', 'afgeleverd'));
        $this->get(route('settings.sms'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('messages.0.delivery', 'delivered')->where('webhook.secret_set', false)->where('webhook.url', route('webhooks.smstools')));

        // Niet afgeleverd, met reden.
        $message->forceFill(['delivery_status' => null])->save();
        $this->webhook(['messageid' => 'msg-123', 'delivery_code' => '2', 'delivery_status' => 'not delivered', 'delivery_code_detail' => 'Receiver number is invalid'])->assertOk();
        $this->assertSame(['failed', 'Receiver number is invalid'], [$message->fresh()->delivery_status, $message->fresh()->delivery_detail]);
    }

    public function test_with_a_secret_the_signature_is_required(): void
    {
        config(['services.smstools.webhook_secret' => 'geheim']);
        $user = $this->demoUser();
        $message = SmsMessage::create(['company_id' => $user->company_id, 'recipient' => '31612345678', 'sender' => 'Test', 'body' => 'x', 'status' => 'sent', 'provider_id' => 'msg-9']);

        $this->webhook(['messageid' => 'msg-9', 'delivery_code' => '1'])->assertStatus(401);
        $this->webhook(['messageid' => 'msg-9', 'delivery_code' => '1'], 'verkeerd')->assertStatus(401);
        $this->assertNull($message->fresh()->delivery_status);
        $this->webhook(['messageid' => 'msg-9', 'delivery_code' => '1'], 'geheim')->assertOk();
        $this->assertSame('delivered', $message->fresh()->delivery_status);
    }
}
