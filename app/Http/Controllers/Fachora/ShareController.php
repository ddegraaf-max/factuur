<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\FachoraFile;
use App\Services\Fachora\JobService;
use App\Services\Fachora\Presenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 1.98: de alleen-lezen opdrachtpagina voor een registerbedrijf (/z/{share}/{id}-{code}).
 * De klant zet de link zelf in zijn bericht; Fachora stuurt het bedrijf niets. Het bedrijf
 * ziet de klus, leest waarom het die mail kreeg (en dat het geen spam is) en antwoordt met
 * één klik; de klant krijgt daar een mail van en ziet het op zijn opdrachtpagina.
 */
class ShareController extends Controller
{
    public function __construct(private JobService $jobs, private Presenter $presenter) {}

    public function show(string $share, string $firm): View
    {
        $found = $this->jobs->findShared($share, $firm) ?? abort(404);
        ['job' => $job, 'provider' => $provider, 'active' => $active] = $found;
        if ($active) {
            $this->jobs->recordShareOpened($job, $provider);
        }
        // Afmeldlink voor een registerbedrijf (zelfde pagina als onder een aanvraagmail): token zo nodig aanmaken.
        if ($provider->claimed_at === null && blank($provider->optout_token)) {
            $provider->forceFill(['optout_token' => bin2hex(random_bytes(32))])->save();
        }
        $response = $this->jobs->directResponse($job, $provider->id);
        $photos = array_values(array_filter(
            $this->presenter->files($job, fn (FachoraFile $f) => route('fachora.share.file', [$share, $firm, $f->id])),
            fn (array $f) => ! empty($f['image']),
        ));

        return view('fachora.zlecenie-dla-firmy', [
            'job' => $job,
            'view' => $this->presenter->job($job, 'provider'),
            'provider' => $provider,
            'active' => $active,
            'answered' => $response['kind'] ?? null,
            'response' => $response,
            'firstName' => Str::before(trim((string) $job->customer_name), ' ') ?: __('de klant'),
            'photos' => $photos,
            'optoutUrl' => $provider->claimed_at === null ? $provider->optoutUrl() : null,
        ]);
    }

    public function respond(Request $request, string $share, string $firm): RedirectResponse
    {
        $found = $this->jobs->findShared($share, $firm) ?? abort(404);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['interested', 'declined'])],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['kind'] === 'interested' && blank($data['phone'] ?? null) && blank($data['email'] ?? null)) {
            return back()->withErrors(['phone' => __('Geef een telefoonnummer of e-mailadres op, zodat de klant contact kan opnemen.')])->withInput();
        }
        try {
            $this->jobs->respondDirect($found['job'], $found['provider'], $data);
        } catch (\DomainException $e) {
            return back()->withErrors(['kind' => $e->getMessage()]);
        }

        return redirect()->route('fachora.share.show', [$share, $firm])->with('share_done', $data['kind']);
    }

    /** Foto's bij de klus, alleen afbeeldingen en alleen via een geldige deel-link. */
    public function file(string $share, string $firm, int $file): HttpResponse
    {
        $found = $this->jobs->findShared($share, $firm) ?? abort(404);
        $f = FachoraFile::query()->where('owner_type', 'job')->where('owner_id', $found['job']->id)->findOrFail($file);
        abort_unless($f->isImage(), 404);
        $data = $f->contents();
        abort_if($data === null, 404);

        return response($data, 200, ['Content-Type' => $f->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($f->filename) . '"', 'X-Robots-Tag' => 'noindex']);
    }
}
