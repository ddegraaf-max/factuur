<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * Uitloggen is een POST (anders kan een vreemde site je met een link
 * uitloggen), maar wie /logout intikt mag geen kale 405 krijgen: een GET
 * wijst de weg. En een echte 405 toont een pagina in huisstijl.
 */
class LogoutRouteTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_get_logout_sends_guests_to_login_and_users_to_dashboard(): void
    {
        $this->get('/logout')->assertRedirect(route('login'));

        $user = $this->demoUser();
        $this->actingAs($user)->get('/logout')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Echt uitloggen blijft POST.
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_get_portal_logout_redirects_to_portal_login(): void
    {
        $this->get('/portaal/uitloggen')->assertRedirect(route('portal.login'));
    }

    public function test_method_not_allowed_shows_branded_page(): void
    {
        $this->delete('/login')
            ->assertStatus(405)
            ->assertSee('Dit adres kun je zo niet openen')
            ->assertSee(brand('name'))
            ->assertSee(route('login'))
            ->assertDontSee('Oops! An Error Occurred');
    }
}
