@extends('layouts.marketing')

{{-- Strona główna Lopra Polska (APP_BRAND=lopra_pl): faktury, marka, strona www i windykacja z Creditline Polska — od startu po dojrzały biznes. --}}

@section('title', 'Lopra — faktury, marka i windykacja w jednym miejscu, od 49 zł miesięcznie')
@section('description', 'Kompletne narzędzie dla polskich firm: faktury gotowe do KSeF, oferty, rozliczenie VAT, identyfikacja wizualna z AI, wizytówka i strona www, import z Fakturowni, iFirmy, wFirmy i inFaktu oraz windykacja należności z Creditline Polska. 14 dni za darmo.')

@push('styles')
@include('lopra.partials.landing-styles')
<style>
  /* Windykacja: ciemna sekcja z czterema krokami */
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
  .wd-partner .lg { width: 44px; height: 44px; border-radius: 12px; background: #ec3013; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
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
    <div class="eyebrow">Dla firm w Polsce — od startu po dojrzały biznes</div>
    <h1>Cała Twoja firma <span class="accent">w jednym miejscu.</span></h1>
    <p class="hero-sub">
      Faktury gotowe do KSeF, oferty, rozliczenie VAT, identyfikacja wizualna, strona www — i windykacja należności z Creditline Polska, gdy klient nie płaci. Jedno narzędzie, jeden abonament, zero księgowego żargonu.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Wypróbuj 14 dni za darmo
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Zobacz demo</a>
    </div>
    <div class="hero-trust">
      Bez karty · 14 dni za darmo · potem od <b>49 zł netto / mies.</b>
    </div>

    <!-- MOCKUP PRODUKTU — klikalne menu, ekrany zmieniają się automatycznie -->
    <div class="app-mockup-wrap">
      <div class="lp-float one" aria-hidden="true">
        <div class="ic" style="background:#D1FAE5;color:#065F46;">✓</div>
        <div><b>Faktura przyjęta w KSeF</b><span>FV/2026/0007 · numer KSeF nadany</span></div>
      </div>
      <div class="lp-float two" aria-hidden="true">
        <div class="ic" style="background:var(--accent-tint);color:var(--accent-dark);">₿</div>
        <div><b>1 210,00 zł zapłacone BLIK-iem</b><span>Faktura FV/2026/0006 · przed chwilą</span></div>
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
            <div class="d-navlabel">Przegląd</div>
            <div class="d-navitem active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>Pulpit</div>
            <div class="d-navlabel">Sprzedaż</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Faktury</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg>Windykacja</div>
            <div class="d-navlabel">Twoja marka</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>Identyfikacja</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>Wizytówka</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Strona www</div>
          </aside>

          <div class="d-main">
            <div class="d-progress"><i id="lpProgress"></i></div>

            <!-- EKRAN 1: Pulpit -->
            <section class="d-screen active" data-screen="pulpit">
              <div class="d-topbar d-anim" style="--d:.05s">
                <div>
                  <div class="d-greet">Dzień dobry, Anno</div>
                  <div class="d-sub">Strona www przyniosła w tym tygodniu 3 zapytania, 2 faktury zostały opłacone, 1 czeka na wezwanie.</div>
                </div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nowa faktura</div>
              </div>
              <div class="d-kpis">
                <div class="d-kpi d-anim" style="--d:.12s"><div class="d-kpi-label">Do zapłaty</div><div class="d-kpi-value" data-count="5240" data-suffix=" zł">5 240 zł</div><div class="d-kpi-meta">3 faktury</div></div>
                <div class="d-kpi d-anim" style="--d:.19s"><div class="d-kpi-label">Opłacone w tym miesiącu</div><div class="d-kpi-value" data-count="13180" data-suffix=" zł">13 180 zł</div><div class="d-kpi-meta"><span class="up">↑ 24%</span> vs poprzedni miesiąc</div></div>
                <div class="d-kpi hi d-anim" style="--d:.26s"><span class="d-dot"></span><div class="d-kpi-label">Przeterminowane</div><div class="d-kpi-value copper" data-count="2420" data-suffix=" zł">2 420 zł</div><div class="d-kpi-meta">1 faktura · 44 dni · wezwanie gotowe</div></div>
                <div class="d-kpi d-anim" style="--d:.33s"><div class="d-kpi-label">VAT za wrzesień</div><div class="d-kpi-value" data-count="2818" data-suffix=" zł">2 818 zł</div><div class="d-kpi-meta">JPK_V7 do 25 października</div></div>
              </div>
              <div class="d-two">
                <div class="d-card d-anim" style="--d:.4s">
                  <div class="d-card-head"><div><div class="d-card-title">Przychód miesięczny</div><div class="d-card-sub">Netto · od startu w lutym</div></div><span class="d-card-link">Raport →</span></div>
                  <div class="d-bars">
                    <div class="d-bar-col"><div class="d-bar" style="--h:22%;--d:.45s"></div><span>lut</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:38%;--d:.50s"></div><span>mar</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:47%;--d:.55s"></div><span>kwi</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:44%;--d:.60s"></div><span>maj</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:63%;--d:.65s"></div><span>cze</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:71%;--d:.70s"></div><span>lip</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:66%;--d:.75s"></div><span>sie</span></div>
                    <div class="d-bar-col"><div class="d-bar tall" style="--h:92%;--d:.80s"></div><span>wrz</span></div>
                  </div>
                </div>
                <div class="d-card d-anim" style="--d:.48s">
                  <div class="d-card-head"><div><div class="d-card-title">Zapytania ze strony www</div><div class="d-card-sub">Formularz → lead → oferta</div></div></div>
                  <div class="d-lead"><span class="d-avatar">RN</span><div class="who"><div class="t">Rodzina Nowak</div><div class="s">Projekt salonu i kuchni</div></div><span class="when">2 godz.</span></div>
                  <div class="d-lead"><span class="d-avatar">KP</span><div class="who"><div class="t">Kawiarnia Pod Lipą</div><div class="s">Aranżacja nowego ogródka</div></div><span class="when">wczoraj</span></div>
                  <div class="d-lead"><span class="d-avatar">GZ</span><div class="who"><div class="t">Gabinet Zdrowie</div><div class="s">Poczekalnia od nowa</div></div><span class="when">3 dni</span></div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.56s">
                <div class="d-card-head"><div class="d-card-title">Ostatnie faktury</div><span class="d-card-link">Wszystkie →</span></div>
                <table class="d-table">
                  <tbody>
                    <tr><td class="mono">FV/2026/0007</td><td>Studio Północ</td><td class="d-hide">12 wrz</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill green">Opłacona</span></td><td class="mono right">1 210,00 zł</td></tr>
                    <tr><td class="mono">FV/2026/0006</td><td>Rodzina Wiśniewska</td><td class="d-hide">9 wrz</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill blue">Wysłana</span></td><td class="mono right">2 640,00 zł</td></tr>
                    <tr><td class="mono">FV/2026/0004</td><td>TransLog Polska Sp. z o.o.</td><td class="d-hide">28 lip</td><td><span class="ksef">✓ KSeF</span></td><td><span class="d-pill amber">44 dni po terminie</span></td><td class="mono right">2 420,00 zł</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- EKRAN 2: Faktury -->
            <section class="d-screen" data-screen="faktury">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Faktury</div><div class="d-sub">7 faktur · 5 240 zł do zapłaty · każda z Twoim logo, gotowa do KSeF</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nowa faktura</div>
              </div>
              <div class="d-tabs d-anim" style="--d:.09s"><span class="d-tab active">Wszystkie</span><span class="d-tab">Szkice</span><span class="d-tab">Wysłane</span><span class="d-tab">Opłacone</span><span class="d-tab">Przeterminowane</span></div>
              <div class="d-card d-anim" style="--d:.14s">
                <table class="d-table">
                  <thead><tr><th>Numer</th><th>Klient</th><th class="d-hide">Wystawiona</th><th class="d-hide">Termin</th><th>KSeF</th><th>Status</th><th class="right">Kwota</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.18s"><td class="mono">FV/2026/0007</td><td>Studio Północ</td><td class="d-hide">12.09</td><td class="d-hide">26.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Opłacona</span></td><td class="mono right">1 210,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.24s"><td class="mono">FV/2026/0006</td><td>Rodzina Wiśniewska</td><td class="d-hide">09.09</td><td class="d-hide">23.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill blue">Wysłana</span></td><td class="mono right">2 640,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.30s"><td class="mono">FV/2026/0005</td><td>Kawiarnia Pod Lipą</td><td class="d-hide">02.09</td><td class="d-hide">16.09</td><td><span class="ksef">✓</span></td><td><span class="d-pill amber">Częściowo opłacona</span></td><td class="mono right">4 920,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.36s"><td class="mono">FV/2026/0004</td><td>TransLog Polska Sp. z o.o.</td><td class="d-hide">28.07</td><td class="d-hide">11.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill copper">Przeterminowana</span></td><td class="mono right">2 420,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.42s"><td class="mono">FV/2026/0003</td><td>Gabinet Zdrowie</td><td class="d-hide">14.08</td><td class="d-hide">28.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Opłacona</span></td><td class="mono right">3 690,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.48s"><td class="mono">FV/2026/0002</td><td>Studio Północ</td><td class="d-hide">03.08</td><td class="d-hide">17.08</td><td><span class="ksef">✓</span></td><td><span class="d-pill green">Opłacona</span></td><td class="mono right">980,00 zł</td></tr>
                    <tr class="d-fade" style="--d:.54s"><td class="mono">— szkic —</td><td>Rodzina Nowak</td><td class="d-hide">—</td><td class="d-hide">—</td><td>—</td><td><span class="d-pill gray">Szkic</span></td><td class="mono right">5 150,00 zł</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.6s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div><div><span class="big">KSeF</span><span>XML FA wysyłany jednym kliknięciem</span></div></div>
                <div class="d-tile d-anim" style="--d:.66s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div><div><span class="big">BLIK · P24</span><span>link do zapłaty na każdej fakturze</span></div></div>
                <div class="d-tile d-anim" style="--d:.72s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><span class="big">9 dni</span><span>średni czas zapłaty</span></div></div>
              </div>
            </section>

            <!-- EKRAN 3: Windykacja -->
            <section class="d-screen" data-screen="windykacja">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Windykacja</div><div class="d-sub">FV/2026/0004 · TransLog Polska Sp. z o.o. · 44 dni po terminie</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg>Przekaż do Creditline</div>
              </div>
              <div class="d-two even">
                <div class="d-card d-anim" style="--d:.1s" style="margin:0;">
                  <div class="d-card-head"><div><div class="d-card-title">Roszczenie na dziś</div><div class="d-card-sub">Odsetki ustawowe 14% · rekompensata art. 10</div></div></div>
                  <table class="d-table">
                    <tbody>
                      <tr><td>Należność główna</td><td class="mono right">2 420,00 zł</td></tr>
                      <tr><td>Odsetki (14% × 44 dni)</td><td class="mono right">40,84 zł</td></tr>
                      <tr><td>Rekompensata (40 EUR)</td><td class="mono right">172,00 zł</td></tr>
                      <tr><td><b>Razem</b></td><td class="mono right"><b>2 632,84 zł</b></td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="d-card d-anim" style="--d:.18s">
                  <div class="d-card-head"><div><div class="d-card-title">Co już zrobiliśmy</div><div class="d-card-sub">automatycznie, w Twoim imieniu</div></div></div>
                  <div class="d-lead"><span class="d-avatar">1</span><div class="who"><div class="t">Przypomnienie e-mail</div><div class="s">3 dni po terminie · otwarte</div></div><span class="when">14.08</span></div>
                  <div class="d-lead"><span class="d-avatar">2</span><div class="who"><div class="t">Ponaglenie + SMS</div><div class="s">14 dni po terminie</div></div><span class="when">25.08</span></div>
                  <div class="d-lead"><span class="d-avatar">3</span><div class="who"><div class="t">Wezwanie do zapłaty (PDF)</div><div class="s">z odsetkami i rekompensatą</div></div><span class="when">dziś</span></div>
                </div>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.3s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><b>Wezwanie do zapłaty</b><span>gotowe do wysyłki, 7 dni terminu</span></div></div>
                <div class="d-tile d-anim" style="--d:.36s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><b>Creditline Polska</b><span>windykacja polubowna i sądowa, KRD/BIG</span></div></div>
                <div class="d-tile d-anim" style="--d:.42s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div><div><b>Sprzedaj fakturę</b><span>oferta wykupu w 1 dzień roboczy</span></div></div>
              </div>
            </section>

            <!-- EKRAN 4: Identyfikacja wizualna -->
            <section class="d-screen" data-screen="identyfikacja">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Identyfikacja wizualna</div><div class="d-sub">Trzy propozycje na podstawie: projektantka wnętrz · klienci prywatni i gastronomia · ciepło i osobiście</div></div>
                <div class="d-ai">✦ Zaproponuj ponownie</div>
              </div>
              <div class="d-props">
                <div class="d-prop chosen d-anim" style="--d:.12s">
                  <span class="badge">Wybrana</span>
                  <div class="d-logo" style="background:#2E4A3F;">SW</div>
                  <div class="n">Ciepła &amp; rzemieślnicza</div>
                  <div class="f">Fraunces + Instrument Sans</div>
                  <div class="d-swatches"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i><i style="background:#1F1D1A"></i></div>
                  <div class="slogan">„Wnętrza, które do Ciebie pasują.”</div>
                  <span class="btn-mini">✓ Użyj tej identyfikacji</span>
                </div>
                <div class="d-prop d-anim" style="--d:.2s">
                  <div class="d-logo" style="background:#1F3A5F;">SW</div>
                  <div class="n">Nowoczesna &amp; czysta</div>
                  <div class="f">Manrope</div>
                  <div class="d-swatches"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i><i style="background:#101418"></i></div>
                  <div class="slogan">„Wnętrze z charakterem.”</div>
                  <span class="btn-mini">Wybierz</span>
                </div>
                <div class="d-prop d-anim" style="--d:.28s">
                  <div class="d-logo" style="background:#7A4B2B;">sw.</div>
                  <div class="n">Naturalna &amp; spokojna</div>
                  <div class="f">Lora + Karla</div>
                  <div class="d-swatches"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i><i style="background:#2B2723"></i></div>
                  <div class="slogan">„Dom odkryty na nowo.”</div>
                  <span class="btn-mini">Wybierz</span>
                </div>
              </div>
              <div class="d-note d-fade" style="--d:.4s">✦ <b>Stworzone przez AI z czterech odpowiedzi.</b> Jedno kliknięcie ustawia kolory, czcionkę, szablon i logo na fakturach, wizytówce i stronie www. Wszystko można zmienić.</div>
              <div class="d-styled">
                <div class="d-anim" style="--d:.46s">
                  <div class="lbl">Twoja faktura</div>
                  <div class="d-inv">
                    <div class="top"><span class="t">Faktura FV/2026/0007</span><span class="lg">SW</span></div>
                    <div class="row"><span>Projekt salonu</span><span>1 500,00 zł</span></div>
                    <div class="row"><span>Dzień stylizacji</span><span>500,00 zł</span></div>
                    <div class="row tot"><span>Razem brutto (23%)</span><span>2 460,00 zł</span></div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.52s">
                  <div class="lbl">Twoja wizytówka</div>
                  <div class="d-vcard mini">
                    <div class="who"><div class="av">A</div><div><div class="nm">Anna Kowalska</div><div class="rl">Projektantka wnętrz</div></div></div>
                    <div class="ln">+48 600 123 456</div>
                    <div class="ln site">Zobacz moją stronę →</div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.58s">
                  <div class="lbl">Twoja strona www</div>
                  <div class="d-site mini">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="hero"><div class="h">Wnętrza, które do Ciebie pasują.</div><div class="p">Projektowanie wnętrz dla tych, którzy chcą wracać do domu.</div><span class="b">Umów konsultację</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
            </section>

            <!-- EKRAN 5: Wizytówka -->
            <section class="d-screen" data-screen="wizytowka">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Wizytówka cyfrowa</div><div class="d-sub">{{ brand('domain') }}/k/studio-wnetrz-kowalska · udostępnij link lub daj zeskanować kod QR</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>Imię i nazwisko</label><div class="val">Anna Kowalska</div></div>
                  <div class="d-field"><label>Stanowisko</label><div class="val">Projektantka wnętrz</div></div>
                  <div class="d-field"><label>Slogan</label><div class="val">Wnętrza, które do Ciebie pasują.</div></div>
                  <div class="d-field"><label>WhatsApp</label><div class="val">+48 600 123 456</div></div>
                  <div class="d-field"><label>Przycisk</label><div class="val">Zobacz moją stronę → {{ brand('domain') }}/s/studio-wnetrz-kowalska</div></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Podgląd</span><span>W Twojej identyfikacji</span></div>
                  <div class="d-vcard">
                    <div class="qr"><i></i><i></i><i class="o"></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i></div>
                    <div class="who"><div class="av">A</div><div><div class="nm">Anna Kowalska</div><div class="rl">Projektantka wnętrz · Studio Wnętrz Kowalska</div></div></div>
                    <div class="ln">+48 600 123 456</div>
                    <div class="ln">anna@studiokowalska.pl</div>
                    <div class="ln site">Zobacz moją stronę →</div>
                  </div>
                </div>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.3s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg></div><div><b>Pobierz kod QR</b><span>PNG lub SVG, na ofertę i pieczątkę</span></div></div>
                <div class="d-tile d-anim" style="--d:.36s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div><div><b>Kopiuj link</b><span>wyślij przez WhatsApp lub e-mail</span></div></div>
                <div class="d-tile d-anim" style="--d:.42s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div><div><b>Zapisz w kontaktach</b><span>vCard jednym dotknięciem</span></div></div>
              </div>
            </section>

            <!-- EKRAN 6: Strona www -->
            <section class="d-screen" data-screen="strona">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Strona www</div><div class="d-sub">{{ brand('domain') }}/s/studio-wnetrz-kowalska · wiadomości trafiają do Ciebie jako leady</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>Czym się zajmujesz?</label><div class="val multi">Projektowanie i stylizacja wnętrz mieszkań oraz małej gastronomii.</div></div>
                  <div class="d-field"><label>Dla kogo?</label><div class="val multi">Klienci prywatni i firmy z Krakowa i okolic.</div></div>
                  <div class="d-field"><label>Dlaczego Ty?</label><div class="val multi">Osobiście, w budżecie i w trzy tygodnie.</div></div>
                  <div class="d-field"><label>Styl</label><div class="val">Ciepły i osobisty</div></div>
                  <div><span class="d-ai">✦ Napisz tekst</span></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Podgląd</span><span>W pełni edytowalna</span></div>
                  <div class="d-site">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="nav">Studio Wnętrz Kowalska<div><span></span><span></span><span></span></div></div>
                    <div class="hero"><div class="h">Wnętrza, które do Ciebie pasują.</div><div class="p">Projektowanie wnętrz dla tych, którzy chcą wracać do domu — osobiście, w budżecie, w trzy tygodnie.</div><span class="b">Umów konsultację</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.3s; margin-top: 12px;">
                <div class="d-card-head"><div><div class="d-card-title">Zapytania ze strony www</div><div class="d-card-sub">Formularz → lead → oferta, bez przepisywania</div></div><span class="d-pill copper">3 w tym tygodniu</span></div>
                <div class="d-lead"><span class="d-avatar">RN</span><div class="who"><div class="t">Rodzina Nowak</div><div class="s">„Czy pomogą Państwo z salonem i kuchnią?”</div></div><span class="when">2 godz. · oferta wysłana</span></div>
                <div class="d-lead"><span class="d-avatar">KP</span><div class="who"><div class="t">Kawiarnia Pod Lipą</div><div class="s">„W marcu otwieramy ogródek, szukamy projektantki.”</div></div><span class="when">wczoraj</span></div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DLACZEGO LOPRA -->
<section class="section" id="dlaczego">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Dlaczego Lopra</div>
      <h2>Jedno narzędzie zamiast czterech.</h2>
      <p>Program do faktur, projektant logo, kreator stron i firma windykacyjna — zwykle cztery abonamenty i cztery loginy. W Lopra to jedno miejsce, a wszystko ze sobą współpracuje.</p>
    </div>

    <div class="lp-vcards">
      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-inv">
            <div class="top"><div class="t">Faktura FV/2026/0007</div><span class="lg">SW</span></div>
            <div class="row"><span>Projekt salonu</span><span>1 500,00 zł</span></div>
            <div class="row"><span>VAT 23%</span><span>345,00 zł</span></div>
            <div class="row tot"><span>Razem brutto</span><span>1 845,00 zł</span></div>
          </div>
          <span class="lp-mini-badge">✓ Przyjęta w KSeF</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Faktury &amp; VAT</span>
          <h3>KSeF i VAT bez stresu</h3>
          <p>Faktury VAT, korekty i oferty z podpisem online. Każda faktura ma gotowy plik FA-XML do KSeF, a rozliczenie VAT (należny i naliczony per stawka 23/8/5/0) czeka co miesiąc na Ciebie — w układzie JPK_V7. Księgowa dostaje dostęp „tylko do odczytu” za darmo.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-props">
            <div class="lp-mini-prop"><span class="lg" style="background:#1F3A5F;">SW</span><div>Nowoczesna &amp; czysta<small>Manrope · „Wnętrze z charakterem.”</small></div><div class="sw"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i></div></div>
            <div class="lp-mini-prop on"><span class="lg" style="background:#2E4A3F;">SW</span><div>Ciepła &amp; rzemieślnicza <span class="ok">✓ Wybrana</span><small>Fraunces · „Wnętrza, które do Ciebie pasują.”</small></div><div class="sw"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i></div></div>
            <div class="lp-mini-prop"><span class="lg" style="background:#7A4B2B;">sw.</span><div>Naturalna &amp; spokojna<small>Lora · „Dom odkryty na nowo.”</small></div><div class="sw"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i></div></div>
          </div>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Marka &amp; strona www</span>
          <h3>Marka i strona www w kwadrans</h3>
          <p>Nie masz logo ani kolorów? Odpowiedz na cztery pytania — AI zaproponuje trzy identyfikacje z logo, paletą, czcionką i sloganem. Jedno kliknięcie i Twoje faktury, wizytówka cyfrowa i strona www wyglądają jak z agencji. Masz już logo? Wgraj je, resztę dopasujemy.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-inv" style="transform:rotate(-1.5deg);">
            <div class="top"><div class="t">Wezwanie do zapłaty</div><span class="lg" style="background:#ec3013;">C</span></div>
            <div class="row"><span>Należność główna</span><span>2 420,00 zł</span></div>
            <div class="row"><span>Odsetki 14% × 44 dni</span><span>40,84 zł</span></div>
            <div class="row"><span>Rekompensata (40 EUR)</span><span>172,00 zł</span></div>
            <div class="row tot"><span>Razem</span><span>2 632,84 zł</span></div>
          </div>
          <span class="lp-mini-badge" style="border-color:#F5B5AC;color:#B42318;">⏱ 7 dni na zapłatę</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Windykacja</span>
          <h3>Nie płacą? Creditline odzyska.</h3>
          <p>Przypomnienia idą automatycznie. Gdy to nie działa, jednym kliknięciem tworzysz wezwanie do zapłaty z odsetkami ustawowymi i rekompensatą — a potem przekazujesz sprawę do Creditline Polska: windykacja polubowna i sądowa, wpis do KRD/BIG, albo sprzedaż faktury. Wszystko z Twojej faktury, bez przepisywania danych.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WINDYKACJA Z CREDITLINE -->
<section class="wd" id="windykacja">
  <div class="container">
    <div class="eyebrow">Windykacja wbudowana w fakturowanie</div>
    <h2>Od przypomnienia do odzyskanej należności — bez zmiany narzędzia.</h2>
    <p class="lead">W Polsce co trzecia faktura B2B jest płacona po terminie. Lopra pilnuje terminów za Ciebie, a gdy trzeba, Creditline Polska przejmuje sprawę — ze wszystkimi danymi z Twojej faktury.</p>

    <div class="wd-grid">
      <div class="wd-steps">
        <div class="wd-step"><div class="nr">1</div><div><b>Przypomnienia i ponaglenia — automatycznie</b><p>Przed terminem, po terminie i po 14 dniach: e-mail i SMS w Twoim tonie, z linkiem do zapłaty BLIK-iem lub Przelewy24.</p><span class="tag">w cenie abonamentu</span></div></div>
        <div class="wd-step"><div class="nr">2</div><div><b>Wezwanie do zapłaty z odsetkami i rekompensatą</b><p>Jedno kliknięcie: formalne, przedsądowe wezwanie (PDF) z odsetkami ustawowymi za opóźnienie i rekompensatą 40/70/100 EUR z art. 10 ustawy — gotowe do wysyłki.</p><span class="tag">w cenie abonamentu</span></div></div>
        <div class="wd-step"><div class="nr">3</div><div><b>Przekazanie do Creditline Polska</b><p>Kompletne dossier (faktura, historia kontaktu, wezwanie) trafia do zespołu windykacyjnego. Windykacja polubowna, wpis do KRD/BIG, a w razie potrzeby droga sądowa — na koszt dłużnika.</p><span class="tag">wycena przed zleceniem · bez opłat wstępnych</span></div></div>
        <div class="wd-step"><div class="nr">4</div><div><b>Sprzedaj fakturę</b><p>Potrzebujesz gotówki teraz? Zgłoś wierzytelność do wykupu — Creditline Polska przygotuje ofertę cesji w jeden dzień roboczy.</p><span class="tag">wykup wierzytelności</span></div></div>
      </div>

      <div>
        <div class="wd-card">
          <div class="cap"><span>Roszczenie na dziś</span><span class="pill">44 dni po terminie</span></div>
          <table>
            <tr><td>Należność główna · FV/2026/0004</td><td class="r">2 420,00 zł</td></tr>
            <tr><td>Odsetki ustawowe (14% × 44 dni)</td><td class="r">40,84 zł</td></tr>
            <tr><td>Rekompensata art. 10 (40 EUR)</td><td class="r">172,00 zł</td></tr>
            <tr class="tot"><td>Razem do zapłaty</td><td class="r">2 632,84 zł</td></tr>
          </table>
          <div class="acts"><span class="main">Pobierz wezwanie (PDF)</span><span>Przekaż do Creditline</span><span>Sprzedaj fakturę</span></div>
        </div>
        <div class="wd-partner">
          <div class="lg">C</div>
          <div><b>Creditline Polska</b>Partner windykacyjny Lopra — AI-wspomagana windykacja należności, wykup wierzytelności, kalkulator odsetek. <a href="{{ route('pl.kalkulator') }}" style="color:#E0A55C;text-decoration:underline;">Policz odsetki i rekompensatę →</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- IMPORT -->
<section class="section section-alt" id="import">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Masz już program do faktur?</div>
      <h2>Przenieś się w 10 minut.</h2>
      <p>Wyeksportuj klientów, produkty i nieopłacone faktury (CSV lub Excel) ze swojego programu — Lopra rozpozna kolumny i zaimportuje wszystko bez duplikatów. Historię opłaconych faktur zostaw w starym systemie.</p>
    </div>
    <div class="imp">
      <a href="{{ route('pl.przenies', 'fakturownia') }}"><span class="lg" style="background:#0F766E;">F</span><span>Fakturownia<small>eksport CSV klientów i faktur</small></span></a>
      <a href="{{ route('pl.przenies', 'ifirma') }}"><span class="lg" style="background:#1D4ED8;">iF</span><span>iFirma<small>eksport kontrahentów i sprzedaży</small></span></a>
      <a href="{{ route('pl.przenies', 'wfirma') }}"><span class="lg" style="background:#7C3AED;">wF</span><span>wFirma<small>eksport CSV / XLSX</small></span></a>
      <a href="{{ route('pl.przenies', 'infakt') }}"><span class="lg" style="background:#EA580C;">iN</span><span>inFakt<small>eksport klientów i faktur</small></span></a>
    </div>
  </div>
</section>

<!-- FUNKCJE -->
<section class="section" id="funkcje">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Funkcje</div>
      <h2>Wszystko, czego potrzebujesz. Od pierwszej faktury po windykację.</h2>
      <p>Bez programu księgowego ze stu przyciskami. Za to wszystko, żeby dziś wystawić profesjonalną fakturę — i to, co przyda się, gdy firma urośnie.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg></div>
        <div class="feature-title">Faktury VAT &amp; oferty</div>
        <div class="feature-desc">Faktury, korekty, zaliczki i oferty z podpisem online. Numeracja ciągła, Twoje logo, link do zapłaty BLIK / Przelewy24 i kod QR na każdej fakturze.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="feature-title">Gotowe do KSeF</div>
        <div class="feature-desc">Każda faktura ma plik FA-XML zgodny ze schematem Krajowego Systemu e-Faktur — do wysyłki i archiwizacji z numerem KSeF.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">Rozliczenie VAT</div>
        <div class="feature-desc">VAT należny i naliczony per stawka (23/8/5/0) w każdym miesiącu lub kwartale, z przypomnieniem przed 25. dniem. Eksport dla księgowej.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="12" rx="2"/></svg></div>
        <div class="feature-title">Windykacja z Creditline Polska</div>
        <div class="feature-desc">Przypomnienia, wezwanie do zapłaty z odsetkami i rekompensatą, przekazanie sprawy i sprzedaż faktury — prosto z faktury.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></div>
        <div class="feature-title">Identyfikacja wizualna z AI</div>
        <div class="feature-desc">Trzy propozycje logo, kolorów, czcionki i sloganu z czterech odpowiedzi. Albo wgraj własne logo i księgę znaku.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <div class="feature-title">Wizytówka i strona www</div>
        <div class="feature-desc">Wizytówka cyfrowa z kodem QR i kompletna strona-wizytówka z formularzem kontaktowym. Tekst napisze AI, zapytania trafiają do Ciebie jako leady.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="feature-title">Koszty ze zdjęcia</div>
        <div class="feature-desc">Zrób zdjęcie paragonu lub prześlij fakturę kosztową: AI rozpozna sprzedawcę, datę i kwoty, VAT naliczony trafi do rozliczenia.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="feature-title">Księgowa ma wgląd za darmo</div>
        <div class="feature-desc">Zaproś księgową z rolą „tylko odczyt” — widzi wszystko, niczego nie zmieni. Eksport CSV i rozliczenia VAT w jednym kliknięciu.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="feature-title">Rośnie razem z firmą</div>
        <div class="feature-desc">Godziny i przejazdy, faktury cykliczne, kilka firm i marek na jednym koncie, panel klienta, dashboard przepływów pieniężnych.</div>
      </div>
    </div>
  </div>
</section>

<!-- CENNIK -->
<section class="section section-alt" id="cennik">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Uczciwa cena</div>
      <h2>Jeden abonament. Wszystko w cenie.</h2>
      <p>Bez ukrytych opłat i limitów faktur czy klientów. Rezygnujesz, kiedy chcesz.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Wszystko, czego potrzebuje firma, jest w Podstawowym.</h2>
        <p>Faktury, oferty, VAT, wizytówka, strona www i windykacja krok 1–2 — za cenę, jaką inni biorą za sam program do faktur. Chcesz, żeby AI zaprojektowała markę, napisała stronę i księgowała koszty ze zdjęć? Wybierz Smart.</p>
        <ul class="pricing-lead-points">
          <li>Wizytówka cyfrowa i strona www w cenie</li>
          <li>Faktury bez limitu, gotowe do KSeF</li>
          <li>Wezwanie do zapłaty z odsetkami i rekompensatą</li>
          <li>14 dni za darmo — ze wszystkimi funkcjami AI</li>
          <li>Wsparcie po polsku · rezygnacja w każdej chwili</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Podstawowy</div>
          <div class="pricing-desc">Faktury, VAT, wizytówka, strona www i windykacja krok 1–2</div>
          <div class="pricing-price-row">
            <div class="pricing-price">49<span class="euro" style="font-size:22px;margin-left:4px;">zł</span></div>
            <div class="pricing-period">/ mies.</div>
          </div>
          <div class="pricing-vat">netto · 60,27 zł brutto (23% VAT)</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Faktury VAT, korekty i oferty bez limitu</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Plik FA-XML do KSeF dla każdej faktury</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Rozliczenie VAT per stawka, co miesiąc</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Wizytówka cyfrowa z QR i strona www</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Własne logo, kolory i szablony</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Przypomnienia, wezwanie do zapłaty, przekazanie do Creditline</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Link do zapłaty BLIK / Przelewy24 na fakturze</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Godziny, przejazdy, faktury cykliczne</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Bezpłatni dodatkowi użytkownicy i księgowa</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Wypróbuj 14 dni za darmo</a>
          <div class="pricing-fineprint">Bez karty · rezygnacja w każdej chwili</div>
        </div>

        <div class="pricing-card">
          <div class="pricing-badge">Najczęściej wybierany</div>
          <div class="pricing-title">Smart</div>
          <div class="pricing-desc">Wszystko z Podstawowego, plus AI, która projektuje markę i księguje za Ciebie</div>
          <div class="pricing-price-row">
            <div class="pricing-price">79<span class="euro" style="font-size:22px;margin-left:4px;">zł</span></div>
            <div class="pricing-period">/ mies.</div>
          </div>
          <div class="pricing-vat">netto · 97,17 zł brutto (23% VAT)</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Wszystko z Podstawowego</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Identyfikacja wizualna z AI: trzy propozycje z logo, kolorami i sloganem</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Tekst strony www napisany przez AI z czterech odpowiedzi</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Koszty ze zdjęcia: paragony i faktury kosztowe rozpoznane automatycznie</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Oferta z tekstu: wklej opis, formularz wypełni się sam</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Integracja z Claude: faktury i oferty prosto z rozmowy</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Priorytetowa obsługa spraw windykacyjnych w Creditline</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Wypróbuj 14 dni za darmo</a>
          <div class="pricing-fineprint">Bez karty · rezygnacja w każdej chwili</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Najczęstsze pytania</div>
      <h2>Pytania, które pewnie masz.</h2>
      <p>Nie ma Twojego pytania? <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">Napisz do nas.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>Czy Lopra obsługuje KSeF?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Tak. Każda faktura wystawiona w Lopra ma plik XML w strukturze FA zgodnej z Krajowym Systemem e-Faktur, który pobierasz i wysyłasz do KSeF; numer KSeF zapisujesz przy fakturze. Bezpośrednią wysyłkę z poziomu Lopra (autoryzacja tokenem) wdrażamy w kolejnym kroku — przetestowaną na środowisku testowym Ministerstwa Finansów.</div>
      </details>
      <details class="faq-item">
        <summary>Jestem dopiero po rejestracji w CEIDG. Co potrzebuję?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">NIP i konto firmowe. Lopra pobierze nazwę, adres i REGON z Wykazu podatników VAT (białej listy), ustawi numerację faktur i VAT. Markę, wizytówkę i stronę www zrobisz w kwadrans. Zwolniony z VAT? Zaznacz to — na fakturach pojawi się właściwa adnotacja.</div>
      </details>
      <details class="faq-item">
        <summary>Ile kosztuje windykacja z Creditline Polska?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Przypomnienia i wezwanie do zapłaty są w cenie abonamentu. Przekazanie sprawy do Creditline Polska jest wyceniane przed zleceniem — bez opłat wstępnych; koszty windykacji i odsetki co do zasady obciążają dłużnika (ustawa o przeciwdziałaniu nadmiernym opóźnieniom w transakcjach handlowych). Ofertę wykupu faktury dostajesz w jeden dzień roboczy.</div>
      </details>
      <details class="faq-item">
        <summary>Mam faktury w Fakturowni / iFirmie / wFirmie / inFakcie. Czy przeniesienie jest trudne?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nie. Wyeksportuj kontrahentów, produkty i nieopłacone faktury do CSV lub XLSX i wgraj je w kreatorze przenosin — Lopra rozpoznaje polskie nazwy kolumn (NIP, kontrahent, termin płatności, brutto…). Instrukcje dla każdego programu znajdziesz na stronach „Przenieś się z…”.</div>
      </details>
      <details class="faq-item">
        <summary>Ile kosztuje po okresie próbnym?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content"><b>Podstawowy</b> 49 zł netto (60,27 zł brutto) miesięcznie, <b>Smart</b> 79 zł netto (97,17 zł brutto) z funkcjami AI i priorytetową windykacją. Bez umowy na czas określony, płatność BLIK-iem, kartą lub przelewem, faktura VAT co miesiąc.</div>
      </details>
      <details class="faq-item">
        <summary>Czy moje dane są bezpieczne i zgodne z RODO?<svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Dane są przechowywane na serwerach w Unii Europejskiej (Amsterdam), codziennie archiwizowane, a połączenie szyfrowane. Możesz włączyć logowanie dwuskładnikowe. W każdej chwili eksportujesz całą firmę (CSV) i możesz usunąć konto — Twoje dane należą do Ciebie.</div>
      </details>
    </div>
    <div style="text-align:center;margin-top:32px;">
      <a href="{{ route('pl.faq') }}" class="btn btn-secondary">Wszystkie pytania →</a>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Zacznij dziś. Profesjonalnie od pierwszej faktury — i spokojnie, gdy ktoś nie płaci.</h2>
    <p>Konto, marka, wizytówka, strona www i pierwsza faktura — wszystko przed kolacją. A jeśli klient nie zapłaci, Creditline Polska jest o jedno kliknięcie.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Wypróbuj 14 dni za darmo
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Bez karty · potem od 49 zł netto miesięcznie</div>
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
