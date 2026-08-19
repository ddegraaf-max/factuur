<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PurchaseInboxItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook voor binnenkomende e-mail (Postmark inbound-formaat): bijlagen uit
 * mails aan bon-<token>@<inboekdomein> belanden in het Postvak IN van de
 * bijbehorende administratie.
 *
 * Beveiliging: het geheime deel van de URL (INBOUND_MAIL_SECRET) + het unieke
 * token per administratie in het adres. Alles wat niet klopt wordt stil
 * genegeerd met een 200 (mailproviders blijven anders eindeloos herhalen);
 * alleen echte fouten worden gelogd.
 */
class InboundMailController extends Controller
{
    private const ALLOWED_MIMES = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
    private const MAX_ATTACHMENTS = 10;
    private const MAX_BYTES = 10 * 1024 * 1024;

    public function handle(Request $request, string $secret): Response
    {
        $expected = (string) config('services.inbound.secret');
        if ($expected === '' || ! hash_equals($expected, $secret)) {
            abort(404);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            return response('OK');
        }

        $company = $this->resolveCompany($payload);
        if (! $company) {
            Log::info('Inbound mail: geen administratie gevonden voor ontvanger', [
                'to' => $payload['To'] ?? null,
            ]);

            return response('OK');
        }

        $from = mb_substr((string) ($payload['FromFull']['Email'] ?? $payload['From'] ?? ''), 0, 180);
        $subject = mb_substr((string) ($payload['Subject'] ?? ''), 0, 255);

        $stored = 0;
        foreach (array_slice((array) ($payload['Attachments'] ?? []), 0, self::MAX_ATTACHMENTS) as $attachment) {
            $mime = strtolower((string) ($attachment['ContentType'] ?? ''));
            $content = (string) ($attachment['Content'] ?? '');
            if (! in_array($mime, self::ALLOWED_MIMES, true) || $content === '') {
                continue;
            }

            // Base64 valideren en de echte grootte bepalen.
            $binary = base64_decode($content, true);
            if ($binary === false || strlen($binary) === 0 || strlen($binary) > self::MAX_BYTES) {
                continue;
            }

            PurchaseInboxItem::create([
                'company_id' => $company->id,
                'from_email' => $from ?: null,
                'subject' => $subject ?: null,
                'filename' => mb_substr((string) ($attachment['Name'] ?? 'bijlage'), 0, 255) ?: 'bijlage',
                'mime_type' => $mime,
                'size_bytes' => strlen($binary),
                'file_data' => base64_encode($binary),
                'status' => 'pending',
                'received_at' => now(),
            ]);
            $stored++;
        }

        Log::info('Inbound mail verwerkt', ['company' => $company->id, 'bijlagen' => $stored]);

        return response('OK');
    }

    /** Zoek de administratie via bon-<token>@ in een van de ontvangstadressen. */
    protected function resolveCompany(array $payload): ?Company
    {
        $addresses = [];
        foreach (['ToFull', 'CcFull', 'BccFull'] as $field) {
            foreach ((array) ($payload[$field] ?? []) as $recipient) {
                if (! empty($recipient['Email'])) {
                    $addresses[] = $recipient['Email'];
                }
            }
        }
        if (! empty($payload['To']) && is_string($payload['To'])) {
            foreach (explode(',', $payload['To']) as $addr) {
                $addresses[] = trim($addr, " \t<>\"'");
            }
        }

        foreach ($addresses as $address) {
            if (preg_match('/^bon-([a-f0-9]{12})@/i', trim($address), $m)) {
                $company = Company::where('inbound_token', strtolower($m[1]))->first();
                if ($company) {
                    return $company;
                }
            }
        }

        return null;
    }
}
