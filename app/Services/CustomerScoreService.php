<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerCheck;
use App\Models\Invoice;
use App\Models\ReminderLog;
use App\Services\Checks\InsolvencyService;
use App\Services\Checks\ViesService;
use App\Support\Market;
use Illuminate\Support\Carbon;

/**
 * Klantscore: een indicatie van hoe waarschijnlijk het is dat een klant op
 * tijd betaalt, van 0 tot 100 met een letter A tot en met E. Geen
 * kredietrapport van een bureau, maar een optelsom van wat wij zelf zien
 * (het betaalgedrag in deze administratie) en wat de openbare bronnen zeggen:
 * het btw-nummer (VIES), het Handelsregister (KvK) en het insolventieregister.
 *
 * De score wordt getoond aan de ondernemer; hij beslist zelf. Er wordt niets
 * automatisch geweigerd en er gaat niets naar andere administraties.
 */
class CustomerScoreService
{
    /** Zo lang zijn de antwoorden van de bronnen goed. */
    public const SOURCES_FRESH_DAYS = 7;

    public function __construct(
        private ViesService $vies,
        private InsolvencyService $insolvency,
        private KvkService $kvk,
    ) {}

    /** Bestaat de klantscore in deze markt? De bronnen zijn Nederlands. */
    public function available(): bool
    {
        return Market::is('nl');
    }

    /**
     * De score van een klant, met de signalen erachter. Bewaart het resultaat;
     * de bronnen worden opnieuw bevraagd als het antwoord ouder is dan een
     * week, of als daar expliciet om wordt gevraagd.
     *
     * @return array<string, mixed>
     */
    public function score(Customer $customer, bool $refresh = false): array
    {
        $check = CustomerCheck::firstOrNew(['customer_id' => $customer->id], ['company_id' => $customer->company_id]);

        $stale = ! $check->sources_checked_at || $check->sources_checked_at->lt(now()->subDays(self::SOURCES_FRESH_DAYS));
        $sources = $check->sources ?? [];
        if ($refresh || $stale) {
            // Op verzoek de bronnen echt opnieuw bevragen, langs de tussenopslag heen.
            $sources = $this->sources($customer, $refresh);
            $check->sources_checked_at = now();
        }

        $history = $this->history($customer);
        [$score, $signals] = $this->compute($customer, $history, $sources);

        $check->fill([
            'company_id' => $customer->company_id,
            'score' => $score,
            'grade' => $score === null ? null : self::grade($score),
            'signals' => $signals,
            'sources' => $sources,
            'checked_at' => now(),
        ])->save();

        return $this->present($customer, $check, $history);
    }

    /** De bewaarde score, zonder opnieuw te rekenen; null als er nog geen is. */
    public function stored(Customer $customer): ?array
    {
        $check = CustomerCheck::where('customer_id', $customer->id)->first();

        return $check ? $this->present($customer, $check, $this->history($customer)) : null;
    }

    public static function grade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default => 'E',
        };
    }

    public static function label(?string $grade): string
    {
        return match ($grade) {
            'A' => __('Betaalt goed'),
            'B' => __('Redelijk'),
            'C' => __('Let op'),
            'D' => __('Risico'),
            'E' => __('Hoog risico'),
            default => __('Nog niet te beoordelen'),
        };
    }

    /**
     * Het betaalgedrag in deze administratie.
     *
     * @return array<string, mixed>
     */
    private function history(Customer $customer): array
    {
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('is_credit', false)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get(['id', 'status', 'due_date', 'paid_at', 'total', 'paid_total', 'incasso_sent_at', 'invoice_date']);

        $paid = $invoices->filter(fn (Invoice $i) => $i->status === 'paid' && $i->paid_at && $i->due_date);
        // In hele dagen: betaald op de vervaldag zelf is 0, de dag erna 1.
        $lateDays = $paid->map(fn (Invoice $i) => max(0, (int) $i->due_date->copy()->startOfDay()->diffInDays($i->paid_at->copy()->startOfDay(), false)));
        $open = $invoices->filter(fn (Invoice $i) => in_array($i->status, ['sent', 'partial', 'overdue', 'incasso'], true) && $i->due_date && $i->due_date->lt(now()->startOfDay()));

        $reminders = ReminderLog::whereIn('invoice_id', $invoices->pluck('id'))->where('sent_at', '>=', now()->subMonths(12))->get(['kind']);

        return [
            'paid_count' => $paid->count(),
            'avg_late_days' => $paid->count() ? (int) round($lateDays->avg()) : 0,
            'late_share' => $paid->count() ? round($lateDays->filter(fn ($d) => $d > 0)->count() / $paid->count(), 2) : 0.0,
            'open_overdue_count' => $open->count(),
            'open_overdue_total' => round($open->sum(fn (Invoice $i) => (float) $i->total - (float) $i->paid_total), 2),
            'max_days_overdue' => $open->count() ? (int) $open->max(fn (Invoice $i) => $i->due_date->diffInDays(now()->startOfDay())) : 0,
            'reminders' => $reminders->where('kind', 'reminder')->count(),
            'demands' => $reminders->whereIn('kind', ['warning', 'demand'])->count(),
            'incasso' => $invoices->filter(fn (Invoice $i) => $i->status === 'incasso' || ($i->incasso_sent_at && Carbon::parse($i->incasso_sent_at)->gt(now()->subMonths(24))))->count(),
        ];
    }

    /**
     * De openbare bronnen, per bron met status en tekst.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sources(Customer $customer, bool $fresh = false): array
    {
        $sources = [];
        $business = $customer->type !== 'consumer';

        if ($business) {
            $vies = filled($customer->vat_number) ? $this->vies->check($customer->vat_number, $fresh) : null;
            $sources['vies'] = blank($customer->vat_number)
                ? ['status' => 'off', 'text' => __('Geen btw-nummer bij de klant ingevuld.')]
                : ($vies === null
                    ? ['status' => 'unknown', 'text' => __('Het btw-nummer kon niet worden gecontroleerd.')]
                    : ($vies['valid']
                        ? ['status' => 'ok', 'text' => __('Btw-nummer geldig volgens VIES.') . ($vies['name'] ? ' ' . __('Op naam van :name.', ['name' => $vies['name']]) : ''), 'checked_at' => $vies['checked_at']]
                        : ['status' => 'bad', 'text' => __('Btw-nummer niet geldig volgens VIES.'), 'checked_at' => $vies['checked_at']]));

            $facts = filled($customer->kvk_number) && $this->kvk->enabled() ? $this->kvk->facts($customer->kvk_number, $fresh) : null;
            $sources['kvk'] = blank($customer->kvk_number)
                ? ['status' => 'off', 'text' => __('Geen KvK-nummer bij de klant ingevuld.')]
                : (! $this->kvk->enabled()
                    ? ['status' => 'off', 'text' => __('De KvK-koppeling staat niet aan.')]
                    : ($facts === null
                        ? ['status' => 'unknown', 'text' => __('Het Handelsregister gaf geen gegevens.')]
                        : $this->kvkSource($facts)));
        }

        if (! $this->insolvency->configured()) {
            $sources['cir'] = ['status' => 'off', 'text' => __('Het insolventieregister is niet gekoppeld.')];
        } else {
            $result = $business
                ? $this->insolvency->forCompany($customer->kvk_number, $customer->name, $customer->postal_code, $customer->address_line, $fresh)
                : $this->insolvency->forPerson($customer->name, $customer->postal_code, $customer->address_line, $fresh);
            $sources['cir'] = $result === null
                ? ['status' => 'unknown', 'text' => $business
                    ? __('Zoeken in het insolventieregister vraagt een KvK-nummer, of een naam met postcode en huisnummer.')
                    : __('Zoeken in het insolventieregister vraagt een achternaam met postcode en huisnummer.')]
                : $this->cirSource($result, $business);
        }

        return $sources;
    }

    /** @param  array<string, mixed>  $facts */
    private function kvkSource(array $facts): array
    {
        $since = $facts['started_on'] ?? $facts['registered_on'];
        $years = $since ? (int) Carbon::parse($since)->diffInYears(now()) : null;
        $parts = array_filter([
            $facts['legal_form'],
            $years !== null ? __('sinds :year (:n jaar)', ['year' => Carbon::parse($since)->year, 'n' => $years]) : null,
            $facts['employees'] !== null ? __(':n werkzame personen', ['n' => $facts['employees']]) : null,
        ]);

        if (! $facts['active']) {
            return ['status' => 'bad', 'text' => __('Uitgeschreven uit het Handelsregister op :date.', ['date' => Carbon::parse($facts['ended_on'])->translatedFormat('j F Y')]), 'checked_at' => $facts['checked_at'], 'years' => $years, 'active' => false];
        }

        return ['status' => $years !== null && $years < 2 ? 'warn' : 'ok', 'text' => __('Ingeschreven in het Handelsregister: :facts.', ['facts' => implode(', ', $parts)]), 'checked_at' => $facts['checked_at'], 'years' => $years, 'active' => true];
    }

    /** @param  array{cases: array<int, array<string, mixed>>, checked_at: string}  $result */
    private function cirSource(array $result, bool $business): array
    {
        $active = array_filter($result['cases'], fn ($c) => ! $c['ended']);
        $ended = array_filter($result['cases'], fn ($c) => $c['ended']);
        $describe = fn (array $c) => ucfirst($c['type']) . ' ' . $c['number'] . ($c['name'] ? ' (' . $c['name'] . ')' : '') . ($c['latest'] ? ': ' . $c['latest']['description'] : '');

        if ($active) {
            return ['status' => 'bad', 'text' => __('In het insolventieregister:') . ' ' . implode('; ', array_map($describe, $active)) . ($business ? '' : ' ' . __('Controleer zelf of dit dezelfde persoon is.')), 'checked_at' => $result['checked_at'], 'cases' => array_values($result['cases'])];
        }
        if ($ended) {
            return ['status' => 'warn', 'text' => __('Recent beëindigd in het insolventieregister:') . ' ' . implode('; ', array_map($describe, $ended)), 'checked_at' => $result['checked_at'], 'cases' => array_values($result['cases'])];
        }

        return ['status' => 'ok', 'text' => __('Geen faillissement, surseance of schuldsanering bekend.'), 'checked_at' => $result['checked_at'], 'cases' => []];
    }

    /**
     * Van de feiten naar een getal. Elk signaal krijgt een tekst en wat het kost,
     * zodat de ondernemer ziet waar de score vandaan komt.
     *
     * @param  array<string, mixed>  $history
     * @param  array<string, array<string, mixed>>  $sources
     * @return array{0: ?int, 1: array<int, array{label: string, impact: int}>}
     */
    private function compute(Customer $customer, array $history, array $sources): array
    {
        $signals = [];
        $score = 100;
        $known = $history['paid_count'] > 0 || $history['open_overdue_count'] > 0
            || collect($sources)->contains(fn ($s) => in_array($s['status'], ['ok', 'warn', 'bad'], true));
        if (! $known) {
            return [null, [['label' => __('Nog geen betaalde facturen en geen gegevens uit de bronnen.'), 'impact' => 0]]];
        }

        // Betaalgedrag in deze administratie.
        if ($history['paid_count'] === 0) {
            $score -= 10;
            $signals[] = ['label' => __('Nog geen betaalde facturen: geen betaalhistorie'), 'impact' => -10];
        } else {
            $penalty = min(25, (int) round($history['avg_late_days'] * 0.8));
            $score -= $penalty;
            $signals[] = ['label' => __(':n betaalde facturen, gemiddeld :days dagen na de vervaldatum betaald', ['n' => $history['paid_count'], 'days' => $history['avg_late_days']]), 'impact' => -$penalty];
            if ($history['late_share'] > 0.5) {
                $score -= 10;
                $signals[] = ['label' => __('Meer dan de helft van de facturen te laat betaald'), 'impact' => -10];
            } elseif ($history['late_share'] > 0.25) {
                $score -= 5;
                $signals[] = ['label' => __('Een kwart of meer van de facturen te laat betaald'), 'impact' => -5];
            } elseif ($history['late_share'] == 0 && $history['paid_count'] >= 3) {
                $signals[] = ['label' => __('Alles binnen de termijn betaald'), 'impact' => 0];
            }
        }
        if ($history['open_overdue_count'] > 0) {
            $penalty = min(30, (int) round($history['max_days_overdue'] * 0.4));
            $score -= $penalty;
            $signals[] = ['label' => __(':n openstaande facturen over de vervaldatum, de oudste :days dagen (:amount)', ['n' => $history['open_overdue_count'], 'days' => $history['max_days_overdue'], 'amount' => money($history['open_overdue_total'])]), 'impact' => -$penalty];
        } elseif ($history['paid_count'] > 0) {
            $signals[] = ['label' => __('Geen openstaande achterstand'), 'impact' => 0];
        }
        if ($history['reminders'] > 0) {
            $penalty = min(12, 3 * $history['reminders']);
            $score -= $penalty;
            $signals[] = ['label' => __(':n herinneringen in de laatste twaalf maanden', ['n' => $history['reminders']]), 'impact' => -$penalty];
        }
        if ($history['demands'] > 0) {
            $score -= 8;
            $signals[] = ['label' => __(':n aanmaningen in de laatste twaalf maanden', ['n' => $history['demands']]), 'impact' => -8];
        }
        if ($history['incasso'] > 0) {
            $score -= 25;
            $signals[] = ['label' => __('Eerder naar incasso (:n facturen)', ['n' => $history['incasso']]), 'impact' => -25];
        }

        // De openbare bronnen.
        $vies = $sources['vies'] ?? null;
        if ($vies && $vies['status'] === 'bad') {
            $score -= 15;
            $signals[] = ['label' => __('Btw-nummer niet geldig'), 'impact' => -15];
        } elseif ($vies && $vies['status'] === 'ok') {
            $signals[] = ['label' => __('Btw-nummer geldig'), 'impact' => 0];
        }
        $kvk = $sources['kvk'] ?? null;
        if ($kvk && $kvk['status'] === 'bad') {
            $score -= 50;
            $signals[] = ['label' => __('Uitgeschreven uit het Handelsregister'), 'impact' => -50];
        } elseif ($kvk && in_array($kvk['status'], ['ok', 'warn'], true) && isset($kvk['years'])) {
            if ($kvk['years'] < 1) {
                $score -= 10;
                $signals[] = ['label' => __('Jonger dan een jaar volgens het Handelsregister'), 'impact' => -10];
            } elseif ($kvk['years'] < 2) {
                $score -= 5;
                $signals[] = ['label' => __('Jonger dan twee jaar volgens het Handelsregister'), 'impact' => -5];
            } else {
                $signals[] = ['label' => __('Al :n jaar ingeschreven in het Handelsregister', ['n' => $kvk['years']]), 'impact' => 0];
            }
        }
        $cir = $sources['cir'] ?? null;
        if ($cir && $cir['status'] === 'bad') {
            $types = array_column(array_filter($cir['cases'] ?? [], fn ($c) => ! $c['ended']), 'type');
            $penalty = in_array('faillissement', $types, true) ? 70 : 50;
            $score -= $penalty;
            $signals[] = ['label' => __('In het insolventieregister: :type', ['type' => implode(', ', array_unique($types))]), 'impact' => -$penalty];
        } elseif ($cir && $cir['status'] === 'warn') {
            $score -= 15;
            $signals[] = ['label' => __('Recent beëindigde insolventie'), 'impact' => -15];
        } elseif ($cir && $cir['status'] === 'ok') {
            $signals[] = ['label' => __('Niet in het insolventieregister'), 'impact' => 0];
        }

        return [max(0, min(100, $score)), $signals];
    }

    /**
     * @param  array<string, mixed>  $history
     * @return array<string, mixed>
     */
    private function present(Customer $customer, CustomerCheck $check, array $history): array
    {
        $labels = [
            'vies' => __('Btw-nummer (VIES)'),
            'kvk' => __('Handelsregister (KvK)'),
            'cir' => __('Insolventieregister'),
        ];
        $sources = [];
        foreach ($check->sources ?? [] as $key => $source) {
            $sources[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'status' => $source['status'],
                'text' => $source['text'],
                'checked_at_label' => isset($source['checked_at']) ? Carbon::parse($source['checked_at'])->translatedFormat('j M Y') : null,
            ];
        }

        return [
            'score' => $check->score,
            'grade' => $check->grade,
            'label' => self::label($check->grade),
            'signals' => $check->signals ?? [],
            'sources' => $sources,
            'checked_at_label' => $check->checked_at?->translatedFormat('j M Y, H:i'),
            'kind' => $customer->type === 'consumer' ? 'consumer' : 'business',
            'history' => $history,
        ];
    }
}
