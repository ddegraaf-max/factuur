<?php

namespace Tests\Feature;

use App\Mail\WykupRequestMail;
use App\Models\Invoice;
use App\Support\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Polen heeft geen incassopartner: sprzedamfakture.pl koopt alleen facturen.
 * De incasso-route toont er "Facturen verkopen", overdragen aan incasso kan
 * niet, en een verkoopverzoek gaat per mail naar de factuurkoper.
 */
class WykupTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_polish_market_sells_invoices_instead_of_collection(): void
    {
        config(['brand.active' => 'lopra_pl']);
        Mail::fake();
        \Illuminate\Support\Facades\Http::fake(); // NBP niet aanroepen in tests: vangnetkoers

        $this->assertFalse(Market::hasIncasso());
        $this->assertSame('sprzedamfakture.pl', Market::wykup('partner_name'));

        $user = $this->demoUser();
        $this->actingAs($user);

        $overdue = Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)
            ->where('status', 'overdue')->whereNull('sale_requested_at')->firstOrFail();

        // Pagina "Facturen verkopen" in plaats van het incasso-overzicht.
        $this->get(route('incasso.index'))->assertOk()
            ->assertSee('Wykup\/Index', false)
            ->assertSee('sprzedamfakture.pl')
            ->assertSee(str_replace('/', '\/', $overdue->number), false); // JSON in data-page: FV/2026/0040

        // Overdragen aan incasso bestaat niet in Polen.
        $this->post(route('incasso.send', $overdue->id))->assertNotFound();
        $this->patch(route('incasso.phase', $overdue->id), ['phase' => 'minnelijk'])->assertNotFound();

        // Factuur verkopen: verzoek naar de factuurkoper, factuur gemarkeerd als aangeboden.
        $this->post(route('windykacja.wykup', $overdue->id), ['note' => 'Proszę o ofertę'])->assertRedirect();
        $this->assertNotNull($overdue->fresh()->sale_requested_at);
        $this->assertSame('overdue', $overdue->fresh()->status);
        Mail::assertSent(WykupRequestMail::class, fn ($mail) => $mail->hasTo(Market::wykup('email')));

        // De formele aanmaning noemt geen incassopartner meer.
        $this->get(route('windykacja.wezwanie', $overdue->id))->assertOk();
    }

    public function test_dutch_market_keeps_the_collection_partner(): void
    {
        $this->assertTrue(Market::hasIncasso());
        $this->assertSame('Armaere Gerechtsdeurwaarders', Market::incasso('partner_name'));

        $user = $this->demoUser();
        $this->actingAs($user);

        $this->get(route('incasso.index'))->assertOk()->assertSee('Incasso\/Index', false);
    }
}
