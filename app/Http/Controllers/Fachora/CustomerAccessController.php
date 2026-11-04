<?php

namespace App\Http\Controllers\Fachora;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use App\Services\Fachora\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * 1.99: "Moje zlecenia" — de klant logt in zonder wachtwoord: e-mailadres, code per
 * mail (6 cijfers, 15 minuten geldig, 5 pogingen), daarna de lijst van zijn eigen
 * aanvragen met de links naar de opdrachtpagina's. Geen account, geen wachtwoord;
 * de sessie onthoudt het bevestigde adres (SESSION_LIFETIME). Of een adres aanvragen
 * heeft, verraden we niet: de melding na "Wyślij kod" is altijd dezelfde.
 */
class CustomerAccessController extends Controller
{
    public const SESSION_EMAIL = 'fachora_my_email';

    public const SESSION_PENDING = 'fachora_my_pending';

    private const CODE_TTL_MINUTES = 15;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private Notifier $notifier) {}

    public function show(Request $request): View
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        return view('fachora.moje-zlecenia', [
            'email' => $email,
            'pending' => $email ? null : $request->session()->get(self::SESSION_PENDING),
            'jobs' => $email ? $this->jobsFor($email) : collect(),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:160']]);
        $email = self::normalise($data['email']);
        $job = $this->jobsFor($email)->first();
        if ($job) {
            $code = (string) random_int(100000, 999999);
            Cache::put(self::cacheKey($email), ['code' => $code, 'attempts' => 0], now()->addMinutes(self::CODE_TTL_MINUTES));
            $this->notifier->customer($job, 'login_code', ['code' => $code]);
        }
        $request->session()->put(self::SESSION_PENDING, $email);

        return redirect()->route('fachora.my')->with('my_sent', true);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:160'], 'code' => ['required', 'digits:6']]);
        $email = self::normalise($data['email']);
        $key = self::cacheKey($email);
        $entry = Cache::get($key);
        if (! is_array($entry)) {
            return back()->withErrors(['code' => __('De code klopt niet of is verlopen. Vraag een nieuwe aan.')]);
        }
        if ((int) $entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return back()->withErrors(['code' => __('Te veel pogingen. Vraag een nieuwe code aan.')]);
        }
        if (! hash_equals((string) $entry['code'], $data['code'])) {
            Cache::put($key, ['code' => $entry['code'], 'attempts' => (int) $entry['attempts'] + 1], now()->addMinutes(self::CODE_TTL_MINUTES));

            return back()->withErrors(['code' => __('De code klopt niet of is verlopen. Vraag een nieuwe aan.')]);
        }
        Cache::forget($key);
        $request->session()->forget(self::SESSION_PENDING);
        $request->session()->put(self::SESSION_EMAIL, $email);
        $request->session()->regenerate();

        return redirect()->route('fachora.my');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_PENDING]);

        return redirect()->route('fachora.my');
    }

    /** Alle echte aanvragen van dit adres, nieuwste eerst (demo-aanvragen bestaan niet voor de klant). */
    private function jobsFor(string $email): \Illuminate\Support\Collection
    {
        return ServiceJob::query()->whereRaw('lower(customer_email) = ?', [$email])->orderByDesc('id')->limit(50)->get()
            ->reject(fn (ServiceJob $j) => $j->isDemo())->values();
    }

    public static function normalise(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function cacheKey(string $email): string
    {
        return 'fachora.my.' . sha1($email);
    }
}
