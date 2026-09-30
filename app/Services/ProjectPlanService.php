<?php

namespace App\Services;

use App\Mail\PlanDigestMail;
use App\Mail\PlanMail;
use App\Mail\PlanNoticeMail;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectPlanItem;
use App\Models\Subcontractor;
use App\Models\TenderRound;
use App\Support\Audit;
use App\Support\DocumentLocale;
use App\Support\IsoWeek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * De tijdslijn van een project en alles wat daar vanzelf omheen gebeurt:
 * een gegunde uitvraag wordt een onderdeel; de onderaannemer krijgt een
 * vooraankondiging een week vooraf en een herinnering als de week aanbreekt;
 * is een onderdeel eerder klaar, dan vragen we de volgende partij(en) of ze
 * eerder kunnen, en hun antwoord schuift de planning zelf op. Op maandag
 * krijgt de ondernemer het weekoverzicht.
 */
class ProjectPlanService
{
    /** Zoveel dagen vooraf gaat de vooraankondiging. */
    public const HEADSUP_DAYS = 7;

    /** Een gegund onderdeel zonder einddatum krijgt één werkweek. */
    private const DEFAULT_DAYS = 5;

    /* ------------------------------------------------------------------ sync */

    /** Elke gegunde uitvraag van het project staat op de tijdslijn; wat er al staat blijft. */
    public function sync(Project $project): int
    {
        $known = $project->planItems()->whereNotNull('tender_round_id')->pluck('tender_round_id')->all();
        $rounds = $project->tenderRounds()->where('status', 'awarded')->whereNotIn('id', $known)
            ->with('awardedRequest.subcontractor')->orderBy('awarded_at')->get();

        foreach ($rounds as $round) {
            $this->fromRound($project, $round);
        }

        return $rounds->count();
    }

    /** Na een gunning: meteen op de tijdslijn van het project, als de uitvraag er een heeft. */
    public function syncRound(TenderRound $round): ?ProjectPlanItem
    {
        if (! $round->project_id || $round->status !== 'awarded') {
            return null;
        }
        $project = Project::withoutGlobalScope('company')->find($round->project_id);
        if (! $project || $project->planItems()->where('tender_round_id', $round->id)->exists()) {
            return null;
        }

        return $this->fromRound($project, $round->loadMissing('awardedRequest.subcontractor'));
    }

    private function fromRound(Project $project, TenderRound $round): ProjectPlanItem
    {
        $request = $round->awardedRequest;
        // De week uit de uitvraag; gaf de winnaar een latere week op, dan die.
        $start = IsoWeek::monday($round->start_week);
        $available = IsoWeek::monday($request?->available_week);
        if ($available && (! $start || $available->gt($start))) {
            $start = $available;
        }

        $item = $project->planItems()->create([
            'tender_round_id' => $round->id,
            'subcontractor_id' => $request?->subcontractor_id,
            'title' => $round->title,
            'starts_on' => $start,
            'ends_on' => $start?->copy()->addDays(self::DEFAULT_DAYS - 1),
            'sort' => (int) $project->planItems()->max('sort') + 1,
        ]);
        Audit::log('created', $project, __('Onderdeel :title op de planning gezet', ['title' => $item->title]), [], $project->company_id);

        return $item;
    }

    /* ------------------------------------------------------------------ plan */

    /**
     * Eigen onderdeel toevoegen of een onderdeel aanpassen.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(Project $project, array $data, ?ProjectPlanItem $item = null): ProjectPlanItem
    {
        $starts = filled($data['starts_on'] ?? null) ? Carbon::parse($data['starts_on'])->startOfDay() : null;
        $ends = filled($data['ends_on'] ?? null) ? Carbon::parse($data['ends_on'])->startOfDay() : null;
        if ($starts && $ends && $ends->lt($starts)) {
            throw new \DomainException(__('De einddatum ligt vóór de startdatum.'));
        }
        if ($starts && ! $ends) {
            $ends = $starts->copy()->addDays(self::DEFAULT_DAYS - 1);
        }
        $subcontractorId = null;
        if (! empty($data['subcontractor_id'])) {
            $subcontractorId = Subcontractor::withoutGlobalScope('company')->where('company_id', $project->company_id)
                ->whereKey($data['subcontractor_id'])->value('id');
        }

        $values = [
            'title' => trim((string) ($data['title'] ?? '')) ?: ($item?->title ?? __('Onderdeel')),
            'starts_on' => $starts,
            'ends_on' => $ends,
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
        ];
        // Bij een gegund onderdeel staat de onderaannemer vast.
        if (! $item || ! $item->tender_round_id) {
            $values['subcontractor_id'] = $subcontractorId;
        }

        if ($item) {
            $moved = $item->starts_on?->toDateString() !== $starts?->toDateString();
            $item->fill($values);
            // Nieuwe start: de herinneringen mogen opnieuw, een lopend verzoek vervalt.
            if ($moved) {
                $item->forceFill(['headsup_sent_at' => null, 'reminder_sent_at' => null, 'confirmed_at' => null]);
                $this->clearRequest($item);
            }
            $item->save();
        } else {
            $item = $project->planItems()->create($values + ['sort' => (int) $project->planItems()->max('sort') + 1]);
        }
        Audit::log('updated', $project, __('Planning: :title', ['title' => $item->title]), [], $project->company_id);

        return $item;
    }

    public function delete(ProjectPlanItem $item): void
    {
        $project = $item->project;
        $item->delete();
        Audit::log('deleted', $project, __('Onderdeel :title van de planning gehaald', ['title' => $item->title]), [], $project?->company_id);
    }

    /**
     * Status zetten. "Klaar" vóór de geplande einddatum: de dagen die zijn
     * gewonnen, vragen we automatisch aan de volgende partij(en) — als het
     * project dat aan heeft staan. Geeft terug hoeveel verzoeken er uitgingen.
     */
    public function setStatus(ProjectPlanItem $item, string $status, ?Carbon $doneOn = null): int
    {
        if (! in_array($status, ProjectPlanItem::STATUSES, true)) {
            throw new \DomainException(__('Onbekende status.'));
        }
        $project = $item->project;
        $doneOn = $status === 'done' ? ($doneOn ?? today())->startOfDay() : null;
        $item->forceFill(['status' => $status, 'done_on' => $doneOn])->save();
        Audit::log('updated', $project, __('Onderdeel :title: :status', ['title' => $item->title, 'status' => __($status)]), [], $project?->company_id);

        if ($status !== 'done' || ! $project || ! $project->auto_earlier || ! $item->ends_on || ! $doneOn->lt($item->ends_on)) {
            return 0;
        }

        return $this->askEarlierAfter($item, (int) $doneOn->diffInDays($item->ends_on));
    }

    /**
     * Een onderdeel is zoveel dagen eerder klaar: vraag elke latere partij
     * met een e-mailadres of zij evenveel eerder kunnen beginnen.
     */
    public function askEarlierAfter(ProjectPlanItem $done, int $gained): int
    {
        if ($gained < 1 || ! $done->done_on) {
            return 0;
        }
        $sent = 0;
        $later = $done->project->planItems()->where('id', '!=', $done->id)->where('status', 'planned')
            ->whereNotNull('starts_on')->where('starts_on', '>', $done->done_on)->with('subcontractor')->get();
        foreach ($later as $item) {
            if (! $item->mailable() || $item->requestPending()) {
                continue;
            }
            $proposed = ProjectPlanItem::workday(max($item->starts_on->copy()->subDays($gained), $done->done_on->copy()->addDay()));
            if ($proposed->gte($item->starts_on)) {
                continue;
            }
            if ($this->askEarlier($item, $proposed, __(':title is :n dagen eerder klaar dan gepland.', ['title' => $done->title, 'n' => $gained]))) {
                $sent++;
            }
        }

        return $sent;
    }

    /** Vraag de onderaannemer of hij op een eerdere dag kan beginnen; het antwoord komt via de tokenlink. */
    public function askEarlier(ProjectPlanItem $item, Carbon $start, ?string $reason = null): bool
    {
        if (! $item->mailable()) {
            throw new \DomainException(__('Dit onderdeel heeft geen onderaannemer met e-mailadres.'));
        }
        if (! $item->starts_on || $start->gte($item->starts_on)) {
            throw new \DomainException(__('Kies een dag vóór de geplande start.'));
        }
        $item->forceFill([
            'request_start' => $start->startOfDay(),
            'request_sent_at' => now(),
            'request_answer' => null,
            'request_answer_start' => null,
            'request_message' => $reason,
            'request_answered_at' => null,
        ])->save();
        $ok = $this->mail($item, 'earlier');
        Audit::log('sent', $item->project, __('Gevraagd of :name eerder kan beginnen met :title (:date)', [
            'name' => $item->subcontractor?->name, 'title' => $item->title, 'date' => $start->translatedFormat('j F'),
        ]), [], $item->project?->company_id);

        return $ok;
    }

    /** Het antwoord van de onderaannemer: ja, een andere dag, of nee. De planning schuift mee. */
    public function answer(ProjectPlanItem $item, string $answer, ?Carbon $start = null, ?string $message = null): void
    {
        if (! $item->requestPending()) {
            throw new \DomainException(__('Dit verzoek is al beantwoord.'));
        }
        $newStart = match ($answer) {
            'accepted' => $item->request_start,
            'counter' => $start?->startOfDay(),
            'declined' => null,
            default => throw new \DomainException(__('Onbekend antwoord.')),
        };
        if ($answer === 'counter' && ! $newStart) {
            throw new \DomainException(__('Geef de dag op waarop u kunt beginnen.'));
        }

        $item->forceFill([
            'request_answer' => $answer,
            'request_answer_start' => $newStart,
            'request_message' => filled($message) ? trim((string) $message) : null,
            'request_answered_at' => now(),
        ]);
        if ($newStart && $item->starts_on && ! $newStart->isSameDay($item->starts_on)) {
            $days = $item->days();
            $item->forceFill([
                'starts_on' => $newStart,
                'ends_on' => $newStart->copy()->addDays($days - 1),
                'headsup_sent_at' => null,
                'reminder_sent_at' => null,
                // Wie een dag toezegt, heeft daarmee bevestigd.
                'confirmed_at' => now(),
            ]);
        }
        $item->save();
        $this->notice($item, 'answer');
        Audit::log('updated', $item->project, __(':name antwoordt op de planning van :title: :answer', [
            'name' => $item->subcontractor?->name, 'title' => $item->title, 'answer' => __($answer),
        ]), [], $item->project?->company_id);
    }

    /** De onderaannemer bevestigt via de link dat hij op de geplande dag komt. */
    public function confirm(ProjectPlanItem $item): void
    {
        $item->forceFill(['confirmed_at' => now(), 'problem' => null, 'problem_at' => null])->save();
    }

    /** De onderaannemer meldt via de link dat er iets niet klopt; de ondernemer krijgt dat direct. */
    public function problem(ProjectPlanItem $item, string $message): void
    {
        if (blank($message)) {
            throw new \DomainException(__('Schrijf kort wat er in de weg zit.'));
        }
        $item->forceFill(['problem' => trim($message), 'problem_at' => now(), 'confirmed_at' => null])->save();
        $this->notice($item, 'problem');
    }

    private function clearRequest(ProjectPlanItem $item): void
    {
        $item->forceFill([
            'request_start' => null, 'request_sent_at' => null, 'request_answer' => null,
            'request_answer_start' => null, 'request_message' => null, 'request_answered_at' => null,
        ]);
    }

    /* ------------------------------------------------------------- dagelijks */

    /**
     * De dagelijkse ronde: vooraankondiging een week vooraf, herinnering als de
     * week aanbreekt, en op maandag het weekoverzicht voor de ondernemer.
     *
     * @return array{headsup: int, reminders: int, digests: int}
     */
    public function runDaily(?Carbon $today = null): array
    {
        $today = ($today ?? today())->startOfDay();
        $count = ['headsup' => 0, 'reminders' => 0, 'digests' => 0];

        $items = ProjectPlanItem::query()->where('status', 'planned')->whereNotNull('starts_on')
            // whereDate en niet whereBetween: sqlite bewaart de datum met een tijd erachter.
            ->whereDate('starts_on', '>=', $today->toDateString())
            ->whereDate('starts_on', '<=', $today->copy()->addDays(self::HEADSUP_DAYS)->toDateString())
            ->whereNotNull('subcontractor_id')
            ->with(['subcontractor', 'project.company'])
            ->get();

        foreach ($items as $item) {
            $project = $item->project;
            if (! $project || ! $project->isOpen() || ! $item->mailable() || ! $this->allowed($project->company)) {
                continue;
            }
            $weekStart = $item->starts_on->copy()->startOfWeek();
            if (! $item->reminder_sent_at && $today->gte($weekStart)) {
                if ($this->mail($item, 'reminder')) {
                    $item->forceFill(['reminder_sent_at' => now()])->save();
                    $count['reminders']++;
                }
            } elseif (! $item->headsup_sent_at && $today->lt($weekStart) && $today->gte($item->starts_on->copy()->subDays(self::HEADSUP_DAYS))) {
                if ($this->mail($item, 'headsup')) {
                    $item->forceFill(['headsup_sent_at' => now()])->save();
                    $count['headsup']++;
                }
            }
        }

        if ($today->isMonday()) {
            $count['digests'] = $this->digests($today);
        }

        return $count;
    }

    /** Maandag: per administratie één mail met wat deze en volgende week start, en wat aandacht vraagt. */
    private function digests(Carbon $monday): int
    {
        $sent = 0;
        $companyIds = ProjectPlanItem::query()->join('projects', 'projects.id', '=', 'project_plan_items.project_id')
            ->where('projects.status', 'open')->distinct()->pluck('projects.company_id');

        foreach (Company::whereIn('id', $companyIds)->get() as $company) {
            $data = $this->digest($company, $monday);
            if (! $data['has_news'] || ! $this->allowed($company)) {
                continue;
            }
            $to = $company->daily_notification_email ?: $company->email ?: $company->users()->value('email');
            if (! $to) {
                continue;
            }
            try {
                Mail::to($to)->send(new PlanDigestMail($company, $data));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Weekoverzicht planning mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * De inhoud van het weekoverzicht.
     *
     * @return array<string, mixed>
     */
    public function digest(Company $company, Carbon $monday): array
    {
        $monday = $monday->copy()->startOfWeek();
        $nextMonday = $monday->copy()->addWeek();
        $items = ProjectPlanItem::query()->join('projects', 'projects.id', '=', 'project_plan_items.project_id')
            ->where('projects.company_id', $company->id)->where('projects.status', 'open')
            ->where('project_plan_items.status', '!=', 'done')
            ->select('project_plan_items.*')->with(['subcontractor', 'project'])->get();

        $row = fn (ProjectPlanItem $i) => [
            'project' => $i->project?->label(),
            'title' => $i->title,
            'who' => $i->subcontractor?->name,
            'starts' => $i->starts_on?->translatedFormat('D j M'),
            'ends' => $i->ends_on?->translatedFormat('D j M'),
            'confirmed' => $i->confirmed_at !== null,
            'problem' => $i->problem,
            'pending' => $i->requestPending(),
        ];
        $inWeek = fn (Carbon $from) => $items->filter(fn ($i) => $i->starts_on && $i->starts_on->gte($from) && $i->starts_on->lt($from->copy()->addWeek()));

        $data = [
            'week' => $monday->isoWeek,
            'this_week' => $inWeek($monday)->sortBy('starts_on')->map($row)->values()->all(),
            'next_week' => $inWeek($nextMonday)->sortBy('starts_on')->map($row)->values()->all(),
            'unplanned' => $items->whereNull('starts_on')->map($row)->values()->all(),
            'problems' => $items->filter(fn ($i) => $i->problem_at !== null)->map($row)->values()->all(),
            'pending' => $items->filter(fn ($i) => $i->requestPending())->map($row)->values()->all(),
        ];
        $data['has_news'] = collect(['this_week', 'next_week', 'unplanned', 'problems', 'pending'])->contains(fn ($k) => count($data[$k]) > 0);

        return $data;
    }

    /* ------------------------------------------------------------- weergave */

    /**
     * De tijdslijn voor de projectpagina: de onderdelen en de weekkolommen.
     *
     * @return array{items: array<int, array<string, mixed>>, weeks: array<int, array<string, mixed>>, today: string}
     */
    public function timeline(Project $project): array
    {
        $items = $project->planItems()->with(['subcontractor', 'round'])->get();
        $dated = $items->filter(fn ($i) => $i->starts_on);
        $from = collect([$dated->min('starts_on'), $project->starts_on, today()])->filter()->min();
        $to = collect([$dated->max('ends_on'), $dated->max('starts_on'), $project->ends_on, today()->addWeeks(3)])->filter()->max();
        $from = Carbon::parse($from)->startOfWeek();
        $to = Carbon::parse($to)->endOfWeek();
        // Niet eindeloos breed: hooguit een half jaar in beeld.
        if ($from->diffInWeeks($to) > 26) {
            $to = $from->copy()->addWeeks(26)->endOfWeek();
        }

        $weeks = [];
        for ($d = $from->copy(); $d->lte($to); $d->addWeek()) {
            $weeks[] = [
                'key' => $d->toDateString(),
                'number' => $d->isoWeek,
                'label' => $d->translatedFormat('j M'),
                'month' => $d->translatedFormat('M'),
                'current' => $d->isSameWeek(today()),
            ];
        }
        $totalDays = max(1, $from->diffInDays($to) + 1);

        return [
            'items' => $items->map(fn (ProjectPlanItem $i) => $this->row($i, $from, $totalDays))->values()->all(),
            'weeks' => $weeks,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'today' => today()->toDateString(),
            'today_pct' => today()->between($from, $to) ? round($from->diffInDays(today()) / $totalDays * 100, 2) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function row(ProjectPlanItem $i, Carbon $from, int $totalDays): array
    {
        $left = $i->starts_on ? round(max(0, $from->diffInDays($i->starts_on, false)) / $totalDays * 100, 2) : null;
        $width = $i->starts_on ? round(max(1, $i->days()) / $totalDays * 100, 2) : null;

        return [
            'id' => $i->id,
            'title' => $i->title,
            'status' => $i->status,
            'subcontractor_id' => $i->subcontractor_id,
            'subcontractor' => $i->subcontractor?->name,
            'email' => $i->subcontractor?->email,
            'mailable' => $i->mailable(),
            'round_id' => $i->tender_round_id,
            'price' => $i->round?->awardedRequest?->price !== null ? (float) $i->round->awardedRequest->price : null,
            'starts_on' => $i->starts_on?->toDateString(),
            'ends_on' => $i->ends_on?->toDateString(),
            'starts_label' => $i->starts_on?->translatedFormat('D j M'),
            'ends_label' => $i->ends_on?->translatedFormat('D j M'),
            'week' => $i->starts_on?->isoWeek,
            'done_on' => $i->done_on?->toDateString(),
            'done_label' => $i->done_on?->translatedFormat('j M'),
            'notes' => $i->notes,
            'left' => $left,
            'width' => $width !== null ? min($width, max(0, 100 - ($left ?? 0))) : null,
            'headsup_at' => $i->headsup_sent_at?->translatedFormat('j M'),
            'reminder_at' => $i->reminder_sent_at?->translatedFormat('j M'),
            'confirmed_at' => $i->confirmed_at?->translatedFormat('j M'),
            'problem' => $i->problem,
            'problem_at' => $i->problem_at?->translatedFormat('j M'),
            'request' => $i->request_sent_at ? [
                'start' => $i->request_start?->toDateString(),
                'start_label' => $i->request_start?->translatedFormat('D j M'),
                'sent_at' => $i->request_sent_at->translatedFormat('j M'),
                'pending' => $i->requestPending(),
                'answer' => $i->request_answer,
                'answer_start_label' => $i->request_answer_start?->translatedFormat('D j M'),
                'message' => $i->request_message,
                'answered_at' => $i->request_answered_at?->translatedFormat('j M'),
            ] : null,
            'late' => $i->status !== 'done' && $i->ends_on && $i->ends_on->lt(today()),
        ];
    }

    /** Onderaannemers uit de pool voor een eigen onderdeel. */
    public function subcontractorOptions(Company $company): array
    {
        return Subcontractor::withoutGlobalScope('company')->where('company_id', $company->id)->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (Subcontractor $s) => ['id' => $s->id, 'name' => $s->name, 'mailable' => filled($s->email)])
            ->values()->all();
    }

    /* ---------------------------------------------------------------- mail */

    /** Geen post vanuit een demo of een administratie zonder toegang. */
    private function allowed(?Company $company): bool
    {
        return $company && ! $company->is_demo && $company->hasAccess();
    }

    /** Mail aan de onderaannemer, in de taal van de markt; mislukt hij, dan breekt niets. */
    private function mail(ProjectPlanItem $item, string $kind): bool
    {
        $email = $item->subcontractor?->email;
        $company = $item->project?->company;
        if (! $email || ! $this->allowed($company)) {
            return false;
        }
        try {
            DocumentLocale::using(DocumentLocale::default(), fn () => Mail::to($email)->send(new PlanMail($item, $kind)));

            return true;
        } catch (\Throwable $e) {
            Log::error('Planningsmail mislukt', ['item' => $item->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** Bericht aan de ondernemer zelf. */
    private function notice(ProjectPlanItem $item, string $kind): void
    {
        $company = $item->project?->company;
        $to = $company?->daily_notification_email ?: $company?->email ?: $company?->users()->value('email');
        if (! $to || ! $this->allowed($company)) {
            return;
        }
        try {
            Mail::to($to)->send(new PlanNoticeMail($item, $kind));
        } catch (\Throwable $e) {
            Log::error('Planningsbericht mislukt', ['item' => $item->id, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }

    /** @return Collection<int, ProjectPlanItem> */
    public function pendingRequests(Project $project): Collection
    {
        return $project->planItems()->whereNotNull('request_sent_at')->whereNull('request_answered_at')->get();
    }
}
