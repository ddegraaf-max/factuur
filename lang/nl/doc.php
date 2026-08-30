<?php

/**
 * Teksten op klantdocumenten (factuur- en offerte-PDF's en -mails).
 * De taal volgt het document (invoices.language / quotes.language, een
 * momentopname van de klantinstelling) — zie App\Support\DocumentLocale.
 */
return [
    // Documenttitels
    'invoice' => 'FACTUUR',
    'invoice_tc' => 'Factuur',
    'quote' => 'OFFERTE',
    'quote_tc' => 'Offerte',
    'draft' => 'CONCEPT',
    'draft_tc' => 'Concept',

    // Partijen en meta
    'to' => 'Aan',
    'from' => 'Van',
    'for' => 'Voor',
    'addressee' => 'Geadresseerde',
    'coc' => 'KVK',
    'vat_no' => 'BTW',
    'invoice_date' => 'Factuurdatum',
    'sale_date' => 'Leverdatum',
    'due_date' => 'Vervaldatum',
    'quote_date' => 'Offertedatum',
    'valid_until' => 'Geldig tot',
    'reference' => 'Referentie',

    // Regels en totalen
    'description' => 'Omschrijving',
    'quantity' => 'Aantal',
    'price' => 'Prijs',
    'vat' => 'BTW',
    'total' => 'Totaal',
    'discount' => 'Korting',
    'subtotal' => 'Subtotaal',
    'total_incl_vat' => 'Totaal incl. btw',
    'already_settled' => 'Reeds doorgestort',
    'amount_due' => 'Te betalen',
    'note' => 'Opmerking',
    'vat_summary' => 'Btw-overzicht',
    'net' => 'Netto',
    'gross' => 'Bruto',

    // Betaalinstructie op de factuur
    'pay_instruction' => 'Gelieve het bedrag binnen <strong>:days dagen</strong> over te maken naar <strong>:iban</strong> ten name van <strong>:name</strong>',
    'pay_reference' => ' onder vermelding van factuurnummer <strong>:number</strong>',

    // QR-code betalen op de factuur
    'pay_qr_title' => 'Scan & betaal',
    'pay_qr_hint' => 'Scan de QR-code met je telefoon en betaal direct online (iDEAL).',

    // Geldigheid op de offerte
    'quote_valid_note' => 'Deze offerte is geldig tot en met <strong>:date</strong>.',
    'quote_accept_note' => 'Ga je akkoord? Laat het ons weten via een reply op deze e-mail:phone — dan zetten wij de offerte om in een opdracht. Genoemde bedragen zijn exclusief btw, tenzij anders vermeld.',
    'quote_accept_phone' => ' of bel :phone',

    // Factuurmail
    'mail_invoice_subject' => 'Factuur :number — :company',
    'mail_greeting' => 'Beste :name,',
    'mail_invoice_intro' => 'Hierbij ontvangt u factuur <strong>:number</strong> van :date voor een bedrag van <strong>:total</strong>. De factuur vindt u als PDF in de bijlage.',
    'mail_settled_partial' => 'Hierop is reeds <strong>:settled</strong> verrekend/doorgestort; het te betalen bedrag is <strong>:open</strong>.',
    'mail_settled_full' => 'Het volledige bedrag is reeds verrekend/doorgestort — u hoeft niets meer te betalen.',
    'mail_the_amount' => 'het bedrag',
    'mail_remaining_amount' => 'het resterende bedrag',
    'mail_by_date' => 'uiterlijk <strong>:date</strong>',
    'mail_within_days' => 'binnen <strong>:days dagen</strong>',
    'mail_to_account' => ' op <strong>:iban</strong> t.n.v. :name',
    'mail_pay_request' => 'Wij verzoeken u :amount :deadline te voldoen:account onder vermelding van factuurnummer <strong>:number</strong>.',
    'mail_view_invoice' => 'Bekijk factuur online',
    'mail_view_pay_invoice' => 'Bekijk en betaal online (iDEAL)',
    'mail_portal_hint' => 'In de beveiligde online omgeving ziet u de factuur, de betaalstatus en kunt u de PDF opnieuw downloaden. Voor uw veiligheid bevestigt u eerst uw e-mailadres met een eenmalige code.',
    'mail_regards' => 'Met vriendelijke groet,',
    'mail_sent_via' => 'Verzonden via :brand namens :name.',

    // Bedankmail na betaling
    'mail_thanks_subject' => 'Bedankt voor uw betaling — factuur :number',
    'mail_thanks_title' => 'Bedankt voor uw betaling!',
    'mail_thanks_intro' => 'Wij hebben uw betaling voor factuur <strong>:number</strong> in goede orde ontvangen. Hartelijk dank voor de prettige samenwerking.',
    'mail_thanks_invoice' => 'Factuur',
    'mail_thanks_amount' => 'Bedrag',
    'mail_thanks_settled' => 'VOLDAAN',
    'mail_thanks_received_on' => 'Ontvangen op',
    'mail_thanks_method' => 'Betaalwijze',
    'mail_thanks_attachment' => 'De factuur is, voorzien van het stempel BETAALD, als PDF bijgevoegd — handig voor uw administratie.',
    'mail_thanks_review_title' => 'Blij met de samenwerking?',
    'mail_thanks_review_text' => 'Een korte review helpt ons enorm — en kost u maar een minuut.',
    'mail_thanks_review_cta' => 'Laat een review achter',
    'pay_method_bank_transfer' => 'Bankoverschrijving',
    'pay_method_ideal' => 'iDEAL',
    'pay_method_blik' => 'BLIK',
    'pay_method_p24' => 'Przelewy24',
    'pay_method_cash' => 'Contant',
    'pay_method_card' => 'Pinpas / creditcard',
    'pay_method_direct_debit' => 'Automatische incasso',
    'pay_method_other' => 'Anders',

    // Offertemail
    'mail_quote_subject' => 'Offerte :number — :company',
    'mail_quote_intro_default' => 'Hierbij ontvang je onze offerte. In de bijlage vind je het volledige overzicht als PDF.',
    'mail_quote_total_lbl' => 'totaal incl. btw',
    'mail_quote_number' => 'Offertenummer',
    'mail_date' => 'Datum',
    'mail_quote_agree' => '<strong>Akkoord?</strong> Beantwoord deze e-mail — dan zetten we de offerte om in een opdracht. Vragen of iets aanpassen kan natuurlijk ook.',
    'mail_view_quote' => 'Bekijk en onderteken online',
    'signed_by' => 'Digitaal ondertekend door <strong>:name</strong> op :date.',

    // Bevestiging na akkoord op een offerte
    'mail_accept_subject' => 'Bevestiging van uw akkoord — offerte :number',
    'mail_accept_title' => 'Bedankt voor uw akkoord!',
    'mail_accept_intro' => 'U heeft offerte <strong>:number</strong> van :company op :date geaccepteerd:signed. Hierbij onze bevestiging.',
    'mail_accept_signed_suffix' => ' en digitaal ondertekend',
    'mail_accept_next' => 'Wij nemen binnenkort contact met u op over de planning en de verdere afspraken. Heeft u in de tussentijd vragen? Beantwoord dan gewoon deze e-mail.',
    'mail_accept_quote' => 'Offerte',
    'mail_accept_date' => 'Akkoord op',
    'mail_accept_signed_by' => 'Ondertekend door',
    'mail_accept_total' => 'Totaal incl. btw',
    'mail_accept_installments' => 'Facturatie in termijnen',
    'mail_accept_attachment' => 'De offerte is als PDF bijgevoegd — bewaar deze voor uw administratie.',
    'mail_accept_attachment_signed' => 'De offerte, inclusief uw digitale handtekening, is als PDF bijgevoegd — bewaar deze voor uw administratie.',
    'mail_view_quote_online' => 'Bekijk de offerte online',
];
