<?php

namespace App\Services;

use App\Mail\QuoteMail;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteManager
{
    public function __construct(
        protected VatCalculator $vat,
        protected InvoiceManager $invoices,
    ) {}

    public function create(array $data): Quote
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $company = $customer->company;
            $mode = $this->resolveMode($data, $company);

            $quoteDate = isset($data['quote_date']) ? Carbon::parse($data['quote_date']) : now();
            $validDays = (int) ($data['valid_days'] ?? $company->quote_valid_days ?? 30);

            // Handelsnaam: alleen een profiel van hetzelfde bedrijf telt.
            $profile = ! empty($data['brand_profile_id'])
                ? \App\Models\BrandProfile::withoutGlobalScope('company')
                    ->where('company_id', $company->id)
                    ->find($data['brand_profile_id'])
                : null;

            // Zonder btw-bedragen: het bedrijf doet mee aan de kleineondernemersregeling
            // (dan geldt de vrijstelling en is er niets te verleggen), of de btw is verlegd.
            $exempt = \App\Support\Kor::applies($company);
            $reversed = ! $exempt && \App\Support\VatReverse::requested($data);
            $lines = \App\Support\VatReverse::lines($data['lines'] ?? [], $reversed || $exempt);
            $totals = $this->vat->calculateInvoice($lines, $mode);

            // Documenttaal: momentopname van de klantinstelling, tenzij op het
            // formulier een andere taal is gekozen.
            $language = $data['language'] ?? $customer->language ?? 'nl';
            $language = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';

            $quote = Quote::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'brand_profile_id' => $profile?->id,
                'language' => $language,
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                // Alleen een project van dezelfde administratie.
                'project_id' => \App\Models\Project::withoutGlobalScope('company')->where('company_id', $company->id)->whereKey($data['project_id'] ?? 0)->value('id'),
                'quote_date' => $quoteDate,
                'valid_until' => $quoteDate->copy()->addDays($validDays),

                'customer_name' => $customer->name,
                'customer_address_line' => $customer->address_line,
                'customer_postal_code' => $customer->postal_code,
                'customer_city' => $customer->city,
                'customer_country' => $customer->country,
                'customer_vat_number' => $customer->vat_number,
                'customer_kvk_number' => $customer->kvk_number,
                'customer_email' => $customer->email,

                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
                'vat_reversed' => $reversed,
                'vat_exempt' => $exempt,

                'intro' => $data['intro'] ?? null,
                'notes' => $data['notes'] ?? null,
                'footer' => $company->documentFooter($profile, $language),
            ]);

            $this->syncLines($quote, $lines, $mode);

            return $quote->fresh('lines');
        });
    }

    public function update(Quote $quote, array $data): Quote
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            throw new \DomainException(__('Een geaccepteerde of afgewezen offerte kun je niet meer wijzigen.'));
        }

        return DB::transaction(function () use ($quote, $data) {
            $mode = $this->resolveMode($data, $quote->company);
            // Een concept volgt de instelling van het bedrijf: KOR gaat voor btw verlegd.
            // Zonder keuze op het formulier blijft btw verlegd zoals het was.
            $exempt = \App\Support\Kor::applies($quote->company);
            $reversed = ! $exempt && (array_key_exists('vat_reversed', $data)
                ? \App\Support\VatReverse::requested($data)
                : (bool) $quote->vat_reversed);
            $lines = \App\Support\VatReverse::lines($data['lines'] ?? [], $reversed || $exempt);
            $totals = $this->vat->calculateInvoice($lines, $mode);

            $quoteDate = isset($data['quote_date']) ? Carbon::parse($data['quote_date']) : $quote->quote_date;
            $validDays = (int) ($data['valid_days'] ?? $quote->quote_date->diffInDays($quote->valid_until));

            // Handelsnaam wijzigen; de voetnoot schuift mee naar die van het
            // nieuwe profiel (of terug naar de standaard van het bedrijf).
            $brandChanges = [];
            $profile = $quote->brand_profile_id
                ? \App\Models\BrandProfile::withoutGlobalScope('company')->find($quote->brand_profile_id)
                : null;
            if (array_key_exists('brand_profile_id', $data)) {
                $profile = ! empty($data['brand_profile_id'])
                    ? \App\Models\BrandProfile::withoutGlobalScope('company')
                        ->where('company_id', $quote->company_id)
                        ->find($data['brand_profile_id'])
                    : null;
                $brandChanges = ['brand_profile_id' => $profile?->id];
            }

            // Klant: de offerte volgt de actuele klantgegevens zolang hij nog
            // gewijzigd mag worden; een andere klant kiezen werkt zo ook.
            $customerChanges = [];
            if (! empty($data['customer_id'])) {
                $customer = Customer::withoutGlobalScope('company')
                    ->where('company_id', $quote->company_id)
                    ->find($data['customer_id']);
                if ($customer) {
                    $customerChanges = [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_address_line' => $customer->address_line,
                        'customer_postal_code' => $customer->postal_code,
                        'customer_city' => $customer->city,
                        'customer_country' => $customer->country,
                        'customer_vat_number' => $customer->vat_number,
                        'customer_kvk_number' => $customer->kvk_number,
                        'customer_email' => $customer->email,
                    ];
                    if ((int) $customer->id !== (int) $quote->customer_id) {
                        $language = $customer->language ?? 'nl';
                        $customerChanges['language'] = in_array($language, \App\Support\DocumentLocale::SUPPORTED, true) ? $language : 'nl';
                    }
                }
            }

            // Documenttaal: een keuze op het formulier wint van de klantinstelling.
            if (! empty($data['language']) && in_array($data['language'], \App\Support\DocumentLocale::SUPPORTED, true)) {
                $customerChanges['language'] = $data['language'];
            }

            // Voettekst hoort bij handelsnaam én taal: opnieuw bepalen zodra een
            // van beide gewijzigd kan zijn (vertaalde voetnoot uit Instellingen).
            $language = $customerChanges['language'] ?? $quote->language;
            if (array_key_exists('brand_profile_id', $data) || $language !== $quote->language) {
                $brandChanges['footer'] = $quote->company->documentFooter($profile, $language);
            }

            $quote->update($brandChanges + $customerChanges + [
                // Leeggemaakt veld = null; sleutel aanwezig is het criterium (zie InvoiceManager).
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $quote->reference,
                'project_id' => array_key_exists('project_id', $data)
                    ? \App\Models\Project::withoutGlobalScope('company')->where('company_id', $quote->company_id)->whereKey($data['project_id'] ?? 0)->value('id')
                    : $quote->project_id,
                'quote_date' => $quoteDate,
                'valid_until' => $quoteDate->copy()->addDays($validDays),
                'subtotal' => $totals['subtotal'],
                'vat_total' => $totals['vat_total'],
                'total' => $totals['total'],
                'vat_breakdown' => $totals['vat_breakdown'],
                'vat_reversed' => $reversed,
                'vat_exempt' => $exempt,
                'intro' => array_key_exists('intro', $data) ? $data['intro'] : $quote->intro,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quote->notes,
            ]);

            $quote->lines()->delete();
            $this->syncLines($quote, $lines, $mode);

            return $quote->fresh('lines');
        });
    }

    /** Ken een definitief nummer toe, markeer als verstuurd en mail de offerte. */
    public function send(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            throw new \DomainException(__('Deze offerte kan niet (opnieuw) worden verstuurd.'));
        }

        // Geen adres op de offerte, maar inmiddels wel bij de klant? Dan dat gebruiken (zie markSent).
        $quote = $this->markSent($quote);

        $this->email($quote);
        $this->inviteCoSigner($quote); // 1.107: de tweede ondertekenaar krijgt zijn eigen link

        \App\Support\Audit::log('sent', $quote, $quote->customer_email
            ? __(':label verstuurd naar :email', ['label' => \App\Support\Audit::label($quote), 'email' => $quote->customer_email])
            : __(':label verstuurd (zonder e-mail)', ['label' => \App\Support\Audit::label($quote)]));

        return $quote;
    }

    /**
     * 1.101: offerte per sms versturen, voor klanten zonder e-mailadres (of als
     * aanvulling). Zelfde vastlegging als bij mail (nummer, status 'sent',
     * portaalsleutel), daarna één sms met een korte link naar het portaal waar
     * de klant de offerte bekijkt en digitaal ondertekent. Tegoed en grenzen
     * via SmsService.
     *
     * @throws \DomainException
     */
    public function sendBySms(Quote $quote, ?string $text = null, ?string $phone = null, ?int $userId = null): \App\Models\SmsMessage
    {
        if (! in_array($quote->status, ['draft', 'sent'], true)) {
            throw new \DomainException(__('Deze offerte kan niet (opnieuw) worden verstuurd.'));
        }
        $sms = app(\App\Services\SmsService::class);
        $to = \App\Support\PhoneNumber::mobile($phone) ?? $this->smsMobile($quote);
        if ($to === null) {
            throw new \DomainException(__('Er is geen mobiel nummer bekend; een sms kan alleen naar een mobiel nummer.'));
        }
        // De voorgestelde tekst in het venster is gemaakt vóór het nummer bestond; is die ongewijzigd,
        // dan maken we hem na het nummeren opnieuw (dan staat het echte offertenummer erin).
        $preset = trim($this->smsText($quote));
        $quote = $this->markSent($quote);
        $body = filled($text) && trim((string) $text) !== $preset ? (string) $text : $this->smsText($quote);
        $message = $sms->send($quote->company, $to, $body, $quote, $userId);
        $this->inviteCoSigner($quote); // 1.107: de tweede ondertekenaar krijgt (per e-mail) zijn eigen link

        // Nummer onthouden bij de klant als die er nog geen had.
        if ($quote->customer && blank($quote->customer->phone) && filled($phone)) {
            $quote->customer->forceFill(['phone' => $phone])->save();
        }
        \App\Support\Audit::log('sent', $quote, __(':label verstuurd per sms naar :number', ['label' => \App\Support\Audit::label($quote), 'number' => \App\Support\PhoneNumber::display($to)]));

        return $message;
    }

    /** Het mobiele nummer van de klant op de offerte, als dat er is. */
    public function smsMobile(Quote $quote): ?string
    {
        return \App\Support\PhoneNumber::mobile($quote->customer?->phone);
    }

    /** De tekst die klaarstaat voor de sms, in de taal van de offerte, met een korte link naar het portaal. */
    public function smsText(Quote $quote, ?string $sender = null): string
    {
        $quote->ensurePortalToken();
        $link = \App\Models\ShortLink::for($quote->portalUrl(), $quote->company_id)->shortUrl();

        // Afzender met voornaam eronder: zo ziet de klant dat het een persoonlijk bericht is, geen spam (vraag Daniël).
        $sender ??= \Illuminate\Support\Str::before(trim((string) auth()->user()?->name), ' ');
        $greeting = fn () => filled($sender) ? "\n" . __('Met vriendelijke groet,') . ' ' . $sender : '';

        return \App\Support\DocumentLocale::using($quote->language ?: 'nl', fn () => ($quote->number
            ? __(':company: offerte :number van :amount. Bekijk en accepteer: :link', ['company' => $quote->brandedCompany()->name, 'number' => $quote->number, 'amount' => money($quote->total), 'link' => $link])
            : __(':company: offerte van :amount. Bekijk en accepteer: :link', ['company' => $quote->brandedCompany()->name, 'amount' => money($quote->total), 'link' => $link])) . $greeting());
    }

    /** Nummer, status en portaalsleutel vastleggen (gedeeld door mail en sms). */
    protected function markSent(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            if (! $quote->number) {
                $quote->number = $this->nextNumber($quote->company, $quote->quote_date->year);
            }
            if (! $quote->customer_email && $quote->customer?->email) {
                $quote->customer_email = $quote->customer->email;
            }
            $quote->status = 'sent';
            $quote->sent_at = now();
            if (! $quote->portal_token) {
                $quote->portal_token = bin2hex(random_bytes(32));
            }
            $quote->save();

            return $quote;
        });
    }

    public function accept(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'expired'], true)) {
            throw new \DomainException(__('Alleen een verstuurde offerte kan worden geaccepteerd.'));
        }

        $quote->update(['status' => 'accepted', 'accepted_at' => now(), 'rejected_at' => null]);
        \App\Support\Audit::log('accepted', $quote, auth()->check()
            ? __(':label geaccepteerd', ['label' => \App\Support\Audit::label($quote)])
            : __(':label geaccepteerd door de klant via het portaal', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);

        return $quote->fresh();
    }

    /** Waarom er (nu) geen bevestiging kan — of null als het kan. */
    public function acceptConfirmationBlocker(Quote $quote, bool $force = false): ?string
    {
        if ($quote->status !== 'accepted') {
            return __('De offerte is (nog) niet geaccepteerd.');
        }
        if (! $quote->customer_email) {
            return __('Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.');
        }
        if ($quote->accept_mail_sent_at && ! $force) {
            return __('Er is al een bevestiging verstuurd op :date.', ['date' => $quote->accept_mail_sent_at->translatedFormat('j F Y')]);
        }

        return null;
    }

    /**
     * Bevestiging van het akkoord naar de klant, met de (ondertekende)
     * offerte als PDF. Met $force ook als er al eerder een is verstuurd.
     *
     * @throws \DomainException  met een uitlegbare reden wanneer het niet kan
     */
    public function sendAcceptConfirmation(Quote $quote, bool $force = false): bool
    {
        if ($reason = $this->acceptConfirmationBlocker($quote, $force)) {
            throw new \DomainException($reason);
        }

        $company = $quote->company;
        $branded = $quote->brandedCompany();
        $quote->load('lines', 'installments');
        $quote->ensurePortalToken();

        \App\Support\DocumentLocale::using($quote->language, function () use ($quote, $company, $branded) {
            // Dezelfde PDF als in het portaal — mét handtekeningblok als er is getekend.
            $pdf = Pdf::loadView('pdf.quote', [
                'quote' => $quote,
                'company' => $branded,
            ])->setPaper('a4')->output();

            // Uit een demo vertrekt nooit echte post (ook niet via het portaal).
            $mail = Mail::mailer($company->is_demo ? 'log' : null)->to($quote->customer_email);
            if ($quote->needsCoSigner() && strcasecmp((string) $quote->co_signer_email, (string) $quote->customer_email) !== 0) {
                $mail->cc($quote->co_signer_email); // 1.107: de tweede ondertekenaar krijgt de bevestiging ook
            }
            $mail->send(new \App\Mail\QuoteAcceptedMail($quote, $pdf));
        });

        $quote->forceFill([
            'accept_mail_sent_at' => now(),
            'accept_mail_sent_to' => $quote->customer_email,
        ])->saveQuietly();

        return true;
    }

    public function reject(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'expired'], true)) {
            throw new \DomainException(__('Alleen een verstuurde offerte kan worden afgewezen.'));
        }

        $quote->update(['status' => 'rejected', 'rejected_at' => now(), 'accepted_at' => null]);
        \App\Support\Audit::log('rejected', $quote, auth()->check()
            ? __(':label afgewezen', ['label' => \App\Support\Audit::label($quote)])
            : __(':label afgewezen door de klant via het portaal', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);

        return $quote->fresh();
    }

    /**
     * Een afgewezen of verlopen offerte opnieuw aanbieden: terug naar concept
     * met een nieuwe offertedatum en geldigheidsdatum, zodat je hem kunt
     * aanpassen en opnieuw versturen. Nummer en portaallink blijven; de eerdere
     * beslissing van de klant (afwijzing, reden, ondertekening) wordt gewist,
     * want die ging over het vorige voorstel. Het logboek bewaart de historie.
     */
    public function reopen(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['rejected', 'expired', 'withdrawn'], true)) {
            throw new \DomainException(__('Alleen een afgewezen, verlopen of vervallen offerte kun je opnieuw aanbieden.'));
        }
        if ($quote->converted_invoice_id) {
            throw new \DomainException(__('Deze offerte is al omgezet in een factuur.'));
        }

        $validDays = max(1, (int) ($quote->company?->quote_valid_days ?? 30));
        $quote->forceFill([
            'status' => 'draft',
            'quote_date' => now(),
            'valid_until' => now()->addDays($validDays),
            'sent_at' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'decline_reason' => null,
            'signed_name' => null,
            'signature_data' => null,
            'signed_at' => null,
            'signed_ip' => null,
            'signed_email' => null,
            'co_signed_name' => null,
            'co_signature_data' => null,
            'co_signed_at' => null,
            'co_signed_ip' => null,
            'co_signed_email' => null,
            'co_signer_invited_at' => null,
            'accept_mail_sent_at' => null,
            'accept_mail_sent_to' => null,
            'withdrawn_at' => null,
            'withdrawal_note' => null,
            'replaced_by_quote_id' => null,
            'withdrawal_mail_sent_at' => null,
            'withdrawal_mail_sent_to' => null,
        ])->save();
        \App\Support\Audit::log('reopened', $quote, __(':label opnieuw aangeboden (terug naar concept)', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);

        return $quote->fresh();
    }

    /**
     * Een verstuurde, verlopen of geaccepteerde offerte laten vervallen,
     * bijvoorbeeld omdat er een nieuwe voor in de plaats komt. De klant kan hem daarna niet meer
     * ondertekenen via het portaal en hij telt niet meer als open voorstel.
     * Nummer en portaallink blijven; het logboek bewaart de historie.
     */
    public function withdraw(Quote $quote, ?Quote $replacement = null, ?string $note = null): Quote
    {
        if (! in_array($quote->status, ['sent', 'expired', 'accepted'], true)) {
            throw new \DomainException(__('Alleen een verstuurde, verlopen of geaccepteerde offerte kun je laten vervallen. Een concept verwijder je gewoon.'));
        }
        // Geaccepteerd mag ook (de nieuwe offerte vervangt de afspraak), maar niet als er al is gefactureerd.
        if ($quote->converted_invoice_id || $quote->installments()->whereNotNull('invoice_id')->exists()) {
            throw new \DomainException(__('Van deze offerte is al een factuur gemaakt; dan kun je hem niet meer laten vervallen.'));
        }
        if ($replacement && (
            $replacement->id === $quote->id
            || (int) $replacement->company_id !== (int) $quote->company_id
            || in_array($replacement->status, ['rejected', 'withdrawn'], true)
        )) {
            throw new \DomainException(__('Kies een andere offerte als vervanger: deze is afgewezen of zelf vervallen.'));
        }

        $quote->forceFill([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
            'withdrawal_note' => filled($note) ? trim($note) : null,
            'replaced_by_quote_id' => $replacement?->id,
            // Een eerdere acceptatie of ondertekening blijft als historie staan.
        ])->save();

        \App\Support\Audit::log('withdrawn', $quote, $replacement
            ? __(':label vervallen, vervangen door :replacement', ['label' => \App\Support\Audit::label($quote), 'replacement' => $replacement->number ?: __('een concept')])
            : __(':label vervallen', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);

        return $quote->fresh();
    }

    /** Waarom de klant (nu) niet over het vervallen kan worden gemaild — of null als het kan. */
    /**
     * Laten vervallen én meteen de vervanger klaarzetten: een nieuw concept als
     * kopie van deze offerte. In één transactie, zodat er bij een weigering
     * (verkeerde status, al gefactureerd) geen los concept achterblijft.
     */
    public function withdrawWithCopy(Quote $quote, ?string $note = null): Quote
    {
        return DB::transaction(function () use ($quote, $note) {
            $copy = $this->duplicate($quote);
            $this->withdraw($quote, $copy, $note);

            return $copy;
        });
    }

    /**
     * Een nieuw concept als kopie van een offerte. Regels, teksten, termijnen en
     * bijlagen gaan mee; nummer, link, handtekening, status en mails niet. De
     * klantgegevens zijn een verse momentopname en de geldigheid loopt vanaf vandaag.
     */
    public function duplicate(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $quote->loadMissing('lines', 'installments', 'attachments', 'customer', 'company');
            $customer = $quote->customer;
            $validDays = (int) ($quote->company?->quote_valid_days ?? 30);

            $copy = $quote->replicate([
                'number', 'portal_token', 'status', 'sent_at', 'accepted_at', 'rejected_at', 'converted_invoice_id',
                'signed_name', 'signature_data', 'signed_at', 'signed_ip', 'signed_email', 'decline_reason',
                'accept_mail_sent_at', 'accept_mail_sent_to',
                'withdrawn_at', 'withdrawal_note', 'replaced_by_quote_id', 'withdrawal_mail_sent_at', 'withdrawal_mail_sent_to',
            ]);
            $copy->forceFill(array_merge([
                'status' => 'draft',
                'quote_date' => now(),
                'valid_until' => now()->addDays($validDays),
            ], $customer ? [
                'customer_name' => $customer->name,
                'customer_address_line' => $customer->address_line,
                'customer_postal_code' => $customer->postal_code,
                'customer_city' => $customer->city,
                'customer_country' => $customer->country,
                'customer_vat_number' => $customer->vat_number,
                'customer_kvk_number' => $customer->kvk_number,
                'customer_email' => $customer->email,
            ] : []))->save();

            foreach ($quote->lines as $line) {
                $l = $line->replicate();
                $l->quote_id = $copy->id;
                $l->save();
            }
            foreach ($quote->installments as $installment) {
                $i = $installment->replicate(['invoice_id']);
                $i->quote_id = $copy->id;
                $i->save();
            }
            // Bijlagen: de inhoud gaat mee (ook van oude bijlagen op schijf), zodat
            // beide offertes los van elkaar hun bijlagen kunnen verwijderen.
            foreach ($quote->attachments as $attachment) {
                $data = $attachment->file_data ?: (($raw = $attachment->contents()) !== null ? base64_encode($raw) : null);
                if ($data === null) {
                    continue;
                }
                $a = $attachment->replicate(['storage_path', 'file_data']);
                $a->attachable_id = $copy->id;
                $a->file_data = $data;
                $a->save();
            }

            \App\Support\Audit::log('created', $copy, __('Nieuw concept als kopie van :label', ['label' => \App\Support\Audit::label($quote)]), [], $quote->company_id);

            return $copy->fresh('lines');
        });
    }

    public function withdrawalNoticeBlocker(Quote $quote, bool $force = false): ?string
    {
        if ($quote->status !== 'withdrawn') {
            return __('Deze offerte is niet vervallen.');
        }
        if (! $quote->customer_email) {
            return __('Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.');
        }
        if ($quote->withdrawal_mail_sent_at && ! $force) {
            return __('De klant is al gemaild op :date.', ['date' => $quote->withdrawal_mail_sent_at->translatedFormat('j F Y')]);
        }

        return null;
    }

    /**
     * De klant laten weten dat de offerte is vervallen en, als die er is, door
     * welke hij is vervangen — zodat twee offertes niet dubbel lijken. Bewust
     * zonder PDF: de oude offerte hoeft niet nog eens de deur uit.
     *
     * @throws \DomainException  met een uitlegbare reden wanneer het niet kan
     */
    public function sendWithdrawalNotice(Quote $quote, bool $force = false): bool
    {
        if ($reason = $this->withdrawalNoticeBlocker($quote, $force)) {
            throw new \DomainException($reason);
        }

        $company = $quote->company;
        $quote->load('replacedBy');

        \App\Support\DocumentLocale::using($quote->language, function () use ($quote, $company) {
            $branded = $quote->brandedCompany();

            // Uit een demo vertrekt nooit echte post.
            $mail = Mail::mailer($company->is_demo ? 'log' : null)->to($quote->customer_email);

            // Kopie naar de ondernemer, net als bij de offertemail zelf.
            $cc = $branded->copy_email ?: $branded->email;
            if ($cc && strcasecmp($cc, $quote->customer_email) !== 0) {
                $mail->cc($cc);
            }

            $mail->send(new \App\Mail\QuoteWithdrawnMail($quote));
        });

        $quote->forceFill([
            'withdrawal_mail_sent_at' => now(),
            'withdrawal_mail_sent_to' => $quote->customer_email,
        ])->saveQuietly();

        return true;
    }

    /**
     * Zet de offerte om in een concept-factuur met dezelfde regels.
     * De offerte blijft bestaan als vastlegging van de afspraak.
     */
    public function convertToInvoice(Quote $quote): Invoice
    {
        if ($quote->converted_invoice_id) {
            throw new \DomainException(__('Van deze offerte is al een factuur gemaakt.'));
        }
        if ($quote->status === 'rejected') {
            throw new \DomainException(__('Een afgewezen offerte kun je niet omzetten.'));
        }
        if ($quote->installments()->exists()) {
            throw new \DomainException(__('Deze offerte wordt in termijnen gefactureerd — gebruik het termijnplan (of verwijder dat eerst).'));
        }

        $quote->loadMissing('lines');
        $mode = $this->priceMode($quote->company);

        return DB::transaction(function () use ($quote, $mode) {
            // De regels staan netto opgeslagen; InvoiceManager rekent in de
            // ingestelde modus, dus geven we de prijzen in diezelfde vorm door.
            $lines = $quote->lines->map(fn ($l) => [
                'product_id' => $l->product_id,
                'description' => $l->description,
                'details' => $l->details,
                'quantity' => (float) $l->quantity,
                'unit' => $l->unit,
                'unit_price' => $mode === 'incl'
                    ? $this->grossUnitPrice($l)
                    : (float) $l->unit_price,
                'vat_rate' => (float) $l->vat_rate,
                // De korting verhuist mee; grossUnitPrice geeft de prijs
                // vóór korting terug, dus er wordt niets dubbel gekort.
                'discount_pct' => (float) ($l->discount_pct ?? 0),
            ])->values()->all();

            $invoice = $this->invoices->create([
                'customer_id' => $quote->customer_id,
                // De factuur gaat onder dezelfde handelsnaam én taal de deur uit.
                'brand_profile_id' => $quote->brand_profile_id,
                'language' => $quote->language,
                'invoice_date' => now()->toDateString(),
                'reference' => $quote->reference ?: __('Offerte :number', ['number' => $quote->number]),
                // De factuur hoort bij hetzelfde project als de offerte.
                'project_id' => $quote->project_id,
                'notes' => $quote->notes,
                'vat_reversed' => (bool) $quote->vat_reversed,
                'lines' => $lines,
            ]);

            // Omzetten betekent dat de klant akkoord is; leg dat ook zo vast.
            $quote->update([
                'converted_invoice_id' => $invoice->id,
                'status' => 'accepted',
                'accepted_at' => $quote->accepted_at ?? now(),
            ]);

            return $invoice;
        });
    }

    /** Zet verstuurde offertes waarvan de geldigheid voorbij is op 'verlopen'. */
    public function markExpired(): int
    {
        return Quote::withoutGlobalScope('company')
            ->where('status', 'sent')
            ->whereDate('valid_until', '<', today())
            ->update(['status' => 'expired']);
    }

    public function nextNumber(Company $company, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        return DB::transaction(function () use ($company, $year) {
            $row = DB::table('quote_sequences')
                ->where('company_id', $company->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $next = 1;
                DB::table('quote_sequences')->insert([
                    'company_id' => $company->id,
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $next = $row->last_number + 1;
                DB::table('quote_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            $template = $company->quote_number_format ?: 'OFF-{year}-{sequence:4}';

            return preg_replace_callback(
                '/\{(year|sequence)(?::(\d+))?\}/',
                function ($m) use ($year, $next) {
                    $value = $m[1] === 'year' ? $year : $next;
                    $padding = isset($m[2]) ? (int) $m[2] : 0;

                    return str_pad((string) $value, $padding, '0', STR_PAD_LEFT);
                },
                $template
            );
        });
    }

    /**
     * 1.107: de tweede ondertekenaar uitnodigen met zijn eigen link en mail. Gebeurt bij het versturen
     * van de offerte, en op verzoek opnieuw vanaf de offertepagina ($resend).
     *
     * @throws \DomainException  bij opnieuw sturen als het niet kan
     */
    public function inviteCoSigner(Quote $quote, bool $resend = false): bool
    {
        if (! $quote->needsCoSigner()) {
            if ($resend) {
                throw new \DomainException(__('Deze offerte heeft geen tweede ondertekenaar.'));
            }

            return false;
        }
        if ($quote->co_signed_at) {
            if ($resend) {
                throw new \DomainException(__('De tweede ondertekenaar heeft al getekend.'));
            }

            return false;
        }
        $quote->ensureCoSignerToken();
        try {
            \App\Support\DocumentLocale::using($quote->language ?: 'nl', fn () => Mail::mailer($quote->company?->is_demo ? 'log' : null)
                ->to($quote->co_signer_email)
                ->send(new \App\Mail\QuoteSignerMail($quote, 'co', 'invite')));
        } catch (\Throwable $e) {
            Log::error('Uitnodiging tweede ondertekenaar mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
            if ($resend) {
                throw new \DomainException(__('De uitnodiging kon niet worden verstuurd.'));
            }

            return false;
        }
        $quote->forceFill(['co_signer_invited_at' => now()])->saveQuietly();
        \App\Support\Audit::log('sent', $quote, __('Tweede ondertekenaar :name uitgenodigd per e-mail (:email)', [
            'name' => $quote->co_signer_name, 'email' => $quote->co_signer_email,
        ]), [], $quote->company_id);

        return true;
    }

    /** 1.107: na de eerste handtekening de andere ondertekenaar laten weten dat hij aan de beurt is. */
    public function notifyOtherSigner(Quote $quote, string $signedRole): void
    {
        $role = $signedRole === 'co' ? 'customer' : 'co';
        $to = $role === 'co' ? $quote->co_signer_email : ($quote->customer_email ?: $quote->customer?->email);
        if (! $to) {
            return;
        }
        if ($role === 'co') {
            $quote->ensureCoSignerToken();
        }
        try {
            \App\Support\DocumentLocale::using($quote->language ?: 'nl', fn () => Mail::mailer($quote->company?->is_demo ? 'log' : null)
                ->to($to)
                ->send(new \App\Mail\QuoteSignerMail($quote, $role, 'turn')));
        } catch (\Throwable $e) {
            Log::warning('Beurtmelding aan ondertekenaar mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
        }
    }

    protected function email(Quote $quote): void
    {
        try {
            if (! $quote->customer_email) {
                return;
            }

            // PDF én mail in de taal van het document (nl of en).
            \App\Support\DocumentLocale::using($quote->language, fn () => $this->renderAndMail($quote));
        } catch (\Throwable $e) {
            Log::error('Offerte mailen mislukt', [
                'quote' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Bouwt de offerte-PDF en verstuurt de mail — binnen de documenttaal. */
    protected function renderAndMail(Quote $quote): void
    {
        // Huisstijl van de gekozen handelsnaam (of gewoon het bedrijf).
        $company = $quote->brandedCompany();
        $quote->load('lines');

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
        ])->setPaper('a4')->output();

        $mail = Mail::to($quote->customer_email);

        $cc = $company->copy_email ?: $company->email;
        if ($cc && strcasecmp($cc, $quote->customer_email) !== 0) {
            $mail->cc($cc);
        }

        $mail->send(new QuoteMail($quote, $pdf));
    }

    /**
     * Brutostuksprijs afgeleid uit het regeltotaal — precies wat er is
     * ingetypt. Het regeltotaal bevat al de regelkorting; die delen we er
     * weer uit, anders zou de korting dubbel worden toegepast.
     */
    protected function grossUnitPrice($line): float
    {
        $qty = (float) $line->quantity;
        $factor = 1 - min(100, max(0, (float) ($line->discount_pct ?? 0))) / 100;

        return ($qty > 0 && $factor > 0)
            ? round((float) $line->line_total / $qty / $factor, 2)
            : round((float) $line->unit_price * (1 + (float) $line->vat_rate / 100), 2);
    }

    protected function priceMode(?Company $company): string
    {
        return ($company?->price_mode === 'incl') ? 'incl' : 'excl';
    }

    /**
     * De schakelaar op het formulier wint van de bedrijfsinstelling — zo kun
     * je per offerte kiezen hoe je de prijzen intypt.
     */
    protected function resolveMode(array $data, ?Company $company): string
    {
        return in_array($data['price_mode'] ?? null, ['excl', 'incl'], true)
            ? $data['price_mode']
            : $this->priceMode($company);
    }

    protected function syncLines(Quote $quote, array $lines, string $mode = 'excl'): void
    {
        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = (float) ($line['vat_rate'] ?? 0);
            $discount = min(100, max(0, (float) ($line['discount_pct'] ?? 0)));
            $calc = $this->vat->calculateLine($qty, $price, $rate, $mode, $discount);

            $quote->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'sort_order' => $index,
                'description' => $line['description'] ?? '',
                'details' => $line['details'] ?? null,
                'quantity' => $qty,
                'unit' => $line['unit'] ?? __('stuk'),
                'unit_price' => $mode === 'incl' ? $this->vat->netUnitPrice($price, $rate) : $price,
                'vat_rate' => $rate,
                'discount_pct' => $discount > 0 ? $discount : null,
                'line_subtotal' => $calc['subtotal'],
                'line_vat' => $calc['vat'],
                'line_total' => $calc['total'],
            ]);
        }
    }
}
