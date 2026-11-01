<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Services\DemoAccess;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Eigenaar: demo-aanvragen van dit merk goedkeuren, afwijzen of iemand direct uitnodigen. */
class OwnerDemoController extends Controller
{
    public function index(): Response
    {
        $stamp = fn ($d) => $d?->translatedFormat('j M Y H:i');
        $requests = DemoRequest::forBrand(Brand::key())->orderByDesc('id')->limit(300)->get()->map(fn (DemoRequest $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'email' => $r->email,
            'company' => $r->company,
            'message' => $r->message,
            'locale' => $r->locale,
            'status' => $r->status,
            'usable' => $r->isUsable(),
            'created_label' => $stamp($r->created_at),
            'expires_label' => $r->expires_at?->translatedFormat('j M Y'),
            'starts' => $r->starts,
            'last_started_label' => $stamp($r->last_started_at),
            'link' => $r->isApproved() ? route('demo.landing', $r->token) : null,
        ])->values();

        return Inertia::render('Stats/DemoAanvragen', [
            'requests' => $requests,
            'brand' => Brand::name(),
            'validDays' => DemoRequest::VALID_DAYS,
        ]);
    }

    public function approve(DemoRequest $demoRequest, DemoAccess $access): RedirectResponse
    {
        $this->own($demoRequest);
        $mailed = $access->approve($demoRequest);

        return back()->with($mailed ? 'flash' : 'error', $mailed
            ? "Goedgekeurd: {$demoRequest->email} heeft de link per mail gekregen."
            : "Goedgekeurd, maar de mail aan {$demoRequest->email} kon niet worden verstuurd. Kopieer de link en stuur hem zelf.");
    }

    public function deny(DemoRequest $demoRequest, DemoAccess $access): RedirectResponse
    {
        $this->own($demoRequest);
        $access->deny($demoRequest);

        return back()->with('flash', "Afgewezen: {$demoRequest->email} krijgt geen link.");
    }

    public function invite(Request $request, DemoAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'string', 'in:nl,en,pl'],
        ]);

        $access->invite($data['email'], $data['name'] ?? null, $data['locale'] ?? null);

        return back()->with('flash', "Uitnodiging verstuurd naar {$data['email']}.");
    }

    public function destroy(DemoRequest $demoRequest): RedirectResponse
    {
        $this->own($demoRequest);
        $demoRequest->delete();

        return back()->with('flash', 'Aanvraag verwijderd.');
    }

    /** Alleen aanvragen van het merk waar je nu in zit (de links horen bij dat domein). */
    private function own(DemoRequest $demoRequest): void
    {
        abort_unless($demoRequest->brand === Brand::key(), 404);
    }
}
