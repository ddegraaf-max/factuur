<?php

namespace App\Http\Controllers;

use App\Support\LegalInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Gratis calculator op /incassokosten-berekenen: incassokosten en wettelijke
 * rente over een te laat betaalde factuur. De invoer staat in de URL, zodat
 * een berekening te delen en te bewaren is; de pagina werkt zonder JavaScript.
 */
class CollectionCostsController extends Controller
{
    public function show(Request $request)
    {
        $input = [
            'bedrag' => $request->query('bedrag'),
            'vervaldatum' => $request->query('vervaldatum'),
            'tot' => $request->query('tot') ?: now()->toDateString(),
            'klant' => $request->query('klant') === 'consument' ? 'consument' : 'zakelijk',
            'btw' => $request->boolean('btw'),
        ];

        $result = null;
        $errors = [];

        if (filled($input['bedrag']) || filled($input['vervaldatum'])) {
            $amount = $this->amount($input['bedrag']);
            $validator = Validator::make(
                ['bedrag' => $amount, 'vervaldatum' => $input['vervaldatum'], 'tot' => $input['tot']],
                [
                    'bedrag' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
                    'vervaldatum' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . config('rente.from'), 'before_or_equal:' . now()->addYears(2)->toDateString()],
                    'tot' => ['required', 'date_format:Y-m-d', 'after_or_equal:vervaldatum', 'before_or_equal:' . now()->addYears(2)->toDateString()],
                ],
                [
                    'bedrag.*' => 'Vul het factuurbedrag in, bijvoorbeeld 1.250,00.',
                    'vervaldatum.after_or_equal' => 'De calculator rekent met vervaldata vanaf ' . Carbon::parse(config('rente.from'))->translatedFormat('j F Y') . '.',
                    'vervaldatum.*' => 'Vul de vervaldatum van de factuur in.',
                    'tot.after_or_equal' => 'De einddatum ligt vóór de vervaldatum.',
                    'tot.*' => 'Vul een einddatum in die hoogstens twee jaar vooruit ligt.',
                ]
            );

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
            } else {
                $result = LegalInterest::calculate(
                    (float) $amount,
                    Carbon::parse($input['vervaldatum']),
                    Carbon::parse($input['tot']),
                    $input['klant'] === 'zakelijk',
                    $input['btw'],
                );
            }
        }

        return view('marketing.incassokosten-calculator', [
            'input' => $input,
            'result' => $result,
            'calcErrors' => $errors,
            'rates' => [
                'zakelijk' => array_reverse(LegalInterest::rates(true), true),
                'consument' => array_reverse(LegalInterest::rates(false), true),
            ],
        ]);
    }

    /** '1.250,50', '1.250' en '1250.50' worden gelezen zoals een Nederlander ze bedoelt; onleesbare invoer wordt null. */
    private function amount(mixed $value): ?float
    {
        $value = trim(str_replace(['€', ' ', "\u{00A0}"], '', (string) $value));
        if (str_contains($value, ',')) {
            $value = str_replace(',', '.', str_replace('.', '', $value));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
