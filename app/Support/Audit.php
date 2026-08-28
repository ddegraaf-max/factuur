<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Logboek-schrijver. Gebruik: Audit::log('sent', $invoice, 'Factuur 2026-0004 verstuurd naar klant@…').
 * Schrijft nooit een fout naar de gebruiker door: loggen mag de actie zelf niet breken.
 */
class Audit
{
    /** Velden die nooit in het logboek terechtkomen (waarden noch namen zijn nuttig). */
    private const NOISE = [
        'updated_at', 'created_at', 'remember_token', 'password', 'two_factor_secret', 'two_factor_recovery_codes',
        'portal_token', 'logo_data', 'stationery_data', 'signature_data', 'attachment_data', 'mollie_api_key', 'ob_number',
        'verification_code', 'verification_code_expires_at', 'peppol_verification_url', 'last_login_at',
    ];

    /** Leesbare naam per model — voor het onderwerp en de filter op het logboek. */
    public const TYPES = [
        \App\Models\Invoice::class => 'factuur',
        \App\Models\Quote::class => 'offerte',
        \App\Models\Customer::class => 'klant',
        \App\Models\Product::class => 'product',
        \App\Models\Payment::class => 'betaling',
        \App\Models\PurchaseInvoice::class => 'inkoopfactuur',
        \App\Models\Company::class => 'instellingen',
        \App\Models\User::class => 'gebruiker',
        \App\Models\RecurringInvoice::class => 'terugkerend',
        \App\Models\BrandProfile::class => 'handelsnaam',
    ];

    public static function log(string $action, ?Model $subject = null, ?string $description = null, array $changes = [], ?int $companyId = null): ?ActivityLog
    {
        try {
            $user = auth()->user();
            $type = $subject ? (self::TYPES[get_class($subject)] ?? mb_strtolower(class_basename($subject))) : null;
            $label = $subject ? self::label($subject) : null;
            $companyId = $companyId
                ?? ($subject?->getAttribute('company_id'))
                ?? ($subject instanceof \App\Models\Company ? $subject->id : null)
                ?? $user?->company_id;

            return ActivityLog::query()->withoutGlobalScope('company')->create([
                'company_id' => $companyId,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? (app()->runningInConsole() ? 'Systeem' : null),
                'action' => $action,
                'subject_type' => $type,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $label ? mb_substr($label, 0, 160) : null,
                'description' => mb_substr($description ?? trim(($label ?? ucfirst($type ?? '')) . ' ' . (ActivityLog::ACTION_LABELS[$action] ?? $action)), 0, 255),
                'changes' => $changes ?: null,
                'ip' => app()->runningInConsole() ? null : request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /** "Factuur 2026-0004", "Offerte OFF-2026-0002", "Klant Vries Design", "Betaling € 250,00". */
    public static function label(Model $subject): string
    {
        $type = self::TYPES[get_class($subject)] ?? mb_strtolower(class_basename($subject));
        $name = match (true) {
            $subject instanceof \App\Models\Invoice => $subject->number ?: ('concept #' . $subject->id),
            $subject instanceof \App\Models\Quote => $subject->number ?: ('concept #' . $subject->id),
            $subject instanceof \App\Models\Payment => '€ ' . number_format((float) $subject->amount, 2, ',', '.') . ($subject->invoice?->number ? ' op ' . $subject->invoice->number : ''),
            $subject instanceof \App\Models\PurchaseInvoice => trim(($subject->supplier_name ?? '') . ' ' . ($subject->invoice_number ?? '')) ?: ('#' . $subject->id),
            $subject instanceof \App\Models\Company => 'bedrijfsgegevens',
            default => (string) ($subject->getAttribute('name') ?? $subject->getAttribute('description') ?? $subject->getAttribute('email') ?? ('#' . $subject->getKey())),
        };

        return ucfirst($type) . ' ' . $name;
    }

    /** Gewijzigde velden van een model, zonder ruis en zonder gevoelige waarden. */
    public static function changes(Model $model): array
    {
        $changes = [];
        foreach ($model->getChanges() as $key => $value) {
            if (in_array($key, self::NOISE, true)) {
                continue;
            }
            $old = $model->getOriginal($key);
            $changes[$key] = [
                'van' => self::scalar($old),
                'naar' => self::scalar($value),
            ];
        }

        return $changes;
    }

    private static function scalar(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_array($value) || is_object($value)) {
            return '…';
        }
        if (is_string($value) && mb_strlen($value) > 80) {
            return mb_substr($value, 0, 77) . '…';
        }

        return $value;
    }
}
