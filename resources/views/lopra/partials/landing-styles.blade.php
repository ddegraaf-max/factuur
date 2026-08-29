{{-- Stijlen van de Lopra-landingspagina (NL én PL): hero-mockup, visuele kaarten, vergelijking. --}}
<style>
  /* ===== Hero ===== */
  .hero h1 .accent { white-space: nowrap; }

  /* Zwevende meldingen naast de mockup */
  .lp-float { position: absolute; z-index: 3; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 12px 16px 12px 12px; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 11px; text-align: left; animation: lpFloat 6s ease-in-out infinite; }
  .lp-float .ic { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; flex-shrink: 0; }
  .lp-float b { display: block; font-size: 13px; color: var(--text); }
  .lp-float span { display: block; font-size: 11.5px; color: var(--text-3); margin-top: 1px; }
  .lp-float.one { top: -30px; right: -36px; }
  .lp-float.two { bottom: 44px; left: -40px; animation-delay: -3s; }
  /* Extra's onder de schermen: tegels en previews in de gekozen huisstijl */
  #lpDash .d-tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px; margin-top: 12px; }
  #lpDash .d-tile { display: flex; align-items: center; gap: 10px; background: var(--surface); border: 1px solid var(--border); border-radius: 11px; padding: 11px 13px; }
  #lpDash .d-tile .ic { width: 32px; height: 32px; border-radius: 9px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  #lpDash .d-tile .ic svg { width: 16px; height: 16px; }
  #lpDash .d-tile .ic.copper { background: var(--accent-tint); color: var(--accent-dark); }
  #lpDash .d-tile .ic.green { background: var(--success-bg); color: #047857; }
  #lpDash .d-tile b { display: block; font-size: 12px; color: var(--text); }
  #lpDash .d-tile span { display: block; font-size: 10.5px; color: var(--text-3); margin-top: 1px; }
  #lpDash .d-tile .big { font-family: var(--font-display); font-weight: 600; font-size: 17px; color: var(--brand-darker); display: block; line-height: 1.1; }
  #lpDash .d-styled { display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px; margin-top: 12px; }
  #lpDash .d-styled .lbl { font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); font-weight: 700; margin-bottom: 7px; }
  #lpDash .d-inv { background: #fff; border: 1px solid var(--border); border-radius: 9px; padding: 11px 12px; font-size: 9.5px; color: var(--text-3); }
  #lpDash .d-inv .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
  #lpDash .d-inv .lg { width: 20px; height: 20px; border-radius: 5px; background: #2E4A3F; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 8.5px; display: flex; align-items: center; justify-content: center; }
  #lpDash .d-inv .t { font-family: var(--font-display); font-weight: 600; color: #2E4A3F; font-size: 11px; }
  #lpDash .d-inv .row { display: flex; justify-content: space-between; padding: 4px 0; border-top: 1px solid var(--border); }
  #lpDash .d-inv .row.tot { font-weight: 700; color: var(--text); }
  #lpDash .d-vcard.mini { padding: 11px; border-radius: 10px; }
  #lpDash .d-vcard.mini .av { width: 26px; height: 26px; font-size: 12px; border-radius: 7px; }
  #lpDash .d-vcard.mini .nm { font-size: 12px; }
  #lpDash .d-vcard.mini .rl { font-size: 9px; }
  #lpDash .d-vcard.mini .ln { font-size: 9.5px; padding: 5px 8px; margin-top: 5px; }
  #lpDash .d-vcard.mini .qr { display: none; }
  #lpDash .d-site.mini .hero { padding: 10px 10px 9px; }
  #lpDash .d-site.mini .hero .h { font-size: 11px; }
  #lpDash .d-site.mini .hero .p { font-size: 7.5px; margin: 3px 0 6px; }
  #lpDash .d-site.mini .blocks { padding: 7px 10px 9px; }
  #lpDash .d-site.mini .blocks div { height: 18px; }
  @media (max-width: 760px) { #lpDash .d-tiles, #lpDash .d-styled { grid-template-columns: 1fr; } }
  @keyframes lpFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
  @media (max-width: 1180px) { .lp-float { display: none; } }

  /* ===== Productmockup: Lopra-schermen (#lpDash) ===== */
  #lpDash .d-body { display: grid; grid-template-columns: 204px 1fr; height: 572px; background: var(--bg); text-align: left; }
  #lpDash .d-side { background: linear-gradient(180deg, #163E62 0%, #132F49 100%); padding: 14px 12px; display: flex; flex-direction: column; gap: 1px; }
  #lpDash .d-brand { display: flex; align-items: center; gap: 9px; padding: 4px 8px 14px; color: #fff; font-family: var(--font-display); font-weight: 600; font-size: 16px; letter-spacing: 0.01em; }
  #lpDash .d-brand img { width: 30px; height: 22px; display: block; }
  #lpDash .d-navlabel { font-size: 9px; text-transform: uppercase; letter-spacing: 0.09em; color: rgba(255,255,255,0.4); font-weight: 700; padding: 12px 10px 5px; }
  #lpDash .d-navitem { position: relative; display: flex; align-items: center; gap: 9px; padding: 7px 10px; border-radius: 7px; font-size: 12.5px; color: rgba(255,255,255,0.78); font-weight: 500; }
  #lpDash .d-navitem svg { width: 15px; height: 15px; opacity: 0.75; }
  #lpDash .d-navitem.active { background: rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
  #lpDash .d-navitem.active::before { content: ''; position: absolute; left: 0; top: 7px; bottom: 7px; width: 3px; border-radius: 3px; background: var(--accent-light); }
  #lpDash .d-navitem.active svg { opacity: 1; }
  #lpDash.js-ready .d-navitem { cursor: pointer; transition: background 0.15s, color 0.15s; }
  #lpDash.js-ready .d-navitem:not(.active):hover { background: rgba(255,255,255,0.07); color: #fff; }
  #lpDash .d-main { padding: 16px 20px; overflow: hidden; }
  #lpDash .d-progress { height: 3px; background: var(--surface-3); border-radius: 3px; margin: 0 0 14px; overflow: hidden; }
  #lpDash .d-progress i { display: block; height: 100%; width: 0; background: var(--accent); border-radius: 3px; }
  #lpDash .d-topbar, #lpDash .d-shead { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
  #lpDash .d-greet, #lpDash .d-h1 { font-family: var(--font-display); font-weight: 600; font-size: 19px; color: var(--brand-darker); }
  #lpDash .d-sub { font-size: 12px; color: var(--text-3); margin-top: 3px; }
  #lpDash .d-newbtn { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); color: #fff; font-size: 12px; font-weight: 600; padding: 8px 13px; border-radius: 8px; box-shadow: 0 4px 12px rgba(200,117,42,0.28); white-space: nowrap; }
  #lpDash .d-newbtn svg { width: 13px; height: 13px; }
  #lpDash .d-toggle { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 600; color: var(--success); white-space: nowrap; }
  #lpDash .d-toggle i { width: 30px; height: 17px; border-radius: 10px; background: var(--success); position: relative; display: inline-block; }
  #lpDash .d-toggle i::after { content: ''; position: absolute; right: 2px; top: 2px; width: 13px; height: 13px; border-radius: 50%; background: #fff; }

  #lpDash .d-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 11px; margin-bottom: 12px; }
  #lpDash .d-kpi { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 11px; padding: 12px 14px; }
  #lpDash .d-kpi.hi { border-color: #EBCBA8; background: linear-gradient(180deg, var(--accent-tint) 0%, var(--surface) 70%); }
  #lpDash .d-kpi-label { font-size: 10.5px; color: var(--text-3); font-weight: 500; margin-bottom: 7px; }
  #lpDash .d-kpi-value { font-family: var(--font-display); font-weight: 600; font-size: 21px; color: var(--brand-darker); font-variant-numeric: tabular-nums; line-height: 1; }
  #lpDash .d-kpi-value.copper { color: var(--accent-dark); }
  #lpDash .d-kpi-meta { font-size: 10.5px; color: var(--text-3); margin-top: 6px; }
  #lpDash .d-kpi-meta .up { color: var(--success); font-weight: 700; }
  #lpDash .d-dot { position: absolute; top: 11px; right: 11px; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
  #lpDash .d-screen.active .d-dot { animation: lpPulse 2s ease-in-out infinite; }
  @keyframes lpPulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(200,117,42,0.5); } 50% { box-shadow: 0 0 0 6px rgba(200,117,42,0); } }

  #lpDash .d-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 13px 16px; margin-bottom: 12px; }
  #lpDash .d-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
  #lpDash .d-card-title { font-family: var(--font-display); font-weight: 600; font-size: 13.5px; color: var(--text); }
  #lpDash .d-card-sub { font-size: 10.5px; color: var(--text-3); margin-top: 2px; }
  #lpDash .d-card-link { font-size: 11px; color: var(--brand); font-weight: 600; }
  #lpDash .d-two { display: grid; grid-template-columns: 1.25fr 1fr; gap: 12px; }
  #lpDash .d-two.even { grid-template-columns: 1fr 1fr; }

  #lpDash .d-bars { display: flex; align-items: flex-end; gap: 7px; height: 92px; }
  #lpDash .d-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; }
  #lpDash .d-bar-col span { font-size: 9px; color: var(--text-4); font-weight: 500; }
  #lpDash .d-bar { width: 100%; max-width: 26px; border-radius: 4px 4px 0 0; background: var(--brand-tint-2); height: var(--h); transition: height 0.9s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #lpDash .d-bar.tall { background: var(--brand); }
  #lpDash.js-ready .d-screen .d-bar { height: 0; }
  #lpDash.js-ready .d-screen.active .d-bar { height: var(--h); }

  #lpDash .d-table { width: 100%; border-collapse: collapse; }
  #lpDash .d-table td { padding: 7px 6px; font-size: 11.5px; color: var(--text-2); border-top: 1px solid var(--border); }
  #lpDash .d-table tr:first-child td { border-top: none; }
  #lpDash .d-table thead th { text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); font-weight: 700; padding: 0 6px 8px; border-bottom: 1px solid var(--border); }
  #lpDash .d-table thead th.right { text-align: right; }
  #lpDash .d-table .mono { font-family: var(--font-mono); font-size: 11px; color: var(--text-3); }
  #lpDash .d-table .right { text-align: right; color: var(--text); font-weight: 600; }
  #lpDash .d-pill { display: inline-block; font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 100px; white-space: nowrap; }
  #lpDash .d-pill.green { background: var(--success-bg); color: #047857; }
  #lpDash .d-pill.blue { background: var(--brand-tint-2); color: var(--brand); }
  #lpDash .d-pill.amber { background: #FEF3C7; color: #B45309; }
  #lpDash .d-pill.gray { background: var(--surface-3); color: var(--text-3); }
  #lpDash .d-pill.copper { background: var(--accent-tint); color: var(--accent-dark); }

  #lpDash .d-lead { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border); font-size: 11.5px; min-width: 0; }
  #lpDash .d-lead:first-child { border-top: none; padding-top: 2px; }
  #lpDash .d-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--brand-tint-2); color: var(--brand); font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  #lpDash .d-lead .who { min-width: 0; }
  #lpDash .d-lead .t { font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  #lpDash .d-lead .s { color: var(--text-3); font-size: 10.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  #lpDash .d-lead .when { margin-left: auto; font-size: 10px; color: var(--text-4); white-space: nowrap; }

  #lpDash .d-tabs { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
  #lpDash .d-tab { font-size: 11px; font-weight: 600; color: var(--text-3); padding: 5px 11px; border-radius: 100px; background: var(--surface); border: 1px solid var(--border); }
  #lpDash .d-tab.active { background: var(--brand-darker); color: #fff; border-color: var(--brand-darker); }

  /* Huisstijl-voorstellen */
  #lpDash .d-props { display: grid; grid-template-columns: repeat(3, 1fr); gap: 11px; }
  #lpDash .d-prop { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
  #lpDash .d-prop.chosen { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-tint); }
  #lpDash .d-prop .badge { position: absolute; top: 10px; right: 10px; font-size: 9.5px; font-weight: 700; color: var(--accent-dark); background: var(--accent-tint); border-radius: 100px; padding: 3px 8px; }
  #lpDash .d-logo { width: 46px; height: 46px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 18px; color: #fff; margin-bottom: 10px; }
  #lpDash .d-prop .n { font-size: 12.5px; font-weight: 700; color: var(--text); }
  #lpDash .d-prop .f { font-size: 10.5px; color: var(--text-3); margin: 2px 0 8px; }
  #lpDash .d-swatches { display: flex; gap: 5px; margin-bottom: 8px; }
  #lpDash .d-swatches i { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--border); }
  #lpDash .d-prop .slogan { font-size: 11.5px; font-style: italic; color: var(--text-2); font-family: var(--font-display); }
  #lpDash .d-prop .btn-mini { margin-top: 10px; display: inline-block; font-size: 10.5px; font-weight: 600; padding: 6px 10px; border-radius: 7px; background: var(--surface-2); color: var(--text-2); }
  #lpDash .d-prop.chosen .btn-mini { background: var(--accent); color: #fff; }
  #lpDash .d-note { display: flex; align-items: center; gap: 8px; font-size: 11px; color: var(--text-3); margin-top: 12px; }
  #lpDash .d-note b { color: var(--accent-dark); }

  /* Formulier + preview (visitekaartje / website) */
  #lpDash .d-form { display: grid; gap: 9px; align-content: start; }
  #lpDash .d-field label { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-4); font-weight: 700; margin-bottom: 4px; }
  #lpDash .d-field .val { font-size: 11.5px; color: var(--text); background: var(--surface); border: 1px solid var(--border-strong); border-radius: 8px; padding: 7px 10px; }
  #lpDash .d-field .val.multi { color: var(--text-2); line-height: 1.4; }
  #lpDash .d-preview { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
  #lpDash .d-preview .cap { display: flex; justify-content: space-between; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-4); font-weight: 700; margin-bottom: 9px; }
  #lpDash .d-ai { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #fff; background: var(--brand); border-radius: 8px; padding: 7px 11px; }

  /* Visitekaartje in de huisstijl van de starter (groen + terracotta) */
  #lpDash .d-vcard { position: relative; background: #2E4A3F; border-radius: 14px; padding: 14px; color: #fff; overflow: hidden; }
  #lpDash .d-vcard::before { content: ''; position: absolute; width: 180px; height: 180px; border-radius: 50%; right: -70px; top: -90px; background: radial-gradient(circle, rgba(217,160,102,0.35) 0%, rgba(217,160,102,0) 65%); }
  #lpDash .d-vcard .who { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; position: relative; }
  #lpDash .d-vcard .av { width: 34px; height: 34px; border-radius: 9px; background: #D9A066; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 15px; }
  #lpDash .d-vcard .nm { font-family: var(--font-display); font-weight: 600; font-size: 14px; }
  #lpDash .d-vcard .rl { font-size: 10px; opacity: 0.7; }
  #lpDash .d-vcard .ln { display: flex; align-items: center; gap: 8px; font-size: 10.5px; padding: 7px 10px; border-radius: 8px; background: rgba(255,255,255,0.1); margin-top: 6px; position: relative; }
  #lpDash .d-vcard .ln.site { background: #D9A066; font-weight: 600; }
  #lpDash .d-vcard .qr { position: absolute; right: 12px; top: 12px; width: 38px; height: 38px; background: #fff; border-radius: 6px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; padding: 5px; }
  #lpDash .d-vcard .qr i { background: #2E4A3F; border-radius: 1px; }
  #lpDash .d-vcard .qr i.o { background: transparent; }

  /* Website-preview */
  #lpDash .d-site { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #fff; }
  #lpDash .d-site .bar { display: flex; gap: 4px; padding: 6px 8px; background: var(--surface-2); border-bottom: 1px solid var(--border); }
  #lpDash .d-site .bar i { width: 6px; height: 6px; border-radius: 50%; background: var(--border-strong); }
  #lpDash .d-site .nav { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; font-size: 9.5px; font-weight: 700; color: #2E4A3F; }
  #lpDash .d-site .nav div { display: flex; gap: 6px; }
  #lpDash .d-site .nav span { width: 22px; height: 3px; background: var(--border); border-radius: 2px; }
  #lpDash .d-site .hero { padding: 14px 12px 12px; background: #F3EFE8; }
  #lpDash .d-site .hero .h { font-family: var(--font-display); font-weight: 600; font-size: 13.5px; color: #2E4A3F; line-height: 1.2; }
  #lpDash .d-site .hero .p { font-size: 8.5px; color: #6B675F; margin: 5px 0 8px; line-height: 1.4; }
  #lpDash .d-site .hero .b { display: inline-block; font-size: 8.5px; font-weight: 700; color: #fff; background: #D9A066; border-radius: 5px; padding: 4px 8px; }
  #lpDash .d-site .blocks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; padding: 10px 12px 12px; }
  #lpDash .d-site .blocks div { height: 30px; border-radius: 6px; background: #F3EFE8; border: 1px solid #E6E1D8; }

  /* Tour: schermen + animaties */
  #lpDash .d-screen { display: none; }
  #lpDash .d-screen:first-of-type { display: block; }
  #lpDash.js-ready .d-screen { display: none; }
  #lpDash.js-ready .d-screen.active { display: block; animation: lpIn 0.45s ease both; }
  @keyframes lpIn { from { opacity: 0.3; } to { opacity: 1; } }
  #lpDash .d-anim, #lpDash .d-fade { transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1); transition-delay: var(--d, 0s); }
  #lpDash.js-ready .d-screen .d-anim { opacity: 0; transform: translateY(12px); }
  #lpDash.js-ready .d-screen .d-fade { opacity: 0; }
  #lpDash.js-ready .d-screen.active .d-anim,
  #lpDash.js-ready .d-screen.active .d-fade { opacity: 1; transform: none; }

  @media (max-width: 760px) {
    #lpDash .d-body { grid-template-columns: 1fr; height: auto; }
    #lpDash .d-side { display: none; }
    #lpDash .d-kpis { grid-template-columns: repeat(2, 1fr); }
    #lpDash .d-two, #lpDash .d-two.even { grid-template-columns: 1fr; }
    #lpDash .d-props { grid-template-columns: 1fr; }
    #lpDash .d-hide { display: none; }
  }
  @media (prefers-reduced-motion: reduce) {
    #lpDash .d-anim, #lpDash .d-fade, #lpDash .d-bar { transition: none !important; }
    #lpDash .d-screen.active .d-dot { animation: none !important; }
    #lpDash.js-ready .d-screen.active { animation: none !important; }
    .lp-float { animation: none !important; }
  }

  /* ===== Waarom Lopra: drie visuele kaarten ===== */
  .lp-vcards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  .lp-vcard { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; }
  .lp-vcard-visual { position: relative; height: 216px; padding: 22px; overflow: hidden; background: linear-gradient(180deg, var(--brand-tint) 0%, #F7F5F1 100%); border-bottom: 1px solid var(--border); }
  .lp-vcard-body { padding: 22px 26px 28px; }
  .lp-vcard-tag { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--accent-dark); margin-bottom: 8px; }
  .lp-vcard h3 { font-size: 19px; margin-bottom: 8px; color: var(--brand-darker); }
  .lp-vcard p { color: var(--text-2); font-size: 14.5px; line-height: 1.6; margin: 0; }
  @media (max-width: 900px) { .lp-vcards { grid-template-columns: 1fr; } }

  .lp-mini-props { display: grid; gap: 8px; }
  .lp-mini-prop { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--border); border-radius: 11px; padding: 9px 11px; font-size: 11.5px; color: var(--text); font-weight: 600; box-shadow: var(--shadow-sm); }
  .lp-mini-prop .lg { width: 30px; height: 30px; border-radius: 8px; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .lp-mini-prop small { display: block; font-weight: 400; color: var(--text-3); font-size: 10.5px; }
  .lp-mini-prop .sw { display: flex; gap: 4px; margin-left: auto; }
  .lp-mini-prop .sw i { width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px var(--border); }
  .lp-mini-prop.on { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-tint); }
  .lp-mini-prop .ok { font-size: 9.5px; font-weight: 700; color: var(--accent-dark); background: var(--accent-tint); border-radius: 100px; padding: 2px 7px; margin-left: 6px; }

  .lp-mini-site { background: #fff; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-md); transform: rotate(-1.5deg); margin: 2px 8px 0; }
  .lp-mini-site .bar { display: flex; gap: 4px; padding: 7px 9px; background: var(--surface-2); border-bottom: 1px solid var(--border); }
  .lp-mini-site .bar i { width: 7px; height: 7px; border-radius: 50%; background: var(--border-strong); }
  .lp-mini-site .nav { display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 10px; font-weight: 700; color: #2E4A3F; }
  .lp-mini-site .nav div { display: flex; gap: 6px; }
  .lp-mini-site .nav span { width: 24px; height: 3px; background: var(--border); border-radius: 2px; }
  .lp-mini-site .hero { padding: 14px 12px 12px; background: #F3EFE8; }
  .lp-mini-site .hero .h { font-family: var(--font-display); font-weight: 600; font-size: 15px; color: #2E4A3F; line-height: 1.2; }
  .lp-mini-site .hero .p { font-size: 9px; color: #6B675F; margin: 5px 0 8px; }
  .lp-mini-site .hero .b { display: inline-block; font-size: 9px; font-weight: 700; color: #fff; background: #D9A066; border-radius: 5px; padding: 4px 9px; }
  .lp-mini-site .blocks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; padding: 10px 12px 12px; }
  .lp-mini-site .blocks div { height: 26px; border-radius: 6px; background: #F3EFE8; border: 1px solid #E6E1D8; }
  .lp-online { position: absolute; left: 22px; bottom: 16px; display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid var(--border); border-radius: 100px; padding: 5px 11px; font-size: 11px; font-weight: 700; color: var(--success); box-shadow: var(--shadow-md); }
  .lp-online i { width: 7px; height: 7px; border-radius: 50%; background: var(--success); }

  .lp-mini-inv { background: #fff; border-radius: 8px; box-shadow: var(--shadow-md); padding: 16px 18px; width: 84%; margin: 0 auto; transform: rotate(1.5deg); font-size: 10.5px; color: var(--text-3); }
  .lp-mini-inv .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .lp-mini-inv .lg { width: 28px; height: 28px; border-radius: 7px; background: #2E4A3F; display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 11px; }
  .lp-mini-inv .t { font-family: var(--font-display); font-weight: 600; color: var(--text); font-size: 12.5px; }
  .lp-mini-inv .row { display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid var(--border); }
  .lp-mini-inv .row.tot { font-weight: 700; color: var(--text); border-top-width: 2px; }
  .lp-mini-badge { position: absolute; right: 18px; bottom: 18px; background: #fff; border: 1px solid #A7E3C0; color: #047857; font-weight: 700; font-size: 11.5px; border-radius: 100px; padding: 7px 12px; box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 6px; }

  /* ===== "Los kopen" vergelijking ===== */
  .lp-compare { display: grid; grid-template-columns: 1.1fr 1fr; gap: 40px; align-items: center; }
  .lp-compare-list { list-style: none; padding: 0; margin: 18px 0 0; display: grid; gap: 10px; }
  .lp-compare-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; font-size: 14.5px; color: var(--text-2); }
  .lp-compare-list li s { color: var(--text-4); }
  .lp-compare-list li b { color: var(--text); }
  .lp-compare-sum { margin-top: 18px; padding: 18px 20px; border-radius: 14px; background: var(--brand-darker); color: #fff; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
  .lp-compare-sum .amount { font-family: var(--font-display); font-size: 30px; font-weight: 600; letter-spacing: -0.01em; }
  .lp-compare-sum .amount small { font-family: var(--font-body); font-size: 13px; font-weight: 500; opacity: 0.75; margin-left: 4px; }
  .lp-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--accent-light); background: rgba(255,255,255,0.1); border-radius: 100px; padding: 5px 12px; }
  @media (max-width: 900px) { .lp-compare { grid-template-columns: 1fr; } }

  /* Voorbeeld-visitekaartje in de huisstijl van de starter */
  .lp-card-demo { position: relative; background: #2E4A3F; border-radius: 22px; padding: 32px; color: #fff; box-shadow: var(--shadow-lg); overflow: hidden; }
  .lp-card-demo::before { content: ''; position: absolute; width: 320px; height: 320px; border-radius: 50%; right: -120px; top: -140px; background: radial-gradient(circle, rgba(217,160,102,0.32) 0%, rgba(217,160,102,0) 65%); }
  .lp-card-demo .who { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; position: relative; }
  .lp-card-demo .avatar { width: 52px; height: 52px; border-radius: 14px; background: #D9A066; color: #fff; font-family: var(--font-display); font-weight: 600; font-size: 22px; display: flex; align-items: center; justify-content: center; }
  .lp-card-demo .name { font-family: var(--font-display); font-size: 20px; font-weight: 600; }
  .lp-card-demo .role { font-size: 13px; opacity: 0.7; }
  .lp-card-demo .lines { display: grid; gap: 8px; position: relative; }
  .lp-card-demo .line { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: rgba(255,255,255,0.08); font-size: 13.5px; }
  .lp-card-demo .line svg { width: 16px; height: 16px; opacity: 0.85; flex-shrink: 0; }
  .lp-card-demo .line.site { background: #D9A066; color: #fff; font-weight: 600; }
  .lp-card-demo .qr { position: absolute; right: 28px; bottom: 28px; width: 66px; height: 66px; border-radius: 10px; background: #fff; display: grid; grid-template-columns: repeat(5, 1fr); gap: 3px; padding: 8px; }
  .lp-card-demo .qr i { background: #2E4A3F; border-radius: 2px; }
  .lp-card-demo .qr i.o { background: transparent; }
  .lp-card-cap { margin-top: 12px; font-size: 12px; color: var(--text-3); text-align: center; }
</style>
