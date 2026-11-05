<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Huisstijl 1.105.0 van Fachora ("Dobry fachowiec. Bez stresu."): de startpagina (pl en en) heeft de
 * nachtblauwe hero met zoekbalk, foto en zwevende kaartjes, kafelki met foto per categorie en de vier
 * stappen naast een voorbeeldprofiel; de balk toont het woordmerk, "Usługi" klapt uit naar de categorieën
 * en de klant ziet "Moje zlecenia" in plaats van de inlogknop. De andere merken houden hun eigen balk.
 */
class FachoraHouseStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['brand.active' => 'fachora', 'inertia.testing.ensure_pages_exist' => false, 'services.anthropic.key' => null, 'services.stripe.secret' => null]);
        Mail::fake();
        Http::fake();
    }

    public function test_the_polish_home_page_shows_the_new_hero_tiles_and_steps(): void
    {
        $res = $this->get('/')->assertOk();

        $res->assertSee('Dobry fachowiec.', false)
            ->assertSee('Bez stresu.', false)
            ->assertSee('class="fh-hero"', false)
            ->assertSee('/brand/fachora/hero-fachowiec.jpg', false)
            ->assertSee('Jakiej usługi szukasz?')
            ->assertSee('Miasto lub kod pocztowy')
            ->assertSee('W czym możemy pomóc?')
            ->assertSee('Jak to działa?')
            ->assertSee('Dodaj zlecenie')
            ->assertSee('Zrealizuj i oceń')
            ->assertSee('Instalacje Przykładowe');

        // Elke categorie is een kafelek met link naar het aanvraagformulier; de categorieën met foto krijgen die mee.
        foreach (config('fachora.categories') as $key => $cat) {
            $res->assertSee(route('fachora.intake', ['kategoria' => $key]), false)->assertSee($cat['pl']);
        }
        $res->assertSee("/brand/fachora/cat/hydraulik.jpg", false);

        // Geen cijfers die we niet kunnen waarmaken (het ontwerp noemde "+1 200 opinii").
        $res->assertDontSee('1 200 opinii')->assertDontSee('Gwarancja wykonania usługi');
    }

    public function test_the_english_home_page_follows_the_same_layout(): void
    {
        $this->get('/?lang=en')->assertOk()
            ->assertSee('A good tradesperson.', false)
            ->assertSee('No stress.', false)
            ->assertSee('What service do you need?')
            ->assertSee('How can we help?')
            ->assertSee('How does it work?')
            ->assertSee('Post your job')
            ->assertSee('Get it done and rate')
            ->assertSee(route('fachora.intake', ['kategoria' => 'elektryk']), false)
            ->assertSee('Electrician');
    }

    public function test_the_bar_shows_the_wordmark_the_services_menu_and_my_jobs(): void
    {
        $res = $this->get('/')->assertOk();

        // Woordmerk in balk en voet in plaats van beeldmerk + naam.
        // Met ?v=versie erachter: Cloudflare bewaart /brand-bestanden vier uur, een nieuw logo moet meteen doorkomen.
        $res->assertSee('class="nav-logo" src="/brand/fachora/fachora-logo-dark.svg?v='.\App\Support\Brand::versionNumber().'"', false)
            ->assertSee('class="footer-logo"', false)
            ->assertSee('/brand/fachora/cat/hydraulik.jpg?v=', false)
            ->assertSee('/brand/fachora/favicon.svg?v=', false);

        // "Usługi" klapt uit naar alle categorieën (desktop) en staat als sublijst in het mobiele menu.
        $res->assertSee('class="nav-has-dd"', false)
            ->assertSee('nav-mobile-link nav-mobile-sub', false)
            ->assertSee('>Ślusarz</a>', false);

        // Klant: "Moje zlecenia" als tweede knop, de inlogknop voor bedrijven blijft in de voet.
        $res->assertSee('class="btn btn-outline"', false)
            ->assertSee('Moje zlecenia')
            ->assertSee('Panel firmy')
            ->assertDontSee('class="btn btn-ghost"', false);

        // Zoekicoon in de hoofdknop, nieuwe kleuren in het thema.
        $res->assertSee('Znajdź fachowca');
        $this->assertStringContainsString('--accent: #027A74', file_get_contents(public_path('brand/fachora/theme.css')));
        $this->assertStringContainsString('--accent-light: #1ED1B6', file_get_contents(public_path('brand/fachora/theme.css')));
    }

    public function test_the_other_brands_keep_their_own_bar(): void
    {
        config(['brand.active' => 'easyinvoice']);

        $this->get('/')->assertOk()
            ->assertDontSee('class="nav-logo"', false)
            ->assertDontSee('Moje zlecenia')
            ->assertSee('Inloggen');
    }

    public function test_the_new_logo_files_exist_and_use_the_house_style_colours(): void
    {
        foreach (['fachora-logo.svg', 'fachora-logo-dark.svg', 'fachora-mark.svg', 'fachora-mark-white.svg', 'favicon.svg'] as $file) {
            $this->assertFileExists(public_path('brand/fachora/'.$file));
        }
        $dark = file_get_contents(public_path('brand/fachora/fachora-logo-dark.svg'));
        $this->assertStringContainsString('#1ED1B6', $dark);
        $this->assertStringNotContainsString('#F4781F', $dark);
        $this->assertStringContainsString('<path d=', $dark, 'het woordmerk is een pad, geen tekst die van het lettertype van de bezoeker afhangt');
        foreach (['fachora-icon-32.png', 'fachora-icon-180.png', 'fachora-icon-512.png', 'og-fachora.png', 'hero-fachowiec.jpg'] as $file) {
            $this->assertFileExists(public_path('brand/fachora/'.$file));
        }
    }
}
