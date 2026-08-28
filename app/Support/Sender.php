<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Mail\Mailables\Address;

/**
 * Afzender van klantgerichte mail (facturen, offertes, herinneringen …):
 * het eigen, bij Resend geverifieerde domein van de administratie als dat is
 * ingericht, anders het standaardadres van EasyInvoice. De weergavenaam is
 * altijd de bedrijfsnaam.
 */
class Sender
{
    public static function address(?Company $company, ?string $name = null): Address
    {
        $name = $name ?: ($company?->name ?: config('mail.from.name'));

        if ($company && $company->mail_domain_status === 'verified' && filled($company->mail_from_address)) {
            return new Address($company->mail_from_address, $name);
        }

        return new Address(config('mail.from.address'), $name);
    }

    /** Alleen het adres, voor teksten zoals "je mail gaat uit naam van …". */
    public static function email(?Company $company): string
    {
        return static::address($company)->address;
    }
}
