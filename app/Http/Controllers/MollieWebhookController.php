<?php

namespace App\Http\Controllers;

use App\Services\MolliePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Webhook van Mollie: die stuurt alleen een betalings-id mee — de echte
 * status halen we zelf bij Mollie op (met de API-key van de administratie),
 * dus een vervalst verzoek kan hooguit een status-check uitlokken.
 * Altijd 200 teruggeven; anders blijft Mollie eindeloos herhalen.
 */
class MollieWebhookController extends Controller
{
    public function handle(Request $request, MolliePaymentService $mollie): Response
    {
        $id = (string) $request->input('id', '');

        if ($id !== '' && preg_match('/^tr_[\w]+$/', $id)) {
            try {
                $mollie->handleWebhook($id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Mollie-webhook verwerken mislukt', [
                    'mollie_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response('OK');
    }
}
