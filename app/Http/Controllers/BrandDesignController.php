<?php

namespace App\Http\Controllers;

use App\Models\AiUsageEvent;
use App\Models\User;
use App\Services\BrandDesignService;
use Illuminate\Http\Request;

/** Huisstijl ontwerpen met AI: drie voorstellen uit een paar antwoorden; de gebruiker kiest en slaat zelf op. */
class BrandDesignController extends Controller
{
    public function propose(Request $request, BrandDesignService $designer)
    {
        abort_unless($designer->enabled(), 404);

        /** @var User $user */
        $user = $request->user();
        $company = $user->company;
        if (! $company->hasAiAccess()) {
            return response()->json(['message' => __('Huisstijl ontwerpen met AI zit in het Slim-abonnement. Upgrade via Instellingen → Abonnement.')], 403);
        }
        if ($company->aiLimitReached()) {
            return response()->json(['message' => __('Het maandelijkse AI-tegoed is opgebruikt (fair use). Volgende maand staat de teller weer op nul.')], 429);
        }

        $answers = $request->validate([
            'sector' => ['required', 'string', 'min:3', 'max:200'],
            'audience' => ['nullable', 'string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:120'],
            'colors' => ['nullable', 'string', 'max:120'],
        ], ['sector.required' => __('Vertel kort wat je bedrijf doet.'), 'sector.min' => __('Vertel iets meer over wat je bedrijf doet.')]);

        try {
            $directions = $designer->propose($company, $answers);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AiUsageEvent::record($company->id, 'brand_design', 'form');

        return response()->json(['directions' => $directions]);
    }
}
