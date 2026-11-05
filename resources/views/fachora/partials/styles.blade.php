{{-- Wspólne style stron marketingowych Fachory (PL i EN). Kolory przez zmienne z public/brand/fachora/theme.css
     (huisstijl 1.105.0): --brand (granat), --accent (ciemna zieleń morska: akcje, linki, ikony), --accent-light (mięta,
     tylko na ciemnym tle), --brand-tint (jasna mięta), --star (żółty gwiazdek). --}}
<style>
  /* ===== Hero z wyszukiwarką ===== */
  .hero { padding: 72px 0 56px; }
  .fh-search { display: grid; grid-template-columns: 1.25fr 1fr auto; gap: 6px; max-width: 780px; margin: 0 auto 20px; padding: 8px; background: var(--surface); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow-lg); text-align: left; }
  .fh-search label { display: block; border-radius: 12px; transition: background 0.15s; }
  .fh-search label:focus-within { background: var(--brand-tint); }
  .fh-search .lbl { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-3); padding: 8px 14px 0; }
  .fh-search select, .fh-search input { width: 100%; border: 0; background: transparent; font: inherit; font-size: 15px; color: var(--text); padding: 3px 14px 10px; outline: none; }
  .fh-search input::placeholder { color: var(--text-4); }
  .fh-search select { appearance: none; -webkit-appearance: none; padding-right: 36px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 12px center; cursor: pointer; }
  .fh-search .btn { align-self: center; justify-content: center; margin: 2px; }
  @media (max-width: 720px) {
    .fh-search { grid-template-columns: 1fr; }
    .fh-search label { border-bottom: 1px solid var(--border); border-radius: 0; }
    .fh-search .btn { width: 100%; margin: 6px 0 0; }
  }
  .fh-ticks { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 18px; font-size: 13.5px; color: var(--text-2); list-style: none; padding: 0; margin: 0; }
  .fh-ticks li { display: inline-flex; align-items: center; gap: 7px; }
  .fh-ticks li::before { content: ''; width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0; background: var(--success-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='3.5'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E") center no-repeat; }
  .fh-ticks.left { justify-content: flex-start; }
  /* Pasek pod wyszukiwarką: „do 15 → maks. 5 → 1”, czyli jak działa wybór firm */
  .fh-pick { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; max-width: 880px; margin: 30px auto 0; text-align: left; }
  @media (max-width: 760px) { .fh-pick { grid-template-columns: 1fr; } }
  .fh-pick > div { display: flex; gap: 14px; align-items: center; padding: 16px 18px; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; }
  .fh-pick b { flex-shrink: 0; font-family: var(--font-display); font-size: 26px; font-weight: 800; letter-spacing: -0.02em; color: var(--brand); line-height: 1; white-space: nowrap; }
  .fh-pick span { font-size: 13.5px; color: var(--text-2); line-height: 1.45; }

  /* ===== Kafelki kategorii ===== */
  .fh-cats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
  @media (max-width: 1000px) { .fh-cats { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 600px) { .fh-cats { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
  .fh-cat { display: flex; flex-direction: column; gap: 9px; padding: 18px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
  .fh-cat:hover { border-color: var(--brand); transform: translateY(-2px); box-shadow: var(--shadow-md); }
  .fh-cat .ic { width: 44px; height: 44px; border-radius: 12px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; }
  .fh-cat b { font-family: var(--font-display); font-size: 16px; color: var(--text); letter-spacing: -0.01em; }
  .fh-cat span { font-size: 12.5px; color: var(--text-3); line-height: 1.45; }

  /* ===== Kroki ===== */
  .fh-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
  .fh-steps.cols-3 { grid-template-columns: repeat(3, 1fr); }
  @media (max-width: 900px) { .fh-steps, .fh-steps.cols-3 { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 560px) { .fh-steps, .fh-steps.cols-3 { grid-template-columns: 1fr; } }
  .fh-step { padding: 24px 22px 22px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; }
  .fh-step .nr { width: 34px; height: 34px; border-radius: 50%; background: var(--brand); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
  .fh-step h3 { font-size: 18px; margin-bottom: 6px; }
  .fh-step p { margin: 0; font-size: 14.5px; color: var(--text-2); line-height: 1.6; }
  .fh-step .tag { display: inline-block; margin-top: 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--brand); }
  /* Lista pionowa (strona „Jak to działa”) */
  .fh-steps.list { grid-template-columns: 1fr; max-width: 820px; margin: 0 auto; gap: 12px; }
  .fh-steps.list .fh-step { display: grid; grid-template-columns: 34px 1fr; gap: 18px; align-items: start; }
  .fh-steps.list .fh-step .nr { margin: 0; }
  .fh-steps.list .fh-step ul { margin: 10px 0 0; padding-left: 18px; font-size: 14px; color: var(--text-2); line-height: 1.6; }

  /* ===== Granatowy pas (fh-band: Protect, tylko gdy włączony) ===== */
  .fh-band { background: linear-gradient(160deg, #163D5F 0%, var(--brand) 60%, var(--brand-darker) 100%); color: #fff; padding: 88px 0; position: relative; overflow: hidden; }
  .fh-band::before { content: ''; position: absolute; width: 700px; height: 700px; border-radius: 50%; right: -260px; top: -320px; background: radial-gradient(circle, rgba(30,209,182,0.2) 0%, rgba(30,209,182,0) 62%); pointer-events: none; }
  .fh-band .eyebrow { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.18); color: var(--accent-light); }
  .fh-band .eyebrow::before { background: var(--accent-light); box-shadow: 0 0 0 4px rgba(30,209,182,0.2); }
  .fh-band h2 { color: #fff; font-size: clamp(28px, 3.6vw, 42px); max-width: 720px; }
  .fh-band .lead { color: rgba(255,255,255,0.8); font-size: 17px; max-width: 640px; margin: 14px 0 40px; line-height: 1.6; }
  .fh-band-grid { display: grid; grid-template-columns: 1.15fr 1fr; gap: 44px; align-items: start; position: relative; }
  @media (max-width: 900px) { .fh-band-grid { grid-template-columns: 1fr; } }
  .fh-flow { display: grid; gap: 12px; }
  .fh-flow-item { display: flex; gap: 16px; align-items: flex-start; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 18px 20px; }
  .fh-flow-item .nr { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; background: var(--accent); color: #fff; font-family: var(--font-display); font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .fh-flow-item b { display: block; font-size: 16px; margin-bottom: 4px; }
  .fh-flow-item p { margin: 0; font-size: 14px; color: rgba(255,255,255,0.75); line-height: 1.55; }
  .fh-band-card { background: #fff; color: var(--text); border-radius: 20px; padding: 26px 28px; box-shadow: 0 30px 80px rgba(0,0,0,0.3); }
  .fh-band-card .cap { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-3); margin-bottom: 12px; display: flex; justify-content: space-between; gap: 10px; }
  .fh-band-card .pill { font-size: 10.5px; font-weight: 700; color: #065F46; background: #D1FAE5; border-radius: 100px; padding: 3px 9px; }
  .fh-band-card table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .fh-band-card td { padding: 9px 0; border-top: 1px solid var(--border); vertical-align: top; }
  .fh-band-card td.r { text-align: right; font-family: var(--font-mono); font-size: 13px; white-space: nowrap; }
  .fh-band-card tr.tot td { border-top: 2px solid var(--brand-darker); font-weight: 700; font-size: 15px; }
  .fh-band-card .ex { font-size: 11px; color: var(--text-4); margin-top: 10px; }
  .fh-band .note { margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.75); font-size: 13.5px; line-height: 1.6; }
  .fh-band .note b { color: #fff; }

  /* ===== Przykładowy profil firmy ===== */
  .fh-companies { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  @media (max-width: 900px) { .fh-companies { grid-template-columns: 1fr; max-width: 520px; margin: 0 auto; } }
  .fh-company { position: relative; background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 22px 22px 20px; display: flex; flex-direction: column; gap: 10px; }
  .fh-company .ex { position: absolute; top: 14px; right: 14px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-3); background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 100px; padding: 3px 9px; }
  .fh-company .name { font-family: var(--font-display); font-weight: 700; font-size: 18px; color: var(--text); letter-spacing: -0.01em; margin-top: 6px; }
  .fh-company .meta { font-size: 13px; color: var(--text-3); }
  .fh-company .rating { font-size: 13.5px; color: var(--text-2); }
  .fh-company .rating b { color: var(--accent-dark); }
  .fh-company .langs { display: flex; gap: 6px; flex-wrap: wrap; }
  .fh-company .langs span { font-size: 12px; background: var(--surface-2); border-radius: 100px; padding: 2px 9px; color: var(--text-2); }
  .fh-company .ticks { list-style: none; padding: 0; margin: 4px 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 8px; font-size: 12.5px; color: var(--text-2); }
  .fh-company .ticks li::before { content: '✓ '; color: var(--success); font-weight: 700; }
  .fh-company .rates { font-size: 12.5px; color: var(--text-3); border-top: 1px solid var(--border); padding-top: 10px; margin-top: auto; line-height: 1.5; }

  /* ===== Cztery obietnice (USP) i listy kontrolne ===== */
  .fh-usps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
  .fh-usps.cols-3 { grid-template-columns: repeat(3, 1fr); }
  @media (max-width: 900px) { .fh-usps, .fh-usps.cols-3 { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 520px) { .fh-usps, .fh-usps.cols-3 { grid-template-columns: 1fr; } }
  .fh-usp { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px 22px; }
  .fh-usp .ic { width: 44px; height: 44px; border-radius: 12px; background: var(--accent-tint); color: var(--accent-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
  .fh-usp h3 { font-size: 18px; margin-bottom: 6px; }
  .fh-usp p { margin: 0; font-size: 14.5px; color: var(--text-2); line-height: 1.6; }
  .fh-checks { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 700px) { .fh-checks { grid-template-columns: 1fr; } }
  .fh-check { display: flex; gap: 14px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 18px; }
  .fh-check .ic { flex-shrink: 0; width: 36px; height: 36px; border-radius: 10px; background: var(--brand-tint); color: var(--brand); display: flex; align-items: center; justify-content: center; }
  .fh-check b { display: block; font-size: 15px; color: var(--text); margin-bottom: 2px; }
  .fh-check p { margin: 0; font-size: 14px; color: var(--text-2); line-height: 1.55; }

  /* ===== Dziesięć zasad Standardu ===== */
  .fh-rules { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; counter-reset: rule; }
  @media (max-width: 700px) { .fh-rules { grid-template-columns: 1fr; } }
  .fh-rule { display: flex; gap: 16px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; }
  .fh-rule .no { flex-shrink: 0; font-family: var(--font-mono); font-weight: 600; font-size: 13px; color: var(--accent-dark); background: var(--accent-tint); border-radius: 8px; padding: 4px 8px; margin-top: 1px; }
  .fh-rule b { display: block; font-size: 15.5px; color: var(--text); margin-bottom: 3px; }
  .fh-rule p { margin: 0; font-size: 14px; color: var(--text-2); line-height: 1.55; }

  /* ===== Cennik dla firm, terminy, liczby ===== */
  .fh-prices { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  @media (max-width: 900px) { .fh-prices { grid-template-columns: 1fr 1fr; } }
  .fh-price { text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 26px 18px 22px; }
  .fh-price.hl { border: 2px solid var(--brand); box-shadow: var(--shadow-brand); }
  .fh-price .v { font-family: var(--font-display); font-weight: 700; font-size: 40px; letter-spacing: -0.03em; line-height: 1; color: var(--brand-darker); }
  .fh-price .v small { font-size: 20px; font-weight: 600; }
  .fh-price b { display: block; margin-top: 10px; font-size: 15px; color: var(--text); }
  .fh-price span { display: block; margin-top: 4px; font-size: 13px; color: var(--text-3); line-height: 1.5; }
  .fh-times { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
  @media (max-width: 900px) { .fh-times { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 560px) { .fh-times { grid-template-columns: 1fr 1fr; } }
  .fh-time { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 18px 16px; text-align: center; }
  .fh-time .v { font-family: var(--font-display); font-weight: 700; font-size: 30px; color: var(--brand-darker); letter-spacing: -0.02em; line-height: 1.1; }
  .fh-time span { display: block; font-size: 13px; color: var(--text-2); margin-top: 6px; line-height: 1.45; }

  /* ===== Tabela porównawcza, ramka z uwagą, języki ===== */
  .fh-table { width: 100%; border-collapse: collapse; font-size: 14.5px; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
  .fh-table th, .fh-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
  .fh-table th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); background: var(--surface-2); }
  .fh-table tr:last-child td { border-bottom: 0; }
  .fh-table td.yes { color: var(--success); font-weight: 600; }
  .fh-table td.no { color: var(--text-4); }
  .fh-table-wrap { overflow-x: auto; }
  .fh-note { background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 14px; padding: 18px 20px; font-size: 14.5px; color: var(--text-2); line-height: 1.6; }
  .fh-note b { color: var(--brand-darker); }
  .fh-note.warm { background: var(--accent-tint); border-color: var(--brand-border); }
  .fh-langs { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
  .fh-lang { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 100px; font-weight: 600; font-size: 14px; color: var(--text); }
  .fh-lang .flag { font-size: 18px; line-height: 1; }

  /* ===== Trzy drogi dla firm, układ dwukolumnowy, FAQ ===== */
  .fh-ways { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
  @media (max-width: 800px) { .fh-ways { grid-template-columns: 1fr; } }
  .fh-way { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px 22px; display: flex; flex-direction: column; gap: 8px; }
  .fh-way .k { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); }
  .fh-way h3 { font-size: 18px; }
  .fh-way p { margin: 0 0 8px; font-size: 14.5px; color: var(--text-2); line-height: 1.6; }
  .fh-way .btn { align-self: flex-start; margin-top: auto; }
  .fh-split { display: grid; grid-template-columns: 1.1fr 1fr; gap: 40px; align-items: center; }
  @media (max-width: 860px) { .fh-split { grid-template-columns: 1fr; } }
  .fh-split h2 { font-size: clamp(26px, 3.4vw, 36px); margin-bottom: 14px; }
  .fh-split p { color: var(--text-2); font-size: 16px; line-height: 1.65; margin: 0 0 14px; }
  .fh-faq-group { max-width: 760px; margin: 34px auto 12px; font-size: 12.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-3); }
  .fh-faq-group:first-child { margin-top: 0; }
  .faq-content a { color: var(--brand); font-weight: 500; text-decoration: underline; }
  .fh-cta-row { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
  .fh-more { text-align: center; margin-top: 28px; }
  .fh-more a { color: var(--brand); font-weight: 600; }
  .fh-prose-wide { max-width: 860px; margin: 0 auto; }

  /* ===== Strona główna 1.105.0: granatowy hero z wyszukiwarką, zdjęciem i pływającymi kartami ===== */
  .fh-hero { background: linear-gradient(135deg, #0A2138 0%, #0F2A44 55%, #163D5F 100%); color: #fff; padding: 56px 0 0; position: relative; overflow: hidden; }
  .fh-hero::before { content: ''; position: absolute; width: 640px; height: 640px; border-radius: 50%; left: -260px; top: -300px; background: radial-gradient(circle, rgba(30,209,182,0.16) 0%, rgba(30,209,182,0) 62%); pointer-events: none; }
  .fh-hero-grid { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 36px; align-items: end; position: relative; }
  .fh-hero-text { padding-bottom: 52px; }
  .fh-hero h1 { color: #fff; font-size: clamp(38px, 5.2vw, 66px); font-weight: 800; line-height: 1.02; letter-spacing: -0.03em; margin: 0 0 18px; }
  .fh-hero h1 .accent { color: var(--accent-light); }
  .fh-hero-sub { font-size: clamp(16px, 1.3vw, 18px); color: rgba(255,255,255,0.86); line-height: 1.55; max-width: 600px; margin: 0 0 26px; }
  .fh-hero .fh-search { margin: 0 0 24px; max-width: none; box-shadow: 0 20px 50px rgba(0,0,0,0.28); }
  .fh-hero .fh-search label { position: relative; }
  .fh-hero .fh-search label .fi { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--accent); pointer-events: none; display: flex; }
  .fh-hero .fh-search .lbl { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); padding: 0; margin: -1px; }
  .fh-hero .fh-search select, .fh-hero .fh-search input { padding: 15px 14px 15px 44px; font-size: 15.5px; font-weight: 500; }
  .fh-hero .fh-search label + label { border-left: 1px solid var(--border); }
  .fh-usp3 { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 10px 0; }
  .fh-usp3 li { display: flex; align-items: center; gap: 12px; padding: 4px 24px 4px 0; margin-right: 24px; border-right: 1px solid rgba(255,255,255,0.16); font-size: 14.5px; line-height: 1.3; font-weight: 700; color: #fff; }
  .fh-usp3 li:last-child { border-right: 0; margin-right: 0; padding-right: 0; }
  .fh-usp3 .ic { flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px; border: 1.5px solid rgba(30,209,182,0.6); color: var(--accent-light); display: flex; align-items: center; justify-content: center; }
  .fh-usp3 small { display: block; font-weight: 400; color: rgba(255,255,255,0.75); font-size: 13px; }
  .fh-hero-visual { position: relative; align-self: stretch; min-height: 440px; }
  .fh-hero-visual img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 55% 30%; border-radius: 22px 22px 0 0; }
  .fh-hero-visual::after { content: ''; position: absolute; inset: 0; border-radius: 22px 22px 0 0; background: linear-gradient(180deg, rgba(10,33,56,0.18) 0%, rgba(10,33,56,0) 40%); pointer-events: none; }
  .fh-float { position: absolute; z-index: 2; display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #fff; color: var(--text); border-radius: 14px; box-shadow: 0 14px 40px rgba(0,0,0,0.25); font-size: 13.5px; line-height: 1.25; }
  .fh-float b { display: block; font-size: 14.5px; }
  .fh-float span { color: var(--text-3); font-size: 12.5px; }
  .fh-float .ic { flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; }
  .fh-float .ic.star { background: #FFF4D6; color: var(--star); }
  .fh-float .ic.shield { background: var(--accent-tint); color: var(--accent-dark); }
  .fh-float-1 { top: 44px; left: -28px; }
  .fh-float-2 { top: 38%; right: -18px; }
  .fh-float-3 { bottom: 20%; right: -8px; }
  @media (max-width: 1000px) {
    .fh-hero-grid { grid-template-columns: 1fr; gap: 28px; }
    .fh-hero-text { padding-bottom: 0; }
    .fh-hero-visual { min-height: 340px; max-width: 560px; }
    .fh-float-1 { left: 12px; } .fh-float-2, .fh-float-3 { right: 12px; }
  }
  @media (max-width: 720px) {
    .fh-hero { padding-top: 36px; }
    .fh-hero .fh-search label + label { border-left: 0; }
    .fh-usp3 li { border-right: 0; margin-right: 0; padding-right: 0; width: 100%; }
    .fh-float-2 { display: none; }
    .fh-hero-visual { min-height: 280px; }
  }
  /* Pasek „do 15 → maks. 5 → 1” pod hero */
  .fh-pickband { background: var(--surface); border-bottom: 1px solid var(--border); padding: 24px 0; }
  .fh-pickband .fh-pick { margin: 0; max-width: none; }

  /* ===== Kafelki kategorii ze zdjęciem (public/brand/fachora/cat/<klucz>.jpg; bez zdjęcia gradient) ===== */
  .fh-sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 26px; }
  .fh-sec-head h2 { font-size: clamp(26px, 3.4vw, 38px); }
  .fh-sec-head a { font-weight: 700; color: var(--accent-dark); white-space: nowrap; }
  @media (max-width: 640px) { .fh-sec-head { flex-direction: column; align-items: flex-start; gap: 6px; } }
  .fh-tiles { display: grid; grid-template-columns: repeat(7, 1fr); gap: 14px; }
  @media (max-width: 1100px) { .fh-tiles { grid-template-columns: repeat(4, 1fr); } }
  @media (max-width: 640px) { .fh-tiles { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
  .fh-tile { position: relative; display: flex; flex-direction: column; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; }
  .fh-tile:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--brand-border); }
  .fh-tile .ph { display: block; height: 84px; background: linear-gradient(135deg, #CCEAE3 0%, #E6F4F1 60%, #F6F9F9 100%) center/cover no-repeat; }
  .fh-tile .ph.has-photo { background-size: cover; background-position: center; }
  .fh-tile .ic { position: absolute; top: 62px; left: 14px; width: 44px; height: 44px; border-radius: 50%; background: #fff; color: var(--accent-dark); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(15,42,68,0.14); }
  .fh-tile .tx { padding: 28px 14px 12px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
  .fh-tile b { font-family: var(--font-display); font-size: 16px; color: var(--text); letter-spacing: -0.01em; }
  .fh-tile .ex { font-size: 12.5px; color: var(--text-3); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .fh-tile .go { margin-top: auto; padding-top: 6px; align-self: flex-end; color: var(--accent); display: flex; }

  /* ===== Jak to działa: cztery kroki obok przykładowego profilu ===== */
  .fh-how { display: grid; grid-template-columns: 1fr 340px; gap: 44px; align-items: start; }
  @media (max-width: 1000px) { .fh-how { grid-template-columns: 1fr; } .fh-excard { max-width: 440px; } }
  .fh-how-intro h2 { font-size: clamp(26px, 3.4vw, 38px); margin-bottom: 10px; }
  .fh-how-intro > p { color: var(--text-2); font-size: 16px; line-height: 1.6; max-width: 600px; margin: 0 0 28px; }
  .fh-how-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
  @media (max-width: 760px) { .fh-how-steps { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 460px) { .fh-how-steps { grid-template-columns: 1fr; } }
  .fh-how-step { position: relative; }
  .fh-how-step .ic { position: relative; width: 60px; height: 60px; border-radius: 16px; background: var(--accent-tint); color: var(--accent-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
  .fh-how-step .ic i { position: absolute; top: -8px; left: -8px; width: 24px; height: 24px; border-radius: 50%; background: var(--accent); color: #fff; font: 700 12px/24px var(--font-display); text-align: center; font-style: normal; }
  .fh-how-step h3 { font-size: 17px; margin-bottom: 6px; }
  .fh-how-step p { margin: 0; font-size: 13.5px; color: var(--text-2); line-height: 1.55; }
  .fh-how-step::after { content: ''; position: absolute; top: 24px; right: -15px; width: 9px; height: 9px; border-top: 2px solid var(--border-strong); border-right: 2px solid var(--border-strong); transform: rotate(45deg); }
  .fh-how-step:last-child::after { display: none; }
  @media (max-width: 760px) { .fh-how-step::after { display: none; } }
  .fh-excard { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 22px; box-shadow: var(--shadow-lg); position: relative; }
  .fh-excard .ex { position: absolute; top: 14px; right: 14px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-3); background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 100px; padding: 3px 9px; }
  .fh-excard .top { display: flex; gap: 14px; align-items: center; margin: 12px 0; }
  .fh-excard .av { flex-shrink: 0; width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, var(--accent-light), var(--accent)); color: #fff; font: 800 22px var(--font-display); display: flex; align-items: center; justify-content: center; }
  .fh-excard .name { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
  .fh-excard .meta { font-size: 13px; color: var(--text-3); }
  .fh-excard .row { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: var(--text-2); padding: 5px 0; }
  .fh-excard .row b { color: var(--text); }
  .fh-excard .row .st { color: var(--star); font-size: 16px; }
  .fh-excard .row svg { color: var(--accent); flex-shrink: 0; }
  .fh-excard .btn { width: 100%; justify-content: center; margin-top: 12px; }

  @media (prefers-reduced-motion: reduce) { .fh-cat, .fh-cat:hover, .fh-tile, .fh-tile:hover { transition: none; transform: none; } }
</style>
