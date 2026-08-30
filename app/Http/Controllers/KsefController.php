<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Ksef\FaXmlBuilder;
use App\Support\Market;
use Illuminate\Http\Request;

/**
 * KSeF (Poolse markt): FA-XML van een factuur downloaden om (handmatig of via
 * een ander programma) in het Krajowy System e-Faktur in te dienen, en het
 * toegekende KSeF-nummer bij de factuur bewaren. Directe verzending via de
 * KSeF-API volgt later.
 */
class KsefController extends Controller
{
    /** Vorm van een KSeF-nummer: NIP-JJJJMMDD-XXXXXX-XXXXXX-XX. */
    public const NUMBER_PATTERN = '/^[0-9]{10}-[0-9]{8}-[0-9A-F]{6}-[0-9A-F]{6}-[0-9A-F]{2}$/i';

    public function __construct(private FaXmlBuilder $builder) {}

    public function xml(Invoice $invoice)
    {
        abort_unless(Market::isPl(), 404);
        $this->authorizeInvoice($invoice);
        abort_if($invoice->status === 'draft', 422, __('Alleen voor verstuurde facturen.'));

        $xml = $this->builder->build($invoice);

        if (! $invoice->ksef_status) {
            $invoice->forceFill(['ksef_status' => 'exported', 'ksef_sent_at' => now()])->save();
        }

        $name = 'faktura-' . preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $invoice->number) . '-ksef.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    /** KSeF-nummer (toegekend na indienen) bij de factuur opslaan. */
    public function number(Request $request, Invoice $invoice)
    {
        abort_unless(Market::isPl(), 404);
        $this->authorizeInvoice($invoice);

        $data = $request->validate([
            'ksef_number' => ['required', 'string', 'max:40', function (string $attribute, mixed $value, \Closure $fail) {
                $value = trim((string) $value);
                if (! preg_match(self::NUMBER_PATTERN, $value) && mb_strlen($value) < 20) {
                    $fail(__('Dit is geen geldig KSeF-nummer (vorm NIP-JJJJMMDD-XXXXXX-XXXXXX-XX).'));
                }
            }],
        ]);

        $invoice->forceFill([
            'ksef_number' => strtoupper(trim($data['ksef_number'])),
            'ksef_status' => 'accepted',
            'ksef_sent_at' => $invoice->ksef_sent_at ?: now(),
        ])->save();

        return back()->with('flash', __('KSeF-nummer opgeslagen.'));
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless((int) $invoice->company_id === (int) auth()->user()->company_id, 404);
    }
}
