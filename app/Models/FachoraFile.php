<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Bestand bij een bedrijf (polis, foto) of opdracht (foto's van de klus, het
 * resultaat of een geschil). In de database: wat op schijf staat verdwijnt
 * bij elke deploy.
 */
class FachoraFile extends Model
{
    protected $fillable = ['owner_type', 'owner_id', 'kind', 'filename', 'mime_type', 'size_bytes', 'file_data'];

    protected $hidden = ['file_data'];

    public static function fromUpload(string $ownerType, int $ownerId, string $kind, UploadedFile $file): self
    {
        return static::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'kind' => $kind,
            'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => (int) $file->getSize(),
            'file_data' => base64_encode((string) file_get_contents($file->getRealPath())),
        ]);
    }

    public function contents(): ?string
    {
        return $this->file_data ? (base64_decode($this->file_data, true) ?: null) : null;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /** @return array<string, mixed> */
    public function summary(string $url): array
    {
        return ['id' => $this->id, 'kind' => $this->kind, 'filename' => $this->filename, 'image' => $this->isImage(), 'url' => $url, 'size' => $this->size_bytes];
    }
}
