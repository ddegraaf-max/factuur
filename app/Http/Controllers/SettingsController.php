<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    // ----- COMPANY / BEDRIJFSGEGEVENS -----
    public function company()
    {
        return Inertia::render('Settings/Company', [
            'company' => auth()->user()->company,
        ]);
    }

    public function updateCompany(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'kvk_number' => ['nullable', 'string', 'max:20'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'iban' => ['nullable', 'string', 'max:34'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            // New preference fields — all optional so older forms keep working
            'price_mode' => ['nullable', 'in:excl,incl'],
            'fiscal_year_start' => ['nullable', 'integer', 'min:1', 'max:12'],
            'default_send_method' => ['nullable', 'in:email,post,both'],
            'results_per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'copy_email' => ['nullable', 'email'],
            'daily_notification_enabled' => ['nullable', 'boolean'],
            'daily_notification_email' => ['nullable', 'email'],
            'default_payment_terms' => ['required', 'integer', 'min:0', 'max:365'],
            // Legacy invoice fields still accepted from older Company form
            'invoice_footer' => ['nullable', 'string'],
            'invoice_number_format' => ['nullable', 'string', 'max:50'],
            'brand_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        // Drop nulls so we don't overwrite existing values with null
        $data = array_filter($data, fn ($v) => $v !== null);

        auth()->user()->company->update($data);
        return back()->with('flash', 'Bedrijfsgegevens opgeslagen.');
    }

    // ----- NUMBERING -----
    public function numbering()
    {
        $company = auth()->user()->company;
        return Inertia::render('Settings/Numbering', [
            'numbering' => $company->resolved_numbering,
        ]);
    }

    public function updateNumbering(Request $request)
    {
        $data = $request->validate([
            'numbering' => 'required|array',
            'numbering.*.prefix' => 'nullable|string|max:10',
            'numbering.*.start' => 'required|integer|min:1',
        ]);
        auth()->user()->company->update(['numbering_settings' => $data['numbering']]);
        return back()->with('flash', 'Nummering opgeslagen.');
    }

    // ----- BRAND / HUISSTIJL -----
    public function brand()
    {
        return Inertia::render('Settings/Brand', [
            'company' => auth()->user()->company,
        ]);
    }

    public function updateBrand(Request $request)
    {
        $data = $request->validate([
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'invoice_template' => ['required', 'in:modern,classic,minimal'],
            'invoice_font' => ['required', 'in:sans,serif'],
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        // Optional logo upload — store as base64 data URL in DB (survives Railway redeploys)
        if ($request->hasFile('logo')) {
            $request->validate(['logo' => 'image|mimes:png,jpg,jpeg,svg,webp|max:2048']);
            $file = $request->file('logo');
            $mime = $file->getMimeType();
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $data['logo_data'] = 'data:' . $mime . ';base64,' . $base64;
            $data['logo_path'] = null; // clear old path-based logo
        }

        auth()->user()->company->update($data);
        return back()->with('flash', 'Huisstijl opgeslagen.');
    }

    public function removeLogo()
    {
        $company = auth()->user()->company;
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }
        $company->update(['logo_path' => null, 'logo_data' => null]);
        return back()->with('flash', 'Logo verwijderd.');
    }

    // ----- REMINDERS -----
    public function reminders()
    {
        $company = auth()->user()->company;
        return Inertia::render('Settings/Reminders', [
            'reminders' => $company->resolved_reminders,
            'default_payment_terms' => (int) $company->default_payment_terms,
        ]);
    }

    public function updateReminders(Request $request)
    {
        $data = $request->validate([
            'payment_term_reminder' => 'required|integer|min:0|max:60',
            'payment_term_warning' => 'required|integer|min:0|max:60',
            'num_reminders' => 'required|integer|min:0|max:5',
            'second_reminder_email' => 'required|in:first,custom',
            'negative_outstanding' => 'boolean',
            'reminder_delay' => 'required|integer|min:0|max:30',
            'warning_delay' => 'required|integer|min:0|max:30',
        ]);
        auth()->user()->company->update(['reminder_settings' => $data]);
        return back()->with('flash', 'Herinneringen opgeslagen.');
    }
}
