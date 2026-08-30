@extends('layouts.marketing')

{{-- "Switch from {package}" — Lopra Polska, English version. Package data ($pkg: name, intro, export[], notes) and the package list ($packages) come from config/przenies.php (texts currently in Polish); $slug is the current package. --}}

@section('title', 'Switch from ' . $pkg['name'] . ' to ' . brand('name') . ' — in fifteen minutes')
@section('description', 'Move your clients, products and unpaid invoices from ' . $pkg['name'] . ' to ' . brand('name') . ': CSV or XLSX export, import with automatic column detection (NIP, name, due date, gross amount), no duplicates. Invoices ready for KSeF, your brand, a website and debt collection with Creditline Polska.')

@section('content')
<style>
  .pz-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px;}
  .pz-step{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;box-shadow:var(--shadow-sm);}
  .pz-step .nr{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:100px;background:var(--brand);color:#fff;font-weight:700;font-size:14px;margin-bottom:12px;}
  .pz-step h3{font-size:17px;margin:0 0 8px;}
  .pz-step p{color:var(--text-2);line-height:1.65;margin:0 0 8px;font-size:14.5px;}
  .pz-step ol{color:var(--text-2);line-height:1.6;margin:0;padding-left:18px;font-size:14px;}
  .pz-step li{margin-bottom:8px;}
  .pz-step a{color:var(--brand);font-weight:500;}
  .pz-table-wrap{overflow-x:auto;margin-top:22px;}
  .pz-table{width:100%;border-collapse:collapse;font-size:14.5px;background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;min-width:560px;}
  .pz-table th,.pz-table td{padding:13px 16px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);line-height:1.55;}
  .pz-table th{background:var(--surface-2);color:var(--text);font-weight:600;}
  .pz-table td:first-child{color:var(--text);font-weight:500;width:24%;}
  .pz-table tr:last-child td{border-bottom:none;}
  .pz-yes{color:var(--success);font-weight:600;}
  .pz-note{font-size:13px;color:var(--text-3);margin-top:12px;line-height:1.6;}
  .pz-cols{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
  .pz-faq{max-width:760px;}
  .pz-faq h3{font-size:16px;margin:22px 0 6px;}
  .pz-faq p{color:var(--text-2);line-height:1.7;margin:0;}
  .pz-others{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:24px;}
  .pz-others a{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px;font-weight:600;color:var(--text);transition:border-color .15s,transform .15s;}
  .pz-others a:hover{border-color:var(--brand);transform:translateY(-2px);}
  .pz-others small{display:block;font-weight:400;color:var(--text-3);font-size:12px;}
  @media (max-width:820px){.pz-steps{grid-template-columns:minmax(0,1fr);}.pz-others{grid-template-columns:1fr;}}
</style>

<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Switch from {{ $pkg['name'] }}</span>
    <h1>From {{ $pkg['name'] }} to {{ brand('name') }} in fifteen minutes</h1>
    <p class="lead">{{ $pkg['intro'] }} You move your clients, products and unpaid invoices yourself in the migration wizard — or send us the export and we do it for you.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Try it free for 14 days →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">See the demo</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Three steps, no retyping</h2>
      <p>You can leave the old program running until everything is in place. You lose nothing.</p>
    </div>
    <div class="pz-steps">
      <div class="pz-step">
        <div class="nr">1</div>
        <h3>Export from {{ $pkg['name'] }}</h3>
        <p>Save three CSV or XLSX files:</p>
        <ol>
          @foreach($pkg['export'] as $step)
            <li>{{ $step }}</li>
          @endforeach
        </ol>
      </div>
      <div class="pz-step">
        <div class="nr">2</div>
        <h3>Import into {{ brand('name') }}</h3>
        <p>After logging in, go to <strong>Settings → Migration</strong> (Ustawienia → Przenosiny) and upload the files. {{ brand('name') }} detects the columns automatically, shows a preview and skips duplicates. One click and your data is in place.</p>
        <p>The migration wizard is available in the app from the first day of your trial — <a href="{{ route('register') }}">create a free account</a> and you will find the link in the settings.</p>
      </div>
      <div class="pz-step">
        <div class="nr">3</div>
        <h3>Check and go</h3>
        <p>Review the client list and open invoices, set the numbering so that it continues from the last number in {{ $pkg['name'] }}, and issue your first invoice — with your logo, ready for KSeF (the national e-invoicing system). Payment reminders for the imported invoices start automatically.</p>
      </div>
    </div>
    <p class="pz-note">{{ $pkg['notes'] }}</p>
    <p class="pz-note">Rather not do it yourself? Send the export to <a href="mailto:{{ brand('email') }}" style="color:var(--brand);">{{ brand('email') }}</a> — we will migrate your data free of charge, usually the same working day.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>What you move</h2>
      <p>Three data sets are enough to work in {{ brand('name') }} from tomorrow. The rest — the history of paid invoices — stays in {{ $pkg['name'] }}.</p>
    </div>
    <div class="pz-table-wrap">
      <table class="pz-table">
        <thead><tr><th>Data</th><th>From the {{ $pkg['name'] }} file</th><th>In {{ brand('name') }}</th></tr></thead>
        <tbody>
          <tr>
            <td>Clients (contractors)</td>
            <td>Name, NIP, address, postcode, city, e-mail, phone</td>
            <td class="pz-yes">Complete client list; NIP (tax ID) verified against the VAT white list, duplicates skipped</td>
          </tr>
          <tr>
            <td>Products and services</td>
            <td>Name, net price, VAT rate, unit</td>
            <td class="pz-yes">A product list to drop onto invoices and quotes in one click</td>
          </tr>
          <tr>
            <td>Unpaid invoices</td>
            <td>Invoice number, client, issue date, due date, net, VAT, gross, amount paid</td>
            <td class="pz-yes">Invoices with the status "sent" and the correct due date — reminders, demand letters and debt collection work straight away</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h3 style="font-size:17px;margin-top:32px;">Columns {{ brand('name') }} recognises on its own</h3>
    <p class="pz-note" style="margin-top:6px;">Column names can be in Polish or English, in any order. Columns we don't need are simply not imported.</p>
    <div class="pz-cols">
      @foreach(['NIP', 'Name', 'Address', 'Postcode', 'City', 'E-mail', 'Phone', 'Invoice number', 'Issue date', 'Due date', 'Gross', 'Net', 'VAT', 'Paid'] as $col)
        <span class="value-pill">{{ $col }}</span>
      @endforeach
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container pz-faq">
    <div class="section-header" style="text-align:left;">
      <h2>Questions about switching</h2>
    </div>
    <h3>What about the history of paid invoices?</h3>
    <p>It stays in {{ $pkg['name'] }} — you don't need to move it to work in {{ brand('name') }}. Before closing the old account, download a full export from it (CSV/XLSX plus the PDF files) so that you have the documents in case of a tax audit.</p>
    <h3>How long do I have to keep old invoices?</h3>
    <p>As a rule, accounting documents must be kept for <strong>5 years, counting from the end of the calendar year in which the tax payment deadline expired</strong>. This obligation rests with you regardless of the software — so keep the export from {{ $pkg['name'] }} in a safe place. From the day you switch, {{ brand('name') }} stores your invoices and lets you download a full export at any time.</p>
    <h3>Will the invoice numbering continue?</h3>
    <p>Yes. In the numbering settings you enter the format (e.g. FV/2026/0001) and the starting number, so that new invoices follow on from the last one issued in {{ $pkg['name'] }}. The numbering sequence for the year remains unbroken.</p>
    <h3>Will the imported invoices go to KSeF?</h3>
    <p>Invoices issued in {{ $pkg['name'] }} have already been submitted to KSeF (the national e-invoicing system) from that program (if you were obliged to do so) — in {{ brand('name') }} they serve to track payment, not to be sent again. Every new invoice issued in {{ brand('name') }} comes with a ready FA-XML file for KSeF.</p>
    <h3>Can my accountant have access?</h3>
    <p>Yes, free of charge. You invite your accountant with the "read-only" role: they see invoices, expenses and the VAT summary per rate and can download a CSV export — but cannot change anything.</p>
    <h3>Am I tied in?</h3>
    <p>No. The subscription is monthly, you can cancel at any time, and you can always export your entire business in full (CSV, PDF, XML) — should you ever want to move on.</p>
    <div style="margin-top:28px;"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Try it free for 14 days →</a></div>
  </div>
</section>

<section class="section" style="padding-top:56px;padding-bottom:72px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:8px;">
      <h2 style="font-size:24px;">Using a different program?</h2>
      <p>We have also prepared migration guides for other popular programs.</p>
    </div>
    <div class="pz-others">
      @foreach($packages as $key => $other)
        @continue($key === $slug)
        <a href="{{ route('pl.przenies', $key) }}">
          <span>Switch from {{ $other['name'] }}<small>CSV / XLSX export → import into {{ brand('name') }}</small></span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endsection
