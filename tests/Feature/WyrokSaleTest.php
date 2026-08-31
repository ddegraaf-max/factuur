<?php

namespace Tests\Feature;

use App\Mail\WyrokSaleRequestMail;
use App\Support\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Skup starych wyroków: sprzedamfakture.pl koopt ook vorderingen met een
 * executoriale titel. Publieke pagina + formulier (lead) en het formulier
 * in de app (met bedrijfscontext) mailen het dossier naar de factuurkoper.
 */
class WyrokSaleTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    /** @return array<string, string> */
    private function wyrok(): array
    {
        return [
            'sygnatura' => 'VI GNc 1234/20',
            'sad' => 'Sąd Rejonowy dla m.st. Warszawy',
            'data_wyroku' => '2020-05-12',
            'kwota' => '48 200,00',
            'dluznik' => 'Budrex Sp. z o.o.',
            'dluznik_nip' => '5260250883',
            'forma' => 'sp_zoo',
            'egzekucja' => 'bezskutecznosc',
            'egzekucja_rok' => '2022',
            'uwagi' => 'Umorzenie z powodu bezskuteczności, postanowienie w aktach.',
        ];
    }

    public function test_public_page_and_lead_form(): void
    {
        config(['brand.active' => 'lopra_pl']);
        Mail::fake();

        $this->get('/skup-wyrokow')->assertOk()
            ->assertSee('<html lang="pl"', false)
            ->assertSee('sygnatura', false)
            ->assertSee('bezskuteczno');

        // Engelse variant (als de Engelse tegenhanger bestaat).
        if (view()->exists('lopra-pl.en.skup-wyrokow')) {
            $this->withSession(['ui_locale' => 'en'])->get('/skup-wyrokow')->assertOk()->assertSee('<html lang="en"', false);
        }

        $this->post('/skup-wyrokow', array_merge(['name' => 'Jan Nowak', 'email' => 'jan@przyklad.pl', 'firm' => 'Nowak Sp.j.'], $this->wyrok()))
            ->assertRedirect()
            ->assertSessionHas('flash');

        Mail::assertSent(WyrokSaleRequestMail::class, function ($mail) {
            return $mail->hasTo(Market::wykup('email'))
                && $mail->data['sygnatura'] === 'VI GNc 1234/20'
                && $mail->company === null;
        });

        // Zonder verplichte velden: terug met fouten, geen mail.
        $this->from('/skup-wyrokow')->post('/skup-wyrokow', ['name' => 'X', 'email' => 'geen-mail'])
            ->assertRedirect('/skup-wyrokow')
            ->assertSessionHasErrors(['email', 'sygnatura', 'kwota', 'dluznik']);
        Mail::assertSentCount(1);
    }

    public function test_app_form_sends_the_company_context(): void
    {
        config(['brand.active' => 'lopra_pl']);
        Mail::fake();

        $user = $this->demoUser();
        $this->actingAs($user);

        $this->post(route('wykup.wyrok'), $this->wyrok())->assertRedirect()->assertSessionHas('flash');

        Mail::assertSent(WyrokSaleRequestMail::class, function ($mail) use ($user) {
            return $mail->hasTo(Market::wykup('email'))
                && $mail->company?->id === $user->company_id
                && $mail->user?->id === $user->id;
        });
    }

    public function test_not_available_outside_the_polish_market(): void
    {
        $this->get('/skup-wyrokow')->assertNotFound();
        $this->post('/skup-wyrokow', [])->assertNotFound();

        $user = $this->demoUser();
        $this->actingAs($user);
        $this->post(route('wykup.wyrok'), $this->wyrok())->assertNotFound();
    }
}
