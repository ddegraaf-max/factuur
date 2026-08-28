<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** AVG-zelfbediening: volledige export als ZIP en definitief verwijderen van de eigen administratie. */
class AccountDataTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_owner_can_download_a_full_export_as_zip(): void
    {
        $user = $this->demoUser();
        $this->actingAs($user);

        $response = $this->get(route('settings.data.export'));
        $response->assertOk()->assertHeader('content-type', 'application/zip');

        $path = tempnam(sys_get_temp_dir(), 'zip');
        copy($response->baseResponse->getFile()->getPathname(), $path);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));
        $this->assertContains('klanten.csv', $names);
        $this->assertContains('facturen.csv', $names);
        $this->assertContains('factuurregels.csv', $names);
        $this->assertContains('volledige-export.json', $names);
        $json = json_decode($zip->getFromName('volledige-export.json'), true);
        $this->assertSame($user->company->name, $json['administratie']['name']);
        $this->assertArrayNotHasKey('mollie_api_key', $json['administratie']);
        $zip->close();
        @unlink($path);
    }

    public function test_deleting_the_administration_requires_password_and_name_and_then_purges_everything(): void
    {
        $user = $this->demoUser();
        $user->forceFill(['password' => bcrypt('wachtwoord-123')])->save();
        $companyId = $user->company_id;
        $name = $user->company->name;
        $this->actingAs($user);

        $this->delete(route('settings.company.destroy'), ['password' => 'fout', 'confirm' => $name])->assertRedirect();
        $this->assertNotNull(Company::withoutGlobalScope('company')->find($companyId));

        $this->delete(route('settings.company.destroy'), ['password' => 'wachtwoord-123', 'confirm' => 'andere naam'])->assertRedirect();
        $this->assertNotNull(Company::withoutGlobalScope('company')->find($companyId));

        $this->delete(route('settings.company.destroy'), ['password' => 'wachtwoord-123', 'confirm' => $name])->assertRedirect('/');
        $this->assertNull(Company::withoutGlobalScope('company')->find($companyId));
        $this->assertGuest();
    }
}
