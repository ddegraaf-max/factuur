<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Support\StorageUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Globaal zoeken (Ctrl-K) en de opslagmeter. */
class SearchAndStorageTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_search_finds_invoices_customers_and_actions(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $invoice = Invoice::regular()->whereNotNull('number')->firstOrFail();
        $customer = Customer::orderBy('id')->firstOrFail();

        $byNumber = $this->getJson(route('search', ['q' => $invoice->number]))->assertOk()->json();
        $titles = collect($byNumber['groups'])->firstWhere('title', 'Facturen')['items'] ?? [];
        $this->assertTrue(collect($titles)->contains(fn ($i) => str_contains($i['title'], $invoice->number)));

        $byName = $this->getJson(route('search', ['q' => mb_substr($customer->name, 0, 5)]))->assertOk()->json();
        $this->assertNotNull(collect($byName['groups'])->firstWhere('title', 'Klanten'));

        $actions = $this->getJson(route('search', ['q' => 'nieuwe offerte']))->assertOk()->json('actions');
        $this->assertTrue(collect($actions)->contains('title', 'Nieuwe offerte'));

        $this->getJson(route('search', ['q' => 'x']))->assertOk()->assertJsonPath('groups', []);
    }

    public function test_search_is_scoped_to_the_own_administration(): void
    {
        $this->demoUser();
        $other = new \App\Models\Company();
        // Met proefperiode, anders stuurt 'subscribed' door naar de abonnementspagina.
        $other->forceFill(['name' => 'Andere BV', 'trial_ends_at' => now()->addDays(14)])->save();
        $stranger = new \App\Models\User();
        $stranger->forceFill(['name' => 'Vreemde', 'email' => 'vreemde@example.com', 'password' => bcrypt('x-y-z-1234'), 'company_id' => $other->id])->save();

        $this->actingAs($stranger)->getJson(route('search', ['q' => 'Jansen']))->assertOk()->assertJsonPath('groups', []);
    }

    public function test_storage_meter_is_shown_and_counts_attachments(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $usage = StorageUsage::for($user->company);
        $this->assertSame(StorageUsage::LIMIT_BASIC, $usage['limit_bytes']);
        $this->assertFalse($usage['full']);
        $this->assertSame('1,5 MB', StorageUsage::human((int) (1.5 * 1024 * 1024)));

        $this->get(route('settings.company'))->assertOk()->assertInertia(fn ($page) => $page->has('storage.used_label')->where('storage.limit_label', '2,00 GB'));
    }
}
