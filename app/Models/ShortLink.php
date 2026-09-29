<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Een kort adres op het eigen domein, voor in een sms: /u/{code} leidt door
 * naar het lange adres met de geheime sleutel. De code is zelf ook niet te
 * raden (acht tekens uit 55).
 */
class ShortLink extends Model
{
    /** Zonder tekens die op elkaar lijken (0/O, 1/l/I). */
    private const ALPHABET = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LENGTH = 8;

    protected $fillable = ['code', 'url', 'url_hash', 'company_id', 'hits', 'last_hit_at'];

    protected $casts = [
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    /** Het korte adres bij dit lange adres; bestaat het al, dan hetzelfde. */
    public static function for(string $url, ?int $companyId = null): self
    {
        $hash = sha1($url);
        $existing = static::where('url_hash', $hash)->where('url', $url)->first();
        if ($existing) {
            return $existing;
        }

        do {
            $code = '';
            for ($i = 0; $i < self::LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return static::create(['code' => $code, 'url' => $url, 'url_hash' => $hash, 'company_id' => $companyId]);
    }

    public function shortUrl(): string
    {
        return route('short', $this->code);
    }

    public static function isCode(string $code): bool
    {
        return strlen($code) === self::LENGTH && strspn($code, self::ALPHABET) === self::LENGTH;
    }
}
