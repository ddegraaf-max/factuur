{{-- Wspólne style stron marketingowych Fachory (PL i EN). Kolory przez zmienne z public/brand/fachora/theme.css:
     --brand (granat), --accent (pomarańcz: tylko główna akcja i znak Verified), --brand-tint (jasny błękit). --}}
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

  /* ===== Granatowy pas (Fachora Protect) ===== */
  .fh-band { background: linear-gradient(160deg, #143A66 0%, var(--brand) 60%, var(--brand-darker) 100%); color: #fff; padding: 88px 0; position: relative; overflow: hidden; }
  .fh-band::before { content: ''; position: absolute; width: 700px; height: 700px; border-radius: 50%; right: -260px; top: -320px; background: radial-gradient(circle, rgba(244,120,31,0.22) 0%, rgba(244,120,31,0) 62%); pointer-events: none; }
  .fh-band .eyebrow { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.18); color: var(--accent-light); }
  .fh-band .eyebrow::before { background: var(--accent); box-shadow: 0 0 0 4px rgba(244,120,31,0.2); }
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
  .fh-note.warm { background: var(--accent-tint); border-color: #F8C9A3; }
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

  @media (prefers-reduced-motion: reduce) { .fh-cat, .fh-cat:hover { transition: none; transform: none; } }
</style>
