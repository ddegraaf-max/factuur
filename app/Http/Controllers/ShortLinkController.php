<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Het korte adres uit een sms (/u/{code}) leidt door naar het lange adres.
 * Zonder inlog; wie codes probeert te raden, loopt tegen een grens aan.
 */
class ShortLinkController extends Controller
{
    private const PER_MINUTE = 30;

    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $limit = 'short-link:' . $request->ip();
        abort_if(RateLimiter::tooManyAttempts($limit, self::PER_MINUTE), 429);
        RateLimiter::hit($limit, 60);

        $link = ShortLink::isCode($code) ? ShortLink::where('code', $code)->first() : null;
        abort_unless($link, 404);

        $link->forceFill(['hits' => $link->hits + 1, 'last_hit_at' => now()])->save();

        return redirect()->away($link->url)->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
