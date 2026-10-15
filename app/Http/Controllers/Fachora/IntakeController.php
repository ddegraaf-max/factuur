<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Services\Fachora\JobService;
use App\Support\Fachora;
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
