<?php

/**
 * Engelstalige documentteksten — voor klanten met taalinstelling "Engels".
 * Bedragen houden bewust de Europese notatie (€ 1.234,56).
 */
return [
    // Documenttitels
    'invoice' => 'INVOICE',
    'invoice_tc' => 'Invoice',
    'quote' => 'QUOTATION',
    'quote_tc' => 'Quotation',
    'draft' => 'DRAFT',
    'draft_tc' => 'Draft',

    // Partijen en meta
    'to' => 'To',
    'from' => 'From',
    'for' => 'For',
    'addressee' => 'Addressee',
    'coc' => 'CoC',
    'vat_no' => 'VAT',
    'invoice_date' => 'Invoice date',
    'due_date' => 'Due date',
    'quote_date' => 'Quotation date',
    'valid_until' => 'Valid until',
    'reference' => 'Reference',

    // Regels en totalen
    'description' => 'Description',
    'quantity' => 'Qty',
    'price' => 'Price',
    'vat' => 'VAT',
    'total' => 'Total',
    'discount' => 'Discount',
    'subtotal' => 'Subtotal',
    'total_incl_vat' => 'Total incl. VAT',
    'already_settled' => 'Already settled',
    'amount_due' => 'Amount due',
    'note' => 'Note',

    // Betaalinstructie op de factuur
    'pay_instruction' => 'Please transfer the amount within <strong>:days days</strong> to <strong>:iban</strong> in the name of <strong>:name</strong>',
    'pay_reference' => ', quoting invoice number <strong>:number</strong>',

    // QR-code betalen op de factuur
    'pay_qr_title' => 'Scan & pay',
    'pay_qr_hint' => 'Scan the QR code with your phone to pay this invoice online (iDEAL).',

    // Geldigheid op de offerte
    'quote_valid_note' => 'This quotation is valid until <strong>:date</strong>.',
    'quote_accept_note' => 'Would you like to go ahead? Simply reply to this e-mail:phone and we will turn the quotation into an assignment. All amounts are exclusive of VAT unless stated otherwise.',
    'quote_accept_phone' => ' or call :phone',

    // Factuurmail
    'mail_invoice_subject' => 'Invoice :number — :company',
    'mail_greeting' => 'Dear :name,',
    'mail_invoice_intro' => 'Please find attached invoice <strong>:number</strong> dated :date for the amount of <strong>€ :total</strong>.',
    'mail_settled_partial' => 'An amount of <strong>€ :settled</strong> has already been settled; the amount due is <strong>€ :open</strong>.',
    'mail_settled_full' => 'The full amount has already been settled — no further payment is required.',
    'mail_the_amount' => 'the amount',
    'mail_remaining_amount' => 'the remaining amount',
    'mail_by_date' => 'by <strong>:date</strong> at the latest',
    'mail_within_days' => 'within <strong>:days days</strong>',
    'mail_to_account' => ' to <strong>:iban</strong> in the name of :name',
    'mail_pay_request' => 'We kindly request that you pay :amount :deadline:account, quoting invoice number <strong>:number</strong>.',
    'mail_view_invoice' => 'View invoice online',
    'mail_view_pay_invoice' => 'View and pay online (iDEAL)',
    'mail_portal_hint' => 'In the secure online environment you can view the invoice and its payment status, and download the PDF again. For your security, you will first confirm your e-mail address with a one-time code.',
    'mail_regards' => 'Kind regards,',
    'mail_sent_via' => 'Sent via EasyInvoice on behalf of :name.',

    // Bedankmail na betaling
    'mail_thanks_subject' => 'Thank you for your payment — invoice :number',
    'mail_thanks_title' => 'Thank you for your payment!',
    'mail_thanks_intro' => 'We have received your payment for invoice <strong>:number</strong>. Thank you — it has been a pleasure working with you.',
    'mail_thanks_invoice' => 'Invoice',
    'mail_thanks_amount' => 'Amount',
    'mail_thanks_settled' => 'PAID',
    'mail_thanks_received_on' => 'Received on',
    'mail_thanks_method' => 'Payment method',
    'mail_thanks_attachment' => 'The invoice, marked as paid, is attached as a PDF for your records.',
    'mail_thanks_review_title' => 'Happy with our work?',
    'mail_thanks_review_text' => 'A short review helps us a great deal — and only takes a minute.',
    'mail_thanks_review_cta' => 'Leave a review',
    'pay_method_bank_transfer' => 'Bank transfer',
    'pay_method_ideal' => 'iDEAL',
    'pay_method_cash' => 'Cash',
    'pay_method_card' => 'Debit / credit card',
    'pay_method_direct_debit' => 'Direct debit',
    'pay_method_other' => 'Other',

    // Offertemail
    'mail_quote_subject' => 'Quotation :number — :company',
    'mail_quote_intro_default' => 'Please find our quotation attached as a PDF.',
    'mail_quote_total_lbl' => 'total incl. VAT',
    'mail_quote_number' => 'Quotation number',
    'mail_date' => 'Date',
    'mail_quote_agree' => '<strong>Ready to go ahead?</strong> Simply reply to this e-mail and we will turn the quotation into an assignment. Questions or changes? Just let us know.',
    'mail_view_quote' => 'View and sign online',
    'signed_by' => 'Digitally signed by <strong>:name</strong> on :date.',

    // Bevestiging na akkoord op een offerte
    'mail_accept_subject' => 'Confirmation of your approval — quotation :number',
    'mail_accept_title' => 'Thank you for your approval!',
    'mail_accept_intro' => 'You accepted quotation <strong>:number</strong> from :company on :date:signed. This e-mail confirms your approval.',
    'mail_accept_signed_suffix' => ' and signed it digitally',
    'mail_accept_next' => 'We will contact you shortly about the planning and next steps. Any questions in the meantime? Simply reply to this e-mail.',
    'mail_accept_quote' => 'Quotation',
    'mail_accept_date' => 'Approved on',
    'mail_accept_signed_by' => 'Signed by',
    'mail_accept_total' => 'Total incl. VAT',
    'mail_accept_installments' => 'Invoicing in instalments',
    'mail_accept_attachment' => 'The quotation is attached as a PDF — please keep it for your records.',
    'mail_accept_attachment_signed' => 'The quotation, including your digital signature, is attached as a PDF — please keep it for your records.',
    'mail_view_quote_online' => 'View the quotation online',
];
