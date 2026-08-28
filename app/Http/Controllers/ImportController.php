<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** Instellingen → Overstappen: CSV-exports van andere pakketten importeren. */
class ImportController extends Controller
{
    public function __construct(protected ImportService $import) {}

    public function index(Request $request)
    {
        $preview = $request->session()->get('import_preview');

        return Inertia::render('Import/Index', [
            'types' => ImportService::TYPES,
            'fields' => collect(ImportService::FIELDS)->map(fn ($fields) => collect($fields)->map(fn ($f, $key) => ['key' => $key, 'label' => $f[0], 'required' => $f[1]])->values())->all(),
            'preview' => $preview,
            'result' => $request->session()->get('import_result'),
        ]);
    }

    /** Stap 1: bestand inlezen, koppeling voorstellen, voorbeeld tonen. */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(ImportService::TYPES))],
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt'],
        ], ['file.mimes' => 'Upload een CSV-bestand (exporteer vanuit je oude pakket als CSV, niet als Excel).']);

        try {
            $parsed = $this->import->parse($request->file('file')->get());
        } catch (\DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $token = Str::random(32);
        Cache::put('import:' . $request->user()->id . ':' . $token, ['type' => $data['type'], 'headers' => $parsed['headers'], 'rows' => $parsed['rows']], now()->addMinutes(30));

        return redirect()->route('import.index')->with('import_preview', [
            'token' => $token,
            'type' => $data['type'],
            'filename' => $request->file('file')->getClientOriginalName(),
            'headers' => $parsed['headers'],
            'sample' => array_slice($parsed['rows'], 0, 5),
            'total' => count($parsed['rows']),
            'mapping' => $this->import->suggestMapping($data['type'], $parsed['headers']),
        ]);
    }

    /** Stap 2: importeren met de (aangepaste) koppeling. */
    public function commit(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:32'],
            'mapping' => ['required', 'array'],
        ]);

        $cached = Cache::pull('import:' . $request->user()->id . ':' . $data['token']);
        if (! $cached) {
            return redirect()->route('import.index')->with('error', 'Het voorbeeld is verlopen — upload het bestand opnieuw.');
        }

        $mapping = array_filter($data['mapping'], fn ($v) => $v !== null && $v !== '');
        foreach (ImportService::FIELDS[$cached['type']] as $field => [$label, $required]) {
            if ($required && ! isset($mapping[$field])) {
                Cache::put('import:' . $request->user()->id . ':' . $data['token'], $cached, now()->addMinutes(30));

                return back()->with('error', "Koppel eerst de kolom voor \"{$label}\".")->with('import_preview', [
                    'token' => $data['token'], 'type' => $cached['type'], 'filename' => 'bestand', 'headers' => $cached['headers'],
                    'sample' => array_slice($cached['rows'], 0, 5), 'total' => count($cached['rows']), 'mapping' => $mapping,
                ]);
            }
        }

        $result = $this->import->import($request->user()->company, $cached['type'], $cached['rows'], $mapping);
        $label = ImportService::TYPES[$cached['type']];
        Audit::log('created', null, "Import {$label}: {$result['created']} toegevoegd, {$result['skipped']} overgeslagen");

        return redirect()->route('import.index')->with('import_result', $result + ['type' => $cached['type'], 'label' => $label]);
    }
}
