<?php

namespace App\Http\Controllers;

use App\Models\ProjectPlanItem;
use App\Services\ProjectPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * De planningspagina voor de onderaannemer: bereikbaar via de geheime
 * tokenlink uit de mail, zonder inlog. Hij bevestigt de geplande start, meldt
 * dat er iets niet klopt, of antwoordt op de vraag of hij eerder kan beginnen.
 */
class PlanResponseController extends Controller
{
    public function __construct(private ProjectPlanService $service) {}

    public function show(string $token): Response
    {
        $item = $this->find($token);
        if (! $item || ! $item->project) {
            return Inertia::render('Projects/PlanRespond', ['valid' => false]);
        }
        $project = $item->project;
        $company = $project->company;

        return Inertia::render('Projects/PlanRespond', [
            'valid' => true,
            'token' => $token,
            'company' => [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'color' => $company->brand_color,
            ],
            'item' => [
                'title' => $item->title,
                'project' => $project->name,
                'location' => $project->location ?: $item->round?->location,
                'name' => $item->subcontractor?->contact_name ?: $item->subcontractor?->name,
                'status' => $item->status,
                'starts_on' => $item->starts_on?->toDateString(),
                'starts_label' => $item->starts_on?->translatedFormat('l j F Y'),
                'ends_label' => $item->ends_on?->translatedFormat('l j F Y'),
                'notes' => $item->notes,
                'confirmed_at' => $item->confirmed_at?->translatedFormat('j F Y'),
                'problem' => $item->problem,
                'project_open' => $project->isOpen(),
                'request' => $item->request_sent_at ? [
                    'pending' => $item->requestPending(),
                    'start' => $item->request_start?->toDateString(),
                    'start_label' => $item->request_start?->translatedFormat('l j F Y'),
                    'message' => $item->request_answer === null ? $item->request_message : null,
                    'answer' => $item->request_answer,
                    'answer_start_label' => $item->request_answer_start?->translatedFormat('l j F Y'),
                ] : null,
            ],
        ]);
    }

    public function respond(Request $http, string $token): RedirectResponse
    {
        $item = $this->find($token) ?? abort(404);
        $data = $http->validate([
            'action' => ['required', 'in:confirm,problem,accepted,counter,declined'],
            'start' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            if (! $item->project?->isOpen()) {
                throw new \DomainException(__('Dit project is afgerond; reageren is niet meer nodig.'));
            }
            match ($data['action']) {
                'confirm' => $this->service->confirm($item),
                'problem' => $this->service->problem($item, (string) ($data['message'] ?? '')),
                default => $this->service->answer(
                    $item,
                    $data['action'],
                    filled($data['start'] ?? null) ? Carbon::parse($data['start']) : null,
                    $data['message'] ?? null,
                ),
            };
        } catch (\DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back()->with('flash', match ($data['action']) {
            'confirm' => __('Bedankt, uw bevestiging is ontvangen.'),
            'problem' => __('Bedankt voor uw bericht; wij nemen contact met u op.'),
            default => __('Bedankt, uw antwoord is doorgegeven.'),
        });
    }

    private function find(string $token): ?ProjectPlanItem
    {
        if (strlen($token) < 20) {
            return null;
        }

        return ProjectPlanItem::where('token', $token)->with(['project.company', 'subcontractor', 'round'])->first();
    }
}
