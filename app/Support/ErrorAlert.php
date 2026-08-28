<?php

namespace App\Support;

use App\Mail\ErrorAlertMail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Foutbewaking zonder externe dienst: elke onverwachte fout in productie gaat
 * per mail naar de eigenaar — één keer per uur per fout (zelfde plek + melding),
 * zodat een storing niet honderd mails oplevert. Verwachte fouten (404, 403,
 * validatie, verlopen sessie, te veel verzoeken) worden overgeslagen.
 */
class ErrorAlert
{
    public const THROTTLE_MINUTES = 60;

    public static function shouldReport(Throwable $e): bool
    {
        if (app()->environment('local', 'testing') && ! config('app.error_alerts_in_tests')) {
            return false;
        }
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }
        foreach ([AuthenticationException::class, TokenMismatchException::class, ModelNotFoundException::class, ThrottleRequestsException::class, \Illuminate\Validation\ValidationException::class] as $ignored) {
            if ($e instanceof $ignored) {
                return false;
            }
        }

        return true;
    }

    /** Stuur (gedoseerd) een alarmmail. Mag zelf nooit een fout veroorzaken. */
    public static function report(Throwable $e): void
    {
        try {
            if (! static::shouldReport($e)) {
                return;
            }

            $key = 'error-alert:' . md5(get_class($e) . '|' . $e->getFile() . '|' . $e->getLine() . '|' . mb_substr($e->getMessage(), 0, 120));
            if (! Cache::add($key, now()->toDateTimeString(), now()->addMinutes(self::THROTTLE_MINUTES))) {
                return; // al gemeld in het afgelopen uur
            }

            $to = OwnerAccess::emails();
            if (! $to) {
                return;
            }

            $request = app()->runningInConsole() ? null : request();
            $context = [
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'user' => $request?->user()?->email,
                'company' => $request?->user()?->company?->name,
                'ip' => $request?->ip(),
                'console' => app()->runningInConsole() ? implode(' ', $_SERVER['argv'] ?? []) : null,
                'version' => config('app.version'),
                'time' => now()->format('d-m-Y H:i:s'),
            ];

            Mail::to($to)->send(new ErrorAlertMail($e, $context));
        } catch (Throwable) {
            // Bewust stil: een falende alarmmail mag de oorspronkelijke fout niet verbergen.
        }
    }
}
