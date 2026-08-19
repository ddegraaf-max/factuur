<?php

namespace App\Support;

use App\Models\Invoice;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;

/**
 * QR-code betalen: een QR op de factuur-PDF die naar de betaalpagina in het
 * klantenportaal leidt. De QR verwijst bewust naar het portaal en niet naar
 * een Mollie-checkout: die checkout-URL's verlopen, terwijl de portaallink
 * altijd de actuele status en het actuele openstaande bedrag toont (en na
 * betaling gewoon "betaald" laat zien in plaats van een dode link).
 *
 * De QR verschijnt alleen als er ook echt online betaald kan worden:
 * Mollie gekoppeld, factuur verstuurd (portaal-token) en nog niet voldaan.
 */
class PaymentQr
{
    public static function forInvoice(Invoice $invoice): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        if (
            ! $invoice->portal_token
            || $invoice->is_credit
            || ! in_array($invoice->status, ['sent', 'partial', 'overdue'], true)
            || $invoice->remaining_amount <= 0.009
            || blank($invoice->company?->mollie_api_key)
        ) {
            return null;
        }

        return self::render(route('portal.invoice', $invoice->portal_token));
    }

    /** PNG-data-URI van een QR-code voor deze URL (of null als het misgaat). */
    public static function render(string $url): ?string
    {
        try {
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => true,
                'eccLevel' => EccLevel::M,
                'scale' => 4,
                'quietzoneSize' => 2,
            ]);

            return (new QRCode($options))->render($url);
        } catch (\Throwable $e) {
            // Een kapotte QR mag nooit een factuur-PDF tegenhouden.
            Log::warning('QR-code genereren mislukt', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
