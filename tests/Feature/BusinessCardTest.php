<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Digitaal visitekaartje: instellen, publiceren, publieke pagina en vCard. */
class BusinessCardTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_owner_can_publish_a_card_under_an_own_slug(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $this->get(route('settings.card'))->assertOk()->assertInertia(fn ($page) => $page->has('slug')->where('card.published', false));
        $this->assertNotNull($user->company->fresh()->public_slug, 'Slug wordt bij het openen van de pagina aangemaakt.');

        $this->patch(route('settings.card.update'), [
            'published' => true, 'contact_name' => 'Daniël de Graaf', 'job_title' => 'Eigenaar', 'tagline' => 'Snel en netjes',
            'whatsapp' => '06 12345678', 'linkedin_url' => 'https://linkedin.com/in/daniel', 'show_kvk' => true, 'show_vat' => false, 'show_address' => true,
            'public_slug' => 'vries-design',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $company = $user->company->fresh();
        $this->assertSame('vries-design', $company->public_slug);
        $this->assertTrue($company->businessCard->published);

        $public = $this->get('/k/vries-design')->assertOk();
        $public->assertSee('Daniël de Graaf')->assertSee('Opslaan in contacten')->assertSee('https://wa.me/31612345678', false);

        $vcard = $this->get('/k/vries-design/vcard')->assertOk()->assertHeader('Content-Type', 'text/vcard; charset=utf-8');
        $this->assertStringContainsString("BEGIN:VCARD\r\n", $vcard->getContent());
        $this->assertStringContainsString('FN:Daniël de Graaf', $vcard->getContent());
        $this->assertStringContainsString('TITLE:Eigenaar', $vcard->getContent());
    }

    public function test_unpublished_or_demo_cards_are_not_public(): void
    {
        $user = $this->demoUser();
        $company = $user->company;
        $company->forceFill(['public_slug' => 'stil'])->save();
        BusinessCard::create(['company_id' => $company->id, 'published' => false, 'contact_name' => 'X']);

        $this->get('/k/stil')->assertNotFound();
        $this->get('/k/bestaat-niet')->assertNotFound();

        $company->businessCard->update(['published' => true]);
        $this->get('/k/stil')->assertOk();

        // Demo-administraties krijgen geen publieke pagina's.
        $company->forceFill(['is_demo' => true])->save();
        $this->get('/k/stil')->assertNotFound();
    }

    public function test_slug_must_be_unique_and_well_formed(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);
        $other = new Company();
        $other->forceFill(['name' => 'Andere BV', 'public_slug' => 'bezet'])->save();

        $this->patch(route('settings.card.update'), ['public_slug' => 'bezet'])->assertSessionHasErrors('public_slug');
        $this->patch(route('settings.card.update'), ['public_slug' => 'Niet Geldig!'])->assertSessionHasErrors('public_slug');
        $this->patch(route('settings.card.update'), ['public_slug' => 'wel-geldig-2'])->assertSessionHasNoErrors();
    }
}
