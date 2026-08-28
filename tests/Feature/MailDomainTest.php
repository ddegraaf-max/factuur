<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Support\Sender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Mail vanaf eigen domein: aanmelden bij Resend, DNS-records tonen, verifiëren en dan als afzender gebruiken. */
class MailDomainTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_domain_can_be_connected_verified_and_used_as_sender(): void
    {
        config(['services.resend.key' => 're_test', 'mail.from.address' => 'hallo@easyinvoice.nl']);
        $records = [
            ['record' => 'DKIM', 'name' => 'resend._domainkey', 'type' => 'TXT', 'value' => 'p=MIGf…', 'status' => 'not_started'],
            ['record' => 'SPF', 'name' => 'send', 'type' => 'MX', 'value' => 'feedback-smtp.eu-west-1.amazonses.com', 'priority' => 10, 'status' => 'not_started'],
        ];
        $state = ['status' => 'pending'];
        Http::fake([
            'api.resend.com/domains/dom_1/verify' => Http::response(['object' => 'domain', 'id' => 'dom_1']),
            'api.resend.com/domains/dom_1' => function () use (&$state, $records) {
                return Http::response(['id' => 'dom_1', 'name' => 'vriesdesign.nl', 'status' => $state['status'], 'records' => array_map(fn ($r) => $r + ['status' => $state['status'] === 'verified' ? 'verified' : 'pending'], $records)]);
            },
            'api.resend.com/domains' => Http::response(['id' => 'dom_1', 'name' => 'vriesdesign.nl', 'status' => 'not_started', 'records' => $records]),
        ]);

        $user = $this->demoUser();
        $this->actingAs($user);
        $company = $user->company;

        $this->post(route('settings.integrations.maildomain.connect'), ['domain' => 'vriesdesign.nl', 'local_part' => 'facturen'])->assertRedirect()->assertSessionMissing('error');
        $company->refresh();
        $this->assertSame('dom_1', $company->mail_domain_id);
        $this->assertSame('pending', $company->mail_domain_status);
        $this->assertSame('facturen@vriesdesign.nl', $company->mail_from_address);
        $this->assertCount(2, $company->mail_domain_records);
        $this->assertSame('hallo@easyinvoice.nl', Sender::email($company));   // nog niet geverifieerd → standaardadres
        $this->get(route('settings.integrations'))->assertOk();

        $state['status'] = 'verified';
        $this->post(route('settings.integrations.maildomain.refresh'))->assertRedirect();
        $company->refresh();
        $this->assertSame('verified', $company->mail_domain_status);
        $this->assertSame('facturen@vriesdesign.nl', Sender::email($company));

        $invoice = Invoice::regular()->where('status', 'sent')->firstOrFail();
        $envelope = (new InvoiceMail($invoice, ''))->envelope();
        $this->assertSame('facturen@vriesdesign.nl', $envelope->from->address);
        $this->assertSame($company->name, $envelope->from->name);

        $this->delete(route('settings.integrations.maildomain.disconnect'))->assertRedirect();
        $this->assertNull($company->fresh()->mail_domain_id);
        $this->assertSame('hallo@easyinvoice.nl', Sender::email($company->fresh()));
    }

    public function test_free_mail_providers_are_refused(): void
    {
        config(['services.resend.key' => 're_test']);
        $this->actingAs($this->demoUser());

        $this->post(route('settings.integrations.maildomain.connect'), ['domain' => 'gmail.com', 'local_part' => 'jan'])->assertRedirect()->assertSessionHas('error');
        $this->post(route('settings.integrations.maildomain.connect'), ['domain' => 'https://bedrijf.nl', 'local_part' => 'jan'])->assertSessionHasErrors('domain');
    }
}
