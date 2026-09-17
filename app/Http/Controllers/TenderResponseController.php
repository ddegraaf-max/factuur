<?php

namespace App\Http\Controllers;

use App\Models\TenderRequest;
use App\Services\TenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Het reactieformulier voor de onderaannemer: bereikbaar via de geheime
 * tokenlink uit de mail, zonder inlog. Prijs, beschikbaarheid, geldigheid,
 * opmerkingen en een eigen offerte-PDF — of afzeggen.
 */
class TenderResponseController extends Controller
{
    public function __construct(private TenderService $service) {}

    public function show(string $token): Response
    {
        $request = $this->find($token);
        if (! $request) {
            return Inertia::render('Tenders/Respond', ['valid' => false]);
        }

        if (! $request->opened_at) {
            $request->forceFill(['opened_at' => now()])->save();
        }

        $round = $request->round;
        $company = $round->company;

        return Inertia::render('Tenders/Respond', [
            'valid' => true,
            'token' => $token,
            'company' => [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'color' => $company->brand_color,
            ],
            'round' => [
                'title' => $round->title,
                'description' => $round->description,
                'location' => $round->location,
                'start_week' => $round->start_week,
                'deadline_label' => $round->deadline->translatedFormat('j F Y'),
                'open' => $round->isOpen(),
            ],
            'request' => [
                'status' => $request->status,
                'name' => $request->subcontractor?->name,
                'contact_name' => $request->subcontractor?->contact_name,
                'price' => $request->price !== null ? (float) $request->price : null,
                'available_week' => $request->available_week,
                'valid_until' => $request->valid_until?->toDateString(),
                'remarks' => $request->remarks,
                'decline_reason' => $request->decline_reason,
                'attachment_name' => $request->attachment_name,
                'responded_at_label' => $request->responded_at?->translatedFormat('j F Y, H:i'),
            ],
        ]);
    }

    public function respond(Request $http, string $token): RedirectResponse
    {
        $request = $this->find($token) ?? abort(404);

        // Een onderaannemer typt '4.250,50' of '4250,50': komma als decimaalteken, punten als duizendtallen.
        $price = trim((string) $http->input('price'));
        if (str_contains($price, ',')) {
            $price = str_replace(',', '.', str_replace('.', '', $price));
        }
        $http->merge(['price' => $price]);

        $data = $http->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'available_week' => ['nullable', 'string', 'max:12'],
            'valid_until' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg'],
        ], [
            'price.required' => __('Vul uw prijs in (exclusief btw).'),
            'price.numeric' => __('De prijs moet een getal zijn (gebruik een punt of komma als decimaalteken).'),
            'attachment.mimetypes' => __('Alleen PDF-, PNG- of JPG-bestanden zijn toegestaan.'),
            'attachment.max' => __('Het bestand mag maximaal 10 MB groot zijn.'),
        ]);

        try {
            $this->service->respond($request, $data, $http->file('attachment'));
        } catch (\DomainException $e) {
            return back()->withErrors(['tender' => $e->getMessage()]);
        }

        return back()->with('flash', __('Bedankt, uw prijs is ontvangen.'));
    }

    public function decline(Request $http, string $token): RedirectResponse
    {
        $request = $this->find($token) ?? abort(404);
        $data = $http->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->service->decline($request, $data['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['tender' => $e->getMessage()]);
        }

        return back()->with('flash', __('Bedankt voor uw bericht.'));
    }

    private function find(string $token): ?TenderRequest
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        return TenderRequest::with(['round.company', 'round.workPackage', 'subcontractor'])
            ->where('token', $token)
            ->first();
    }
}
