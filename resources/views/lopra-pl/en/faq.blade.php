@extends('layouts.marketing')

{{-- Frequently asked questions — Lopra Polska (APP_BRAND=lopra_pl), English edition. Content consistent with the landing page: KSeF, white list, VAT, import, brand, collection toolkit and invoice sale to sprzedamfakture.pl, pricing 49/79 zł net. --}}

@section('title', 'Frequently asked questions — ' . brand('name'))
@section('description', 'Answers to the most common questions about ' . brand('name') . ': KSeF, NIP and the VAT white list, VAT rates, import from Fakturownia, iFirma, wFirma and inFakt, brand and website, reminders, payment demands and selling unpaid invoices to sprzedamfakture.pl, pricing and data security.')

@php
  // One source for the visible list of questions and the FAQPage schema (rich results):
  // question + answer (the answer may contain simple HTML, e.g. <b>).
  $faqGroups = [
    'Getting started & account' => [
      ['What is ' . brand('name') . ' and who is it for?', brand('name') . ' is one tool for businesses in Poland: invoices ready for KSeF (the national e-invoicing system), quotes, VAT settlement, a visual identity, a digital business card and a website — plus reminders, a payment demand and the option to sell an unpaid invoice to sprzedamfakture.pl when a client does not pay. It suits sole traders, sp. z o.o. companies and small firms with a few people. No accounting knowledge needed: everything is explained in plain language, without jargon.'],
      ['What do I need to get started?', 'Just your NIP (tax ID) and an e-mail address. Once you enter your NIP, ' . brand('name') . ' fetches your company name, address and REGON from the VAT taxpayer register (the white list), sets up invoice numbering and a default VAT rate. You will issue your first invoice within minutes, and have your brand, business card and website ready in a quarter of an hour.'],
      ['Can I try ' . brand('name') . ' for free?', 'Yes. The first 14 days are free, with every feature — including those in the Smart plan (AI). No card or payment details required. When the trial ends you choose a subscription or simply stop using it; your data stays available for download.'],
      ['Can I invite colleagues?', 'Yes, and extra users cost nothing. You give each person a role: administrator (everything), employee (day-to-day work without settings and reports) or accountant (read-only). You can also run several companies or brands on a single account.'],
    ],
    'Invoices & KSeF' => [
      ['Does ' . brand('name') . ' support KSeF?', 'Yes. Every invoice issued in ' . brand('name') . ' comes with an XML file in the FA structure required by KSeF (the national e-invoicing system). You download it with one click, submit it to KSeF and store the assigned KSeF number alongside the invoice. <b>Direct submission from within the app</b> (token authorisation) is our next step — once it has been tested on the Ministry of Finance test environment.'],
      ['Do the invoices comply with Polish regulations?', 'Yes. Invoices are numbered consecutively in the format you choose (e.g. FV/2026/0001) and contain every element required by the Polish VAT Act, including the NIP of both parties, the issue and sale dates, VAT rates and amounts per rate, and annotations such as "metoda kasowa" (cash accounting) or the legal basis for a VAT exemption. Correcting invoices and advance invoices are supported too.'],
      ['Does ' . brand('name') . ' verify the NIP of my clients?', 'Yes. When you add a client, ' . brand('name') . ' verifies the NIP (tax ID) against the VAT taxpayer register (the white list): it checks the checksum and VAT status and fetches the name and address. That way you avoid typos in the details and know whether your client is an active VAT payer.'],
      ['My invoices are in Fakturownia, iFirma, wFirma or inFakt. How do I switch?', 'Export your clients, products and unpaid invoices to CSV or XLSX and upload the files in the migration wizard. ' . brand('name') . ' recognises Polish column names (NIP, name, address, due date, net, gross…) and skips duplicates. Leave the history of paid invoices in your old software. Step-by-step instructions are on the pages <a href="' . route('pl.przenies', 'fakturownia') . '">Switch from Fakturownia</a>, <a href="' . route('pl.przenies', 'ifirma') . '">from iFirma</a>, <a href="' . route('pl.przenies', 'wfirma') . '">from wFirma</a> and <a href="' . route('pl.przenies', 'infakt') . '">from inFakt</a>.'],
    ],
    'VAT & your accountant' => [
      ['Which VAT rates does ' . brand('name') . ' support?', 'All rates that apply in Poland: 23%, 8%, 5% and 0%, plus VAT-exempt items ("zw") and reverse charge. You choose the rate per invoice line, and output and input VAT are totalled per rate — in the layout your JPK_V7 VAT return needs.'],
      ['I am VAT-exempt. Is ' . brand('name') . ' for me?', 'Yes. Tick the subjective exemption (Art. 113 of the VAT Act) or an objective exemption in the settings — your invoices will carry the correct annotation and legal basis, and amounts will be issued without VAT. When you pass the threshold and become a VAT payer, you switch a single setting.'],
      ['Can my accountant have access?', 'Yes, free of charge. Invite your accountant with a "read-only" role: they see every invoice, expense and VAT settlement and can download CSV exports and summaries, but cannot change anything. Every month or quarter the VAT settlement per rate is ready and waiting — with a reminder before the 25th of the month.'],
    ],
    'Brand & website' => [
      ['I have no logo or colours. Can ' . brand('name') . ' help with that?', 'Yes. On the Smart plan you answer four questions (what you do, for whom, which style, which colours) and the AI proposes three visual identities: logo, colour palette, font, invoice template and slogan. One click applies everything to your invoices, business card and website. Already have a logo? Upload it — we will match the rest.'],
      ['What are the digital business card and website in ' . brand('name') . '?', 'The digital business card is a public page with your contact details, "call", "e-mail" and "WhatsApp" buttons, a QR code and save-to-contacts (vCard). The website is a complete one-page site for your business in your own identity: services, why you, about us and a contact form. Messages from the form land in ' . brand('name') . ' as enquiries (leads). Both are included in the Podstawowy plan; on the Smart plan the AI writes the website copy for you.'],
    ],
    'Collection toolkit and selling invoices' => [
      ['What happens when a client does not pay?', 'First, ' . brand('name') . ' sends reminders and dunning notices on your behalf (e-mail, SMS) with a link to pay by BLIK or Przelewy24. If that does not work, one click creates a formal wezwanie do zapłaty (payment demand) as a PDF, with statutory interest for late payment (14%) and the 40/70/100 EUR recovery compensation. Then you choose: pursue the claim yourself, with the demand as your pre-court step, or sell the invoice to sprzedamfakture.pl straight from ' . brand('name') . ' — no retyping of data.'],
      ['How much does chasing an unpaid invoice cost?', 'Reminders, dunning notices and the payment demand are included in your subscription. Selling an invoice to sprzedamfakture.pl involves <b>no fees and no upfront costs</b>: you receive a purchase offer with the price before you decide anything. If you accept, the agreed amount is paid out and the buyer takes over the risk and any further recovery. You always know what you are getting before you sell.'],
      ['Can I sell an unpaid invoice?', 'Yes. Submit the unpaid B2B invoice for purchase straight from the invoice in ' . brand('name') . '. sprzedamfakture.pl — our invoice-purchase partner — sends a purchase offer within <b>one working day</b>. Accept, and the money is paid out; from then on the risk and any further recovery rest with the buyer. The decision is always yours.'],
      ['How do I calculate late-payment interest and compensation?', 'Use the free <a href="' . route('pl.kalkulator') . '">interest and compensation calculator</a>: enter the invoice amount and due date, and the calculator works out the days overdue, statutory interest for late payment in commercial transactions and the recovery compensation under Art. 10 of the Act. You can generate a payment demand straight away.'],
    ],
    'Pricing & security' => [
      ['How much does ' . brand('name') . ' cost after the trial?', '<b>Podstawowy</b>: 49 zł net (60.27 zł gross) per month — unlimited invoices, KSeF, VAT, business card, website and the collection toolkit. <b>Smart</b>: 79 zł net (97.17 zł gross) per month — everything in Podstawowy plus an AI visual identity, AI website copy, expenses from a photo, quotes from text and priority purchase quotes from sprzedamfakture.pl. No hidden fees, no client limits.'],
      ['Am I tied to a fixed-term contract?', 'No. The subscription is monthly and you can cancel at any time, effective at the end of the current billing period. Each month you receive a VAT invoice for the subscription. Pay by BLIK, card or bank transfer.'],
      ['Where is my data stored and is it secure?', 'Data is stored on servers in the European Union (Amsterdam), backed up daily, and the connection is encrypted (TLS). You can enable two-factor authentication (2FA). We process data in line with the GDPR (RODO) — see the <a href="' . route('pl.prywatnosc') . '">privacy policy</a> for details.'],
      ['Can I take my data with me if I leave?', 'Yes, at any time. Export your whole company to CSV (clients, products, invoices, expenses), and download the FA-XML files and invoice PDFs separately. You can also delete your account — your data belongs to you.'],
    ],
  ];

  $faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqGroups)->flatten(1)->map(fn ($item) => [
      '@type' => 'Question',
      'name' => $item[0],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item[1])],
    ])->values()->all(),
  ];
@endphp

@section('content')
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Frequently asked questions</div>
    <h1>Questions and answers</h1>
    <p class="lead">Everything businesses most often ask before getting started with {{ brand('name') }}. Can't find your question? <a href="{{ route('pl.kontakt') }}" style="color:var(--brand);font-weight:600;">Write to us</a> — we reply within one working day.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="faq-list">
      @foreach($faqGroups as $groupTitle => $items)
        <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:{{ $loop->first ? '8px' : '32px' }} 0 14px;">{{ $groupTitle }}</h2>
        @foreach($items as [$question, $answer])
          <details class="faq-item">
            <summary>{{ $question }} <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
            <div class="faq-content">{!! $answer !!}</div>
          </details>
        @endforeach
      @endforeach
    </div>

    <div style="text-align:center;margin-top:40px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Didn't find your answer?</p>
      <a href="{{ route('pl.kontakt') }}" class="btn btn-primary">Contact us</a>
      <a href="{{ route('register') }}" class="btn btn-secondary" style="margin-left:8px;">Try 14 days free</a>
    </div>
  </div>
</section>
@endsection
