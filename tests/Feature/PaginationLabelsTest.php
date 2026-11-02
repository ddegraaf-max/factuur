<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/**
 * De paginator onder lijsten spreekt Nederlands: "« Vorige" en "Volgende »" uit
 * lang/nl/pagination.php, niet het Engelse "Previous"/"Next" van Laravel zelf.
 */
class PaginationLabelsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_customer_list_pagination_uses_dutch_labels(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        for ($i = 1; $i <= 30; $i++) {
            Customer::create(['company_id' => $user->company_id, 'name' => sprintf('Klant %02d', $i), 'country' => 'NL']);
        }

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->where('customers.last_page', fn ($p) => $p >= 2)
                ->where('customers.links.0.label', '&laquo; Vorige')
                ->where('customers.links', fn ($links) => collect($links)->last()['label'] === 'Volgende &raquo;'
                    && ! collect($links)->pluck('label')->contains(fn ($l) => str_contains($l, 'Previous') || str_contains($l, 'Next'))));
    }
}
