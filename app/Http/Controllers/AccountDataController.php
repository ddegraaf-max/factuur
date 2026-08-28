<?php

namespace App\Http\Controllers;

use App\Services\CompanyPurger;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * AVG-zelfbediening voor de eigen administratie: alles exporteren (ZIP met
 * CSV + JSON per tabel) en de administratie definitief verwijderen.
 */
class AccountDataController extends Controller
{
    /** Tabellen die met company_id aan de administratie hangen, in leesbare volgorde. */
    private const TABLES = [
        'customers' => 'klanten', 'products' => 'producten', 'invoices' => 'facturen', 'quotes' => 'offertes',
        'payments' => 'betalingen', 'purchase_invoices' => 'inkoopfacturen', 'recurring_invoices' => 'terugkerende-facturen',
        'time_entries' => 'uren', 'trips' => 'ritten', 'bank_transactions' => 'banktransacties', 'reminder_logs' => 'herinneringen',
        'brand_profiles' => 'handelsnamen', 'activity_logs' => 'logboek',
    ];

    /** Kolommen die nooit in een export horen (binaire of geheime inhoud). */
    private const SKIP_COLUMNS = ['logo_data', 'stationery_data', 'signature_data', 'attachment_data', 'file_data', 'mollie_api_key', 'ob_number', 'portal_token', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    public function export(Request $request)
    {
        $company = $request->user()->company;
        $zip = new \ZipArchive();
        $path = tempnam(sys_get_temp_dir(), 'export');
        $zip->open($path, \ZipArchive::OVERWRITE);

        $strip = fn ($row) => array_diff_key((array) $row, array_flip(self::SKIP_COLUMNS));
        $all = ['geexporteerd_op' => now()->toDateTimeString(), 'administratie' => $strip($company->getAttributes())];

        foreach (self::TABLES as $table => $label) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)->where('company_id', $company->id)->orderBy('id')->get()->map($strip)->all();
            $all[$label] = $rows;
            if ($rows) {
                $zip->addFromString("{$label}.csv", $this->csv($rows));
            }
        }

        // Factuur- en offerteregels apart (hebben geen company_id).
        foreach (['invoice_lines' => ['invoices', 'invoice_id', 'factuurregels'], 'quote_lines' => ['quotes', 'quote_id', 'offerteregels']] as $table => [$parent, $fk, $label]) {
            $ids = DB::table($parent)->where('company_id', $company->id)->pluck('id');
            $rows = $ids->isEmpty() ? [] : DB::table($table)->whereIn($fk, $ids)->orderBy('id')->get()->map($strip)->all();
            $all[$label] = $rows;
            if ($rows) {
                $zip->addFromString("{$label}.csv", $this->csv($rows));
            }
        }

        $zip->addFromString('volledige-export.json', json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('LEESMIJ.txt', "Export van de EasyInvoice-administratie \"{$company->name}\" op " . now()->format('d-m-Y H:i') . ".\n\nElke CSV is puntkomma-gescheiden (UTF-8) en opent direct in Excel. volledige-export.json bevat dezelfde gegevens in één bestand.\nBijlagen (PDF's, bonnen) zitten niet in deze export; download die per document.\n");
        $zip->close();

        Audit::log('exported', null, 'Volledige export van de administratie gedownload (ZIP)');

        return response()->download($path, 'easyinvoice-export-' . now()->format('Y-m-d') . '.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    public function destroy(Request $request, CompanyPurger $purger)
    {
        $user = $request->user();
        $company = $user->company;

        $data = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['required', 'string'],
        ]);

        if (! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'Het wachtwoord klopt niet — er is niets verwijderd.');
        }
        if (mb_strtolower(trim($data['confirm'])) !== mb_strtolower($company->name)) {
            return back()->with('error', 'De bedrijfsnaam komt niet overeen — er is niets verwijderd.');
        }
        if ($company->is_exempt) {
            return back()->with('error', 'Deze administratie kan niet via deze weg worden verwijderd.');
        }

        $name = $company->name;
        \Illuminate\Support\Facades\Log::warning('Administratie door eigenaar verwijderd', ['company' => $company->id, 'name' => $name, 'user' => $user->email]);

        $purger->purge($company);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('flash', "De administratie \"{$name}\" is definitief verwijderd. Bedankt dat je EasyInvoice gebruikte.");
    }

    private function csv(array $rows): string
    {
        $out = "\xEF\xBB\xBF" . implode(';', array_keys($rows[0])) . "\n";
        foreach ($rows as $row) {
            $out .= implode(';', array_map(function ($v) {
                $v = is_array($v) || is_object($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;

                return '"' . str_replace('"', '""', $v) . '"';
            }, $row)) . "\n";
        }

        return $out;
    }
}
