<?php

namespace App\Services;

use App\Mail\QuoteMail;
use App\Models\Quote;
use App\Models\ShortLink;
use App\Support\Audit;
use App\Support\DocumentLocale;
use App\Support\PhoneNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 1.102: opvolging van een verstuurde offerte. Twee geplande herinneringen aan de klant
 * (N dagen na het versturen en N dagen vóór de vervaldatum, instelbaar per administratie,
 * aan/uit per offerte), per mail of — als de klant alleen een mobiel nummer heeft — per sms.
 * Stopt vanzelf zodra de klant beslist of de offerte verloopt. De tijdlijn voor het scherm
 * komt uit timeline().
 */
class QuoteFollowUpService
{
    public const MAX_REMINDERS = 2;

    public function __construct(private SmsService $sms) {}

    /** @return array{first: ?Carbon, second: ?Carbon} */
    public function plannedReminders(Quote $quote): array
    {
        if (! $quote->sent_at || ! $quote->valid_until) {
            return ['first' => null, 'second' => null];
        }
        $s = $quote->company->quoteReminderSettings();
        $sent = $quote->sent_at->copy()->startOfDay();
        $expiry = $quote->valid_until->copy()->startOfDay();
        $first = $sent->copy()->addDays($s['first_after_days']);
        $second = $expiry->copy()->subDays($s['second_before_expiry_days']);
        if ($first->gte($expiry)) {
            $first = null;
        }
        if ($second->lte($sent) || ($first && $second->lte($first))) {
            $second = null;
        }

        return ['first' => $first, 'second' => $second];
    }

    /** Eerstvolgende geplande herinnering, of null als er geen meer komt. */
    public function nextReminderAt(Quote $quote): ?Carbon
    {
        if ($quote->status !== 'sent' || ! $quote->reminders_enabled || ! $quote->company->quoteReminderSettings()['enabled']) {
            return null;
        }
        if (count((array) $quote->reminder_log) >= self::MAX_REMINDERS) {
            return null;
        }
        // Elke verstuurde herinnering (ook een handmatige) neemt een plek in: na één herinnering
        // blijft alleen nog de tweede geplande over, en die alleen als hij ná de laatste valt.
        $p = array_values($this->plannedReminders($quote));
        $last = $this->lastReminderAt($quote);
        for ($k = count((array) $quote->reminder_log); $k < self::MAX_REMINDERS; $k++) {
            $planned = $p[$k] ?? null;
            if ($planned && (! $last || $planned->gt($last->copy()->startOfDay()))) {
                return $planned;
            }
        }

        return null;
    }

    public function lastReminderAt(Quote $quote): ?Carbon
    {
        $log = (array) $quote->reminder_log;
        $last = end($log);

        return $last ? Carbon::parse($last['at']) : null;
    }

    /** Offertes waarvoor vandaag (of eerder, bij een gemiste dag) een herinnering uit moet. */
    public function due(): Collection
    {
        $today = now()->startOfDay();

        return Quote::query()->withoutGlobalScope('company')->with('company', 'customer')
            ->where('status', 'sent')->where('reminders_enabled', true)->whereNotNull('sent_at')
            ->whereDate('valid_until', '>=', $today->toDateString())->get()
            ->filter(function (Quote $q) use ($today) {
                $next = $this->nextReminderAt($q);
                $last = $this->lastReminderAt($q);

                return $next && $next->lte($today) && (! $last || ! $last->isSameDay($today));
            })->values();
    }

    /** Dagelijkse run: stuurt wat vandaag aan de beurt is. Geeft het aantal verstuurde herinneringen terug. */
    public function runDue(): int
    {
        $n = 0;
        foreach ($this->due() as $quote) {
            try {
                $this->send($quote, 'auto');
                $n++;
            } catch (\Throwable $e) {
                Log::warning('Offerteherinnering mislukt', ['quote' => $quote->id, 'error' => $e->getMessage()]);
            }
        }

        return $n;
    }

    /**
     * Eén herinnering, nu: per mail als er een e-mailadres is, anders per sms naar een mobiel nummer.
     * Geeft het kanaal terug ('mail' of 'sms').
     *
     * @throws \DomainException
     */
    public function send(Quote $quote, string $trigger = 'manual', ?int $userId = null): string
    {
        if ($quote->status !== 'sent') {
            throw new \DomainException(__('Alleen bij een verstuurde offerte kan een herinnering worden gestuurd.'));
        }
        $email = $quote->customer_email ?: $quote->customer?->email;
        $mobile = PhoneNumber::mobile($quote->customer?->phone);
        if ($email) {
            DocumentLocale::using($quote->language ?: 'nl', function () use ($quote, $email) {
                $company = $quote->brandedCompany();
                $quote->load('lines');
                $pdf = Pdf::loadView('pdf.quote', ['quote' => $quote, 'company' => $company])->setPaper('a4')->output();
                $mail = Mail::to($email);
                $cc = $company->copy_email ?: $company->email;
                if ($cc && strcasecmp($cc, $email) !== 0) {
                    $mail->cc($cc);
                }
                $mail->send(new QuoteMail($quote, $pdf, reminder: true));
            });
            $channel = 'mail';
        } elseif ($mobile && $this->sms->available($quote->company)) {
            $this->sms->send($quote->company, $mobile, $this->smsText($quote), $quote, $userId);
            $channel = 'sms';
        } else {
            throw new \DomainException(__('Geen e-mailadres of mobiel nummer bekend; er kan geen herinnering worden verstuurd.'));
        }
        $log = array_values((array) $quote->reminder_log);
        $log[] = ['at' => now()->toIso8601String(), 'channel' => $channel, 'trigger' => $trigger];
        $quote->forceFill(['reminder_log' => $log])->save();
        Audit::log('reminded', $quote, __('Herinnering :n voor :label verstuurd per :channel', [
            'n' => count($log), 'label' => Audit::label($quote), 'channel' => $channel === 'sms' ? 'sms' : __('e-mail'),
        ]), [], $quote->company_id);

        return $channel;
    }

    /** Sms-tekst in de taal van de offerte, met een korte link naar het portaal. */
    public function smsText(Quote $quote): string
    {
        $quote->ensurePortalToken();
        $link = ShortLink::for($quote->portalUrl(), $quote->company_id)->shortUrl();

        return DocumentLocale::using($quote->language ?: 'nl', fn () => __(':company: herinnering aan offerte :number van :amount, geldig tot :date. Bekijk en accepteer: :link', [
            'company' => $quote->brandedCompany()->name,
            'number' => $quote->number,
            'amount' => money($quote->total),
            'date' => $quote->valid_until?->translatedFormat('j F') ?? '',
            'link' => $link,
        ]));
    }

    /** Of er (nu) een herinnering de deur uit kán: er is een e-mailadres of een mobiel nummer met sms-tegoed. */
    public function canRemind(Quote $quote): bool
    {
        if ($quote->status !== 'sent') {
            return false;
        }
        if (filled($quote->customer_email ?: $quote->customer?->email)) {
            return true;
        }

        return PhoneNumber::mobile($quote->customer?->phone) !== null && $this->sms->available($quote->company);
    }

    /**
     * Alles wat het scherm nodig heeft voor de opvolgingsbalk.
     *
     * @return array<string, mixed>
     */
    public function timeline(Quote $quote): array
    {
        $p = $this->plannedReminders($quote);
        $log = array_values((array) $quote->reminder_log);
        $next = $this->nextReminderAt($quote);
        $fmt = fn (?Carbon $d) => $d ? rtrim($d->translatedFormat('j M'), '.') : null; // "7 nov" (zonder de Nederlandse punt)
        $open = $quote->status === 'sent';
        $reminder = function (int $i, ?Carbon $planned) use ($log, $fmt, $open, $next) {
            if (isset($log[$i - 1])) {
                return [
                    'state' => 'done',
                    'date' => $fmt(Carbon::parse($log[$i - 1]['at'])),
                    'channel' => $log[$i - 1]['channel'] ?? 'mail',
                    'trigger' => $log[$i - 1]['trigger'] ?? 'auto',
                ];
            }
            if (! $open || ! $planned || ! $next || $planned->lt($next)) {
                return ['state' => 'skipped', 'date' => $fmt($planned), 'channel' => null, 'trigger' => null];
            }

            return ['state' => $next->isSameDay($planned) ? 'next' : 'planned', 'date' => $fmt($planned), 'channel' => null, 'trigger' => null];
        };

        return [
            'steps' => [
                ['key' => 'sent', 'label' => __('Verstuurd'), 'date' => $fmt($quote->sent_at), 'state' => $quote->sent_at ? 'done' : 'pending'],
                ['key' => 'viewed', 'label' => __('Bekeken'), 'date' => $fmt($quote->portal_viewed_at), 'state' => $quote->portal_viewed_at ? 'done' : 'pending', 'views' => (int) $quote->portal_views],
                ['key' => 'reminder1', 'label' => __('Herinnering 1')] + $reminder(1, $p['first']),
                ['key' => 'reminder2', 'label' => __('Herinnering 2')] + $reminder(2, $p['second']),
                ['key' => 'expires', 'label' => __('Verloopt'), 'date' => $fmt($quote->valid_until), 'state' => $quote->valid_until && $quote->valid_until->isPast() ? 'done' : 'pending'],
            ],
            'enabled' => (bool) $quote->reminders_enabled,
            'company_enabled' => $quote->company->quoteReminderSettings()['enabled'],
            'next' => $fmt($next),
            'count' => count($log),
            'can_remind' => $this->canRemind($quote),
            'last_at' => $log ? $fmt(Carbon::parse($log[count($log) - 1]['at'])) : null,
            'last_channel' => $log ? ($log[count($log) - 1]['channel'] ?? null) : null,
            'days_left' => $quote->valid_until ? (int) $quote->days_left : null,
        ];
    }
}
