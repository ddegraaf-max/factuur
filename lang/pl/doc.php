<?php

/**
 * Teksty na dokumentach dla klientów (PDF faktur i ofert oraz e-maile) — wersja polska.
 * Język dokumentu wynika z invoices.language / quotes.language, zie App\Support\DocumentLocale.
 */
return [
    // Documenttitels
    'invoice' => 'FAKTURA VAT',
    'invoice_tc' => 'Faktura',
    'quote' => 'OFERTA',
    'quote_tc' => 'Oferta',
    'draft' => 'SZKIC',
    'draft_tc' => 'Szkic',

    // Partijen en meta
    'to' => 'Nabywca',
    'from' => 'Sprzedawca',
    'for' => 'Dla',
    'addressee' => 'Adresat',
    'coc' => 'REGON',
    'vat_no' => 'NIP',
    'invoice_date' => 'Data wystawienia',
    'sale_date' => 'Data sprzedaży',
    'due_date' => 'Termin płatności',
    'quote_date' => 'Data oferty',
    'valid_until' => 'Ważna do',
    'reference' => 'Referencja',

    // Regels en totalen
    'description' => 'Nazwa towaru / usługi',
    'quantity' => 'Ilość',
    'price' => 'Cena netto',
    'vat' => 'VAT',
    'total' => 'Wartość',
    'discount' => 'Rabat',
    'subtotal' => 'Suma netto',
    'total_incl_vat' => 'Razem brutto',
    'already_settled' => 'Zapłacono wcześniej',
    'amount_due' => 'Do zapłaty',
    'note' => 'Uwagi',
    'vat_summary' => 'Podsumowanie stawek VAT',
    'net' => 'Netto',
    'gross' => 'Brutto',
    'split_payment' => 'Mechanizm podzielonej płatności',
    'payment_method' => 'Sposób płatności',
    'bank_transfer' => 'Przelew',

    // Betaalinstructie op de factuur
    'pay_instruction' => 'Prosimy o zapłatę w ciągu <strong>:days dni</strong> na rachunek <strong>:iban</strong>, odbiorca: <strong>:name</strong>',
    'pay_reference' => ', w tytule przelewu podając numer faktury <strong>:number</strong>',

    // QR-code betalen op de factuur
    'pay_qr_title' => 'Zeskanuj i zapłać',
    'pay_qr_hint' => 'Zeskanuj kod QR telefonem i zapłać od razu online (BLIK / Przelewy24).',

    // Geldigheid op de offerte
    'quote_valid_note' => 'Oferta jest ważna do <strong>:date</strong> włącznie.',
    'quote_accept_note' => 'Akceptujesz? Daj nam znać, odpowiadając na tego e-maila:phone — wtedy zamienimy ofertę w zlecenie. Podane kwoty są kwotami netto, chyba że zaznaczono inaczej.',
    'quote_accept_phone' => ' lub zadzwoń: :phone',

    // Factuurmail
    'mail_invoice_subject' => 'Faktura :number — :company',
    'mail_greeting' => 'Szanowni Państwo,',
    'mail_invoice_intro' => 'W załączeniu przesyłamy fakturę <strong>:number</strong> z dnia :date na kwotę <strong>:total</strong>. Faktura w formacie PDF znajduje się w załączniku.',
    'mail_settled_partial' => 'Z tej kwoty rozliczono już <strong>:settled</strong>; pozostało do zapłaty <strong>:open</strong>.',
    'mail_settled_full' => 'Cała kwota została już rozliczona — nie muszą Państwo nic płacić.',
    'mail_the_amount' => 'kwotę',
    'mail_remaining_amount' => 'pozostałą kwotę',
    'mail_by_date' => 'do <strong>:date</strong>',
    'mail_within_days' => 'w ciągu <strong>:days dni</strong>',
    'mail_to_account' => ' na rachunek <strong>:iban</strong>, odbiorca: :name',
    'mail_pay_request' => 'Prosimy o zapłatę :amount :deadline:account, podając w tytule przelewu numer faktury <strong>:number</strong>.',
    'mail_view_invoice' => 'Zobacz fakturę online',
    'mail_view_pay_invoice' => 'Zobacz i zapłać online (BLIK / Przelewy24)',
    'mail_portal_hint' => 'W bezpiecznym panelu online zobaczą Państwo fakturę, status płatności i będą mogli ponownie pobrać PDF. Dla bezpieczeństwa najpierw potwierdzają Państwo adres e-mail jednorazowym kodem.',
    'mail_regards' => 'Z poważaniem,',
    'mail_sent_via' => 'Wysłano przez :brand w imieniu :name.',

    // Bedankmail na betaling
    'mail_thanks_subject' => 'Dziękujemy za płatność — faktura :number',
    'mail_thanks_title' => 'Dziękujemy za płatność!',
    'mail_thanks_intro' => 'Otrzymaliśmy Państwa płatność za fakturę <strong>:number</strong>. Dziękujemy za współpracę.',
    'mail_thanks_invoice' => 'Faktura',
    'mail_thanks_amount' => 'Kwota',
    'mail_thanks_settled' => 'ZAPŁACONO',
    'mail_thanks_received_on' => 'Otrzymano dnia',
    'mail_thanks_method' => 'Sposób płatności',
    'mail_thanks_attachment' => 'Faktura z adnotacją ZAPŁACONO jest załączona jako PDF — przyda się do Państwa księgowości.',
    'mail_thanks_review_title' => 'Zadowoleni ze współpracy?',
    'mail_thanks_review_text' => 'Krótka opinia bardzo nam pomoże — zajmie tylko minutę.',
    'mail_thanks_review_cta' => 'Zostaw opinię',
    'pay_method_bank_transfer' => 'Przelew bankowy',
    'pay_method_ideal' => 'iDEAL',
    'pay_method_blik' => 'BLIK',
    'pay_method_p24' => 'Przelewy24',
    'pay_method_cash' => 'Gotówka',
    'pay_method_card' => 'Karta płatnicza',
    'pay_method_direct_debit' => 'Polecenie zapłaty',
    'pay_method_other' => 'Inny',

    // Offertemail
    'mail_quote_subject' => 'Oferta :number — :company',
    'mail_quote_intro_default' => 'Przesyłamy naszą ofertę. Pełne zestawienie znajduje się w załączonym pliku PDF.',
    'mail_quote_total_lbl' => 'razem brutto',
    'mail_quote_number' => 'Numer oferty',
    'mail_date' => 'Data',
    'mail_quote_agree' => '<strong>Akceptują Państwo?</strong> Wystarczy odpowiedzieć na tego e-maila — zamienimy ofertę w zlecenie. Pytania i zmiany również mile widziane.',
    'mail_view_quote' => 'Zobacz i podpisz online',
    'signed_by' => 'Podpisano elektronicznie przez <strong>:name</strong> dnia :date.',

    // Bevestiging na akkoord op een offerte
    'mail_accept_subject' => 'Potwierdzenie akceptacji — oferta :number',
    'mail_accept_title' => 'Dziękujemy za akceptację!',
    'mail_accept_intro' => 'Zaakceptowali Państwo ofertę <strong>:number</strong> firmy :company dnia :date:signed. Niniejszym potwierdzamy.',
    'mail_accept_signed_suffix' => ' i podpisali elektronicznie',
    'mail_accept_next' => 'Wkrótce skontaktujemy się w sprawie terminu i dalszych ustaleń. W razie pytań wystarczy odpowiedzieć na tego e-maila.',
    'mail_accept_quote' => 'Oferta',
    'mail_accept_date' => 'Zaakceptowano dnia',
    'mail_accept_signed_by' => 'Podpisano przez',
    'mail_accept_total' => 'Razem brutto',
    'mail_accept_installments' => 'Fakturowanie w ratach',
    'mail_accept_attachment' => 'Oferta jest załączona jako PDF — prosimy zachować ją w dokumentacji.',
    'mail_accept_attachment_signed' => 'Oferta, wraz z Państwa podpisem elektronicznym, jest załączona jako PDF — prosimy zachować ją w dokumentacji.',
    'mail_view_quote_online' => 'Zobacz ofertę online',
];
