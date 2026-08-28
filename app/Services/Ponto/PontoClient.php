<?php

namespace App\Services\Ponto;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Laag-niveau client voor Ponto Connect (Ibanity): mutual TLS met het
 * applicatiecertificaat, HTTP-handtekening (als een handtekeningcertificaat
 * is ingesteld), OAuth2-tokens en JSON:API-aanroepen met een accesstoken.
 */
class PontoClient
{
    private ?PontoSignature $signature = null;

    /** @var array<string, string>|null */
    private ?array $certificateFiles = null;

    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    public static function fromConfig(): ?self
    {
        $c = (array) config('services.ponto', []);
        if (empty($c['client_id']) || empty($c['client_secret']) || empty($c['certificate']) || empty($c['private_key'])) {
            return null;
        }

        return new self($c);
    }

    public function sandbox(): bool { return (bool) ($this->config['sandbox'] ?? false); }
    public function clientId(): string { return (string) $this->config['client_id']; }

    public function apiBase(): string
    {
        return rtrim((string) ($this->config['api_base'] ?: 'https://api.ibanity.com/ponto-connect'), '/');
    }

    public function authorizationUrl(): string
    {
        return $this->sandbox()
            ? 'https://sandbox-authorization.myponto.com/oauth2/auth'
            : 'https://authorization.myponto.com/oauth2/auth';
    }

    /* ===================== OAUTH2 ===================== */

    /** @return array<string, mixed> */
    public function exchangeCode(string $code, string $redirectUri, string $codeVerifier): array
    {
        return $this->token([
            'grant_type' => 'authorization_code', 'client_id' => $this->clientId(),
            'code' => $code, 'code_verifier' => $codeVerifier, 'redirect_uri' => $redirectUri,
        ]);
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        return $this->token(['grant_type' => 'refresh_token', 'client_id' => $this->clientId(), 'refresh_token' => $refreshToken]);
    }

    public function revoke(string $refreshToken): void
    {
        $this->oauthPost('oauth2/revoke', ['token' => $refreshToken]);
    }

    /** @return array<string, mixed> */
    private function token(array $form): array
    {
        $response = $this->oauthPost('oauth2/token', $form);
        if ($response->failed()) {
            $detail = $response->json('error_description') ?: $response->json('error') ?: 'HTTP ' . $response->status();
            throw new PontoException('Ponto-token: ' . $detail, $response->status(), $response->json('error'));
        }
        $data = (array) $response->json();
        if (empty($data['access_token'])) {
            throw new PontoException('Ponto-token: geen access_token in het antwoord.', $response->status());
        }

        return $data;
    }

    private function oauthPost(string $path, array $form): Response
    {
        $url = $this->apiBase() . '/' . $path;
        $body = http_build_query($form, '', '&');
        $basic = 'Basic ' . base64_encode($this->clientId() . ':' . $this->config['client_secret']);

        try {
            return $this->base()
                ->withHeaders(array_merge(['Authorization' => $basic, 'Accept' => 'application/json'], $this->signatureHeaders('POST', $url, $body, $basic)))
                ->withBody($body, 'application/x-www-form-urlencoded')
                ->send('POST', $url);
        } catch (ConnectionException $e) {
            throw new PontoException('Ponto is niet bereikbaar: ' . $e->getMessage(), 0, null, $e);
        }
    }

    /* ===================== JSON:API ===================== */

    /** @return array<string, mixed> */
    public function get(string $accessToken, string $pathOrUrl, array $query = []): array
    {
        return $this->call('GET', $accessToken, $pathOrUrl, $query);
    }

    /** @return array<string, mixed> */
    public function post(string $accessToken, string $path, array $body): array
    {
        return $this->call('POST', $accessToken, $path, [], $body);
    }

    /** @return array<string, mixed> */
    private function call(string $method, string $accessToken, string $pathOrUrl, array $query = [], ?array $body = null): array
    {
        $url = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : $this->apiBase() . '/' . ltrim($pathOrUrl, '/');
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $json = $body === null ? '' : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $bearer = 'Bearer ' . $accessToken;

        $request = $this->base()->withHeaders(array_merge(
            ['Authorization' => $bearer, 'Accept' => 'application/vnd.api+json'],
            $this->signatureHeaders($method, $url, $json, $bearer),
        ));
        if ($body !== null) {
            $request = $request->withBody($json, 'application/vnd.api+json');
        }
        try {
            $response = $request->send($method, $url);
        } catch (ConnectionException $e) {
            throw new PontoException('Ponto is niet bereikbaar: ' . $e->getMessage(), 0, null, $e);
        }

        if ($response->failed()) {
            $error = (array) ($response->json('errors.0') ?? []);
            $detail = $error['detail'] ?? $error['code'] ?? 'HTTP ' . $response->status();
            throw new PontoException('Ponto: ' . $detail, $response->status(), $error['code'] ?? null);
        }

        return (array) $response->json();
    }

    /* ===================== TLS & HANDTEKENING ===================== */

    private function base(): PendingRequest
    {
        $files = $this->certificateFiles();
        $passphrase = (string) ($this->config['key_passphrase'] ?? '');

        return Http::timeout(60)->withOptions([
            'cert' => $files['cert'],
            'ssl_key' => $passphrase !== '' ? [$files['key'], $passphrase] : $files['key'],
        ]);
    }

    /** @return array<string, string> */
    private function signatureHeaders(string $method, string $url, string $body, string $authorization): array
    {
        if (empty($this->config['signature_certificate_id']) || empty($this->config['signature_private_key'])) {
            return [];
        }
        $this->signature ??= new PontoSignature(
            (string) $this->config['signature_certificate_id'],
            self::pem((string) $this->config['signature_private_key']),
            $this->config['signature_key_passphrase'] ?? null,
        );

        return $this->signature->headers($method, $url, $body, $authorization);
    }

    /**
     * Guzzle wil certificaat en sleutel als bestand: schrijf de PEM's uit de
     * configuratie eenmalig naar storage/app/ponto (naam op basis van inhoud).
     *
     * @return array<string, string>
     */
    private function certificateFiles(): array
    {
        if ($this->certificateFiles !== null) {
            return $this->certificateFiles;
        }
        $dir = storage_path('app/ponto');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $files = [];
        foreach (['cert' => 'certificate', 'key' => 'private_key'] as $name => $configKey) {
            $pem = self::pem((string) $this->config[$configKey]);
            $path = $dir . '/' . $name . '-' . substr(sha1($pem), 0, 12) . '.pem';
            if (! is_file($path)) {
                file_put_contents($path, $pem);
                @chmod($path, 0600);
            }
            $files[$name] = $path;
        }

        return $this->certificateFiles = $files;
    }

    /** PEM uit een env-waarde: letterlijk, met '\n'-escapes, of base64-gecodeerd. */
    public static function pem(string $value): string
    {
        $value = trim($value);
        if (! str_contains($value, '-----BEGIN')) {
            $decoded = base64_decode($value, true);
            if ($decoded !== false && str_contains($decoded, '-----BEGIN')) {
                $value = trim($decoded);
            }
        }

        return str_replace('\n', "\n", $value) . "\n";
    }
}
