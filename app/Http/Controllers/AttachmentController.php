<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttachmentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:10240', // 10 MB per file
        ]);

        $added = 0;
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("attachments/{$invoice->company_id}/invoices/{$invoice->id}", 'local');
            Attachment::create([
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => $file->getSize(),
                'storage_path' => $path,
            ]);
            $added++;
        }

        return back()->with('flash', "{$added} bijlage(n) toegevoegd.");
    }

    public function show(Attachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);
        return response()->file(Storage::disk('local')->path($attachment->storage_path), [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"',
        ]);
    }

    public function download(Attachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);
        return response()->download(
            Storage::disk('local')->path($attachment->storage_path),
            $attachment->filename
        );
    }

    public function destroy(Attachment $attachment)
    {
        Storage::disk('local')->delete($attachment->storage_path);
        $attachment->delete();
        return back()->with('flash', 'Bijlage verwijderd.');
    }
}
