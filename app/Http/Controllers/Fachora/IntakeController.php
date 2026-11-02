<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Services\Ai\StructuredClaude;
use App\Services\Fachora\JobService;
use App\Support\Fachora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * De aanvraag van een klant (/zlecenie/nowe): categorie, plaats, omschrijving,
 * foto's, wanneer, taal, contact. Zonder account; de klant bevestigt zijn
 * e-mailadres via de link in de mail (zoals de online aanmaning).
 */
class IntakeController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function show(Request $request): Response
    {
        $wait = null;
        if ($request->filled('czekaj')) {
            $job = $this->jobs->findByToken((string) $request->query('czekaj'));
            if ($job && $job->isPending()) {
                $wait = [
                    'token' => $job->token,
                    'reference' => $job->reference,
                    'email' => $this->mask($job->customer_email),
                    'mailFailed' => $request->boolean('fout'),
                    'resent' => $request->query('opnieuw') === '1',
                    'resendLimit' => $request->query('opnieuw') === 'limiet',
                ];
            }
        }

        return Inertia::render('Fachora/Intake', [
            'categories' => Fachora::categoryList(),
            'languages' => Fachora::languageList(),
            'preset' => Fachora::hasCategory($request->query('kategoria')) ? $request->query('kategoria') : null,
            'presetCity' => mb_substr((string) $request->query('miasto', ''), 0, 120),
            'wait' => $wait,
            'maxPhotos' => 5,
            'assistant' => app(StructuredClaude::class)->enabled(),
        ]);
    }

    /**
     * AI-hulp bij de omschrijving (1.93), zoals de klant het in de incassomodule
     * kent: hij schrijft in zijn eigen taal, Claude maakt er een complete en
     * zakelijke omschrijving van in diezelfde taal, een Poolse versie voor het
     * bedrijf, een korte titel en hooguit drie vragen over wat nog ontbreekt.
     * Niets wordt opgeslagen; de klant kiest zelf of hij het voorstel overneemt.
     * Zonder ANTHROPIC-sleutel bestaat de knop niet (404).
     */
    public function assist(Request $request, StructuredClaude $ai): JsonResponse
    {
        abort_unless($ai->enabled(), 404);
        $data = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'title' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'string', 'max:5'],
        ]);
        $category = Fachora::hasCategory((string) ($data['category'] ?? '')) ? Fachora::categoryName($data['category'], 'pl') : 'nieznana';
        $city = trim((string) ($data['city'] ?? '')) ?: 'nieznane';

        $schema = [
            'type' => 'object',
            'properties' => [
                'language' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'title_pl' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'description_pl' => ['type' => 'string'],
                'questions' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['language', 'title', 'title_pl', 'description', 'description_pl', 'questions'],
            'additionalProperties' => false,
        ];
        $prompt = "You are the assistant of Fachora, a Polish platform where customers (often foreigners living in Poland) describe a job for a tradesperson.\n"
            . "Category (Polish): {$category}\nTown: {$city}\n"
            . "The customer's draft, in any language:\n\"\"\"\n{$data['description']}\n\"\"\"\n"
            . (filled($data['title'] ?? null) ? "Their title: {$data['title']}\n" : '')
            . "\nReturn JSON:\n"
            . "- language: the ISO 639-1 code of the language the draft is written in.\n"
            . "- description: the draft rewritten in that SAME language as a complete, concrete, friendly description for a tradesperson: what is wrong or wanted, where exactly, since when, what has been tried, access and timing if mentioned. 2 to 6 sentences. Keep every fact; never invent details, measurements or brands; leave out names, phone numbers and e-mail addresses.\n"
            . "- description_pl: the same description in natural Polish for the tradesperson (if the draft is already Polish, the polished Polish text).\n"
            . "- title and title_pl: a short title (max 8 words) in the draft's language and in Polish.\n"
            . "- questions: at most 3 short questions in the draft's language about details that are missing but matter for a price (for example gas or electric, which floor, dimensions, materials already bought, when the fault started). An empty list if nothing important is missing.";

        try {
            $result = $ai->json($prompt, $schema, 1200, 'low', [], 'Fachora asystent');
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json([
            'language' => mb_substr((string) ($result['language'] ?? ''), 0, 5),
            'title' => mb_substr(trim((string) ($result['title'] ?? '')), 0, 160),
            'title_pl' => mb_substr(trim((string) ($result['title_pl'] ?? '')), 0, 160),
            'description' => mb_substr(trim((string) ($result['description'] ?? '')), 0, 3000),
            'description_pl' => mb_substr(trim((string) ($result['description_pl'] ?? '')), 0, 3000),
            'questions' => array_slice(array_values(array_filter((array) ($result['questions'] ?? []), fn ($q) => is_string($q) && trim($q) !== '')), 0, 3),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Lokveld: een mens vult het niet in.
        if ($request->filled('website')) {
            return redirect()->route('fachora.intake');
        }

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Fachora::categories()))],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'urgency' => ['required', Rule::in((array) config('fachora.urgencies'))],
            'wanted_date' => ['nullable', 'date', 'after_or_equal:today'],
            'time_window' => ['nullable', 'string', 'max:40'],
            'language' => ['required', Rule::in(array_keys((array) config('fachora.languages')))],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email:rfc', 'max:180'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'postal_code' => ['nullable', 'regex:/^\d{2}-\d{3}$/'],
            'city' => ['required', 'string', 'max:120'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'image', 'max:8192'],
        ], [
            'description.min' => __('Beschrijf de klus in minstens een paar woorden.'),
            'postal_code.regex' => __('Een Poolse postcode ziet eruit als 00-000.'),
            'photos.*.image' => __('Alleen foto\'s (jpg, png, webp).'),
        ]);

        if (! $this->jobs->mayCreate($request)) {
            return back()->withInput()->withErrors(['limiet' => __('Er zijn vanaf deze verbinding te veel aanvragen gedaan. Probeer het over een uur opnieuw, of mail naar :email.', ['email' => brand('email')])]);
        }

        $job = $this->jobs->create($data, array_values((array) $request->file('photos', [])), $request);
        PageView::milestone($request, 'fachora_job');

        return redirect()->route('fachora.intake', array_filter(['czekaj' => $job->token, 'fout' => $job->confirm_mails > 0 ? null : 1]));
    }

    public function resend(Request $request): RedirectResponse
    {
        $job = $this->jobs->findByToken((string) $request->input('token'));
        if (! $job || ! $job->isPending()) {
            return redirect()->route('fachora.intake');
        }
        $result = $this->jobs->resendConfirm($job, $request);

        return redirect()->route('fachora.intake', ['czekaj' => $job->token] + match ($result) {
            'sent' => ['opnieuw' => 1],
            'limit' => ['opnieuw' => 'limiet'],
            default => ['fout' => 1],
        });
    }

    private function mask(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($user, 0, 2) . str_repeat('•', max(1, mb_strlen($user) - 2)) . '@' . $domain;
    }
}
