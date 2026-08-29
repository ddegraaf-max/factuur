@extends('layouts.marketing')

{{-- Homepage van Lopra (APP_BRAND=lopra): het startpakket — administratie, huisstijl, visitekaartje en website in één. --}}

@section('title', 'Lopra — administratie, huisstijl en website voor starters, vanaf € 12,10 per maand')
@section('description', 'Begin professioneel vanaf dag één: factureren, offertes, btw-overzicht, huisstijl met AI, digitaal visitekaartje en je eigen website — alles wat een startende ondernemer nodig heeft, in één abonnement. 14 dagen gratis.')

@push('styles')
@include('lopra.partials.landing-styles')
@endpush

@section('content')

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor wie net begint</div>
    <h1>Je hele administratie <span class="accent">op één plek.</span></h1>
    <p class="hero-sub">
      Factureren, offertes, btw, huisstijl, visitekaartje en je eigen website. Alles wat je als startende ondernemer nodig hebt — in één abonnement, zonder boekhoudkennis.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Start 14 dagen gratis
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
    <div class="hero-trust">
      Geen creditcard nodig · 14 dagen gratis · Daarna vanaf <b>€ 12,10/maand incl. btw</b>
    </div>

    <!-- PRODUCTMOCKUP — klikbaar menu, wisselt automatisch van scherm -->
    <div class="app-mockup-wrap">
      <div class="lp-float one" aria-hidden="true">
        <div class="ic" style="background:var(--accent-tint);color:var(--accent-dark);">✦</div>
        <div><b>Huisstijl klaar</b><span>3 voorstellen · 1 gekozen · logo als SVG</span></div>
      </div>
      <div class="lp-float two" aria-hidden="true">
        <div class="ic" style="background:var(--success-bg);color:#047857;">✓</div>
        <div><b>€ 1.210,00 betaald via iDEAL</b><span>Factuur 2026-0007 · zojuist</span></div>
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
            <div class="d-navlabel">Overzicht</div>
            <div class="d-navitem active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>Dashboard</div>
            <div class="d-navlabel">Verkoop</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Facturen</div>
            <div class="d-navlabel">Jouw merk</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>Huisstijl</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>Visitekaartje</div>
            <div class="d-navitem"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Website</div>
          </aside>

          <div class="d-main">
            <div class="d-progress"><i id="lpProgress"></i></div>

            <!-- SCHERM 1: Dashboard -->
            <section class="d-screen active" data-screen="dashboard">
              <div class="d-topbar d-anim" style="--d:.05s">
                <div>
                  <div class="d-greet">Goedemorgen, Sanne</div>
                  <div class="d-sub">Je website leverde deze week 3 aanvragen op — en 2 facturen zijn betaald.</div>
                </div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nieuwe factuur</div>
              </div>
              <div class="d-kpis">
                <div class="d-kpi d-anim" style="--d:.12s"><div class="d-kpi-label">Openstaand</div><div class="d-kpi-value" data-count="1240" data-prefix="€ ">€ 1.240</div><div class="d-kpi-meta">2 facturen</div></div>
                <div class="d-kpi d-anim" style="--d:.19s"><div class="d-kpi-label">Betaald deze maand</div><div class="d-kpi-value" data-count="3180" data-prefix="€ ">€ 3.180</div><div class="d-kpi-meta"><span class="up">↑ 24%</span> vs vorige maand</div></div>
                <div class="d-kpi hi d-anim" style="--d:.26s"><span class="d-dot"></span><div class="d-kpi-label">Aanvragen via je website</div><div class="d-kpi-value copper" data-count="3">3</div><div class="d-kpi-meta">deze week · 1 al offerte</div></div>
                <div class="d-kpi d-anim" style="--d:.33s"><div class="d-kpi-label">Btw 3e kwartaal</div><div class="d-kpi-value" data-count="668" data-prefix="€ ">€ 668</div><div class="d-kpi-meta">aangifte vóór 31 okt.</div></div>
              </div>
              <div class="d-two">
                <div class="d-card d-anim" style="--d:.4s">
                  <div class="d-card-head"><div><div class="d-card-title">Omzet per maand</div><div class="d-card-sub">Excl. btw · sinds je start in februari</div></div><span class="d-card-link">Rapport →</span></div>
                  <div class="d-bars">
                    <div class="d-bar-col"><div class="d-bar" style="--h:22%;--d:.45s"></div><span>feb</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:38%;--d:.50s"></div><span>mrt</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:47%;--d:.55s"></div><span>apr</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:44%;--d:.60s"></div><span>mei</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:63%;--d:.65s"></div><span>jun</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:71%;--d:.70s"></div><span>jul</span></div>
                    <div class="d-bar-col"><div class="d-bar" style="--h:66%;--d:.75s"></div><span>aug</span></div>
                    <div class="d-bar-col"><div class="d-bar tall" style="--h:92%;--d:.80s"></div><span>sep</span></div>
                  </div>
                </div>
                <div class="d-card d-anim" style="--d:.48s">
                  <div class="d-card-head"><div><div class="d-card-title">Aanvragen via je website</div><div class="d-card-sub">Contactformulier → lead → offerte</div></div></div>
                  <div class="d-lead"><span class="d-avatar">FB</span><div class="who"><div class="t">Familie Bakker</div><div class="s">Advies woonkamer en keuken</div></div><span class="when">2 u</span></div>
                  <div class="d-lead"><span class="d-avatar">GL</span><div class="who"><div class="t">Café De Gouden Leeuw</div><div class="s">Inrichting nieuw terras</div></div><span class="when">gisteren</span></div>
                  <div class="d-lead"><span class="d-avatar">PZ</span><div class="who"><div class="t">Praktijk Zonnehoek</div><div class="s">Wachtruimte opnieuw inrichten</div></div><span class="when">3 d</span></div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.56s">
                <div class="d-card-head"><div class="d-card-title">Recente facturen</div><span class="d-card-link">Alle →</span></div>
                <table class="d-table">
                  <tbody>
                    <tr><td class="mono">2026-0007</td><td>Studio Noord</td><td class="d-hide">12 sep</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 1.210,00</td></tr>
                    <tr><td class="mono">2026-0006</td><td>Familie Jansen</td><td class="d-hide">9 sep</td><td><span class="d-pill blue">Verzonden</span></td><td class="mono right">€ 640,00</td></tr>
                    <tr><td class="mono">2026-0005</td><td>Café De Gouden Leeuw</td><td class="d-hide">2 sep</td><td><span class="d-pill amber">Deels betaald</span></td><td class="mono right">€ 2.420,00</td></tr>
                  </tbody>
                </table>
              </div>
            </section>

            <!-- SCHERM 2: Facturen -->
            <section class="d-screen" data-screen="facturen">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Facturen</div><div class="d-sub">7 facturen · € 1.240 openstaand · alles in je eigen huisstijl</div></div>
                <div class="d-newbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nieuwe factuur</div>
              </div>
              <div class="d-tabs d-anim" style="--d:.09s"><span class="d-tab active">Alle</span><span class="d-tab">Concept</span><span class="d-tab">Verzonden</span><span class="d-tab">Betaald</span><span class="d-tab">Achterstallig</span></div>
              <div class="d-card d-anim" style="--d:.14s">
                <table class="d-table">
                  <thead><tr><th>Nummer</th><th>Klant</th><th class="d-hide">Datum</th><th class="d-hide">Vervalt</th><th>Status</th><th class="right">Bedrag</th></tr></thead>
                  <tbody>
                    <tr class="d-fade" style="--d:.18s"><td class="mono">2026-0007</td><td>Studio Noord</td><td class="d-hide">12-09</td><td class="d-hide">26-09</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 1.210,00</td></tr>
                    <tr class="d-fade" style="--d:.24s"><td class="mono">2026-0006</td><td>Familie Jansen</td><td class="d-hide">09-09</td><td class="d-hide">23-09</td><td><span class="d-pill blue">Verzonden</span></td><td class="mono right">€ 640,00</td></tr>
                    <tr class="d-fade" style="--d:.30s"><td class="mono">2026-0005</td><td>Café De Gouden Leeuw</td><td class="d-hide">02-09</td><td class="d-hide">16-09</td><td><span class="d-pill amber">Deels betaald</span></td><td class="mono right">€ 2.420,00</td></tr>
                    <tr class="d-fade" style="--d:.36s"><td class="mono">2026-0004</td><td>Praktijk Zonnehoek</td><td class="d-hide">21-08</td><td class="d-hide">04-09</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 895,00</td></tr>
                    <tr class="d-fade" style="--d:.42s"><td class="mono">2026-0003</td><td>Familie Bakker</td><td class="d-hide">14-08</td><td class="d-hide">28-08</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 1.575,00</td></tr>
                    <tr class="d-fade" style="--d:.48s"><td class="mono">2026-0002</td><td>Studio Noord</td><td class="d-hide">03-08</td><td class="d-hide">17-08</td><td><span class="d-pill green">Betaald</span></td><td class="mono right">€ 480,00</td></tr>
                    <tr class="d-fade" style="--d:.54s"><td class="mono">— concept —</td><td>Familie Bakker</td><td class="d-hide">—</td><td class="d-hide">—</td><td><span class="d-pill gray">Concept</span></td><td class="mono right">€ 2.150,00</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="d-note d-fade" style="--d:.6s">✓ <b>iDEAL-link en QR-code</b> staan automatisch op elke factuur; herinneringen gaan vanzelf.</div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.64s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div><div><span class="big">€ 3.180</span><span>betaald deze maand</span></div></div>
                <div class="d-tile d-anim" style="--d:.7s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><span class="big">9 dagen</span><span>gemiddelde betaaltermijn</span></div></div>
                <div class="d-tile d-anim" style="--d:.76s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div><span class="big">1</span><span>herinnering, automatisch verstuurd</span></div></div>
              </div>
            </section>

            <!-- SCHERM 3: Huisstijl -->
            <section class="d-screen" data-screen="huisstijl">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Huisstijl</div><div class="d-sub">Drie voorstellen op basis van: interieurstylist · particulieren &amp; horeca · warm en persoonlijk</div></div>
                <div class="d-ai">✦ Opnieuw voorstellen</div>
              </div>
              <div class="d-props">
                <div class="d-prop chosen d-anim" style="--d:.12s">
                  <span class="badge">Gekozen</span>
                  <div class="d-logo" style="background:#2E4A3F;">dW</div>
                  <div class="n">Warm &amp; ambachtelijk</div>
                  <div class="f">Fraunces + Instrument Sans</div>
                  <div class="d-swatches"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i><i style="background:#1F1D1A"></i></div>
                  <div class="slogan">"Ruimtes die bij je passen."</div>
                  <span class="btn-mini">✓ Deze huisstijl gebruiken</span>
                </div>
                <div class="d-prop d-anim" style="--d:.2s">
                  <div class="d-logo" style="background:#1F3A5F;">DW</div>
                  <div class="n">Strak &amp; modern</div>
                  <div class="f">Manrope</div>
                  <div class="d-swatches"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i><i style="background:#101418"></i></div>
                  <div class="slogan">"Interieur met karakter."</div>
                  <span class="btn-mini">Kies</span>
                </div>
                <div class="d-prop d-anim" style="--d:.28s">
                  <div class="d-logo" style="background:#7A4B2B;">dw.</div>
                  <div class="n">Natuurlijk &amp; rustig</div>
                  <div class="f">Lora + Karla</div>
                  <div class="d-swatches"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i><i style="background:#2B2723"></i></div>
                  <div class="slogan">"Thuis, opnieuw ontdekt."</div>
                  <span class="btn-mini">Kies</span>
                </div>
              </div>
              <div class="d-note d-fade" style="--d:.4s">✦ <b>Door AI gemaakt uit vier vragen.</b> Eén klik zet kleuren, lettertype, sjabloon en logo op je facturen, je visitekaartje en je website. Alles blijft aanpasbaar.</div>
              <div class="d-styled">
                <div class="d-anim" style="--d:.46s">
                  <div class="lbl">Je factuur</div>
                  <div class="d-inv">
                    <div class="top"><span class="t">Factuur 2026-0007</span><span class="lg">dW</span></div>
                    <div class="row"><span>Interieuradvies woonkamer</span><span>€ 750,00</span></div>
                    <div class="row"><span>Stylingdag incl. materialen</span><span>€ 250,00</span></div>
                    <div class="row tot"><span>Totaal incl. btw</span><span>€ 1.210,00</span></div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.52s">
                  <div class="lbl">Je visitekaartje</div>
                  <div class="d-vcard mini">
                    <div class="who"><div class="av">S</div><div><div class="nm">Sanne de Wit</div><div class="rl">Interieurstylist</div></div></div>
                    <div class="ln">06 - 12 34 56 78</div>
                    <div class="ln site">Bekijk mijn website →</div>
                  </div>
                </div>
                <div class="d-anim" style="--d:.58s">
                  <div class="lbl">Je website</div>
                  <div class="d-site mini">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="hero"><div class="h">Ruimtes die bij je passen.</div><div class="p">Interieuradvies voor wie thuis wil komen.</div><span class="b">Plan een kennismaking</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
            </section>

            <!-- SCHERM 4: Visitekaartje -->
            <section class="d-screen" data-screen="visitekaartje">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Visitekaartje</div><div class="d-sub">{{ brand('domain') }}/k/dewit-interieur · deel de link of laat de QR-code scannen</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>Naam</label><div class="val">Sanne de Wit</div></div>
                  <div class="d-field"><label>Functie</label><div class="val">Interieurstylist</div></div>
                  <div class="d-field"><label>Slogan</label><div class="val">Ruimtes die bij je passen.</div></div>
                  <div class="d-field"><label>WhatsApp</label><div class="val">06 - 12 34 56 78</div></div>
                  <div class="d-field"><label>Knop</label><div class="val">Bekijk mijn website → {{ brand('domain') }}/s/dewit-interieur</div></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Voorbeeld</span><span>In je huisstijl</span></div>
                  <div class="d-vcard">
                    <div class="qr"><i></i><i></i><i class="o"></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i></div>
                    <div class="who"><div class="av">S</div><div><div class="nm">Sanne de Wit</div><div class="rl">Interieurstylist · De Wit Interieur</div></div></div>
                    <div class="ln">06 - 12 34 56 78</div>
                    <div class="ln">sanne@dewitinterieur.nl</div>
                    <div class="ln site">Bekijk mijn website →</div>
                  </div>
                </div>
              </div>
              <div class="d-tiles">
                <div class="d-tile d-anim" style="--d:.3s"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg></div><div><b>QR-code downloaden</b><span>PNG of SVG, voor op je offerte</span></div></div>
                <div class="d-tile d-anim" style="--d:.36s"><div class="ic copper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></div><div><b>Link kopiëren</b><span>deel via WhatsApp of e-mail</span></div></div>
                <div class="d-tile d-anim" style="--d:.42s"><div class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div><div><b>Opslaan in contacten</b><span>vCard met één tik, op elke telefoon</span></div></div>
              </div>
            </section>

            <!-- SCHERM 5: Website -->
            <section class="d-screen" data-screen="website">
              <div class="d-shead d-anim" style="--d:.04s">
                <div><div class="d-h1">Website</div><div class="d-sub">{{ brand('domain') }}/s/dewit-interieur · berichten komen binnen als leads</div></div>
                <div class="d-toggle"><i></i>Online</div>
              </div>
              <div class="d-two even">
                <div class="d-form d-anim" style="--d:.1s">
                  <div class="d-field"><label>Wat doe je?</label><div class="val multi">Interieuradvies en styling voor woningen en kleine horeca.</div></div>
                  <div class="d-field"><label>Voor wie?</label><div class="val multi">Particulieren en ondernemers in en rond Utrecht.</div></div>
                  <div class="d-field"><label>Waarom jij?</label><div class="val multi">Persoonlijk, binnen budget en in drie weken klaar.</div></div>
                  <div class="d-field"><label>Uitstraling</label><div class="val">Warm en persoonlijk</div></div>
                  <div><span class="d-ai">✦ Tekst laten schrijven</span></div>
                </div>
                <div class="d-preview d-anim" style="--d:.2s">
                  <div class="cap"><span>Voorbeeld</span><span>Volledig bewerkbaar</span></div>
                  <div class="d-site">
                    <div class="bar"><i></i><i></i><i></i></div>
                    <div class="nav">De Wit Interieur<div><span></span><span></span><span></span></div></div>
                    <div class="hero"><div class="h">Ruimtes die bij je passen.</div><div class="p">Interieuradvies voor wie thuis wil komen — persoonlijk, binnen budget, in drie weken klaar.</div><span class="b">Plan een kennismaking</span></div>
                    <div class="blocks"><div></div><div></div><div></div></div>
                  </div>
                </div>
              </div>
              <div class="d-card d-anim" style="--d:.3s; margin-top: 12px;">
                <div class="d-card-head"><div><div class="d-card-title">Aanvragen via je website</div><div class="d-card-sub">Contactformulier → lead → offerte, zonder overtypen</div></div><span class="d-pill copper">3 deze week</span></div>
                <div class="d-lead"><span class="d-avatar">FB</span><div class="who"><div class="t">Familie Bakker</div><div class="s">"Kunnen jullie ons helpen met de woonkamer en keuken?"</div></div><span class="when">2 u · offerte gemaakt</span></div>
                <div class="d-lead"><span class="d-avatar">GL</span><div class="who"><div class="t">Café De Gouden Leeuw</div><div class="s">"We openen in maart een terras en zoeken een stylist."</div></div><span class="when">gisteren</span></div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WAAROM LOPRA -->
<section class="section" id="waarom">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Waarom Lopra</div>
      <h2>Begin professioneel vanaf dag één.</h2>
      <p>De meeste starters kopen drie losse tools: een factuurprogramma, een logo-ontwerper en een websitebouwer. Lopra is alle drie — en ze werken samen.</p>
    </div>

    <div class="lp-vcards">
      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-props">
            <div class="lp-mini-prop"><span class="lg" style="background:#1F3A5F;">DW</span><div>Strak &amp; modern<small>Manrope · "Interieur met karakter."</small></div><div class="sw"><i style="background:#1F3A5F"></i><i style="background:#C9A227"></i><i style="background:#E8E4DD"></i></div></div>
            <div class="lp-mini-prop on"><span class="lg" style="background:#2E4A3F;">dW</span><div>Warm &amp; ambachtelijk <span class="ok">✓ Gekozen</span><small>Fraunces · "Ruimtes die bij je passen."</small></div><div class="sw"><i style="background:#2E4A3F"></i><i style="background:#D9A066"></i><i style="background:#F3EFE8"></i></div></div>
            <div class="lp-mini-prop"><span class="lg" style="background:#7A4B2B;">dw.</span><div>Natuurlijk &amp; rustig<small>Lora · "Thuis, opnieuw ontdekt."</small></div><div class="sw"><i style="background:#7A4B2B"></i><i style="background:#B8A48A"></i><i style="background:#F6F1EA"></i></div></div>
          </div>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Huisstijl met AI</span>
          <h3>Een huisstijl in een kwartier</h3>
          <p>Nog geen logo of kleuren? Vertel wat je doet en voor wie. Je krijgt drie voorstellen met kleuren, lettertype, factuursjabloon, slogan en logo. Eén klik, en alles draagt dezelfde stijl. Heb je al een logo? Upload het en Lopra neemt de kleuren over.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-site">
            <div class="bar"><i></i><i></i><i></i></div>
            <div class="nav">De Wit Interieur<div><span></span><span></span><span></span></div></div>
            <div class="hero"><div class="h">Ruimtes die bij je passen.</div><div class="p">Interieuradvies voor wie thuis wil komen.</div><span class="b">Plan een kennismaking</span></div>
            <div class="blocks"><div></div><div></div><div></div></div>
          </div>
          <span class="lp-online"><i></i>Online · 3 aanvragen deze week</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Visitekaartje &amp; website</span>
          <h3>Zichtbaar zonder webbouwer</h3>
          <p>Een digitaal visitekaartje met QR-code en een complete website met diensten, over jou en een contactformulier — beide in je huisstijl, beide met één klik online. Berichten komen als leads binnen, dus je maakt er direct een offerte van.</p>
        </div>
      </div>

      <div class="lp-vcard">
        <div class="lp-vcard-visual" aria-hidden="true">
          <div class="lp-mini-inv">
            <div class="top"><div class="t">Factuur 2026-0007</div><span class="lg">dW</span></div>
            <div class="row"><span>Interieuradvies woonkamer</span><span>€ 750,00</span></div>
            <div class="row"><span>Stylingdag incl. materialen</span><span>€ 250,00</span></div>
            <div class="row"><span>Btw 21%</span><span>€ 210,00</span></div>
            <div class="row tot"><span>Totaal</span><span>€ 1.210,00</span></div>
          </div>
          <span class="lp-mini-badge">✓ Betaald via iDEAL</span>
        </div>
        <div class="lp-vcard-body">
          <span class="lp-vcard-tag">Factureren &amp; btw</span>
          <h3>Factureren zoals het hoort</h3>
          <p>Offertes die je klant digitaal ondertekent, facturen met iDEAL-link en QR-code, herinneringen die vanzelf gaan. Je btw-overzicht staat elk kwartaal klaar in de indeling van de Belastingdienst. Je boekhouder kijkt gratis mee.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LOS KOPEN VS LOPRA -->
<section class="section section-alt">
  <div class="container">
    <div class="lp-compare">
      <div>
        <div class="eyebrow" style="margin-bottom:16px;">Reken het na</div>
        <h2>Wat je anders los zou kopen.</h2>
        <p style="color:var(--text-2);margin-top:12px;">Een startende ondernemer betaalt al snel voor een factuurprogramma, een logo, een websitebouwer én een visitekaartjes-app — vier abonnementen, vier inlogs, vier keer je gegevens invullen.</p>
        <ul class="lp-compare-list">
          <li><span>🧾</span><span><b>Factuurprogramma</b> — <s>€ 10 tot € 25 per maand</s></span></li>
          <li><span>🎨</span><span><b>Logo en huisstijl</b> — <s>€ 150 tot € 800 eenmalig</s></span></li>
          <li><span>🌐</span><span><b>Websitebouwer</b> — <s>€ 12 tot € 30 per maand</s></span></li>
          <li><span>📇</span><span><b>Digitaal visitekaartje</b> — <s>€ 5 tot € 10 per maand</s></span></li>
        </ul>
        <div class="lp-compare-sum">
          <div><div style="font-size:13px;opacity:0.75;">Bij Lopra zit het allemaal in één abonnement</div><div class="amount">€ 10<small>per maand excl. btw</small></div></div>
          <span class="lp-pill">Basis · alles inbegrepen</span>
        </div>
      </div>

      <div>
        <div class="lp-card-demo" aria-hidden="true">
          <div class="who">
            <div class="avatar">S</div>
            <div>
              <div class="name">Sanne de Wit</div>
              <div class="role">Interieurstylist · De Wit Interieur</div>
            </div>
          </div>
          <div class="lines">
            <div class="line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/></svg>06 - 12 34 56 78</div>
            <div class="line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>sanne@dewitinterieur.nl</div>
            <div class="line site"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Bekijk mijn website →</div>
          </div>
          <div class="qr">
            <i></i><i></i><i></i><i class="o"></i><i></i>
            <i></i><i class="o"></i><i></i><i></i><i class="o"></i>
            <i></i><i></i><i class="o"></i><i></i><i></i>
            <i class="o"></i><i></i><i></i><i class="o"></i><i></i>
            <i></i><i class="o"></i><i></i><i></i><i></i>
          </div>
        </div>
        <div class="lp-card-cap">Het digitale visitekaartje van een starter — in haar eigen huisstijl, gemaakt in Lopra.</div>
      </div>
    </div>
  </div>
</section>

<!-- FUNCTIES -->
<section class="section" id="functies">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Functies</div>
      <h2>Alles wat je nodig hebt om te beginnen. En om te groeien.</h2>
      <p>Geen boekhoudpakket met honderd knoppen. Wel alles om vandaag professioneel te starten — en wat je later nodig hebt, zit er al in.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg></div>
        <div class="feature-title">Facturen &amp; offertes</div>
        <div class="feature-desc">In drie klikken opgesteld, automatisch genummerd, in je eigen huisstijl verstuurd. Je klant ondertekent de offerte digitaal en betaalt met iDEAL of QR-code.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-title">Btw-overzicht, aangifte-klaar</div>
        <div class="feature-desc">Per kwartaal alle rubrieken in de indeling van Mijn Belastingdienst Zakelijk, met een herinnering vóór de deadline. Ook als je onder de KOR valt.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></div>
        <div class="feature-title">Huisstijl met AI</div>
        <div class="feature-desc">Drie voorstellen met kleuren, lettertype, sjabloon, slogan en logo — uit vier vragen. Of upload je eigen huisstijlgids en alles staat goed.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/></svg></div>
        <div class="feature-title">Digitaal visitekaartje</div>
        <div class="feature-desc">Eén link en een QR-code met al je contactgegevens: bellen, mailen, WhatsApp, opslaan in contacten. Voor op je offerte, je e-mailhandtekening en je telefoon.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></div>
        <div class="feature-title">Je eigen website</div>
        <div class="feature-desc">Een complete one-pager met diensten, over jou, waarom jij en een contactformulier. De tekst laat je schrijven door AI en pas je zelf aan. Online of offline met één schakelaar.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="feature-title">Uren &amp; kilometers</div>
        <div class="feature-desc">Schrijf uren met de timer, verkoop urenbundels die vanzelf aftellen en belast ritten door — alles met één klik op een factuur.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
        <div class="feature-title">Bonnetjes scannen</div>
        <div class="feature-desc">Fotografeer of forward een bon: leverancier, datum en bedragen worden herkend en de btw telt mee als voorbelasting. Jij bevestigt met één klik.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="feature-title">Boekhouder kijkt gratis mee</div>
        <div class="feature-desc">Nodig je boekhouder uit met een "alleen inzien"-rol, of download het auditfile (XAF) dat elk boekhoudpakket direct inleest.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div class="feature-title">Groeit met je mee</div>
        <div class="feature-desc">Herinneringen en incasso, e-facturatie via Peppol, bankkoppeling, meerdere handelsnamen en administraties, een klantenportaal — zonder over te stappen.</div>
      </div>
    </div>
  </div>
</section>

<!-- PRIJZEN -->
<section class="section section-alt" id="prijzen">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Eerlijke prijs</div>
      <h2>Eén abonnement. Alles inbegrepen.</h2>
      <p>Geen verborgen kosten, geen limieten op facturen of klanten. Opzeggen kan elke maand.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Alles wat een starter nodig heeft, zit in Basis.</h2>
        <p>Factureren, offertes, btw, visitekaartje én je website — voor de prijs die anderen alleen voor een factuurprogramma vragen. Wil je dat de AI je huisstijl ontwerpt, je websitetekst schrijft en je bonnetjes inboekt? Kies Slim.</p>
        <ul class="pricing-lead-points">
          <li>Digitaal visitekaartje en website inbegrepen</li>
          <li>Onbeperkt facturen, offertes, klanten en producten</li>
          <li>Meerdere handelsnamen én administraties</li>
          <li>14 dagen gratis proberen — inclusief alle AI-functies</li>
          <li>Persoonlijke ondersteuning · maandelijks opzegbaar</li>
        </ul>
      </div>

      <div class="pricing-cards">
        <div class="pricing-card basic">
          <div class="pricing-title">Basis</div>
          <div class="pricing-desc">Je administratie, je visitekaartje en je website</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>10</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 12,10 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt facturen, offertes en creditnota's</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digitaal visitekaartje met QR-code</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Je eigen website met contactformulier</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Huisstijl: eigen logo, kleuren en sjablonen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Digitaal ondertekenen, iDEAL-link en QR op je factuur</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Btw-, jaar- en cashflow-overzicht</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Uren, urenbundels en kilometers</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Herinneringen, aanmaningen en incasso</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Gratis extra gebruikers en je boekhouder</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-secondary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
        </div>

        <div class="pricing-card">
          <div class="pricing-badge">Meest gekozen door starters</div>
          <div class="pricing-title">Slim</div>
          <div class="pricing-desc">Alles uit Basis, plus de AI die je huisstijl ontwerpt en je administratie invult</div>
          <div class="pricing-price-row">
            <div class="pricing-price"><span class="euro">€</span>17,50</div>
            <div class="pricing-period">/ maand</div>
          </div>
          <div class="pricing-vat">Excl. 21% btw · € 21,18 incl. btw</div>
          <ul class="pricing-features">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><b>Alles uit Basis</b></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Huisstijl ontwerpen met AI: drie voorstellen met logo, kleuren en slogan</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Websitetekst geschreven door AI uit vier vragen</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Scan &amp; herken: bonnen en inkoopfacturen automatisch ingevuld</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Offerte uit tekst: plak je concept, het formulier vult zich in</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Claude-koppeling: maak offertes en facturen vanuit je gesprek</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Eerst proberen? De proefperiode bevat alle AI-functies</li>
          </ul>
          <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
          <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Veelgestelde vragen</div>
      <h2>Vragen die je als starter vast hebt.</h2>
      <p>Staat je vraag er niet bij? <a href="mailto:{{ brand('email') }}" style="color:var(--brand);font-weight:500;">Mail ons direct.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>
          Ik sta net ingeschreven bij de KvK. Wat heb ik minimaal nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je KvK-nummer, je btw-id (of de KOR-vrijstelling) en een zakelijke rekening. Meer niet. Lopra zet je bedrijfsgegevens automatisch op je facturen, nummert ze doorlopend en houdt de btw bij. Een huisstijl en een website maak je er in een kwartier bij.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Heb ik boekhoudkennis nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Nee. Lopra is gemaakt om zonder voorkennis te gebruiken: btw wordt automatisch berekend, je kwartaaloverzicht staat klaar in de indeling van de Belastingdienst, en je boekhouder kan gratis meekijken. Val je onder de Kleine Ondernemersregeling (KOR), dan regelt Lopra de juiste vermelding op je factuur.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Ik heb al een logo. Kan ik dat gebruiken?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja. Upload je logo en Lopra neemt de kleuren over voor je facturen, je visitekaartje en je website. Heb je een huisstijlgids? Upload die en alles staat in één keer goed. De AI-wizard is er voor wie nog níets heeft.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Hoe werkt de website? Heb ik een eigen domeinnaam nodig?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je website staat direct online op een eigen adres bij Lopra ({{ brand('domain') }}/s/jouw-bedrijf) — geen hosting, geen webbouwer. Een eigen domeinnaam is niet nodig; heb je er een, dan laat je die simpelweg doorverwijzen. De tekst schrijf je zelf of laat je door AI schrijven uit vier vragen, en je past hem altijd aan. Berichten via het contactformulier komen als leads in je administratie.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat kost het na de proefperiode?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je kiest uit <b>Basis</b> (€ 12,10 per maand incl. btw) met je administratie, visitekaartje en website, of <b>Slim</b> (€ 21,18 per maand incl. btw) met daarbovenop de AI-functies: huisstijl ontwerpen, websitetekst schrijven, bonnetjes herkennen en de Claude-koppeling. Geen contract, maandelijks opzegbaar, en je facturen blijven altijd downloadbaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat als mijn bedrijf groeit — of als ik wil overstappen?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Lopra groeit mee: meerdere handelsnamen en administraties, een klantenportaal, incasso, e-facturatie via Peppol en een bankkoppeling zitten er al in. En je data blijft van jou: exporteer je hele administratie als CSV of als auditfile (XAF) dat elk boekhoudpakket inleest.
        </div>
      </details>
    </div>
    <div style="text-align:center;margin-top:32px;">
      <a href="{{ route('faq') }}" class="btn btn-secondary">Alle veelgestelde vragen →</a>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Begin vandaag. Professioneel vanaf de eerste factuur.</h2>
    <p>Account, huisstijl, visitekaartje, website en je eerste factuur — allemaal vóór het avondeten.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Start 14 dagen gratis
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Geen creditcard nodig · Daarna vanaf € 12,10/maand incl. btw</div>
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
  var domain = urlEl ? urlEl.textContent.split('/')[0] : 'lopra.nl';
  var slugs = ['dashboard', 'facturen', 'instellingen/huisstijl', 'instellingen/visitekaartje', 'instellingen/website'];
  var DWELL = 4400;
  if (!screens.length) return;

  function fmt(n) { return n.toLocaleString('nl-NL'); }
  function setNum(el, val) { el.textContent = (el.getAttribute('data-prefix') || '') + fmt(Math.round(val)) + (el.getAttribute('data-suffix') || ''); }
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    if (reduce) { setNum(el, target); return; }
    var dur = 1100, s = null;
    function step(ts) {
      if (s === null) s = ts;
      var p = Math.min((ts - s) / dur, 1);
      setNum(el, target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step); else setNum(el, target);
    }
    requestAnimationFrame(step);
  }

  var current = -1, timer = null, hovering = false, started = false;

  function restartProgress() {
    if (!progEl || reduce) return;
    progEl.style.transition = 'none';
    progEl.style.width = '0%';
    void progEl.offsetWidth;
    progEl.style.transition = 'width ' + DWELL + 'ms linear';
    progEl.style.width = '100%';
  }

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

  navitems.forEach(function (item, i) {
    item.addEventListener('click', function () { show(i); if (!hovering && !reduce) play(); });
  });
  mock.addEventListener('mouseenter', function () { hovering = true; stop(); });
  mock.addEventListener('mouseleave', function () { hovering = false; if (started) { restartProgress(); play(); } });

  mock.classList.add('js-ready');

  function onEnter() {
    if (!started) { started = true; show(0); }
    if (!hovering) play();
  }

  if (reduce) { show(0); return; }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      for (var i = 0; i < entries.length; i++) {
        if (entries[i].isIntersecting) onEnter(); else stop();
      }
    }, { threshold: 0.3 });
    io.observe(mock);
  } else {
    onEnter();
  }
})();
</script>
@endsection
