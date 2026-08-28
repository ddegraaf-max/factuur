<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Opslagmeter per administratie. Bijlagen staan (base64) in de database, dus
 * de meter beschermt de database én maakt inzichtelijk wat een administratie
 * gebruikt. Limiet: 2 GB (Slim: 10 GB) — ruim voor jaren aan PDF's en bonnen.
 */
class StorageUsage
{
    public const LIMIT_BASIC = 2 * 1024 * 1024 * 1024;
    public const LIMIT_SLIM = 10 * 1024 * 1024 * 1024;

    public static function usedBytes(Company $company): int
    {
        $attachments = (int) DB::table('attachments')->where('company_id', $company->id)->sum('size_bytes');
        // Inbox-items en logo's: base64 is ~4/3 van de echte grootte.
        $inbox = DB::getSchemaBuilder()->hasColumn('purchase_inbox_items', 'file_data')
            ? (int) DB::table('purchase_inbox_items')->where('company_id', $company->id)->selectRaw('COALESCE(SUM(LENGTH(file_data)), 0) as b')->value('b') * 3 / 4
            : 0;
        $branding = (int) DB::table('companies')->where('id', $company->id)->selectRaw('COALESCE(LENGTH(logo_data), 0) + COALESCE(LENGTH(stationery_data), 0) as b')->value('b') * 3 / 4;

        return (int) ($attachments + $inbox + $branding);
    }

    public static function limitBytes(Company $company): int
    {
        return method_exists($company, 'hasSlim') && $company->hasSlim() ? self::LIMIT_SLIM : self::LIMIT_BASIC;
    }

    /** @return array{used_bytes: int, limit_bytes: int, percent: float, used_label: string, limit_label: string, full: bool} */
    public static function for(Company $company): array
    {
        $used = static::usedBytes($company);
        $limit = static::limitBytes($company);

        return [
            'used_bytes' => $used,
            'limit_bytes' => $limit,
            'percent' => round(min(100, $used / $limit * 100), 1),
            'used_label' => static::human($used),
            'limit_label' => static::human($limit),
            'full' => $used >= $limit,
        ];
    }

    public static function hasRoomFor(Company $company, int $bytes): bool
    {
        return static::usedBytes($company) + $bytes <= static::limitBytes($company);
    }

    public static function human(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) return number_format($bytes / 1024 ** 3, 2, ',', '.') . ' GB';
        if ($bytes >= 1024 ** 2) return number_format($bytes / 1024 ** 2, 1, ',', '.') . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 0, ',', '.') . ' kB';

        return $bytes . ' B';
    }
}
