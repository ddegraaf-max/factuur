<?php

namespace App\Support;

use App\Models\PaymentDemand;
use Carbon\CarbonInterface;

/**
 * De teksten van de online aanmaning, op één plek: de mail, de brief (PDF) en
 * de pagina van de klant zeggen zo precies hetzelfde. Alles volgt de taal van
 * de factuur (aanroepen binnen DocumentLocale::using).
 */
class DemandText
{
    /**
     * De alinea's en regels van de aanmaning zelf.
     *
     * @param  array<string, mixed>  $claim
     * @return array<string, ?string>
     */
    public static function letter(PaymentDemand $demand, array $claim): array
    {
        $invoice = $demand->invoice;
        $company = $invoice->brandedCompany();
        $day = fn (?CarbonInterface $date) => $date?->translatedFormat('j F Y');
        $business = $demand->isBusiness();
        // Zonder code (een berekening vooraf) is er geen pagina om op te reageren.
        $online = filled($demand->token);
        // De dag waarop de klant de aanmaning geacht wordt te hebben; de dag erna begint de termijn.
        $received = $demand->deadline->copy()->subDays($demand->term_days + 1);

        $consequence = $claim['costs_vat'] > 0
            ? __('Betaalt u niet binnen deze termijn, dan bent u ook incassokosten verschuldigd van :costs, te vermeerderen met :vat btw, samen :total.', [
                'costs' => money($claim['costs']), 'vat' => money($claim['costs_vat']), 'total' => money($claim['costs_total']),
            ])
            : __('Betaalt u niet binnen deze termijn, dan bent u ook incassokosten verschuldigd van :costs.', ['costs' => money($claim['costs'])]);
        $after = __('Wij dragen de vordering dan zonder verdere aankondiging over aan de gerechtsdeurwaarder. Komt het tot een procedure bij de rechter, dan vorderen wij ook de kosten daarvan.');

        return [
            'title' => __('Laatste aanmaning'),
            'subtitle' => $invoice->invoice_date
                ? __('Factuur :number van :date', ['number' => $invoice->number, 'date' => $day($invoice->invoice_date)])
                : __('Factuur :number', ['number' => $invoice->number]),
            'salutation' => __('Geachte heer, mevrouw,'),
            'intro' => $invoice->invoice_date
                ? __('Factuur :number van :date staat nog open. De betaaltermijn is op :due verstreken.', [
                    'number' => $invoice->number, 'date' => $day($invoice->invoice_date), 'due' => $day($invoice->due_date),
                ])
                : __('Factuur :number staat nog open. De betaaltermijn is op :due verstreken.', ['number' => $invoice->number, 'due' => $day($invoice->due_date)]),
            'term' => $business
                ? __('Wij verzoeken u het openstaande bedrag uiterlijk :deadline te betalen.', ['deadline' => $day($demand->deadline)])
                : __('Wij verzoeken u het openstaande bedrag te betalen binnen :days dagen nadat u deze aanmaning heeft ontvangen. Wij gaan ervan uit dat u haar op :sent ontvangt; de termijn loopt dan tot en met :deadline.', [
                    'days' => $demand->term_days, 'sent' => $day($received), 'deadline' => $day($demand->deadline),
                ]),
            'interest' => $claim['with_interest']
                ? __('De rente loopt door tot de dag van betaling; per dag komt er :amount bij.', ['amount' => money($claim['per_day'])])
                : null,
            'consequence' => $consequence . ' ' . $after,
            // Op de pagina staat het bedrag van de kosten er al naast.
            'consequence_short' => $after,
            'bank' => filled($company->iban)
                ? __('Overmaken kan op :iban ten name van :name, onder vermelding van factuurnummer :number.', [
                    'iban' => $company->iban, 'name' => $invoice->company?->name ?: $company->name, 'number' => $invoice->number,
                ])
                : null,
            'respond' => $online
                ? __('Heeft u al betaald, wilt u een betaaldatum afspreken of bent u het niet eens met de factuur? Geef het door op de pagina bij deze aanmaning.')
                : __('Heeft u al betaald, wilt u een betaaldatum afspreken of bent u het niet eens met de factuur? Neem dan contact met ons op.'),
            'live' => __('Op die pagina staat ook het bedrag van vandaag.'),
            'legal' => $business
                ? __('Grondslag: artikel 6:96 en 6:119a Burgerlijk Wetboek en het Besluit vergoeding voor buitengerechtelijke incassokosten.')
                : __('Grondslag: artikel 6:96 en 6:119 Burgerlijk Wetboek en het Besluit vergoeding voor buitengerechtelijke incassokosten.'),
            'regards' => __('Met vriendelijke groet,'),
            'button' => __('Bekijk het bedrag en reageer'),
            'l_principal' => __('Openstaand bedrag factuur :number', ['number' => $invoice->number]),
            'l_interest' => $business
                ? __('Wettelijke handelsrente tot en met :date (:days dagen)', ['date' => $day($claim['on']), 'days' => $claim['interest_days']])
                : __('Wettelijke rente tot en met :date (:days dagen)', ['date' => $day($claim['on']), 'days' => $claim['interest_days']]),
            'l_costs' => $claim['costs_vat'] > 0 ? __('Incassokosten, inclusief btw') : __('Incassokosten'),
            'l_total' => __('Te betalen op :date', ['date' => $day($claim['on'])]),
            'l_creditor' => __('Schuldeiser'),
            'l_debtor' => __('Aan'),
            'l_qr' => __('Scan de code voor het bedrag van vandaag, of ga naar:'),
        ];
    }

    /**
     * De vaste teksten van de pagina: wat de klant ziet, het overzicht van de
     * schuldeiser en het voorbeeld op de website.
     *
     * @return array<string, mixed>
     */
    public static function page(PaymentDemand $demand): array
    {
        $invoice = $demand->invoice;
        $company = $invoice->brandedCompany()->name;
        $day = fn (?CarbonInterface $date) => $date?->translatedFormat('j F Y');

        return [
            'invalid_title' => __('Deze link is niet (meer) geldig'),
            'invalid_text' => __('Neem contact op met de afzender van de aanmaning.'),
            'issued' => __('Datum aanmaning'),
            'ref' => __('Nummer'),
            'creditor' => __('Schuldeiser'),
            'debtor' => __('Schuldenaar'),
            'attachment' => __('Bijlage'),
            'live' => __('bedrag van vandaag'),
            'status' => [
                'sent' => __('Verstuurd'),
                'opened' => __('Geopend'),
                'paid' => __('Betaald'),
                'promise' => __('Betaling toegezegd'),
                'dispute' => __('Bezwaar gemaakt'),
                'transferred' => __('Overgedragen'),
                'withdrawn' => __('Ingetrokken'),
            ],
            'today' => __('Te betalen vandaag'),
            'per_day' => __('De wettelijke rente loopt door: per dag komt er :amount bij.'),
            'deadline_open' => __('Betalen zonder incassokosten kan tot en met :date.', ['date' => $day($demand->deadline)]),
            'deadline_passed' => __('De termijn is op :date verstreken. De incassokosten zijn nu verschuldigd.', ['date' => $day($demand->deadline)]),
            'after' => __('Na de termijn: :amount, inclusief incassokosten.'),
            'pay_title' => __('Betalen'),
            'pay_iban' => __('Rekeningnummer'),
            'pay_name' => __('Ten name van'),
            'pay_amount' => __('Bedrag'),
            'pay_reference' => __('Omschrijving'),
            'pay_qr' => __('Scan met de app van uw bank'),
            'pay_online' => __('Factuur bekijken en online betalen'),
            'print' => __('Afdrukken / PDF'),
            'make_own' => __('Maak je eigen aanmaning, gratis'),
            'state_paid' => __('Deze factuur is betaald. Bedankt.'),
            'state_transferred' => __('Deze vordering is overgedragen aan de gerechtsdeurwaarder. U ontvangt van hem bericht.'),
            'state_withdrawn' => __('Deze aanmaning is ingetrokken.'),
            'respond_title' => __('Uw reactie'),
            'respond_intro' => __('Eén klik is genoeg: :company krijgt uw antwoord per e-mail. Uw antwoord geldt als schriftelijke verklaring.', ['company' => $company]),
            'respond_done' => __('Uw reactie is doorgegeven aan :company. Reageren kan één keer.', ['company' => $company]),
            'opt_paid' => __('Ik heb betaald'),
            'opt_paid_hint' => __('Op welke dag heeft u betaald? Dit mag leeg blijven.'),
            'opt_promise' => __('Ik betaal uiterlijk op…'),
            'opt_promise_hint' => __('Kies een datum. Hiermee erkent u de vordering en zegt u toe op die dag te betalen.'),
            'opt_dispute' => __('Ik ben het er niet mee eens'),
            'opt_dispute_hint' => __('Beschrijf kort waarom u het niet eens bent met de factuur.'),
            'date' => __('Datum'),
            'note' => __('Toelichting'),
            'note_placeholder' => __('Bijvoorbeeld het kenmerk van uw betaling, een voorstel voor een regeling of de reden van uw bezwaar'),
            'send' => __('Reactie versturen'),
            'send_note' => __('Met het versturen verklaart u namens :customer te reageren. Reageren kan één keer; het tijdstip, het IP-adres en de browser worden vastgelegd.', ['customer' => $invoice->customer_name]),
            'contact' => __('Vragen? Neem contact op met :company', ['company' => $company]),
            'footer' => $demand->isStandalone()
                ? __('Deze aanmaning is gemaakt in opdracht van :company, via :brand. De rente wordt berekend op de dag dat u de pagina bekijkt.', ['company' => $company, 'brand' => brand('name')])
                : __('Deze aanmaning is verstuurd door :company. De rente wordt berekend op de dag dat u de pagina bekijkt.', ['company' => $company]),
            'cred' => [
                'title' => __('Overzicht voor de schuldeiser'),
                'note' => __('Je bekijkt deze pagina als schuldeiser. Je bezoeken tellen niet als geopend door je klant. Stuur deze link niet door; je klant krijgt de link hieronder.'),
                'share' => __('Link voor je klant'),
                'copy' => __('kopieer'),
                'opened' => __('Geopend door je klant'),
                'not_yet' => __('nog niet'),
                'times' => __(':n keer geopend, laatst op :date'),
                'response' => __('Reactie van je klant'),
                'none' => __('nog geen reactie'),
                'facts' => __('Klant in het handelsregister'),
                'evidence' => __('In het logboek staan het tijdstip, het IP-adres en de browser van elke keer dat je klant de pagina opende en van zijn reactie. Scanners van mailprogramma\'s tellen niet als geopend.'),
                'form_hidden' => __('Het formulier om te reageren ziet alleen je klant, via zijn eigen link.'),
                'paid' => __('Betaling ontvangen'),
                'paid_confirm' => __('Is het bedrag binnen? De aanmaning sluit dan, en op de pagina van je klant staat dat er is betaald.'),
                'withdraw' => __('Intrekken'),
                'withdraw_confirm' => __('Aanmaning intrekken? Op de pagina van je klant staat dan dat ze is ingetrokken.'),
                'transfer' => __('Overdragen aan de deurwaarder'),
                'transfer_confirm' => __('Dossier overdragen aan :partner? De aanmaning, de kopie van de factuur, de berekening en het logboek gaan per e-mail mee. Dit kun je niet ongedaan maken.'),
                'transfer_wait' => __('Overdragen aan de deurwaarder kan na :date, als er dan niet is betaald.'),
                'transfer_now' => __('De termijn is voorbij en er is niet betaald. Je kunt het dossier nu overdragen aan :partner.'),
                'invoice' => __('Terug naar de factuur'),
                'letter' => __('Brief (PDF)'),
            ],
            'demo' => [
                'badge' => __('VOORBEELD'),
                'bar' => __('Zo ziet de pagina eruit die je klant ziet. De gegevens zijn verzonnen en de knoppen werken niet.'),
                'cta' => __('Maak je eigen aanmaning, gratis'),
                'disabled' => __('Dit is een voorbeeld. Bij een echte aanmaning verstuurt je klant hier zijn reactie, en krijg jij een mail.'),
                'mails_title' => __('De mails die we versturen'),
                'mails_text' => __('De tekst maken we van wat je invult; de rente rekenen we op de dag van verzenden.'),
                'to' => __('Aan'),
                'reply_to' => __('Antwoorden gaan naar'),
                'subject' => __('Onderwerp'),
                'attachments' => __('Bijlagen'),
            ],
        ];
    }
}
