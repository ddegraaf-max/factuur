<?php

namespace App\Http\Controllers;

use App\Mail\SiteLeadMail;
use App\Models\AiUsageEvent;
use App\Models\Company;
use App\Models\CompanySite;
use App\Models\SiteLead;
use App\Models\User;
use App\Services\SiteGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/**
 * Website per administratie: inhoud door AI gegenereerd, in de editor bewerkt
 * en gepubliceerd onder /s/{slug} in de eigen huisstijl, met contactformulier
 * waarvan de berichten als leads binnenkomen.
 */
class SiteController extends Controller
{
    public function edit(Request $request, SiteGeneratorService $generator)
    {
        /** @var User $user */
        $user = $request->user();
        $company = $user->company;
        $slug = $company->ensurePublicSlug();
        $site = $company->site;

        return Inertia::render('Settings/Website', [
            'site' => [
                'published' => (bool) $site?->published,
                'content' => $site?->content ?: SiteGeneratorService::blank(),
                'answers' => $site?->answers ?: [],
                'generated_at' => $site?->generated_at?->translatedFormat('j M Y, H:i'),
            ],
            'slug' => $slug,
            'public_url' => route('site.show', $slug),
            'ai_enabled' => $generator->enabled() && $company->hasAiAccess(),
            'ai_locked' => $generator->enabled() && ! $company->hasAiAccess(),
            'allowed' => $company->publicPagesAllowed(),
            'company' => ['name' => $company->publicName(), 'email' => $company->email, 'phone' => $company->phone, 'city' => $company->city, 'brand_color' => $company->brand_color ?: '#E8231F', 'accent_color' => $company->accent_color ?: '#1C1917', 'logo' => $company->logo_data],
            'leads' => SiteLead::where('company_id', $company->id)->latest()->limit(30)->get()->map(fn (SiteLead $l) => [
                'id' => $l->id, 'name' => $l->name, 'email' => $l->email, 'phone' => $l->phone, 'message' => $l->message,
                'received_label' => $l->created_at?->translatedFormat('j M Y, H:i'),
            ])->values(),
        ]);
    }

    /** Inhoud laten schrijven door AI; wordt als concept bewaard, de gebruiker publiceert zelf. */
    public function generate(Request $request, SiteGeneratorService $generator)
    {
        abort_unless($generator->enabled(), 404);
        /** @var User $user */
        $user = $request->user();
        $company = $user->company;
        if (! $company->hasAiAccess()) {
            return response()->json(['message' => __('Website maken met AI zit in het Slim-abonnement. Upgrade via Instellingen → Abonnement.')], 403);
        }
        if ($company->aiLimitReached()) {
            return response()->json(['message' => __('Het maandelijkse AI-tegoed is opgebruikt (fair use). Volgende maand staat de teller weer op nul.')], 429);
        }
        $answers = $request->validate([
            'what' => ['required', 'string', 'min:3', 'max:300'],
            'audience' => ['nullable', 'string', 'max:200'],
            'why' => ['nullable', 'string', 'max:300'],
            'tone' => ['nullable', 'string', 'max:60'],
        ], ['what.required' => __('Vertel kort wat je bedrijf doet.')]);

        try {
            $content = $generator->generate($company, $answers);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        CompanySite::updateOrCreate(['company_id' => $company->id], ['content' => $content, 'answers' => $answers, 'generated_at' => now()]);
        AiUsageEvent::record($company->id, 'site_generate', 'form');

        return response()->json(['content' => $content]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $company = $user->company;
        $data = $request->validate(['published' => ['boolean'], 'content' => ['required', 'array']]);
        $content = SiteGeneratorService::sanitize($data['content']);
        $published = ! empty($data['published']);
        if ($published && $content['hero']['title'] === '') {
            return back()->withErrors(['content' => __('Geef je website minimaal een kop (hero) voordat je hem online zet.')]);
        }

        $company->ensurePublicSlug();
        CompanySite::updateOrCreate(['company_id' => $company->id], ['content' => $content, 'published' => $published]);

        return back()->with('flash', $published ? __('Website opgeslagen en online.') : __('Website opgeslagen (nog niet online).'));
    }

    /* ===================== Publiek ===================== */

    public function show(string $slug)
    {
        [$company, $site] = $this->resolve($slug);

        return response()->view('public.site', [
            'company' => $company, 'site' => $site, 'content' => $site->content,
            'card_url' => $company->businessCard?->published ? route('card.show', $slug) : null,
        ]);
    }

    public function lead(Request $request, string $slug)
    {
        [$company] = $this->resolve($slug);
        if ($request->filled('website_url')) { // honeypot voor bots
            return redirect()->to(route('site.show', $slug) . '#contact');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $lead = SiteLead::create($data + ['company_id' => $company->id]);
        if ($company->email) {
            try {
                Mail::to($company->email)->send(new SiteLeadMail($company, $lead));
            } catch (\Throwable $e) {
                Log::warning('Website-lead mailen mislukt', ['company' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->to(route('site.show', $slug) . '#contact')->with('site_success', __('Bedankt! Je bericht is verstuurd — je hoort snel van ons.'));
    }

    /** @return array{0: Company, 1: CompanySite} */
    private function resolve(string $slug): array
    {
        $company = Company::where('public_slug', $slug)->first();
        $site = $company?->site;
        abort_unless($company && $site && $site->published && $site->hasContent() && $company->publicPagesAllowed(), 404);

        return [$company, $site];
    }
}
