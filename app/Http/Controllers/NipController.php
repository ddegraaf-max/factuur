<?php

namespace App\Http\Controllers;

use App\Services\NipService;
use Illuminate\Http\JsonResponse;

/** JSON-endpoint voor de NIP-zoeker (Poolse markt) in klant- en registratieformulieren. */
class NipController extends Controller
{
    public function __construct(protected NipService $nip) {}

    public function lookup(string $nip): JsonResponse
    {
        $nip = NipService::normalize($nip);
        abort_unless(preg_match('/^\d{10}$/', $nip), 404);

        try {
            $result = $this->nip->lookup($nip);
        } catch (\DomainException $e) {
            return response()->json(['result' => null, 'error' => $e->getMessage()]);
        }

        return response()->json(['result' => $result]);
    }
}
