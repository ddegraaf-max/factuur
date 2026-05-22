<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'attachable_type', 'attachable_id',
        'filename', 'mime_type', 'size_bytes', 'storage_path',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('attachments.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function (Attachment $a) {
            if (! $a->company_id && auth()->check()) {
                $a->company_id = auth()->user()->company_id;
            }
            if (! $a->uploaded_by_user_id && auth()->check()) {
                $a->uploaded_by_user_id = auth()->id();
            }
        });
    }

    public function attachable(): MorphTo { return $this->morphTo(); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by_user_id'); }

    public function getKindAttribute(): string
    {
        $m = strtolower($this->mime_type ?? '');
        $ext = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        if (str_contains($m, 'pdf') || $ext === 'pdf') return 'pdf';
        if (str_starts_with($m, 'image/') || in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp'])) return 'image';
        if (str_contains($m, 'word') || in_array($ext, ['doc','docx','odt'])) return 'doc';
        if (str_contains($m, 'sheet') || str_contains($m, 'excel') || in_array($ext, ['xls','xlsx','csv','ods'])) return 'sheet';
        return 'file';
    }

    public function getSizeFormattedAttribute(): string
    {
        $b = (int) $this->size_bytes;
        if ($b < 1024) return $b . ' B';
        if ($b < 1024 * 1024) return number_format($b / 1024, 1) . ' KB';
        return number_format($b / 1024 / 1024, 1) . ' MB';
    }
}
