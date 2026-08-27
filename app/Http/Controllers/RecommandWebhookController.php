<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\PeppolService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhooks van Recommand (Peppol). Altijd 200 terug — anders blijft Recommand
 * herhalen. Handtekening: X-Signature: sha256=<HMAC-SHA256 over de rauwe body>.
 */
class RecommandWebhookController extends Controller
{
    public function handle(Request $request, PeppolService $peppol): Response
    {
        $secret = (string) config('services.peppol.recommand_webhook_secret');
        if ($secret !== '') {
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, (string) $request->header('X-Signature'))) {
                Log::warning('Recommand-webhook: ongeldige handtekening', ['ip' => $request->ip()]);

                return response('', 401);
            }
        }

        $event = (string) $request->input('eventType', '');

        try {
            match ($event) {
                'document.received' => $this->documentReceived($request, $peppol),
                'company.verification' => $this->companyVerification($request, $peppol),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Recommand-webhook verwerken mislukt', ['event' => $event, 'error' => $e->getMessage()]);
        }

        return response('', 200);
    }

    protected function documentReceived(Request $request, PeppolService $peppol): void
    {
        $documentId = (string) $request->input('documentId', '');
        if ($documentId === '') {
            return;
        }

        $item = $peppol->importReceived($documentId);
        Log::info('Peppol-document ontvangen', ['document' => $documentId, 'inbox_item' => $item?->id]);
    }

    protected function companyVerification(Request $request, PeppolService $peppol): void
    {
        $companyId = (string) $request->input('companyId', '');
        $status = (string) ($request->input('status') ?? $request->input('verificationStatus') ?? '');
        if ($companyId === '') {
            return;
        }

        $company = Company::where('peppol_company_id', $companyId)->first();
        if (! $company) {
            return;
        }

        if ($status !== '') {
            $peppol->applyVerificationStatus($company, $status);
        } else {
            $peppol->refreshStatus($company);
        }
    }
}
