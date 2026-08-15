<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    // Alleen deze typen tonen we inline in de browser; de rest wordt gedownload.
    private const INLINE_SAFE = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];

    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
        ], [
            'files.*.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.',
            'files.*.max' => 'Elk bestand mag maximaal 10 MB groot zijn.',
        ]);

        $added = 0;
        foreach ($request->file('files', []) as $file) {
            // In de database opslaan (base64), niet op schijf: het bestandssysteem
            // van Railway wordt bij elke deploy leeggegooid.
            Attachment::create([
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
            ]);
            $added++;
        }

        return back()->with('flash', "{$added} bijlage(n) toegevoegd.");
    }

    public function show(Attachment $attachment): Response
    {
        $contents = $attachment->contents();
        abort_if($contents === null, 404);

        $inline = in_array($attachment->mime_type, self::INLINE_SAFE, true);
        $disposition = HeaderUtils::makeDisposition(
            $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $attachment->filename,
            'bijlage'
        );

        return response($contents, 200, [
            'Content-Type' => $inline ? $attachment->mime_type : 'application/octet-stream',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Attachment $attachment): Response
    {
        $contents = $attachment->contents();
        abort_if($contents === null, 404);

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $attachment->filename,
                'bijlage'
            ),
            'Content-Length' => (string) strlen($contents),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Attachment $attachment)
    {
        // Oudere bijlagen hebben nog een bestand op schijf staan.
        if ($attachment->storage_path) {
            Storage::disk('local')->delete($attachment->storage_path);
        }

        $attachment->delete();

        return back()->with('flash', 'Bijlage verwijderd.');
    }
}
