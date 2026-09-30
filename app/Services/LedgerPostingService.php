<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Support\Rgs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Zet de documenten van EasyInvoice om in journaalposten.
 *
 * ── Waarom dit apart staat van LedgerService ──────────────────────────────
 *
 * LedgerService weet hóe je boekt: twee kanten, in balans, in een open jaar.
 * Deze klasse weet wát er geboekt moet worden bij een verkoopfactuur, en dat is
 * een heel andere soort kennis — daar zitten de keuzes in die een boekhouder
 * zou maken, en die moeten na te lezen zijn.
 *
 * ── Diensten of goederen ──────────────────────────────────────────────────
 *
 * RGS splitst de omzet in handelsgoederen (8002…) en diensten (8003…). Het
 * pakket weet dat niet: een factuurregel heeft een omschrijving en een prijs,
 * geen soort. We boeken daarom op diensten, omdat dat voor zzp en klein mkb het
 * gewone geval is. Wie handel drijft kan de boeking in het memoriaal verplaatsen
 * of zijn eigen rekening bijkiezen. Raden op basis van de omschrijving zou er
 * soms naast zitten, en dan staat de omzet in de jaarrekening onder de verkeerde
 * kop zonder dat iemand het ziet.
 */
class LedgerPostingService
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    /** @var array<int, bool> heeft deze administratie een grootboek? */
    private array $actief = [];

    /**
     * Heeft deze administratie een grootboek?
     *
     * Zolang het rekeningschema niet is aangelegd boekt er niets. Dat is geen
     * uitzondering maar de normale gang van zaken voor elke administratie die
     * ouder is dan deze functie: die krijgt een grootboek zodra de gebruiker het
     * aanzet, en pas dan gaan de boekingen lopen.
     */
    public function enabled(Company $company): bool
    {
        return $this->actief[$company->id] ??= \App\Models\LedgerAccount::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->exists();
    }

    /**
     * Houdt het grootboek gelijk met een document dat net is opgeslagen.
     *
     * ── Waarom dit nooit een fout naar buiten gooit ───────────────────────
     *
     * Dit hangt aan het opslaan van een factuur. Zou een mislukte boeking het
     * opslaan breken, dan kan een ondernemer zijn factuur niet meer versturen
     * omdat er iets met zijn rekeningschema is. Dat is de verkeerde kant om: de
     * factuur is het document, het grootboek is de administratie erover. Wat
     * hier misgaat komt in het logboek en wordt bij de volgende herbouw
     * (ledger:rebuild) alsnog geboekt.
     */
    public function sync(Invoice|PurchaseInvoice|Payment $document): void
    {
        $company = Company::find($document->company_id);
        if (! $company || ! $this->enabled($company)) {
            return;
        }

        try {
            match (true) {
                $document instanceof Invoice => $this->syncInvoice($company, $document),
                $document instanceof PurchaseInvoice => $this->syncPurchase($company, $document),
                $document instanceof Payment => $this->postPayment($document),
            };
        } catch (\Throwable $e) {
            Log::warning('Grootboek: bijwerken na opslaan mislukt', [
                'document' => $document::class,
                'id' => $document->id,
                'fout' => $e->getMessage(),
            ]);
        }
    }

    private function syncInvoice(Company $company, Invoice $invoice): void
    {
        /*
         * Een factuur die terug naar concept gaat of wordt vervallen verklaard,
         * hoort niet meer in het grootboek. Kan de boeking niet weg — het jaar
         * is vastgesteld — dan wordt hij tegengeboekt, zodat er geen gat in de
         * nummering valt.
         */
        if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
            $bestaand = $this->ledger->findBySource($company, 'invoice', $invoice->id);
            if (! $bestaand) {
                return;
            }

            try {
                $this->ledger->removeForSource($company, 'invoice', $invoice->id);
            } catch (\Throwable) {
                $this->ledger->reverse($bestaand, null, 'factuur vervallen');
            }

            return;
        }

        if ($this->postInvoice($invoice)) {
            $this->settleAdvances($company, $invoice);
        }
    }

    /**
     * Verhuist wat er vooruit is ontvangen naar de debiteur, op het moment dat
     * de factuur definitief wordt.
     *
     * Een aanbetaling op een concept staat op "overige overlopende passiva":
     * geld binnen, nog niets geleverd. Zodra de factuur er is, is er wél een
     * vordering, en hoort de aanbetaling daarop te zijn afgeboekt. Eén boeking
     * in het memoriaal, met een omschrijving die zegt wat er gebeurt — geen
     * tegenboeking van de ontvangst, want die is echt gebeurd en hoort in het
     * bankboek te blijven staan.
     */
    private function settleAdvances(Company $company, Invoice $invoice): bool
    {
        if ($this->ledger->findBySource($company, 'advance_settlement', $invoice->id)) {
            return false;
        }

        $betalingIds = Payment::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->where('invoice_id', $invoice->id)
            ->pluck('id');

        if ($betalingIds->isEmpty()) {
            return false;
        }

        $parkeerrekening = $this->ledger->accountOrAdd($company, Rgs::NOG_TE_VERDELEN);

        $regels = \App\Models\JournalLine::withoutGlobalScope('company')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.company_id', $company->id)
            ->where('journal_lines.ledger_account_id', $parkeerrekening)
            ->where('journal_entries.source_type', 'payment')
            ->whereIn('journal_entries.source_id', $betalingIds)
            ->selectRaw('COALESCE(SUM(journal_lines.credit_cents),0) AS c,
                COALESCE(SUM(journal_lines.debit_cents),0) AS d')
            ->first();

        $bedrag = ((int) ($regels->c ?? 0)) - ((int) ($regels->d ?? 0));
        if ($bedrag <= 0) {
            return false;
        }

        $this->ledger->post(
            $company,
            'MEM',
            Carbon::parse($invoice->invoice_date),
            'Vooruit ontvangen verrekend met ' . $invoice->number,
            [
                [
                    'rgs' => Rgs::NOG_TE_VERDELEN,
                    'debit' => $bedrag,
                    'description' => 'Vooruitbetaling toegerekend aan ' . $invoice->number,
                ],
                [
                    'rgs' => Rgs::DEBITEUREN,
                    'credit' => $bedrag,
                    'description' => trim(($invoice->customer_name ?: 'Debiteur') . ' — ' . $invoice->number),
                    'customer_id' => $invoice->customer_id,
                ],
            ],
            ['source_type' => 'advance_settlement', 'source_id' => $invoice->id]
        );

        return true;
    }

    private function syncPurchase(Company $company, PurchaseInvoice $purchase): void
    {
        $this->postPurchase($purchase);

        if ($purchase->paid_at) {
            $this->postPurchasePayment($purchase);
        }
    }

    /**
     * Boekt een verkoopfactuur (of creditnota) in het verkoopboek.
     *
     * Debiteuren debet, omzet per btw-tarief credit, af te dragen btw credit.
     * Bij een creditnota precies omgekeerd.
     */
    public function postInvoice(Invoice $invoice): ?JournalEntry
    {
        $company = Company::find($invoice->company_id);
        if (! $company) {
            return null;
        }

        // Een concept is nog geen document: dat boekt niet. Een vervallen
        // factuur ook niet — die is er nooit geweest.
        if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
            return null;
        }

        if ($bestaand = $this->ledger->findBySource($company, 'invoice', $invoice->id)) {
            return $bestaand;
        }

        $invoice->loadMissing('lines');

        // Een creditnota staat aan de andere kant. We rekenen met positieve
        // bedragen en zetten aan het eind de kanten om; dat is minder foutgevoelig
        // dan overal een minteken meesleuren.
        $credit = (bool) $invoice->is_credit;

        $omzet = [];   // rgs-code => centen
        $btw = [];     // rgs-code => centen
        $tarieven = []; // rgs-code => het tarief, voor de vastlegging op de regel

        foreach ($invoice->lines as $line) {
            $tarief = (float) $line->vat_rate;
            $rekening = $this->salesAccount($invoice, $tarief);

            $omzet[$rekening] = ($omzet[$rekening] ?? 0) + $this->cents($line->line_subtotal);
            $tarieven[$rekening] = $tarief;

            $btwCent = $this->cents($line->line_vat);
            if ($btwCent !== 0) {
                $btwRekening = $this->vatPayableAccount($invoice, $tarief);
                $btw[$btwRekening] = ($btw[$btwRekening] ?? 0) + $btwCent;
            }
        }

        if (! $omzet && ! $btw) {
            return null;
        }

        $regels = [];
        foreach ($omzet as $rgs => $centen) {
            $regels[] = [
                'rgs' => $rgs,
                $credit ? 'debit' : 'credit' => abs($centen),
                'description' => 'Omzet ' . $invoice->number,
                'vat_rate' => $tarieven[$rgs] ?? null,
                'customer_id' => $invoice->customer_id,
            ];
        }
        foreach ($btw as $rgs => $centen) {
            $regels[] = [
                'rgs' => $rgs,
                $credit ? 'debit' : 'credit' => abs($centen),
                'description' => 'Btw ' . $invoice->number,
                'vat_cents' => abs($centen),
                'customer_id' => $invoice->customer_id,
            ];
        }

        // De tegenrekening: wat de klant ons schuldig is. Dit moet exact het
        // factuurtotaal zijn, want daar wordt de betaling straks tegen
        // afgeboekt.
        $totaal = abs($this->cents($invoice->total));
        $regels[] = [
            'rgs' => Rgs::DEBITEUREN,
            $credit ? 'credit' : 'debit' => $totaal,
            'description' => trim(($invoice->customer_name ?: 'Debiteur') . ' — ' . $invoice->number),
            'customer_id' => $invoice->customer_id,
        ];

        $regels = $this->balance($regels, $invoice->number);

        return $this->ledger->post(
            $company,
            'VRK',
            Carbon::parse($invoice->invoice_date),
            trim(($credit ? 'Creditnota ' : 'Factuur ') . $invoice->number
                . ' — ' . ($invoice->customer_name ?: 'onbekende klant')),
            $regels,
            ['source_type' => 'invoice', 'source_id' => $invoice->id]
        );
    }

    /**
     * Boekt een inkoopfactuur in het inkoopboek.
     *
     * Kosten debet, voorbelasting debet, crediteuren credit.
     */
    public function postPurchase(PurchaseInvoice $purchase): ?JournalEntry
    {
        $company = Company::find($purchase->company_id);
        if (! $company) {
            return null;
        }

        if ($bestaand = $this->ledger->findBySource($company, 'purchase_invoice', $purchase->id)) {
            return $bestaand;
        }

        $kostenRekening = $this->purchaseAccount($purchase);
        $regels = [];

        /*
         * vat_lines is de bron: per btw-tarief een grondslag en een btw-bedrag.
         * Staat die er niet, dan vallen we terug op subtotal en vat_total — bij
         * een factuur die vóór deze functie is ingevoerd kan dat zo zijn.
         */
        $lijnen = collect($purchase->vat_lines ?? [])
            ->filter(fn ($l) => isset($l['base']) || isset($l['vat']));

        if ($lijnen->isEmpty()) {
            $lijnen = collect([[
                'base' => $purchase->subtotal,
                'rate' => null,
                'vat' => $purchase->vat_total,
            ]]);
        }

        $voorbelasting = 0;
        foreach ($lijnen as $l) {
            $grondslag = $this->cents($l['base'] ?? 0);
            if ($grondslag !== 0) {
                $regels[] = [
                    'rgs' => $kostenRekening,
                    'debit' => $grondslag,
                    'description' => trim(($purchase->category ?: 'Inkoop') . ' — ' . ($purchase->supplier_name ?: '')),
                    'vat_rate' => isset($l['rate']) ? (float) $l['rate'] : null,
                    'supplier_name' => $purchase->supplier_name,
                ];
            }
            $voorbelasting += $this->cents($l['vat'] ?? 0);
        }

        if ($voorbelasting !== 0) {
            $regels[] = [
                'rgs' => Rgs::BTW_5B_VOORBELASTING,
                'debit' => $voorbelasting,
                'description' => 'Voorbelasting ' . ($purchase->supplier_reference ?: $purchase->supplier_name),
                'vat_cents' => $voorbelasting,
                'supplier_name' => $purchase->supplier_name,
            ];
        }

        if (! $regels) {
            return null;
        }

        $regels[] = [
            'rgs' => Rgs::CREDITEUREN,
            'credit' => abs($this->cents($purchase->total)),
            'description' => trim(($purchase->supplier_name ?: 'Crediteur')
                . ' — ' . ($purchase->supplier_reference ?: '')),
            'supplier_name' => $purchase->supplier_name,
        ];

        $regels = $this->balance($regels, (string) ($purchase->supplier_reference ?: $purchase->id));

        return $this->ledger->post(
            $company,
            'INK',
            Carbon::parse($purchase->invoice_date),
            trim('Inkoop ' . ($purchase->supplier_name ?: 'onbekende leverancier')
                . ($purchase->supplier_reference ? ' — ' . $purchase->supplier_reference : '')),
            $regels,
            ['source_type' => 'purchase_invoice', 'source_id' => $purchase->id]
        );
    }

    /**
     * Boekt een betaling op een verkoopfactuur in het bankboek.
     *
     * Bank debet, debiteuren credit. Bij een afboeking gaat het bedrag naar
     * "afboeking dubieuze debiteuren" in plaats van naar de bank — er is dan
     * geen geld binnengekomen, de vordering verdwijnt alleen.
     */
    public function postPayment(Payment $payment): ?JournalEntry
    {
        $company = Company::find($payment->company_id);
        if (! $company) {
            return null;
        }

        /*
         * Een betaling met soort "credit" is geen geldstroom: die staat voor een
         * factuur die met een creditnota is verrekend. De creditnota is zelf al
         * geboekt; dit ook boeken zou de omzet twee keer terugdraaien.
         */
        if ($payment->kind === 'credit') {
            return null;
        }

        if ($bestaand = $this->ledger->findBySource($company, 'payment', $payment->id)) {
            return $bestaand;
        }

        $bedrag = abs($this->cents($payment->amount));
        if ($bedrag === 0) {
            return null;
        }

        $invoice = $payment->invoice;
        $tegenrekening = $payment->kind === 'write_off'
            ? Rgs::AFBOEKING_DEBITEUREN
            : ($payment->method === 'cash' ? Rgs::KAS : Rgs::BANK);

        /*
         * Hoort hier een debiteur tegenover te staan?
         *
         * Een aanbetaling op een factuur die nog concept is komt echt binnen op
         * de bank, maar er is nog geen vordering om af te boeken: de omzet is nog
         * niet genomen. Zou de ontvangst dan tóch op debiteuren gaan, dan staat
         * die rekening voor dat bedrag in de min — en dan zegt de balans dat de
         * klant geld van óns krijgt terwijl hij juist vooruit heeft betaald.
         * Zulk geld gaat op "overige overlopende passiva": een schuld, want er
         * moet nog geleverd worden. Zodra de factuur definitief wordt verhuist
         * het naar debiteuren (zie settleAdvances).
         *
         * We kijken naar de stáát van de factuur, niet naar of de boeking er al
         * is. Dat is niet hetzelfde: een betaling kan worden vastgelegd voordat
         * de factuur is geboekt — de demo-bouwer en de import doen dat, en het
         * afletteren van de bank kan het ook. Keken we naar de boeking, dan
         * maakte élke betaling een omweg via de overlopende passiva met een
         * extra memoriaalpost per factuur erachteraan. Dat telt niet verkeerd,
         * maar het maakt het journaal onleesbaar voor een accountant — en dat
         * was op de live demo precies wat er gebeurde.
         *
         * Is de factuur definitief maar de boeking niet gelukt, dan staat er een
         * losse credit op debiteuren. Dat is een fout die `ledger:check` meldt,
         * en zeldzamer dan de omweg.
         */
        $opFactuur = $invoice && ! in_array($invoice->status, ['draft', 'cancelled'], true);
        $rekeningKant = $opFactuur ? Rgs::DEBITEUREN : Rgs::NOG_TE_VERDELEN;

        /*
         * Een negatieve betaling komt voor: een terugstorting, of een correctie
         * van een te hoog geboekt bedrag. Dan gaan de kanten om.
         */
        $terug = $this->cents($payment->amount) < 0;

        $regels = [
            [
                'rgs' => $tegenrekening,
                $terug ? 'credit' : 'debit' => $bedrag,
                'description' => trim(($payment->reference ?: 'Betaling')
                    . ($invoice ? ' ' . $invoice->number : '')),
            ],
            [
                'rgs' => $rekeningKant,
                $terug ? 'debit' : 'credit' => $bedrag,
                'description' => match (true) {
                    $opFactuur => trim(($invoice->customer_name ?: 'Debiteur') . ' — ' . $invoice->number),
                    (bool) $invoice => trim('Vooruit ontvangen — ' . $invoice->number
                        . ' staat nog niet in het grootboek'),
                    default => 'Ontvangst zonder factuur — nog in te delen',
                },
                'customer_id' => $invoice?->customer_id,
            ],
        ];

        $dagboek = $payment->method === 'cash' ? 'KAS' : 'BNK';
        if ($payment->kind === 'write_off') {
            // Een afboeking is geen bank- of kasboeking: er komt geen geld langs.
            $dagboek = 'MEM';
        }

        return $this->ledger->post(
            $company,
            $dagboek,
            Carbon::parse($payment->paid_on),
            $payment->kind === 'write_off'
                ? 'Afboeking ' . ($invoice?->number ?: '')
                : trim('Ontvangst ' . ($invoice?->number ?: '') . ' ' . ($payment->reference ?: '')),
            $regels,
            ['source_type' => 'payment', 'source_id' => $payment->id]
        );
    }

    /**
     * Boekt de betaling van een inkoopfactuur: crediteuren debet, bank credit.
     *
     * Is er op de factuur iets ingehouden (deductions), dan is dat bedrag niet
     * naar de leverancier gegaan én ook niet meer verschuldigd. Wát het was weet
     * het pakket niet — een verrekening, een inhouding, een korting. Het komt
     * daarom op "overige vorderingen" te staan met de omschrijving eruit, zodat
     * het zichtbaar blijft tot iemand het indeelt. Stil op de kosten boeken zou
     * het laten verdwijnen.
     */
    public function postPurchasePayment(PurchaseInvoice $purchase): ?JournalEntry
    {
        $company = Company::find($purchase->company_id);
        if (! $company || ! $purchase->paid_at) {
            return null;
        }

        if ($bestaand = $this->ledger->findBySource($company, 'purchase_payment', $purchase->id)) {
            return $bestaand;
        }

        $totaal = abs($this->cents($purchase->total));
        if ($totaal === 0) {
            return null;
        }

        $ingehouden = abs($this->cents($purchase->deductions_total));
        $betaald = max($totaal - $ingehouden, 0);

        $regels = [[
            'rgs' => Rgs::CREDITEUREN,
            'debit' => $totaal,
            'description' => trim(($purchase->supplier_name ?: 'Crediteur') . ' betaald'),
            'supplier_name' => $purchase->supplier_name,
        ]];

        if ($betaald > 0) {
            $regels[] = [
                'rgs' => $purchase->payment_method === 'cash' ? Rgs::KAS : Rgs::BANK,
                'credit' => $betaald,
                'description' => trim('Betaling ' . ($purchase->supplier_name ?: '')),
            ];
        }

        if ($ingehouden > 0) {
            $labels = collect($purchase->deductions ?? [])
                ->pluck('label')->filter()->implode(', ');
            $regels[] = [
                'rgs' => Rgs::OVERIGE_VORDERINGEN,
                'credit' => $ingehouden,
                'description' => 'Ingehouden' . ($labels ? ': ' . $labels : '') . ' — nog in te delen',
                'supplier_name' => $purchase->supplier_name,
            ];
        }

        return $this->ledger->post(
            $company,
            $purchase->payment_method === 'cash' ? 'KAS' : 'BNK',
            Carbon::parse($purchase->paid_at),
            trim('Betaling inkoop ' . ($purchase->supplier_name ?: '')),
            $regels,
            ['source_type' => 'purchase_payment', 'source_id' => $purchase->id]
        );
    }

    /**
     * Boekt wat er nog niet geboekt is, en meldt wat er niet lukte.
     *
     * Bedoeld om een administratie in één keer op orde te brengen: bij het
     * aanzetten van het grootboek, en na een import.
     *
     * @return array{invoices:int, purchases:int, payments:int, purchase_payments:int, settlements:int, errors:array<int,string>}
     */
    public function rebuild(Company $company, ?int $year = null): array
    {
        $uit = ['invoices' => 0, 'purchases' => 0, 'payments' => 0,
            'purchase_payments' => 0, 'settlements' => 0, 'errors' => []];

        $binnenJaar = function ($query, string $kolom) use ($year) {
            if ($year) {
                $query->whereYear($kolom, $year);
            }

            return $query;
        };

        $invoices = $binnenJaar(
            Invoice::withoutGlobalScope('company')->where('company_id', $company->id)
                ->whereNotIn('status', ['draft', 'cancelled']),
            'invoice_date'
        )->with('lines')->orderBy('invoice_date')->orderBy('id')->cursor();

        foreach ($invoices as $invoice) {
            $this->probeer($uit, 'invoices', "factuur {$invoice->number}", fn () => $this->postInvoice($invoice));
        }

        $purchases = $binnenJaar(
            PurchaseInvoice::withoutGlobalScope('company')->where('company_id', $company->id),
            'invoice_date'
        )->orderBy('invoice_date')->orderBy('id')->cursor();

        foreach ($purchases as $purchase) {
            $this->probeer($uit, 'purchases', "inkoop {$purchase->id}", fn () => $this->postPurchase($purchase));
            if ($purchase->paid_at) {
                $this->probeer($uit, 'purchase_payments', "betaling inkoop {$purchase->id}",
                    fn () => $this->postPurchasePayment($purchase));
            }
        }

        $payments = $binnenJaar(
            Payment::withoutGlobalScope('company')->where('company_id', $company->id),
            'paid_on'
        )->with('invoice')->orderBy('paid_on')->orderBy('id')->cursor();

        foreach ($payments as $payment) {
            $this->probeer($uit, 'payments', "betaling {$payment->id}", fn () => $this->postPayment($payment));
        }

        /*
         * Tot slot de vooruitbetalingen die inmiddels een definitieve factuur
         * hebben. Dit moet ná de betalingen: pas dan staat er iets op de
         * parkeerrekening om te verrekenen. Alleen de facturen die het aangaat,
         * zodat een herbouw niet over alle facturen hoeft te lopen.
         */
        foreach ($this->invoicesWithParkedAdvances($company) as $invoice) {
            $this->probeer($uit, 'settlements', "verrekening {$invoice->number}",
                fn () => $this->settleAdvances($company, $invoice));
        }

        return $uit;
    }

    /**
     * De definitieve facturen waarvan een vooruitbetaling nog op de
     * parkeerrekening staat.
     *
     * @return \Illuminate\Support\Collection<int, Invoice>
     */
    private function invoicesWithParkedAdvances(Company $company)
    {
        try {
            $parkeerrekening = $this->ledger->account($company, Rgs::NOG_TE_VERDELEN);
        } catch (\Throwable) {
            return collect();
        }

        $betalingIds = \App\Models\JournalLine::withoutGlobalScope('company')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.company_id', $company->id)
            ->where('journal_lines.ledger_account_id', $parkeerrekening)
            ->where('journal_entries.source_type', 'payment')
            ->pluck('journal_entries.source_id')
            ->filter()->unique();

        if ($betalingIds->isEmpty()) {
            return collect();
        }

        $factuurIds = Payment::withoutGlobalScope('company')
            ->whereIn('id', $betalingIds)->pluck('invoice_id')->filter()->unique();

        return Invoice::withoutGlobalScope('company')
            ->where('company_id', $company->id)
            ->whereIn('id', $factuurIds)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get();
    }

    /**
     * Voert één boeking uit en houdt de fout vast in plaats van de hele herbouw
     * te laten stoppen. Eén factuur uit een vastgesteld jaar mag de rest niet
     * tegenhouden, en de melding moet leesbaar zijn voor wie hem oplost.
     */
    private function probeer(array &$uit, string $teller, string $wat, callable $doe): void
    {
        try {
            if ($doe()) {
                $uit[$teller]++;
            }
        } catch (\Throwable $e) {
            $uit['errors'][] = "{$wat}: {$e->getMessage()}";
            Log::warning('Grootboek: boeking mislukt', ['wat' => $wat, 'fout' => $e->getMessage()]);
        }
    }

    /**
     * De omzetrekening voor een factuurregel.
     *
     * De volgorde is die van de btw-aangifte: eerst de bijzondere gevallen
     * (verlegd, binnen de EU), dan het tarief. Een factuur met btw verlegd staat
     * in rubriek 2a, ook als er een tarief op de regel staat.
     */
    private function salesAccount(Invoice $invoice, float $tarief): string
    {
        if ($invoice->vat_reversed) {
            return Rgs::OMZET_DIENST_VERLEGD;          // 2a
        }

        if ($this->isEuBuitenNederland($invoice)) {
            return Rgs::OMZET_DIENST_EU;               // 3b
        }

        return match (true) {
            $tarief >= 20.5 => Rgs::OMZET_DIENST_HOOG, // 1a
            $tarief >= 8.5 => Rgs::OMZET_DIENST_LAAG,  // 1b
            $tarief <= 0.01 => Rgs::OMZET_DIENST_NUL,  // 1e
            // Een tarief dat geen 21, 9 of 0 is (bijvoorbeeld het oude 6%) hoort
            // in rubriek 1c. Die rekening zit niet in het startschema, dus hij
            // wordt bijgezet zodra hij nodig is.
            default => 'WOmzNodOdo',                   // 1c
        };
    }

    /** De btw-rekening: de rubriek van de aangifte waar dit bedrag in valt. */
    private function vatPayableAccount(Invoice $invoice, float $tarief): string
    {
        if ($invoice->vat_reversed) {
            return Rgs::BTW_2A_VERLEGD;
        }

        return match (true) {
            $tarief >= 20.5 => Rgs::BTW_1A_HOOG,
            $tarief >= 8.5 => Rgs::BTW_1B_LAAG,
            default => Rgs::BTW_1C_OVERIG,
        };
    }

    /**
     * Levering aan een andere EU-lidstaat? Dan rubriek 3b.
     *
     * Het land staat op de factuur zelf vastgelegd (customer_country), niet op
     * de klant: een klant kan verhuizen, een factuur uit 2024 niet.
     */
    private function isEuBuitenNederland(Invoice $invoice): bool
    {
        $land = strtoupper(trim((string) $invoice->customer_country));

        if ($land === '' || $land === 'NL' || $land === 'NEDERLAND' || $land === 'NETHERLANDS') {
            return false;
        }

        return in_array($land, self::EU_LANDEN, true);
    }

    /**
     * De lidstaten van de EU, zonder Nederland. Bewust een vaste lijst en geen
     * "alles wat niet NL is": een factuur naar Noorwegen of het Verenigd
     * Koninkrijk hoort niet in rubriek 3b.
     */
    private const EU_LANDEN = [
        'BE', 'BG', 'CY', 'DK', 'DE', 'EE', 'FI', 'FR', 'GR', 'HU', 'IE', 'IT',
        'HR', 'LV', 'LT', 'LU', 'MT', 'AT', 'PL', 'PT', 'RO', 'SI', 'SK', 'ES',
        'CZ', 'SE',
    ];

    /**
     * De kostenrekening voor een inkoopfactuur, afgeleid uit de categorie die de
     * gebruiker heeft ingevuld.
     *
     * Een categorie is vrije tekst, dus dit is een vertaling van gewone woorden
     * naar een rekening — geen slimmigheid. Wat er niet in staat komt op
     * "algemene kosten": zichtbaar, en van daaruit in te delen. Stil op een
     * willekeurige rekening boeken zou de jaarrekening laten kloppen terwijl hij
     * iets anders zegt dan de ondernemer denkt.
     *
     * @var array<string, string> trefwoord => RGS-code
     */
    private const CATEGORIE_REKENING = [
        'telefoon' => 'WBedKanTef',
        'mobiel' => 'WBedKanTef',
        'internet' => 'WBedKanTef',
        'auto' => 'WBedAutOak',
        'brandstof' => 'WBedAutOak',
        'tanken' => 'WBedAutOak',
        'lease' => 'WBedAutOak',
        'huur' => 'WBedHuiOhv',
        'huisvesting' => 'WBedHuiOhv',
        'energie' => 'WBedHuiOhv',
        'gas' => 'WBedHuiOhv',
        'water' => 'WBedHuiOhv',
        'elektra' => 'WBedHuiOhv',
        'kantoor' => 'WBedKanOka',
        'kantoorartikelen' => 'WBedKanOka',
        'porto' => 'WBedKanOka',
        'representatie' => 'WBedVkkRep',
        'relatiegeschenk' => 'WBedVkkRep',
        'reclame' => 'WBedVkkOvr',
        'marketing' => 'WBedVkkOvr',
        'advertentie' => 'WBedVkkOvr',
        'inkoop' => Rgs::INKOOP_HANDELSGOEDEREN,
        'handelsgoederen' => Rgs::INKOOP_HANDELSGOEDEREN,
        'voorraad' => Rgs::INKOOP_HANDELSGOEDEREN,
        'onderaannemer' => Rgs::INKOOP_UITBESTEED,
        'uitbesteed' => Rgs::INKOOP_UITBESTEED,
        'zzp' => Rgs::INKOOP_UITBESTEED,
        'rente' => 'WFbeRlsRef',
        'boete' => 'WBedAdlBev',
    ];

    private function purchaseAccount(PurchaseInvoice $purchase): string
    {
        $categorie = mb_strtolower(trim((string) $purchase->category));

        if ($categorie !== '') {
            foreach (self::CATEGORIE_REKENING as $trefwoord => $rgs) {
                if (str_contains($categorie, $trefwoord)) {
                    return $rgs;
                }
            }
        }

        return Rgs::KOSTEN_ALGEMEEN;
    }

    /**
     * Sluit een afrondingsverschil van een paar cent.
     *
     * De bedragen op een factuur staan in twee decimalen en zijn per regel
     * afgerond; de som daarvan kan een cent afwijken van het factuurtotaal. Dat
     * verschil moet érgens staan, anders weigert de database de boeking. Het
     * komt op "betalingsverschillen" — een cent op een eigen regel is
     * navolgbaar, een cent die stil bij de omzet wordt opgeteld niet.
     */
    private function balance(array $regels, string $kenmerk): array
    {
        $debet = 0;
        $credit = 0;
        foreach ($regels as $r) {
            $debet += (int) ($r['debit'] ?? 0);
            $credit += (int) ($r['credit'] ?? 0);
        }

        $verschil = $debet - $credit;
        if ($verschil === 0) {
            return $regels;
        }

        $regels[] = [
            'rgs' => Rgs::BETAALVERSCHIL,
            $verschil > 0 ? 'credit' : 'debit' => abs($verschil),
            'description' => 'Afrondingsverschil ' . $kenmerk,
        ];

        return $regels;
    }

    /** Euro's met twee decimalen naar hele centen, zonder afrondingsdrift. */
    private function cents(float|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
