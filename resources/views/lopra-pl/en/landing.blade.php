@extends('layouts.marketing')

{{-- Lopra Polska home page, English version (APP_BRAND=lopra_pl): invoices, brand, website and a collection toolkit with invoice sale to sprzedamfakture.pl — from day one to an established business. --}}

@section('title', 'Lopra — invoices, brand and unpaid-invoice recovery in one place, from 49 zł a month')
@section('description', 'The complete tool for businesses in Poland: KSeF-ready invoices, quotes, VAT returns, AI-designed brand identity, digital business card and website, import from Fakturownia, iFirma, wFirma and inFakt, automatic reminders and payment demands, and the option to sell unpaid invoices to sprzedamfakture.pl. 14 days free.')

@push('styles')
@include('lopra.partials.landing-styles')
<style>
  /* Windykacja: ciemna sekcja z trzema krokami */
  .wd { background: linear-gradient(160deg, #1C4E7A 0%, #163E62 55%, #132F49 100%); color: #fff; padding: 88px 0; position: relative; overflow: hidden; }
  .wd::before { content: ''; position: absolute; width: 700px; height: 700px; border-radius: 50%; right: -260px; top: -320px; background: radial-gradient(circle, rgba(224,165,92,0.22) 0%, rgba(224,165,92,0) 62%); }
  .wd .eyebrow { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.18); color: #E0A55C; }
  .wd .eyebrow::before { background: #E0A55C; box-shadow: 0 0 0 4px rgba(224,165,92,0.2); }
  .wd h2 { color: #fff; font-size: clamp(28px, 3.6vw, 42px); max-width: 720px; }
  .wd .lead { color: rgba(255,255,255,0.8); font-size: 17px; max-width: 640px; margin: 14px 0 40px; line-height: 1.6; }
  .wd-grid { display: grid; grid-template-columns: 1.15fr 1fr; gap: 44px; align-items: start; position: relative; }
  .wd-steps { display: grid; gap: 14px; }
  .wd-step { display: flex; gap: 16px; align-items: flex-start; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 18px 20px; }
  .wd-step .nr { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; background: #E0A55C; color: #132F49; font-family: var(--font-display); font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .wd-step b { display: block; font-size: 16px; margin-bottom: 4px; }
  .wd-step p { margin: 0; font-size: 14px; color: rgba(255,255,255,0.75); line-height: 1.55; }
  .wd-step .tag { display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #E0A55C; }
  .wd-card { background: #fff; color: var(--text); border-radius: 20px; padding: 26px 28px; box-shadow: 0 30px 80px rgba(0,0,0,0.3); }
  .wd-card .cap { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-3); margin-bottom: 12px; display: flex; justify-content: space-between; }
  .wd-card .pill { font-size: 10.5px; font-weight: 700; color: #B45309; background: #FEF3C7; border-radius: 100px; padding: 3px 9px; }
  .wd-card table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .wd-card td { padding: 9px 0; border-top: 1px solid var(--border); }
  .wd-card td.r { text-align: right; font-family: var(--font-mono); font-size: 13px; }
  .wd-card tr.tot td { border-top: 2px solid var(--brand-darker); font-weight: 700; font-size: 15px; }
  .wd-card .acts { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
  .wd-card .acts span { font-size: 12.5px; font-weight: 600; padding: 9px 13px; border-radius: 9px; border: 1px solid var(--border-strong); }
  .wd-card .acts span.main { background: var(--accent); color: #fff; border-color: var(--accent); }
  .wd-partner { display: flex; align-items: center; gap: 14px; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.8); font-size: 14px; }
  .wd-partner .lg { width: 44px; height: 44px; border-radius: 12px; background: #E0A55C; color: #132F49; font-family: var(--font-display); font-weight: 700; font-size: 15px; letter-spacing: 0.02em; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .wd-partner b { color: #fff; display: block; }
  @media (max-width: 900px) { .wd-grid { grid-template-columns: 1fr; } }

  /* Import: cztery programy */
  .imp { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 30px; }
  .imp a { display: flex; align-items: center; gap: 12px; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 18px; font-weight: 600; color: var(--text); transition: border-color 0.15s, transform 0.15s; }
  .imp a:hover { border-color: var(--brand); transform: translateY(-2px); }
  .imp .lg { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 15px; flex-shrink: 0; }
  .imp small { display: block; font-weight: 400; color: var(--text-3); font-size: 12px; }
  @media (max-width: 900px) { .imp { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 520px) { .imp { grid-template-columns: 1fr; } }

  /* KSeF-badge in mockup en kaartach */
  .ksef { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; color: #065F46; background: #D1FAE5; border-radius: 100px; padding: 3px 8px; }
</style>
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">For businesses in Poland — from start-up to established company</div>
    <h1>Your whole business <span class="accent">in one place.</span></h1>
    <p class="hero-sub">
      Invoices ready for KSeF (the national e-invoicing system), quotes, VAT returns, brand identity, your own website — and, when a client won't pay, automatic reminders, a formal payment demand and the option to sell the invoice to sprzedamfakture.pl. One tool, one subscription, zero accounting jargon.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Try 14 days free
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">See the demo</a>
    </div>
    <div class="hero-trust">
      No card needed · 14 days free · then from <b>49 zł net / month</b>
    </div>

    <!-- PRODUCT MOCKUP — clickable menu, screens change automatically -->
    <div class="app-mockup-wrap">
      <div class="lp-float one" aria-hidden="true">
        <div class="ic" style="background:#D1FAE5;color:#065F46;">✓</div>
        <div><b>Invoice accepted in KSeF</b><span>FV/2026/0007 · KSeF number assigned</span></div>
      </div>
      <div class="lp-float two" aria-hidden="true">
        <div class="ic" style="background:var(--accent-tint);color:var(--accent-dark);">₿</div>
        <div><b>1 210,00 zł paid with BLIK</b><span>Invoice FV/2026/0006 · just now</span></div>
      </div>

      <div class="app-mockup" id="lpDash">
        <div class="mock-chrome">
          <div class="mock-dot red"></div>
          <div class="mock-dot yellow"></div>
          <div class="mock-dot green"></div>
          <div class="mock-url" id="lpUrl">{{ brand('domain') }}/dashboard</div>
        </div>
        <div class="d-body">
          <aside class="d-side">
            <div class="d-brand"><img src="/brand/lopra/lopra-mark-white.svg" alt=""><span>Lopra</span></div>
            <div class="d-navlabel">Overview</div>
            <div class="d-navitem active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>Dashboard</div>
            <div class="d-navlabel">Sales</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Invoices</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg>Debt collection</div>
            <div class="d-navlabel">Your brand</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>Brand identity</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>Business card</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Website</div>
          </aside>

          <div class="d-main">
            <div class="d-progress"><i id="lpProgress"></i></div>

            <!-- SCREEN 1: Dashboard -->
            <section class="d-screen active" data-screen="pulpit">
              <div class="d-topbar d-anim" style="--d:.05s">
                <div>
                  <div class="d-greet">Good morning, Anna</div>
                  <div class="d-sub">Your website brought in 3 enquiries this week, 2 invoices were paid, 1 is ready for a payment demand.</div>
                </div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>New invoice</div>
              </div>
              <div class="d-kpis">
                <div class="d-kpi d-anim" style="--d:.12s"><div class="d-kpi-label">Outstanding</div><div class="d-kpi-value" data-count="5240" data-suffix=" zł">5 240 zł</div><div class="d-kpi-meta">3 invoices</div></div>
                <div class="d-kpi d-anim" style="--d:.19s"><div class="d-kpi-label">Paid this month</div><div class="d-kpi-value" data-count="13180" data-suffix=" zł">13 180 zł</div><div class="d-kpi-meta"><span class="up">↑ 24%</span> vs last month</div></div>
                <div class="d-kpi hi d-anim" style="--d:.26s"><span class="d-dot"></span><div class="d-kpi-label">Overdue</div><div class="d-kpi-value copper" data-count="2420" data-suffix=" zł">2 420 zł</div><div class="d-kpi-meta">1 invoice · 44 days · demand ready</div></div>
                <div class="d-kpi d-anim" style="--d:.33s"><div class="d-kpi-label">VAT for September</div><div class="d-kpi-value" data-count="2818" data-suffix=" zł">2 818 zł</div><div class="d-kpi-meta">JPK_V7 return due 25 October</div></div>
              </div>
              <div class="d-two">
                <div class="d-card d-anim" style="--d:.4s">
                  <div class="d-card-head"><div><div class="d-card-title">Monthly revenue</div><div class="d-card-sub">Net · since launch in February</div></div><span class="d-card-link">Report →</span></div>
                  <div class="d-bars">
                    <div class="d-bar-col"><div class="d-bar" style="--h:22%;--d:.45s"></div><span>Feb</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:38%;--d:.50s"></div><span>Mar</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:47%;--d:.55s"></div><span>Apr</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:44%;--d:.60s"></div><span>May</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:63%;--d:.65s"></div><span>Jun</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:71%;--d:.70s"></div><span>Jul</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:66%;--d:.75s"></div><span>Aug</span></div>
                    <div class="d-bar-col"><div class="d-bar tall" style="--h:92%;--d:.80s"></div><span>Sep</span></div>
                  </div>
                </div>
                <div class="d-card d-anim" style="--d:.48s">
                  <div class="d-card-head"><div><div class="d-card-title">Website enquiries</div><div class="d-card-sub">Form → lead → quote</div></div></div>
                  <div class="d-lead"><span class="d-avatar">RN</span><div class="who"><div class="t">Rodzina Nowak</div><div class="s">Living room and kitchen design</div></div><span class="when">2 hrs</span></div>
                  <div class="d-lead"><span class="d-avatar">KP</span><div class="who"><div class="t">Kawiarnia Pod Lipą</div><div class="s">New garden terrace layout</div></div><span class="when">yesterday</span></div>
                  <div class="d-lead"><span class="d-avatar">GZ</span><div class="who"><div class="t">Gabinet Zdrowie</div><div class="s">Waiting room redesign</div></div><span class="when">3 days</span></div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.56s">
                <div class="d-card-head"><div class="d-card-title">Recent invoices</div><span class="d-card-link">All →</span></div>
                <table class="d-table">
                  <tbody>
                    <tr><td class="mono">FV/2026/0007</td><td>Studio Północ</td><td class="d-hide">12 Sep</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill green">Paid</span></td><td class="mono right">1 210,00 zł</td></tr>
                    <tr><td class="mono">FV/2026/0006</td><td>Rodzina Wiśniewska</td><td class="d-hide">9 Sep</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill blue">Sent</span></td><td class="mono right">2 640,00 zł</td></tr>
                    <tr><td class="mono">FV/2026/0004</td><td>TransLog Polska Sp. z o.o.</td><td class="d-hide">28 Jul</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill amber">44 days overdue</span></td><td class="mono right">2 420,00 zł</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCREEN 2: Invoices -->
            <section class="d-screen" data-screen="faktury">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Invoices</div><div class="d-sub">7 invoices · 5 240 zł outstanding · each with your logo, ready for KSeF</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>New invoice</div>
              </div>
              <div class="d-tabs d-anim" style="--d:.09s"><span class="d-tab active">All</span><span class="d-tab">Drafts</span><span class="d-tab">Sent</span><span class="d-tab">Paid</span><span class="d-tab">Overdue</span></div>
              <div class="d-card d-anim" style="--d:.14s">
                <table class="d-table">
                  <thead><tr><th>Number</th><th>Client</th><th class="d-hide">Issued</th><th class="d-hide">Due</th><th>KSeF</th><th>Status</th><th class="right">Amount</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.18s"><td class="mono">FV/2026/0007</td><td>Studio Północ</td><td class="d-hide">12.09</td><td class="d-hide">26.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Paid</span></td><td class="mono right">1 210,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.24s"><td class="mono">FV/2026/0006</td><td>Rodzina Wiśniewska</td><td class="d-hide">09.09</td><td class="d-hide">23.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill blue">Sent</span></td><td class="mono right">2 640,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.30s"><td class="mono">FV/2026/0005</td><td>Kawiarnia Pod Lipą</td><td class="d-hide">02.09</td><td class="d-hide">16.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill amber">Partly paid</span></td><td class="mono right">4 920,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.36s"><td class="mono">FV/2026/0004</td><td>TransLog Polska Sp. z o.o.</td><td class="d-hide">28.07</td><td class="d-hide">11.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill copper">Overdue</span></td><td class="mono right">2 420,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.42s"><td class="mono">FV/2026/0003</td><td>Gabinet Zdrowie</td><td class="d-hide">14.08</td><td class="d-hide">28.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Paid</span></td><td class="mono right">3 690,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.48s"><td class="mono">FV/2026/0002</td><td>Studio Północ</td><td class="d-hide">03.08</td><td class="d-hide">17.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Paid</span></td><td class="mono right">980,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.54s"><td class="mono">— draft —</td><td>Rodzina Nowak</td><td class="d-hide">—</td><td class="d-hide">—</td><td>—</td><td><span class="d-pill gray">Draft</span></td><td class="mono right">5 150,00 zł</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.6s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div><div><span class="big">KSeF</span><span>FA XML submitted in one click</span></div></div>
                <div class="d-tile d-anim" style="--d:.66s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div><span class="big">BLIK · P24</span><span>payment link on every invoice</span></div></div>
                <div class="d-tile d-anim" style="--d:.72s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><span class="big">9 days</span><span>average time to payment</span></div></div>
              </div>
            </section>

            <!-- SCREEN 3: Debt collection -->
            <section class="d-screen" data-screen="windykacja">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Debt collection</div><div class="d-sub">FV/2026/0004 · TransLog Polska Sp. z o.o. · 44 days overdue</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg>Sell the invoice</div>
              </div>
              <div class="d-two even">
                <div class="d-card d-anim" style="--d:.1s" style="margin:0;">
                  <div class="d-card-head"><div><div class="d-card-title">Claim as of today</div><div class="d-card-sub">Statutory interest 14% · Art. 10 compensation</div></div></div>
                  <table class="d-table">
                    <tbody>
                      <tr><td>Principal amount</td><td class="mono right">2 420,00 zł</td></tr>
                      <tr><td>Interest (14% × 44 days)</td><td class="mono right">40,84 zł</td></tr>
                      <tr><td>Compensation (40 EUR)</td><td class="mono right">172,00 zł</td></tr>
                      <tr><td><b>Total</b></td><td class="mono right"><b>2 632,84 zł</b></td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="d-card d-anim" style="--d:.18s">
                  <div class="d-card-head"><div><div class="d-card-title">What we have already done</div><div class="d-card-sub">automatically, on your behalf</div></div></div>
                  <div class="d-lead"><span class="d-avatar">1</span><div class="who"><div class="t">E-mail reminder</div><div class="s">3 days overdue · opened</div></div><span class="when">14.08</span></div>
                  <div class="d-lead"><span class="d-avatar">2</span><div class="who"><div class="t">Final reminder + SMS</div><div class="s">14 days overdue</div></div><span class="when">25.08</span></div>
                  <div class="d-lead"><span class="d-avatar">3</span><div class="who"><div class="t">Payment demand (PDF)</div><div class="s">with interest and compensation</div></div><span class="when">today</span></div>
                </div>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.3s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><b>Payment demand</b><span>ready to send, 7-day deadline</span></div></div>
                <div class="d-tile d-anim" style="--d:.36s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><b>Interest calculator</b><span>14% p.a. + 40/70/100 EUR compensation</span></div></div>
                <div class="d-tile d-anim" style="--d:.42s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><b>Sell the invoice</b><span>sprzedamfakture.pl · offer within 1 working day</span></div></div>
              </div>
            </section>

            <!-- SCREEN 4: Brand identity -->
            <section class="d-screen" data-screen="identyfikacja">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Brand identity</div><div class="d-sub">Three proposals based on: interior designer · private clients and hospitality · warm and personal</div></div>
                <div class="d-ai">✦ Propose again</div>
              </div>
              <div class="d-props">
                <div class="d-prop chosen d-anim" style="--d:.12s">
                  <span class="badge">Chosen</span>
                  <div class="d-logo" style="background:#2E4A3F;">SW</div>
                  <div class="n">Warm &amp; artisanal</div>
                  <div class="f">Fraunces + Instrument Sans</div>
                  <div class="d-swatches"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i><i style="background:#1F1D1A"></i></div>
                  <div class="slogan">“Interiors that suit you.”</div>
                  <span class="btn-mini">✓ Use this identity</span>
                </div>
                <div class="d-prop d-anim" style="--d:.2s">
                  <div class="d-logo" style="background:#1F3A5F;">SW</div>
                  <div class="n">Modern &amp; clean</div>
                  <div class="f">Manrope</div>
                  <div class="d-swatches"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i><i style="background:#101418"></i></div>
                  <div class="slogan">“Interiors with character.”</div>
                  <span class="btn-mini">Choose</span>
                </div>
                <div class="d-prop d-anim" style="--d:.28s">
                  <div class="d-logo" style="background:#7A4B2B;">sw.</div>
                  <div class="n">Natural &amp; calm</div>
                  <div class="f">Lora + Karla</div>
                  <div class="d-swatches"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i><i style="background:#2B2723"></i></div>
                  <div class="slogan">“Home, rediscovered.”</div>
                  <span class="btn-mini">Choose</span>
                </div>
              </div>
              <div class="d-note d-fade" style="--d:.4s">✦ <b>Created by AI from four answers.</b> One click sets the colours, font, template and logo on your invoices, business card and website. Everything can be changed.</div>
              <div class="d-styled">
                <div class="d-anim" style="--d:.46s">
                  <div class="lbl">Your invoice</div>
                  <div class="d-inv">
                    <div class="top"><span class="t">Invoice FV/2026/0007</span><span class="lg">SW</span></div>
                    <div class="row"><span>Living room design</span><span>1 500,00 zł</span></div>
                    <div class="row"><span>Styling day</span><span>500,00 zł</span></div>
                    <div class="row tot"><span>Total gross (23%)</span><span>2 460,00 zł</span></div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.52s">
                  <div class="lbl">Your business card</div>
                  <div class="d-vcard mini">
                    <div class="who"><div class="av">A</div><div><div class="nm">Anna Kowalska</div><div class="rl">Interior designer</div></div></div>
                    <div class="ln">+48 600 123 456</div>
                    <div class="ln site">See my website →</div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.58s">
                  <div class="lbl">Your website</div>
                  <div class="d-site mini">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="hero"><div class="h">Interiors that suit you.</div><div class="p">Interior design for people who love coming home.</div><span class="b">Book a consultation</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
            </section>

            <!-- SCREEN 5: Business card -->
            <section class="d-screen" data-screen="wizytowka">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Digital business card</div><div class="d-sub">{{ brand('domain') }}/k/studio-wnetrz-kowalska · share the link or let people scan the QR code</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>Full name</label><div class="val">Anna Kowalska</div></div>
                  <div class="d-field"><label>Job title</label><div class="val">Interior designer</div></div>
                  <div class="d-field"><label>Slogan</label><div class="val">Interiors that suit you.</div></div>
                  <div class="d-field"><label>WhatsApp</label><div class="val">+48 600 123 456</div></div>
                  <div class="d-field"><label>Button</label><div class="val">See my website → {{ brand('domain') }}/s/studio-wnetrz-kowalska</div></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Preview</span><span>In your brand identity</span></div>
                  <div class="d-vcard">
                    <div class="qr"><i></i><i></i><i class="o"></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i></div>
                    <div class="who"><div class="av">A</div><div><div class="nm">Anna Kowalska</div><div class="rl">Interior designer · Studio Wnętrz Kowalska</div></div></div>
                    <div class="ln">+48 600 123 456</div>
                    <div class="ln">anna@studiokowalska.pl</div>
                    <div class="ln site">See my website →</div>
                  </div>
                </div>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.3s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg></div><div><b>Download QR code</b><span>PNG or SVG, for quotes and stamps</span></div></div>
                <div class="d-tile d-anim" style="--d:.36s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div><div><b>Copy link</b><span>send via WhatsApp or e-mail</span></div></div>
                <div class="d-tile d-anim" style="--d:.42s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div><div><b>Save to contacts</b><span>vCard with one tap</span></div></div>
              </div>
            </section>

            <!-- SCREEN 6: Website -->
            <section class="d-screen" data-screen="strona">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Website</div><div class="d-sub">{{ brand('domain') }}/s/studio-wnetrz-kowalska · messages reach you as leads</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>What do you do?</label><div class="val multi">Interior design and styling for homes and small hospitality venues.</div></div>
                  <div class="d-field"><label>For whom?</label><div class="val multi">Private clients and businesses in Kraków and the surrounding area.</div></div>
                  <div class="d-field"><label>Why you?</label><div class="val multi">Personal, on budget and done in three weeks.</div></div>
                  <div class="d-field"><label>Style</label><div class="val">Warm and personal</div></div>
                  <div><span class="d-ai">✦ Write the copy</span></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Preview</span><span>Fully editable</span></div>
                  <div class="d-site">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="nav">Studio Wnętrz Kowalska<div><span></span><span></span><span></span></div></div>
                    <div class="hero"><div class="h">Interiors that suit you.</div><div class="p">Interior design for people who love coming home — personal, on budget, in three weeks.</div><span class="b">Book a consultation</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.3s; margin-top: 12px;">
                <div class="d-card-head"><div><div class="d-card-title">Website enquiries</div><div class="d-card-sub">Form → lead → quote, no retyping</div></div><span class="d-pill copper">3 this week</span></div>
                <div class="d-lead"><span class="d-avatar">RN</span><div class="who"><div class="t">Rodzina Nowak</div><div class="s">“Could you help us with the living room and kitchen?”</div></div><span class="when">2 hrs · quote sent</span></div>
                <div class="d-lead"><span class="d-avatar">KP</span><div class="who"><div class="t">Kawiarnia Pod Lipą</div><div class="s">“We are opening a garden terrace in March and need a designer.”</div></div><span class="when">yesterday</span></div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHY LOPRA -->
<section class="section" id="dlaczego">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Why Lopra</div>
      <h2>One tool instead of four.</h2>
      <p>Invoicing software, a logo designer, a website builder and a tool for chasing unpaid invoices — normally four subscriptions and four logins. In Lopra it is one place, and everything works together.</p>
    </div>

    <div class="lp-vcards">
      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-inv">
            <div class="top"><div class="t">Invoice FV/2026/0007</div><span class="lg">SW</span></div>
            <div class="row"><span>Living room design</span><span>1 500,00 zł</span></div>
            <div class="row"><span>VAT 23%</span><span>345,00 zł</span></div>
            <div class="row tot"><span>Total gross</span><span>1 845,00 zł</span></div>
          </div>
          <span class="lp-mini-badge">✓ Accepted in KSeF</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Invoices &amp; VAT</span>
          <h3>KSeF and VAT without the stress</h3>
          <p>VAT invoices, credit notes and quotes with online signature. Every invoice comes with a ready FA-XML file for KSeF, and your VAT return (output and input VAT per rate 23/8/5/0) is waiting for you every month — laid out like the JPK_V7 VAT return. Your accountant gets free read-only access.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-props">
            <div class="lp-mini-prop"><span class="lg" style="background:#1F3A5F;">SW</span><div>Modern &amp; clean<small>Manrope · “Interiors with character.”</small></div><div class="sw"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i></div></div>
            <div class="lp-mini-prop on"><span class="lg" style="background:#2E4A3F;">SW</span><div>Warm &amp; artisanal <span class="ok">✓ Chosen</span><small>Fraunces · “Interiors that suit you.”</small></div><div class="sw"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i></div></div>
            <div class="lp-mini-prop"><span class="lg" style="background:#7A4B2B;">sw.</span><div>Natural &amp; calm<small>Lora · “Home, rediscovered.”</small></div><div class="sw"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i></div></div>
          </div>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Brand &amp; website</span>
          <h3>A brand and website in fifteen minutes</h3>
          <p>No logo or colours yet? Answer four questions — AI proposes three identities with a logo, palette, font and slogan. One click and your invoices, digital business card and website look like they came from an agency. Already have a logo? Upload it and we will match the rest.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-inv" style="transform:rotate(-1.5deg);">
            <div class="top"><div class="t">Payment demand</div><span class="lg" style="background:#ec3013;">C</span></div>
            <div class="row"><span>Principal amount</span><span>2 420,00 zł</span></div>
            <div class="row"><span>Interest 14% × 44 days</span><span>40,84 zł</span></div>
            <div class="row"><span>Compensation (40 EUR)</span><span>172,00 zł</span></div>
            <div class="row tot"><span>Total</span><span>2 632,84 zł</span></div>
          </div>
          <span class="lp-mini-badge" style="border-color:#F5B5AC;color:#B42318;">⏱ 7 days to pay</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Collection toolkit</span>
          <h3>Not paying? Chase it — or sell it.</h3>
          <p>Reminders go out automatically. When that does not work, one click creates a wezwanie do zapłaty (formal payment demand) with statutory interest and compensation. Still nothing? Sell the invoice to sprzedamfakture.pl with one click: a purchase offer within one working day, the money paid out, the risk taken off your hands. All from your invoice, with no retyping.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- COLLECTION TOOLKIT AND INVOICE SALE TO SPRZEDAMFAKTURE.PL -->
<section class="wd" id="windykacja">
  <div class="container">
    <div class="eyebrow">Collection toolkit built into your invoicing</div>
    <h2>From reminder to money in the bank — without switching tools.</h2>
    <p class="lead">In Poland, one in three B2B invoices is paid late. Lopra keeps an eye on due dates, chases for you and prepares the formal demand — and if you would rather not wait or go to court yourself, you sell the invoice to sprzedamfakture.pl straight from Lopra.</p>

    <div class="wd-grid">
      <div class="wd-steps">
        <div class="wd-step"><div class="nr">1</div><div><b>Reminders and dunning notices — automatically</b><p>Before the due date, after it and after 14 days: e-mail and SMS in your tone of voice, with a link to pay by BLIK or Przelewy24.</p><span class="tag">included in your subscription</span></div></div>
        <div class="wd-step"><div class="nr">2</div><div><b>Payment demand with interest and compensation</b><p>One click: a formal pre-court demand (PDF) with statutory late-payment interest (14%) and the 40/70/100 EUR compensation under Art. 10 of the Late Payment Act — ready to send.</p><span class="tag">included in your subscription</span></div></div>
        <div class="wd-step"><div class="nr">3</div><div><b>Sell the invoice</b><p>Rather not wait or go to court yourself? Submit the invoice straight from Lopra — sprzedamfakture.pl sends a purchase offer within one working day. Accept, and the money is paid out; the buyer takes over the risk and any further recovery.</p><span class="tag">offer before you decide · no fees, no upfront costs</span></div></div>
      </div>

      <div>
        <div class="wd-card">
          <div class="cap"><span>Claim as of today</span><span class="pill">44 days overdue</span></div>
          <table>
            <tr><td>Principal amount · FV/2026/0004</td><td class="r">2 420,00 zł</td></tr>
            <tr><td>Statutory interest (14% × 44 days)</td><td class="r">40,84 zł</td></tr>
            <tr><td>Art. 10 compensation (40 EUR)</td><td class="r">172,00 zł</td></tr>
            <tr class="tot"><td>Total due</td><td class="r">2 632,84 zł</td></tr>
          </table>
          <div class="acts"><span class="main">Download demand (PDF)</span><span>Sell the invoice</span></div>
        </div>
        <div class="wd-partner">
          <div class="lg">SF</div>
          <div><b>sprzedamfakture.pl</b>Lopra's invoice-purchase partner: buys unpaid B2B invoices, assignment offer within one working day, no fees or upfront costs. <a href="{{ route('pl.kalkulator') }}" style="color:#E0A55C;text-decoration:underline;">Calculate interest and compensation →</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- IMPORT -->
<section class="section section-alt" id="import">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Already using invoicing software?</div>
      <h2>Switch in 10 minutes.</h2>
      <p>Export your clients, products and unpaid invoices (CSV or Excel) from your current software — Lopra recognises the columns and imports everything without duplicates. Leave the history of paid invoices in the old system.</p>
    </div>
    <div class="imp">
      <a href="{{ route('pl.przenies', 'fakturownia') }}"><span class="lg" style="background:#0F766E;">F</span><span>Fakturownia<small>CSV export of clients and invoices</small></span></a>
      <a href="{{ route('pl.przenies', 'ifirma') }}"><span class="lg" style="background:#1D4ED8;">iF</span><span>iFirma<small>export of contractors and sales</small></span></a>
      <a href="{{ route('pl.przenies', 'wfirma') }}"><span class="lg" style="background:#7C3AED;">wF</span><span>wFirma<small>CSV / XLSX export</small></span></a>
      <a href="{{ route('pl.przenies', 'infakt') }}"><span class="lg" style="background:#EA580C;">iN</span><span>inFakt<small>export of clients and invoices</small></span></a>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section" id="funkcje">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Features</div>
      <h2>Everything you need. From your first invoice to the one that isn't paid.</h2>
      <p>No accounting package with a hundred buttons. Just everything you need to issue a professional invoice today — and what comes in handy once the business grows.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg></div>
        <div class="feature-title">VAT invoices &amp; quotes</div>
        <div class="feature-desc">Invoices, credit notes, advance invoices and quotes with online signature. Continuous numbering, your logo, a BLIK / Przelewy24 payment link and a QR code on every invoice.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="feature-title">KSeF-ready</div>
        <div class="feature-desc">Every invoice has an FA-XML file that matches the National e-Invoicing System schema — for submission and archiving with its KSeF number.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">VAT return</div>
        <div class="feature-desc">Output and input VAT per rate (23/8/5/0) every month or quarter, with a reminder before the 25th. Export for your accountant.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg></div>
        <div class="feature-title">Collection toolkit &amp; invoice sale</div>
        <div class="feature-desc">Reminders and dunning notices, a payment demand with interest and compensation, and — if you would rather have the cash now — selling the invoice to sprzedamfakture.pl. Straight from the invoice.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></div>
        <div class="feature-title">AI brand identity</div>
        <div class="feature-desc">Three proposals for a logo, colours, font and slogan from four answers. Or upload your own logo and brand guidelines.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <div class="feature-title">Business card and website</div>
        <div class="feature-desc">A digital business card with QR code and a complete one-page website with contact form. AI writes the copy, enquiries reach you as leads.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="feature-title">Expenses from a photo</div>
        <div class="feature-desc">Snap a receipt or upload a purchase invoice: AI recognises the seller, date and amounts, and the input VAT lands in your return.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="feature-title">Free access for your accountant</div>
        <div class="feature-desc">Invite your accountant with a read-only role — they see everything and change nothing. CSV export and VAT returns in one click.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="feature-title">Grows with your business</div>
        <div class="feature-desc">Hours and mileage, recurring invoices, several companies and brands on one account, a client portal, a cash-flow dashboard.</div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="section section-alt" id="cennik">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Fair pricing</div>
      <h2>One subscription. Everything included.</h2>
      <p>No hidden fees, no limits on invoices or clients. Cancel whenever you like.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Everything a business needs is in Podstawowy (Basic).</h2>
        <p>Invoices, quotes, VAT, business card, website and the full collection toolkit — for what others charge for invoicing software alone. Want AI to design your brand, write your website and book your expenses from photos? Choose Smart.</p>
        <ul class="pricing-lead-points">
          <li>Digital business card and website included</li>
          <li>Unlimited invoices, ready for KSeF</li>
          <li>Payment demand with interest and compensation</li>
          <li>14 days free — with every AI feature</li>
          <li>Support in English and Polish · cancel at any time</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Podstawowy</div>
          <div class="pricing-desc">Invoices, VAT, business card, website and the collection toolkit</div>
          <div class="pricing-price-row">
            <div class="pricing-price">49<span class="euro" style="font-size:22px;margin-left:4px;">zł</span></div>
            <div class="pricing-period">/ month</div>
          </div>
          <div class="pricing-vat">net · 60,27 zł gross (23% VAT)</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Unlimited VAT invoices, credit notes and quotes</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>FA-XML file for KSeF with every invoice</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>VAT return per rate, every month</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digital business card with QR code and a website</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Your own logo, colours and templates</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Reminders, payment demand, invoice sale to sprzedamfakture.pl</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>BLIK / Przelewy24 payment link on the invoice</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Hours, mileage, recurring invoices</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Extra users and your accountant free of charge</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Try 14 days free</a>
          <div class="pricing-fineprint">No card needed · cancel at any time</div>
        </div>

        <div class="pricing-card">
          <div class="pricing-badge">Most popular</div>
          <div class="pricing-title">Smart</div>
          <div class="pricing-desc">Everything in Podstawowy, plus AI that designs your brand and does the bookkeeping for you</div>
          <div class="pricing-price-row">
            <div class="pricing-price">79<span class="euro" style="font-size:22px;margin-left:4px;">zł</span></div>
            <div class="pricing-period">/ month</div>
          </div>
          <div class="pricing-vat">net · 97,17 zł gross (23% VAT)</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Everything in Podstawowy</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI brand identity: three proposals with logo, colours and slogan</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Website copy written by AI from four answers</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Expenses from a photo: receipts and purchase invoices recognised automatically</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Quote from text: paste a description, the form fills itself in</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Claude integration: invoices and quotes straight from a conversation</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Priority purchase quotes from sprzedamfakture.pl</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Try 14 days free</a>
          <div class="pricing-fineprint">No card needed · cancel at any time</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Frequently asked questions</div>
      <h2>Questions you probably have.</h2>
      <p>Can't find your question? <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">Write to us.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>Does Lopra support KSeF?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Yes. Every invoice issued in Lopra has an XML file in the FA structure required by the National e-Invoicing System (KSeF), which you download and submit to KSeF; you store the KSeF number with the invoice. Direct submission from within Lopra (token authorisation) is our next step — tested on the Ministry of Finance test environment.</div>
      </details>
      <details class="faq-item">
        <summary>I have just registered my business in CEIDG. What do I need?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Your NIP (tax ID) and a business bank account. Lopra pulls your company name, address and REGON from the VAT taxpayer register (the "white list") and sets up invoice numbering and VAT. Your brand, business card and website take fifteen minutes. VAT-exempt? Tick the box — the correct note appears on your invoices.</div>
      </details>
      <details class="faq-item">
        <summary>What does chasing an unpaid invoice cost?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Reminders, dunning notices and the payment demand are included in your subscription. Selling an invoice to sprzedamfakture.pl carries no fees and no upfront costs: you submit the invoice from Lopra, receive a purchase offer with the price within one working day and only then decide. Accept, and the money is paid out — the buyer takes over the risk and any further recovery.</div>
      </details>
      <details class="faq-item">
        <summary>My invoices are in Fakturownia / iFirma / wFirma / inFakt. Is switching difficult?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">No. Export your contractors, products and unpaid invoices to CSV or XLSX and upload them in the migration wizard — Lopra recognises the Polish column names (NIP, kontrahent, termin płatności, brutto…). You will find instructions for each program on the "Switch from…" pages.</div>
      </details>
      <details class="faq-item">
        <summary>How much does it cost after the trial?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content"><b>Podstawowy</b> 49 zł net (60,27 zł gross) a month, <b>Smart</b> 79 zł net (97,17 zł gross) with AI features and priority purchase quotes from sprzedamfakture.pl. No fixed-term contract; pay by BLIK, card or bank transfer, with a VAT invoice every month.</div>
      </details>
      <details class="faq-item">
        <summary>Is my data safe and GDPR-compliant?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Your data is stored on servers in the European Union (Amsterdam), backed up daily, and the connection is encrypted. You can enable two-factor login. You can export your whole company (CSV) at any time and delete your account — your data belongs to you.</div>
      </details>
    </div>
    <div style="text-align:center;margin-top:32px;">
      <a href="{{ route('pl.faq') }}" class="btn btn-secondary">All questions →</a>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Start today. Professional from your very first invoice — and calm when someone doesn't pay.</h2>
    <p>Account, brand, business card, website and your first invoice — all before dinner. And if a client does not pay, the demand is ready in one click — and so is a purchase offer from sprzedamfakture.pl.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Try 14 days free
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">No card needed · then from 49 zł net a month</div>
  </div>
</section>

<script>
(function () {
  var mock = document.getElementById('lpDash');
  if (!mock) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var screens = Array.prototype.slice.call(mock.querySelectorAll('.d-screen'));
  var navitems = Array.prototype.slice.call(mock.querySelectorAll('.d-navitem'));
  var urlEl = document.getElementById('lpUrl');
  var progEl = document.getElementById('lpProgress');
  var domain = urlEl ? urlEl.textContent.split('/')[0] : 'lopra.pl';
  var slugs = ['dashboard', 'faktury', 'windykacja', 'ustawienia/identyfikacja', 'ustawienia/wizytowka', 'ustawienia/strona'];
  var DWELL = 4400;
  if (!screens.length) return;
  function fmt(n) { return n.toLocaleString('pl-PL'); }
  function setNum(el, val) { el.textContent = (el.getAttribute('data-prefix') || '') + fmt(Math.round(val)) + (el.getAttribute('data-suffix') || ''); }
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    if (reduce) { setNum(el, target); return; }
    var dur = 1100, s = null;
    function step(ts) { if (s === null) s = ts; var p = Math.min((ts - s) / dur, 1); setNum(el, target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); else setNum(el, target); }
    requestAnimationFrame(step);
  }
  var current = -1, timer = null, hovering = false, started = false;
  function restartProgress() { if (!progEl || reduce) return; progEl.style.transition = 'none'; progEl.style.width = '0%'; void progEl.offsetWidth; progEl.style.transition = 'width ' + DWELL + 'ms linear'; progEl.style.width = '100%'; }
  function show(idx) {
    idx = ((idx % screens.length) + screens.length) % screens.length;
    if (idx === current) return;
    current = idx;
    for (var i = 0; i < screens.length; i++) screens[i].classList.toggle('active', i === idx);
    for (var j = 0; j < navitems.length; j++) navitems[j].classList.toggle('active', j === idx);
    if (urlEl && slugs[idx]) urlEl.textContent = domain + '/' + slugs[idx];
    var nums = screens[idx].querySelectorAll('[data-count]');
    for (var k = 0; k < nums.length; k++) countUp(nums[k]);
    restartProgress();
  }
  function stop() { if (timer) { clearInterval(timer); timer = null; } }
  function play() { stop(); if (reduce) return; timer = setInterval(function () { show(current + 1); }, DWELL); }
  navitems.forEach(function (item, i) { item.addEventListener('click', function () { show(i); if (!hovering && !reduce) play(); }); });
  mock.addEventListener('mouseenter', function () { hovering = true; stop(); });
  mock.addEventListener('mouseleave', function () { hovering = false; if (started) { restartProgress(); play(); } });
  mock.classList.add('js-ready');
  function onEnter() { if (!started) { started = true; show(0); } if (!hovering) play(); }
  if (reduce) { show(0); return; }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) { for (var i = 0; i < entries.length; i++) { if (entries[i].isIntersecting) onEnter(); else stop(); } }, { threshold: 0.3 });
    io.observe(mock);
  } else { onEnter(); }
})();
</script>
@endsection
