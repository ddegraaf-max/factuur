<?php

namespace App\Services\Ponto;

/**
 * HTTP-handtekening voor de Ibanity-API (schema "hs2019", zoals de officiële
 * Ibanity-clients): een Digest-header (SHA-512, url-safe base64) en een
 * Signature-header met RSA-PSS/SHA-256 (MGF1-SHA-256, zoutlengte 32) over de
 * signing string. PHP's openssl_sign kent geen PSS-padding; de EMSA-PSS-
 * codering (RFC 8017 §9.1.1) doen we daarom zelf en tekenen met een kale
 * RSA-bewerking. Verplicht voor live-applicaties, in de sandbox toegestaan.
 */
class PontoSignature
{
    private \OpenSSLAsymmetricKey $key;
    private int $modulusBytes;

    public function __construct(private string $certificateId, string $privateKeyPem, ?string $passphrase = null)
    {
        $key = openssl_pkey_get_private($privateKeyPem, $passphrase !== '' ? $passphrase : null);
        if ($key === false) {
            throw new PontoException('Ponto: de handtekeningsleutel kan niet worden gelezen (' . openssl_error_string() . ').');
        }
        $this->key = $key;
        $details = openssl_pkey_get_details($key) ?: [];
        $this->modulusBytes = (int) ceil((int) ($details['bits'] ?? 2048) / 8);
    }

    /**
     * Digest- en Signature-header voor één verzoek.
     *
     * @return array{Digest: string, Signature: string}
     */
    public function headers(string $method, string $url, string $body = '', ?string $authorization = null, ?int $created = null): array
    {
        $created ??= time();
        $digest = 'SHA-512=' . self::base64url(hash('sha512', $body, true));
        $parts = parse_url($url) ?: [];
        $target = strtolower($method) . ' ' . ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        $names = ['(request-target)', 'host', 'digest', '(created)'];
        $lines = [
            '(request-target): ' . $target,
            'host: ' . ($parts['host'] ?? ''),
            'digest: ' . $digest,
            '(created): ' . $created,
        ];
        if ($authorization !== null) {
            $names[] = 'authorization';
            $lines[] = 'authorization: ' . $authorization;
        }

        $signature = self::base64url($this->signPss(implode("\n", $lines)));

        return [
            'Digest' => $digest,
            'Signature' => sprintf(
                'keyId="%s",created="%d",algorithm="hs2019",headers="%s",signature="%s"',
                $this->certificateId, $created, implode(' ', $names), $signature
            ),
        ];
    }

    /** RSASSA-PSS met SHA-256, MGF1-SHA-256 en zoutlengte 32 (RFC 8017 §8.1.1). */
    public function signPss(string $message): string
    {
        $encoded = $this->emsaPssEncode($message, $this->modulusBytes * 8 - 1);
        if (! openssl_private_encrypt($encoded, $signature, $this->key, OPENSSL_NO_PADDING)) {
            throw new PontoException('Ponto: ondertekenen mislukt (' . openssl_error_string() . ').');
        }

        return $signature;
    }

    private function emsaPssEncode(string $message, int $emBits): string
    {
        $hashLength = 32;
        $saltLength = 32;
        $emLength = (int) ceil($emBits / 8);

        $messageHash = hash('sha256', $message, true);
        $salt = random_bytes($saltLength);
        $h = hash('sha256', str_repeat("\0", 8) . $messageHash . $salt, true);
        $db = str_repeat("\0", $emLength - $saltLength - $hashLength - 2) . "\x01" . $salt;
        $maskedDb = $db ^ self::mgf1($h, $emLength - $hashLength - 1);
        $clearBits = 8 * $emLength - $emBits;
        $maskedDb[0] = chr(ord($maskedDb[0]) & (0xFF >> $clearBits));

        return $maskedDb . $h . "\xbc";
    }

    private static function mgf1(string $seed, int $length): string
    {
        $out = '';
        for ($counter = 0; strlen($out) < $length; $counter++) {
            $out .= hash('sha256', $seed . pack('N', $counter), true);
        }

        return substr($out, 0, $length);
    }

    /** Url-safe base64 mét '='-padding (zoals Ruby's urlsafe_encode64, dat Ibanity zelf gebruikt). */
    public static function base64url(string $binary): string
    {
        return strtr(base64_encode($binary), '+/', '-_');
    }
}
