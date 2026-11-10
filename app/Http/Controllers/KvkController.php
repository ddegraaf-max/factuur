<?php

namespace App\Http\Controllers;

use App\Services\KvkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** JSON-endpoints voor de KvK-zoeker in het klantformulier. */
class KvkController extends Controller
{
    public function __construct(protected KvkService $kvk) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        if (! $this->kvk->enabled()) {
            return response()->json(['enabled' => false, 'results' => []]);
        }

        try {
            return response()->json([
                'enabled' => true,
                'results' => $this->kvk->search($data['q']),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['enabled' => true, 'results' => [], 'error' => $e->getMessage()], 200);
        }
    }

    /**
     * Het betaalde basisprofiel. Mag het niet naar buiten (plafond of
     * noodstop), dan is result null met een vriendelijke melding; het
     * formulier vult dan in wat het zoekresultaat al weet.
     */
    public function profile(Request $request, string $kvkNumber): JsonResponse
    {
        abort_unless(preg_match('/^\d{8}$/', $kvkNumber), 404);

        $result = $this->kvk->profile($kvkNumber, $request->user()?->company_id);

        return response()->json(array_filter([
            'result' => $result,
            'message' => $result === null ? $this->kvk->refusal() : null,
        ], fn ($v, $k) => $k === 'result' || $v !== null, ARRAY_FILTER_USE_BOTH));
    }
}
