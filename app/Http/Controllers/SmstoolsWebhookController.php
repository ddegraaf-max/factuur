<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Webhook van Smstools (type delivery_report): per verstuurde sms komt er een
 * bericht of hij is afgeleverd. Wij zoeken het bericht op het messageid en
 * bewaren de status. Handtekening: X-Smstools-Signature "t=…,v1=…" is een
 * sha256-hmac van "timestamp.body" met de secret van de webhook; staat die
 * secret niet in de omgeving, dan accepteren we alleen meldingen over een
 * messageid dat wij zelf kennen.
 */
class SmstoolsWebhookController extends Controller
{
    /** Codes van Smstools → onze status. 1 = afgeleverd; 2 en 4 = niet afgeleverd; 0 en 3 = onderweg; 9 = onbekend. */
    private const STATUS = [0 => 'pending', 1 => 'delivered', 2 => 'failed', 3 => 'pending', 4 => 'failed', 5 => 'failed', 9 => 'unknown'];

    public function handle(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $secret = (string) config('services.smstools.webhook_secret');
        if ($secret !== '' && ! $this->validSignature($request, $raw, $secret)) {
            Log::warning('Smstools-webhook: ongeldige handtekening');

            return response()->json(['ok' => false], 401);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response()->json(['ok' => false], 400);
        }
        if (($payload['webhook_type'] ?? 'delivery_report') !== 'delivery_report') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $data = is_array($payload['message'] ?? null) ? $payload['message'] : $payload;
        $providerId = trim((string) ($data['messageid'] ?? $data['message_id'] ?? ''));
        if ($providerId === '') {
            return response()->json(['ok' => false], 400);
        }
        $message = SmsMessage::where('provider_id', $providerId)->latest('id')->first();
        if (! $message) {
            // Zonder secret is een bekend messageid onze enige controle; onbekend = negeren.
            return response()->json(['ok' => true, 'unknown' => true]);
        }

        $code = is_numeric($data['delivery_code'] ?? null) ? (int) $data['delivery_code'] : null;
        $status = self::STATUS[$code] ?? (match (strtolower((string) ($data['delivery_status'] ?? ''))) {
            'delivered' => 'delivered',
            'not delivered', 'rejected', 'failed', 'expired' => 'failed',
            'submitted', 'buffered' => 'pending',
            default => 'unknown',
        });
        $detail = trim((string) ($data['delivery_code_detail'] ?? $data['delivery_status'] ?? ''));
        $when = null;
        try {
            $when = filled($data['delivery_status_datetime'] ?? null) ? Carbon::parse((string) $data['delivery_status_datetime']) : now();
        } catch (\Throwable) {
            $when = now();
        }

        // Een latere melding mag een vroegere overschrijven ('submitted' → 'delivered'), maar niet andersom.
        if ($message->delivery_status === 'delivered' && $status !== 'delivered') {
            return response()->json(['ok' => true, 'kept' => true]);
        }
        $message->forceFill([
            'delivery_status' => $status,
            'delivery_code' => $code,
            'delivery_detail' => mb_substr($detail, 0, 200) ?: null,
            'delivered_at' => $status === 'delivered' ? $when : $message->delivered_at,
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function validSignature(Request $request, string $raw, string $secret): bool
    {
        $header = (string) $request->header('X-Smstools-Signature', '');
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$k] = $v;
        }
        $timestamp = $parts['t'] ?? (string) $request->header('X-Smstools-Timestamp', '');
        $given = $parts['v1'] ?? '';
        if ($timestamp === '' || $given === '') {
            return false;
        }
        // Niet ouder dan een kwartier, tegen herhaald afspelen.
        if (abs(time() - (int) $timestamp) > 900) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $raw, $secret);

        return hash_equals($expected, $given);
    }
}
