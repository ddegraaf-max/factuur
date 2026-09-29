<?php

namespace App\Services;

use App\Mail\PaymentDemandMail;
use App\Mail\PaymentDemandNoticeMail;
use App\Models\Invoice;
use App\Models\PaymentDemand;
use App\Models\ReminderLog;
use App\Support\Audit;
use App\Support\DocumentLocale;
use App\Support\Kor;
use App\Support\LegalInterest;
use App\Support\Market;
use App\Support\PaymentQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Online aanmaning: de laatste aanmaning vóór de deurwaarder.
 *
 *  - De klant krijgt een mail met de aanmaning als PDF en een geheime link naar
 *    een pagina met het bedrag van vandaag: hoofdsom plus wettelijke rente die
 *    per dag oploopt (art. 6:119 en 6:119a BW).
 *  - Tot en met de laatste dag van de termijn betaalt hij zonder incassokosten.
 *    Daarna komen de kosten volgens de wettelijke staffel erbij (art. 6:96 BW).
 *    Voor een consument is die termijn minstens veertien dagen, te rekenen
 *    vanaf de dag na ontvangst.
 *  - Op de pagina reageert de klant: betaald, ik betaal op, of ik ben het er
 *    niet mee eens. Elke stap komt met tijdstip en IP-adres in het logboek.
 *  - Is de termijn voorbij en is er niet betaald, dan gaat het dossier met één
 *    klik naar de deurwaarder (IncassoService), met aanmaning en logboek erbij.
 */
class PaymentDemandService
{
    /** Wettelijk minimum voor een consument (art. 6:96 lid 6 BW). */
    public const TERM_CONSUMER = 14;

    public const TERM_BUSINESS = 7;

    public const TERM_MIN_BUSINESS = 5;

    public const TERM_MAX = 30;

    /** Zo ver vooruit mag een klant een betaaldatum toezeggen. */
    public const PROMISE_MAX_DAYS = 60;

    /**
     * Automatische overdracht: zoveel werkdagen na de laatste dag van de
     * termijn. Een overboeking van de laatste dag staat dan op de rekening
     * en kan nog worden geboekt.
     */
    public const AUTO_TRANSFER_WEEKDAYS = 3;

    /** Een brief die de ondernemer zelf op de post doet, is er na twee dagen. */
    public const POST_DAYS = 2;

    /** De aanmaning rust op Nederlands recht en heeft een deurwaarder nodig om naar over te dragen. */
    public function available(): bool
    {
        return Market::is('nl') && Market::hasIncasso();
    }

    /** Zakelijk of particulier: van de klantkaart, anders afgeleid van KvK- of btw-nummer. */
    public function debtorType(Invoice $invoice): string
    {
        $type = $invoice->customer?->type;
        if (in_array($type, ['business', 'consumer'], true)) {
            return $type;
        }

        return filled($invoice->customer_kvk_number) || filled($invoice->customer_vat_number) ? 'business' : 'consumer';
    }

    /** De termijn in dagen, binnen wat de wet en het gezond verstand toelaten. */
    public function term(string $debtorType, ?int $days = null): int
    {
        $min = $debtorType === 'consumer' ? self::TERM_CONSUMER : self::TERM_MIN_BUSINESS;
        $days ??= $debtorType === 'consumer' ? self::TERM_CONSUMER : self::TERM_BUSINESS;

        return max($min, min(self::TERM_MAX, $days));
    }

    /**
     * De laatste dag waarop zonder incassokosten betaald kan worden. Bij een
     * consument begint de termijn de dag na ontvangst: vandaag verstuurd en
     * ontvangen, dan telt morgen als dag één, en er gaat een dag marge bij.
     */
    public function deadline(string $debtorType, int $termDays, ?Carbon $from = null): Carbon
    {
        $from = ($from ?? now())->copy()->startOfDay();

        return $from->addDays($termDays + ($debtorType === 'consumer' ? 1 : 0));
    }

    /** De dag waarop het dossier vanzelf naar de deurwaarder gaat, als dat is gevraagd. */
    public function autoTransferOn(PaymentDemand $demand): Carbon
    {
        return $demand->deadline->copy()->startOfDay()->addWeekdays(self::AUTO_TRANSFER_WEEKDAYS);
    }

    /**
     * Waarom het dossier niet vanzelf overgaat, of null als niets het tegenhoudt.
     * Heeft de klant gereageerd, dan beslist de ondernemer zelf: een toezegging
     * of een bezwaar vraagt om een antwoord, niet om een deurwaarder.
     */
    public function autoTransferBlocker(PaymentDemand $demand): ?string
    {
        return match (true) {
            filled($demand->response) => __('Je klant heeft gereageerd. Het dossier gaat daarom niet vanzelf naar de deurwaarder; jij beslist.'),
            (bool) $demand->invoice?->remindersPaused() => __('De factuur staat op pauze. Zolang de pauze loopt, gaat het dossier niet naar de deurwaarder.'),
            default => null,
        };
    }

    /** Zet de automatische overdracht van een lopende aanmaning aan of uit. */
    public function setAutoTransfer(PaymentDemand $demand, bool $on): void
    {
        if (! $demand->isActive()) {
            throw new \DomainException(__('Deze aanmaning loopt niet meer.'));
        }

        $demand->forceFill(['auto_transfer' => $on])->save();
        $this->log($demand, 'auto', $on
            ? __('Automatische overdracht aangezet: op :date, als er dan niet is betaald', ['date' => $this->autoTransferOn($demand)->translatedFormat('j F Y')])
            : __('Automatische overdracht uitgezet'));
    }

    /** Waarom er (nog) geen aanmaning uit kan, of null als het kan. */
    public function blocker(Invoice $invoice): ?string
    {
        return match (true) {
            ! $this->available() => __('De online aanmaning is hier niet beschikbaar.'),
            (bool) $invoice->is_credit => __('Voor een creditnota versturen we geen aanmaning.'),
            ! in_array($invoice->status, ['sent', 'partial', 'overdue'], true) || $invoice->remaining_amount <= 0.009
                => __('Alleen een verstuurde factuur die nog openstaat kan een aanmaning krijgen.'),
            ! $invoice->due_date || ! $invoice->due_date->copy()->endOfDay()->isPast()
                => __('De betaaltermijn van deze factuur is nog niet verstreken.'),
            blank($invoice->customer_email) => __('Deze klant heeft geen e-mailadres. Vul het aan bij de klantgegevens.'),
            $invoice->remindersPaused() => __('Deze factuur staat op pauze. Hervat eerst voordat je een aanmaning stuurt.'),
            $invoice->demands()->where('status', 'sent')->exists() => __('Er loopt al een aanmaning voor deze factuur.'),
            default => null,
        };
    }

    /**
     * De berekening vóór het versturen, voor het venster op de factuurpagina.
     *
     * @return array<string, mixed>
     */
    public function preview(Invoice $invoice, ?string $debtorType = null, bool $withInterest = true, ?int $termDays = null): array
    {
        $debtorType ??= $this->debtorType($invoice);
        $term = $this->term($debtorType, $termDays);
        $principal = round(max(0, (float) $invoice->remaining_amount), 2);
        $costs = LegalInterest::collectionCosts($principal);

        return $this->calculate($invoice, $principal, $debtorType === 'business', $withInterest, $costs, $this->costsVat($invoice, $costs), false, now()) + [
            'debtor_type' => $debtorType,
            'term_days' => $term,
            'deadline' => $this->deadline($debtorType, $term),
        ];
    }

    /**
     * De vordering op een bepaalde dag (standaard vandaag): wat er openstaat,
     * de rente tot en met die dag, en de incassokosten zodra de termijn voorbij is.
     *
     * @return array<string, mixed>
     */
    public function claim(PaymentDemand $demand, ?Carbon $on = null): array
    {
        $on = ($on ?? now())->copy();
        $invoice = $demand->invoice;
        $principal = round(max(0, (float) $invoice->remaining_amount), 2);
        $costsDue = $principal > 0 && $on->copy()->startOfDay()->gt($demand->deadline->copy()->startOfDay());

        return $this->calculate($invoice, $principal, $demand->isBusiness(), (bool) $demand->with_interest, (float) $demand->costs, (float) $demand->costs_vat, $costsDue, $on) + [
            'debtor_type' => $demand->debtor_type,
            'term_days' => $demand->term_days,
            'deadline' => $demand->deadline->copy(),
        ];
    }

    /** De vordering zoals ze in de brief stond: op de dag van verzenden, zonder incassokosten. */
    public function claimAsSent(PaymentDemand $demand): array
    {
        $claim = $this->claim($demand, $demand->sent_at ?? $demand->created_at);
        // Een latere deelbetaling verandert de brief niet.
        $principal = (float) $demand->principal;
        $interest = $demand->with_interest && $demand->invoice->due_date
            ? LegalInterest::interest($principal, $demand->invoice->due_date, $claim['on'], $demand->isBusiness())
            : ['total' => 0.0, 'days' => 0, 'periods' => []];

        return array_merge($claim, [
            'principal' => $principal,
            'interest' => $interest['total'],
            'periods' => $interest['periods'],
            'costs_due' => false,
            'total' => round($principal + $interest['total'], 2),
            'total_after' => round($principal + $interest['total'] + (float) $demand->costs + (float) $demand->costs_vat, 2),
        ]);
    }

    /** @return array<string, mixed> */
    private function calculate(Invoice $invoice, float $principal, bool $business, bool $withInterest, float $costs, float $costsVat, bool $costsDue, Carbon $on): array
    {
        $interest = $withInterest && $invoice->due_date && $principal > 0
            ? LegalInterest::interest($principal, $invoice->due_date, $on, $business)
            : ['total' => 0.0, 'days' => 0, 'periods' => []];
        $rate = LegalInterest::rateOn($on, $business);
        $daysOverdue = $invoice->due_date && $invoice->due_date->copy()->startOfDay()->lt($on->copy()->startOfDay())
            ? (int) round($invoice->due_date->copy()->startOfDay()->diffInDays($on->copy()->startOfDay(), true))
            : 0;
        $withCosts = round($costs + $costsVat, 2);

        return [
            'on' => $on,
            'principal' => $principal,
            'business' => $business,
            'with_interest' => $withInterest,
            'interest' => $interest['total'],
            'interest_days' => $interest['days'],
            'periods' => $interest['periods'],
            'rate' => $rate,
            // Wat er morgen bij komt.
            'per_day' => $withInterest ? round($principal * $rate / 100 / ($on->isLeapYear() ? 366 : 365), 2) : 0.0,
            'days_overdue' => $daysOverdue,
            'costs' => round($costs, 2),
            'costs_vat' => round($costsVat, 2),
            'costs_total' => $withCosts,
            'costs_due' => $costsDue,
            'total' => round($principal + $interest['total'] + ($costsDue ? $withCosts : 0), 2),
            'total_after' => round($principal + $interest['total'] + $withCosts, 2),
        ];
    }

    /**
     * Btw over de incassokosten: alleen als de schuldeiser die btw niet kan
     * verrekenen, zoals onder de kleineondernemersregeling.
     */
    private function costsVat(Invoice $invoice, float $costs): float
    {
        return Kor::applies($invoice->company) ? round($costs * 0.21, 2) : 0.0;
    }

    /**
     * Verstuurt de aanmaning. Lukt de mail niet, dan blijft er niets achter.
     *
     * @param  array{debtor_type?: ?string, term_days?: ?int, with_interest?: ?bool}  $options
     */
    public function send(Invoice $invoice, array $options = []): PaymentDemand
    {
        $invoice->loadMissing('company', 'customer');
        if ($reason = $this->blocker($invoice)) {
            throw new \DomainException($reason);
        }

        $debtorType = in_array($options['debtor_type'] ?? null, ['business', 'consumer'], true)
            ? $options['debtor_type']
            : $this->debtorType($invoice);
        $term = $this->term($debtorType, isset($options['term_days']) ? (int) $options['term_days'] : null);
        $principal = round((float) $invoice->remaining_amount, 2);
        $costs = LegalInterest::collectionCosts($principal);

        $demand = PaymentDemand::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'token' => bin2hex(random_bytes(32)),
            'status' => 'sent',
            'debtor_type' => $debtorType,
            'sent_to' => $invoice->customer_email,
            'principal' => $principal,
            'with_interest' => (bool) ($options['with_interest'] ?? true),
            'auto_transfer' => (bool) ($options['auto_transfer'] ?? false),
            'costs' => $costs,
            'costs_vat' => $this->costsVat($invoice, $costs),
            'term_days' => $term,
            'deadline' => $this->deadline($debtorType, $term)->toDateString(),
            'sent_at' => now(),
        ]);
        $demand->setRelation('invoice', $invoice);

        try {
            // Ook vanuit de aanmaning moet de klant de factuur kunnen inzien en online betalen.
            $invoice->ensurePortalToken();
            DocumentLocale::using($invoice->language, function () use ($demand, $invoice) {
                Mail::to($invoice->customer_email)->send(new PaymentDemandMail(
                    $demand,
                    $this->claimAsSent($demand),
                    $this->pdf($demand)->output(),
                    $this->invoicePdf($invoice),
                ));
            });
        } catch (\Throwable $e) {
            Log::error('Online aanmaning versturen mislukt', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
            $demand->delete();

            throw new \DomainException(__('De aanmaning kon niet worden gemaild. Er is niets gewijzigd; probeer het later opnieuw.'));
        }

        $this->log($demand, 'sent', __('Aanmaning gemaild naar :to; betalen zonder incassokosten kan tot en met :date', [
            'to' => $demand->sent_to, 'date' => $demand->deadline->translatedFormat('j F Y'),
        ]));
        if ($demand->auto_transfer) {
            $this->log($demand, 'auto', __('Automatische overdracht aangezet: op :date, als er dan niet is betaald', [
                'date' => $this->autoTransferOn($demand)->translatedFormat('j F Y'),
            ]));
        }

        // In het verloop van de factuur, en daarmee ook in het incassodossier.
        ReminderLog::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'type' => __('Laatste aanmaning'),
            'kind' => 'demand',
            'channel' => 'email',
            'sent_to' => $demand->sent_to,
            'amount_open' => $principal,
            'sent_at' => now(),
        ]);

        Audit::log('reminded', $invoice, __('Laatste aanmaning voor :label verstuurd naar :to', [
            'label' => Audit::label($invoice), 'to' => $demand->sent_to,
        ]), [], $invoice->company_id);

        return $demand;
    }

    /**
     * De klant opent de pagina. De eerste keer telt als ontvangst; daarna komt
     * er hoogstens elk halfuur een regel bij. Wie zelf is ingelogd bij deze
     * administratie kijkt mee zonder spoor.
     */
    public function opened(PaymentDemand $demand, Request $request): void
    {
        if ((int) $request->user()?->company_id === (int) $demand->company_id) {
            return;
        }

        $key = "demand_seen_{$demand->id}";
        if (now()->timestamp - (int) $request->session()->get($key, 0) < 1800) {
            return;
        }
        $request->session()->put($key, now()->timestamp);

        if (! $demand->first_opened_at) {
            $demand->forceFill(['first_opened_at' => now()])->save();
        }
        $this->log($demand, 'opened', __('Pagina van de aanmaning geopend'), $request);
    }

    /**
     * Reactie van de klant. Een toegezegde betaaldatum is een erkenning van de
     * schuld en stuit de verjaring (art. 3:318 BW).
     *
     * @param  array{date?: ?string, note?: ?string}  $data
     */
    public function respond(PaymentDemand $demand, string $response, array $data, Request $request): void
    {
        if (! $demand->isActive() || ! in_array($demand->invoice?->status, ['sent', 'partial', 'overdue'], true)) {
            throw new \DomainException(__('Op deze aanmaning kunt u niet meer reageren.'));
        }
        if (! in_array($response, PaymentDemand::RESPONSES, true)) {
            throw new \DomainException(__('Kies een van de drie antwoorden.'));
        }

        $date = filled($data['date'] ?? null) ? Carbon::parse($data['date'])->startOfDay() : null;
        $note = filled($data['note'] ?? null) ? trim((string) $data['note']) : null;

        $demand->forceFill([
            'response' => $response,
            'response_date' => $date?->toDateString(),
            'response_note' => $note,
            'responded_at' => now(),
        ])->save();

        $this->log($demand, $response, $this->responseLabel($demand) . ($note ? ' — ' . $note : ''), $request);
        $this->notify($demand, 'response');
    }

    /** Wat de klant heeft geantwoord, in één regel. */
    public function responseLabel(PaymentDemand $demand): string
    {
        $date = $demand->response_date?->translatedFormat('j F Y');

        return match ($demand->response) {
            'paid' => $date ? __('Klant meldt op :date te hebben betaald', ['date' => $date]) : __('Klant meldt te hebben betaald'),
            'promise' => __('Klant zegt toe uiterlijk :date te betalen', ['date' => $date]),
            'dispute' => __('Klant is het niet eens met de factuur'),
            default => '',
        };
    }

    /**
     * Dagelijks: aanmaningen van betaalde facturen sluiten, en de ondernemer
     * melden dat een termijn voorbij is. Geeft het aantal meldingen terug.
     */
    public function notifyExpired(): int
    {
        $this->closeSettled();

        $due = PaymentDemand::withoutGlobalScope('company')
            ->where('status', 'sent')
            ->whereNull('expiry_notified_at')
            ->whereDate('deadline', '<', now()->toDateString())
            ->with(['invoice.company'])
            ->get();

        $count = 0;
        foreach ($due as $demand) {
            $company = $demand->invoice?->company;
            // Geen post namens een demo of een administratie zonder toegang; op pauze wacht de melding.
            if (! $company || $company->is_demo || ! $company->hasAccess() || $demand->invoice->remindersPaused()) {
                continue;
            }

            $this->log($demand, 'expired', __('Termijn verstreken zonder betaling'));
            $demand->forceFill(['expiry_notified_at' => now()])->save();
            if ($this->notify($demand, 'expired')) {
                $count++;
            }
        }

        $this->transferDue();

        return $count;
    }

    /**
     * Automatische overdracht: aanmaningen waarbij dat is gevraagd, een paar
     * werkdagen na de termijn, als er niet is betaald en de klant niets heeft
     * laten horen. Geeft het aantal overgedragen dossiers terug.
     */
    public function transferDue(): int
    {
        $due = PaymentDemand::withoutGlobalScope('company')
            ->where('status', 'sent')
            ->where('auto_transfer', true)
            ->whereDate('deadline', '<', now()->toDateString())
            ->with(['invoice.company', 'invoice.customer'])
            ->get();

        $count = 0;
        foreach ($due as $demand) {
            $invoice = $demand->invoice;
            $company = $invoice?->company;
            if (! $company || $company->is_demo || ! $company->hasAccess()
                || ! in_array($invoice->status, ['sent', 'partial', 'overdue'], true)
                || now()->startOfDay()->lt($this->autoTransferOn($demand))
                || $this->autoTransferBlocker($demand)) {
                continue;
            }

            try {
                app(IncassoService::class)->send($invoice);
                $this->log($demand, 'auto', __('Automatisch overgedragen: termijn voorbij, niet betaald en geen reactie'));
                Audit::log('updated', $invoice, __(':label automatisch overgedragen aan :partner na de laatste aanmaning', [
                    'label' => Audit::label($invoice), 'partner' => Market::incasso('partner_name'),
                ]), [], $invoice->company_id);
                $this->notify($demand->fresh(['invoice.company']), 'transferred');
                $count++;
            } catch (\Throwable $e) {
                Log::error('Automatische overdracht mislukt', ['demand' => $demand->id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /** Sluit lopende aanmaningen waarvan de factuur is betaald, vervallen of al bij de deurwaarder ligt. */
    public function closeSettled(): int
    {
        $open = PaymentDemand::withoutGlobalScope('company')->where('status', 'sent')
            ->whereHas('invoice', fn ($q) => $q->withoutGlobalScope('company')->whereNotIn('status', ['sent', 'partial', 'overdue']))
            ->with('invoice')
            ->get();

        foreach ($open as $demand) {
            $this->settle($demand);
        }

        return $open->count();
    }

    /** Brengt één aanmaning in lijn met de factuur (betaald, overgedragen of vervallen). */
    public function settle(PaymentDemand $demand): PaymentDemand
    {
        if (! $demand->isActive()) {
            return $demand;
        }

        $status = $demand->invoice?->status;
        if (in_array($status, ['sent', 'partial', 'overdue'], true)) {
            return $demand;
        }

        [$to, $event, $text] = match ($status) {
            'paid' => ['paid', 'settled', __('Factuur betaald; aanmaning gesloten')],
            'incasso' => ['transferred', 'transferred', __('Dossier overgedragen aan :partner', ['partner' => Market::incasso('partner_name')])],
            default => ['withdrawn', 'withdrawn', __('Factuur staat niet meer open; aanmaning gesloten')],
        };
        $demand->forceFill(['status' => $to, 'closed_at' => now()])->save();
        $this->log($demand, $event, $text);

        return $demand;
    }

    /** De ondernemer trekt de aanmaning in; de pagina van de klant zegt dat ook. */
    public function withdraw(PaymentDemand $demand): void
    {
        if (! $demand->isActive()) {
            throw new \DomainException(__('Deze aanmaning loopt niet meer.'));
        }

        $demand->forceFill(['status' => 'withdrawn', 'closed_at' => now()])->save();
        $this->log($demand, 'withdrawn', __('Aanmaning ingetrokken'));
        Audit::log('updated', $demand->invoice, __('Laatste aanmaning voor :label ingetrokken', ['label' => Audit::label($demand->invoice)]), [], $demand->company_id);
    }

    /**
     * Termijn voorbij en niet betaald: het dossier gaat naar de deurwaarder,
     * met de aanmaning, het logboek en de berekening erbij.
     */
    public function transfer(PaymentDemand $demand): Invoice
    {
        if (! $demand->isActive()) {
            throw new \DomainException(__('Deze aanmaning loopt niet meer.'));
        }
        if (! $demand->isExpired()) {
            throw new \DomainException(__('De termijn van de aanmaning loopt nog tot en met :date. Daarna kun je het dossier overdragen.', [
                'date' => $demand->deadline->translatedFormat('j F Y'),
            ]));
        }

        // IncassoService sluit de aanmaning en stuurt haar mee in het dossier.
        return app(IncassoService::class)->send($demand->invoice);
    }

    /**
     * Een aanmaning die nergens wordt bewaard: voor de gratis tool op de
     * website. Bedrijf, factuur en aanmaning bestaan alleen zolang de brief
     * wordt gemaakt. De ondernemer verstuurt hem zelf, dus bij een particulier
     * tellen we twee dagen voor de post.
     *
     * @param  array<string, mixed>  $data  de gevalideerde velden van de tool
     */
    public function draft(array $data): PaymentDemand
    {
        $company = (new \App\Models\Company())->forceFill([
            'name' => $data['van_bedrijf'],
            'email' => $data['van_email'] ?? null,
            'phone' => $data['van_telefoon'] ?? null,
            'kvk_number' => $data['van_kvk'] ?? null,
            'iban' => $data['van_iban'] ?? null,
            'kor' => (bool) ($data['geen_btw_aftrek'] ?? false),
            'brand_color' => (string) brand('color'),
        ] + $this->address((string) ($data['van_adres'] ?? '')));

        $customer = $this->address((string) ($data['aan_adres'] ?? ''));
        $invoice = (new Invoice())->forceFill([
            'number' => $data['factuurnummer'],
            'status' => 'overdue',
            'is_credit' => false,
            'language' => 'nl',
            'invoice_date' => Carbon::parse($data['factuurdatum'])->toDateString(),
            'due_date' => Carbon::parse($data['vervaldatum'])->toDateString(),
            'total' => round((float) $data['bedrag'], 2),
            'paid_total' => 0,
            'customer_name' => $data['aan_naam'],
            'customer_email' => $data['aan_email'] ?? null,
            'customer_address_line' => $customer['address_line'] ?? null,
            'customer_postal_code' => $customer['postal_code'] ?? null,
            'customer_city' => $customer['city'] ?? null,
        ]);
        $invoice->setRelation('company', $company)->setRelation('brandProfile', null)->setRelation('customer', null);

        $type = ($data['klant'] ?? 'zakelijk') === 'particulier' ? 'consumer' : 'business';
        $term = $this->term($type, isset($data['termijn']) && $data['termijn'] !== '' ? (int) $data['termijn'] : null);
        $principal = round((float) $data['bedrag'], 2);
        $costs = LegalInterest::collectionCosts($principal);

        $demand = (new PaymentDemand())->forceFill([
            'status' => 'sent',
            'debtor_type' => $type,
            'sent_to' => (string) ($data['aan_email'] ?? ''),
            'principal' => $principal,
            'with_interest' => (bool) ($data['rente'] ?? true),
            'costs' => $costs,
            'costs_vat' => $this->costsVat($invoice, $costs),
            'term_days' => $term,
            'deadline' => $this->deadline($type, $term, $type === 'consumer' ? now()->addDays(self::POST_DAYS) : null)->toDateString(),
            'sent_at' => now(),
        ]);

        return $demand->setRelation('invoice', $invoice);
    }

    /**
     * Adres uit een tekstvak, op dezelfde manier als bij de gratis factuur.
     *
     * @return array{address_line?: string, postal_code?: string, city?: string}
     */
    private function address(string $text): array
    {
        return app(FreeInvoiceImport::class)->address($text);
    }

    /** De aanmaning als brief (PDF), in de taal van de factuur en met het bedrag van de dag van verzenden. */
    public function pdf(PaymentDemand $demand): \Barryvdh\DomPDF\PDF
    {
        $invoice = $demand->invoice;
        $invoice->loadMissing('company');

        return DocumentLocale::using($invoice->language, fn () => Pdf::loadView('pdf.aanmaning', [
            'demand' => $demand,
            'invoice' => $invoice,
            'company' => $invoice->brandedCompany(),
            'claim' => $this->claimAsSent($demand),
            // Een losse brief uit de gratis tool heeft geen pagina, dus ook geen code.
            'qr' => filled($demand->token) ? PaymentQr::render($demand->url()) : null,
        ])->setPaper('a4'));
    }

    /**
     * Betaalcode voor de bank-app (EPC-QR, SEPA-overboeking) met het bedrag van
     * vandaag; null zonder IBAN of bij een bedrag dat er niet in past.
     */
    public function paymentQr(PaymentDemand $demand, array $claim): ?string
    {
        $company = $demand->invoice->brandedCompany();
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $company?->iban));
        if ($iban === '' || $claim['total'] < 0.01 || $claim['total'] > 999999999.99) {
            return null;
        }

        return PaymentQr::render(implode("\n", [
            'BCD', '002', '1', 'SCT', '',
            mb_substr((string) ($demand->invoice->company?->name ?: $company->name), 0, 70),
            $iban,
            'EUR' . number_format($claim['total'], 2, '.', ''),
            '', '',
            mb_substr(__('Factuur :number', ['number' => $demand->invoice->number]), 0, 140),
        ]));
    }

    private function invoicePdf(Invoice $invoice): string
    {
        $invoice->loadMissing('lines');
        $company = $invoice->brandedCompany();
        $template = $company->resolvedInvoiceTemplate();

        return Pdf::loadView("pdf.invoice-{$template}", [
            'invoice' => $invoice,
            'company' => $company,
            'watermarkStatus' => 'dunning',
        ])->setPaper('a4')->output();
    }

    /** Bericht aan de ondernemer: de klant heeft gereageerd, of de termijn is voorbij. */
    private function notify(PaymentDemand $demand, string $kind): bool
    {
        $company = $demand->invoice?->company;
        $to = $company?->copy_email ?: $company?->email;
        if (! $to) {
            return false;
        }

        try {
            // Een demo mailt nooit echt, ook niet als de klant (zonder inlog) reageert.
            $auto = $kind === 'expired' && $demand->auto_transfer;
            $blocker = $auto ? $this->autoTransferBlocker($demand) : null;
            Mail::mailer($company->is_demo ? 'log' : null)->to($to)
                ->send(new PaymentDemandNoticeMail(
                    $demand, $kind, $this->claim($demand), $this->responseLabel($demand),
                    $auto && ! $blocker ? $this->autoTransferOn($demand)->translatedFormat('j F Y') : null,
                    $blocker,
                ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Melding over aanmaning mailen mislukt', ['demand' => $demand->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function log(PaymentDemand $demand, string $event, ?string $description = null, ?Request $request = null): void
    {
        $demand->events()->create([
            'event' => $event,
            'description' => $description ? mb_substr($description, 0, 2000) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }
}
