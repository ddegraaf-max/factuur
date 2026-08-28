<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Minimale S3-client (AWS Signature V4, path-style) zonder SDK — voldoende voor
 * back-ups naar elke S3-compatibele opslag: Cloudflare R2, Backblaze B2,
 * Hetzner Object Storage, Scaleway, AWS zelf.
 */
class S3Client
{
    public function __construct(
        private string $endpoint,
        private string $region,
        private string $bucket,
        private string $key,
        private string $secret,
    ) {
        $this->endpoint = rtrim($endpoint, '/');
    }

    public static function fromConfig(): ?self
    {
        $c = config('services.backup');
        if (empty($c['endpoint']) || empty($c['bucket']) || empty($c['key']) || empty($c['secret'])) {
            return null;
        }

        return new self($c['endpoint'], $c['region'] ?: 'auto', $c['bucket'], $c['key'], $c['secret']);
    }

    public function put(string $objectKey, string $body, string $contentType = 'application/octet-stream'): void
    {
        $this->request('PUT', $objectKey, [], $body, ['content-type' => $contentType])->throw();
    }

    public function delete(string $objectKey): void
    {
        $this->request('DELETE', $objectKey)->throw();
    }

    /** @return array<int, array{key: string, size: int, modified: string}> */
    public function list(string $prefix = ''): array
    {
        $response = $this->request('GET', '', ['list-type' => '2', 'prefix' => $prefix, 'max-keys' => '1000'])->throw();
        $xml = @simplexml_load_string($response->body());
        $objects = [];
        foreach ($xml?->Contents ?? [] as $item) {
            $objects[] = ['key' => (string) $item->Key, 'size' => (int) $item->Size, 'modified' => (string) $item->LastModified];
        }

        return $objects;
    }

    /** Ondertekende headers voor een verzoek — apart zodat de handtekening testbaar is. */
    public function signedHeaders(string $method, string $objectKey, array $query, string $body, array $extraHeaders = [], ?\DateTimeInterface $now = null): array
    {
        $now = $now ?: now('UTC');
        $amzDate = $now->format('Ymd\THis\Z');
        $date = $now->format('Ymd');
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $payloadHash = hash('sha256', $body);

        $headers = array_merge($extraHeaders, [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ]);
        ksort($headers);
        $canonicalHeaders = implode('', array_map(fn ($k, $v) => strtolower($k) . ':' . trim($v) . "\n", array_keys($headers), $headers));
        $signedHeaders = implode(';', array_map('strtolower', array_keys($headers)));

        ksort($query);
        $canonicalQuery = implode('&', array_map(fn ($k, $v) => rawurlencode($k) . '=' . rawurlencode($v), array_keys($query), $query));

        $canonicalRequest = implode("\n", [
            $method,
            $this->canonicalUri($objectKey),
            $canonicalQuery,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $headers['authorization'] = "AWS4-HMAC-SHA256 Credential={$this->key}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        unset($headers['host']); // zet de HTTP-client zelf

        return $headers;
    }

    public function url(string $objectKey, array $query = []): string
    {
        return $this->endpoint . $this->canonicalUri($objectKey) . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }

    private function canonicalUri(string $objectKey): string
    {
        $segments = array_map('rawurlencode', array_filter(explode('/', $objectKey), fn ($s) => $s !== ''));

        return '/' . rawurlencode($this->bucket) . ($segments ? '/' . implode('/', $segments) : '');
    }

    private function request(string $method, string $objectKey, array $query = [], string $body = '', array $extraHeaders = []): Response
    {
        $headers = $this->signedHeaders($method, $objectKey, $query, $body, $extraHeaders);
        $pending = Http::withHeaders($headers)->timeout(120);
        if ($body !== '') {
            $pending = $pending->withBody($body, $extraHeaders['content-type'] ?? 'application/octet-stream');
        }

        return $pending->send($method, $this->url($objectKey, $query));
    }
}
