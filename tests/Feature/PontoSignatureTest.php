<?php

namespace Tests\Feature;

use App\Services\Ponto\PontoSignature;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** HTTP-handtekening voor Ibanity (hs2019: SHA-512-digest + RSA-PSS/SHA-256). */
class PontoSignatureTest extends TestCase
{
    public function test_signature_headers_follow_the_ibanity_hs2019_scheme(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $privatePem);
        $publicPem = (string) openssl_pkey_get_details($key)['key'];

        $signer = new PontoSignature('8eddf328-1947-48c0-8525-d1a2ea378f9f', $privatePem);
        $body = '{"data":{"type":"synchronization"}}';
        $url = 'https://api.ibanity.com/ponto-connect/synchronizations?page%5Blimit%5D=10';
        $headers = $signer->headers('POST', $url, $body, 'Bearer at-1', 1756400000);

        $this->assertSame('SHA-512=' . strtr(base64_encode(hash('sha512', $body, true)), '+/', '-_'), $headers['Digest']);
        $this->assertStringStartsWith(
            'keyId="8eddf328-1947-48c0-8525-d1a2ea378f9f",created="1756400000",algorithm="hs2019",headers="(request-target) host digest (created) authorization",signature="',
            $headers['Signature']
        );

        preg_match('/signature="([^"]+)"/', $headers['Signature'], $m);
        $signature = base64_decode(strtr($m[1], '-_', '+/'));
        $this->assertSame(256, strlen($signature));

        $signingString = implode("\n", [
            '(request-target): post /ponto-connect/synchronizations?page%5Blimit%5D=10',
            'host: api.ibanity.com',
            'digest: ' . $headers['Digest'],
            '(created): 1756400000',
            'authorization: Bearer at-1',
        ]);
        $this->assertTrue($this->verifyPss($publicPem, $signingString, $signature), 'RSA-PSS-handtekening moet door openssl worden geaccepteerd.');
        $this->assertFalse($this->verifyPss($publicPem, $signingString . 'x', $signature), 'Gewijzigde signing string mag niet verifiëren.');
    }

    public function test_an_unreadable_key_gives_a_clear_error(): void
    {
        $this->expectException(\App\Services\Ponto\PontoException::class);
        new PontoSignature('id', "-----BEGIN RSA PRIVATE KEY-----\nkapot\n-----END RSA PRIVATE KEY-----");
    }

    /** PHP's openssl_verify kent geen PSS; verifieer daarom met de openssl-CLI (staat op de CI-runner). */
    private function verifyPss(string $publicPem, string $message, string $signature): bool
    {
        $dir = sys_get_temp_dir() . '/ponto-sig-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("$dir/pub.pem", $publicPem);
        file_put_contents("$dir/msg", $message);
        file_put_contents("$dir/sig", $signature);

        $process = new Process([
            'openssl', 'dgst', '-sha256', '-sigopt', 'rsa_padding_mode:pss', '-sigopt', 'rsa_pss_saltlen:32',
            '-verify', "$dir/pub.pem", '-signature', "$dir/sig", "$dir/msg",
        ]);
        try {
            $process->run();
        } catch (\Throwable) {
            $this->markTestSkipped('openssl-CLI niet beschikbaar.');
        }
        $output = $process->getOutput() . $process->getErrorOutput();
        if (! str_contains($output, 'Verified OK') && ! str_contains($output, 'Verification Failure')) {
            $this->markTestSkipped('openssl-CLI gaf geen bruikbaar antwoord: ' . trim($output));
        }

        return str_contains($output, 'Verified OK');
    }
}
