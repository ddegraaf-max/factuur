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
        return $this->storeFor($request, Invoice::class, $invoice->id);
    }

    public function storeForQuote(Request $request, \App\Models\Quote $quote)
    {
        // Offertebijlagen gaan standaard mee met de offertemail.
        return $this->storeFor($request, \App\Models\Quote::class, $quote->id, defaultForCustomer: true);
    }

    protected function storeFor(Request $request, string $attachableType, int $attachableId, bool $defaultForCustomer = false)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
            'for_customer' => ['nullable', 'boolean'],
        ], [
            'files.*.mimetypes' => 'Alleen PDF-, PNG-, JPG- of WEBP-bestanden zijn toegestaan.',
            'files.*.max' => 'Elk bestand mag maximaal 10 MB groot zijn.',
        ]);

        // Opslagmeter: boven de limiet geen nieuwe bijlagen (zie App\Support\StorageUsage).
        $incoming = array_sum(array_map(fn ($f) => (int) $f->getSize(), $request->file('files', [])));
        if (! \App\Support\StorageUsage::hasRoomFor($request->user()->company, $incoming)) {
            $usage = \App\Support\StorageUsage::for($request->user()->company);

            return back()->withErrors(['files' => "De opslag van je administratie is vol ({$usage['used_label']} van {$usage['limit_label']}). Verwijder oude bijlagen of stap over op Slim (10 GB)."]);
        }

        $added = 0;
        foreach ($request->file('files', []) as $file) {
            // In de database opslaan (base64), niet op schijf: het bestandssysteem
            // van Railway wordt bij elke deploy leeggegooid.
            Attachment::create([
                'attachable_type' => $attachableType,
                'attachable_id' => $attachableId,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'file_data' => base64_encode(file_get_contents($file->getRealPath())),
                'for_customer' => $request->has('for_customer') ? $request->boolean('for_customer') : $defaultForCustomer,
            ]);
            $added++;
        }

        return back()->with('flash', "{$added} bijlage(n) toegevoegd.");
    }

    /** Zet een bijlage op "voor de klant" (meesturen + portaal) of weer intern. */
    public function update(Request $request, Attachment $attachment)
    {
        $data = $request->validate([
            'for_customer' => ['required', 'boolean'],
        ]);

        $attachment->update(['for_customer' => $data['for_customer']]);

        return back()->with('flash', $data['for_customer']
            ? 'Bijlage is nu zichtbaar voor de klant (mail + portaal).'
            : 'Bijlage is nu alleen intern zichtbaar.');
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
