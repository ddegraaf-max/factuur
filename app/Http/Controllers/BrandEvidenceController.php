<?php

namespace App\Http\Controllers;

use App\Models\BrandDossier;
use App\Models\BrandIncident;
use App\Services\BrandEvidenceService;
use App\Support\OwnerAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** Merkbewaking (alleen eigenaar): verwarringslog en maandelijkse merkgebruik-dossiers. */
class BrandEvidenceController extends Controller
{
    public function index()
    {
        $incidents = BrandIncident::orderByDesc('occurred_on')->orderByDesc('id')->get()->map(fn ($i) => [
            'id' => $i->id,
            'occurred_on' => $i->occurred_on->format('Y-m-d'),
            'occurred_on_label' => $i->occurred_on->translatedFormat('j M Y'),
            'source' => $i->source,
            'source_label' => BrandIncident::SOURCES[$i->source] ?? $i->source,
            'name' => $i->name,
            'email' => $i->email,
            'summary' => $i->summary,
            'evidence' => $i->evidence,
            'has_attachment' => $i->attachment_name !== null,
            'attachment_name' => $i->attachment_name,
        ]);

        $disk = Storage::disk(BrandEvidenceService::DISK);
        $dossiers = BrandDossier::orderByDesc('month')->get()->map(fn ($d) => [
            'month' => $d->month,
            'generated_at_label' => $d->generated_at->translatedFormat('j M Y, H:i'),
            'mailed_to' => $d->mailed_to,
            'stats' => $d->stats,
            'files' => collect($d->manifest)->map(fn ($f) => [
                'file' => $f['file'],
                'bytes' => $f['bytes'],
                'available' => $disk->exists(BrandEvidenceService::DIR . "/{$d->month}/{$f['file']}"),
            ])->values(),
        ]);

        return Inertia::render('Stats/Merkbewaking', [
            'incidents' => $incidents,
            'sources' => BrandIncident::SOURCES,
            'dossiers' => $dossiers,
            'owner_emails' => OwnerAccess::emails(),
        ]);
    }

    public function storeIncident(Request $request)
    {
        $data = $request->validate([
            'occurred_on' => ['required', 'date'],
            'source' => ['required', 'in:' . implode(',', array_keys(BrandIncident::SOURCES))],
            'name' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:180'],
            'summary' => ['required', 'string', 'max:4000'],
            'evidence' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:8192', 'mimetypes:image/png,image/jpeg,image/webp,application/pdf'],
        ]);

        $attachment = $request->file('attachment');

        BrandIncident::create([
            'occurred_on' => $data['occurred_on'],
            'source' => $data['source'],
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'summary' => $data['summary'],
            'evidence' => $data['evidence'] ?? null,
            'attachment_name' => $attachment?->getClientOriginalName(),
            'attachment_mime' => $attachment?->getMimeType(),
            'attachment_data' => $attachment ? base64_encode($attachment->get()) : null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('flash', 'Incident vastgelegd.');
    }

    public function destroyIncident(BrandIncident $incident)
    {
        $incident->delete();

        return back()->with('flash', 'Incident verwijderd.');
    }

    public function attachment(BrandIncident $incident)
    {
        abort_unless($incident->attachment_data, 404);

        return response(base64_decode($incident->attachment_data), 200, [
            'Content-Type' => $incident->attachment_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($incident->attachment_name ?: 'bijlage') . '"',
        ]);
    }

    /** Verwarringslog als CSV — voor de merkgemachtigde. */
    public function exportCsv()
    {
        $rows = ["datum;bron;naam;e-mail;wat werd gezegd of gevraagd;waaruit blijkt de verwarring;bijlage;vastgelegd op"];
        BrandIncident::orderBy('occurred_on')->get()->each(function ($i) use (&$rows) {
            $clean = fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"';
            $rows[] = implode(';', [
                $i->occurred_on->format('Y-m-d'), BrandIncident::SOURCES[$i->source] ?? $i->source, $clean($i->name), $clean($i->email),
                $clean($i->summary), $clean($i->evidence), $clean($i->attachment_name), $i->created_at?->format('Y-m-d H:i'),
            ]);
        });

        return response(implode("\n", $rows) . "\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="verwarringslog-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /** Dossier van een maand (opnieuw) opstellen en mailen. */
    public function generate(Request $request, BrandEvidenceService $service)
    {
        $data = $request->validate(['month' => ['nullable', 'regex:/^\d{4}-\d{2}$/']]);
        $month = ! empty($data['month']) ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth() : now()->subMonthNoOverflow()->startOfMonth();

        $dossier = $service->buildMonth($month);

        return back()->with('flash', "Dossier {$dossier->month} opgesteld" . ($dossier->mailed_to ? " en gemaild naar {$dossier->mailed_to}." : '.'));
    }

    public function file(string $month, string $file)
    {
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) && preg_match('/^[A-Za-z0-9._-]+$/', $file), 404);
        $path = BrandEvidenceService::DIR . "/{$month}/{$file}";
        $disk = Storage::disk(BrandEvidenceService::DISK);
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $file);
    }
}
