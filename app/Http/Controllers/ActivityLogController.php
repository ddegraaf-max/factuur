<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Instellingen → Logboek: wie deed wat, wanneer. */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request)->orderByDesc('id');

        $logs = $query->paginate(50)->withQueryString()->through(fn (ActivityLog $l) => [
            'id' => $l->id,
            'when' => $l->created_at->translatedFormat('j M Y · H:i'),
            'user' => $l->user_name ?: 'Systeem',
            'action' => $l->action,
            'action_label' => $l->action_label,
            'type' => $l->subject_type,
            'description' => $l->description,
            'changes' => $l->changes,
            'ip' => $l->ip,
        ]);

        $users = ActivityLog::query()->select('user_name')->whereNotNull('user_name')->distinct()->orderBy('user_name')->pluck('user_name');

        return Inertia::render('Settings/Logboek', [
            'logs' => $logs,
            'filters' => $request->only(['q', 'user', 'type', 'action', 'from', 'to']),
            'users' => $users,
            'types' => array_values(array_unique(array_values(Audit::TYPES))),
            'actions' => ActivityLog::ACTION_LABELS,
        ]);
    }

    /** CSV voor de accountant of een geschil: alles binnen de gekozen filters. */
    public function export(Request $request)
    {
        $rows = ["datum;tijd;gebruiker;actie;onderwerp;omschrijving;wijzigingen;ip"];
        $this->filtered($request)->orderBy('id')->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $l) {
                $clean = fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"';
                $changes = $l->changes ? implode(' | ', array_map(fn ($k, $c) => "{$k}: " . ($c['van'] ?? '') . ' → ' . ($c['naar'] ?? ''), array_keys($l->changes), $l->changes)) : '';
                $rows[] = implode(';', [
                    $l->created_at->format('Y-m-d'), $l->created_at->format('H:i:s'), $clean($l->user_name ?: 'Systeem'),
                    $clean($l->action_label), $clean($l->subject_type), $clean($l->description), $clean($changes), $l->ip,
                ]);
            }
        });

        Audit::log('exported', null, 'Logboek geëxporteerd als CSV');

        return response(implode("\n", $rows) . "\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="logboek-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    private function filtered(Request $request)
    {
        return ActivityLog::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'ilike', '%' . $request->q . '%')
                ->orWhere('subject_label', 'ilike', '%' . $request->q . '%')))
            ->when($request->filled('user'), fn ($q) => $q->where('user_name', $request->user))
            ->when($request->filled('type'), fn ($q) => $q->where('subject_type', $request->type))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
    }
}
