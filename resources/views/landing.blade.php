<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="EasyInvoice — eenvoudige facturatie voor Nederlandse ondernemers. Facturen, BTW, klanten en incasso vanaf €2,50 per maand.">
<title>EasyInvoice — Facturatie zonder gedoe vanaf €2,50/maand</title>
<link rel="icon" type="image/png" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23E8231F'/%3E%3Ctext x='16' y='22' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='700' font-size='18'%3EE%3C/text%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --brand: #E8231F;
    --brand-dark: #C71A17;
    --brand-darker: #9C1411;
    --brand-tint: #FEF2F2;
    --brand-tint-2: #FEE2E2;
    --brand-border: #FECACA;
    --text: #1C1917;
    --text-2: #44403C;
    --text-3: #78716C;
    --text-4: #A8A29E;
    --bg: #FAFAF9;
    --surface: #FFFFFF;
    --surface-2: #F5F5F4;
    --surface-3: #E7E5E4;
    --border: #E7E5E4;
    --border-strong: #D6D3D1;
    --success: #059669;
    --success-bg: #D1FAE5;
    --info: #0369A1;
    --warning: #D97706;
    --font-display: 'Bricolage Grotesque', system-ui, sans-serif;
    --font-body: 'DM Sans', system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
    --shadow-sm: 0 1px 2px rgba(28, 25, 23, 0.04);
    --shadow-md: 0 4px 12px rgba(28, 25, 23, 0.06);
    --shadow-lg: 0 12px 40px rgba(28, 25, 23, 0.1);
    --shadow-brand: 0 12px 40px rgba(232, 35, 31, 0.18);
  }
  *, *::before, *::after { box-sizing: border-box; }
  html { scroll-behavior: smooth; }
  body {
    margin: 0;
    font-family: var(--font-body);
    color: var(--text);
    background: var(--bg);
    -webkit-font-smoothing: antialiased;
    line-height: 1.6;
    font-size: 16px;
  }
  a { color: inherit; text-decoration: none; }
  img { max-width: 100%; display: block; }
  button { font-family: inherit; cursor: pointer; }
  .container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

  /* TYPOGRAPHY */
  h1, h2, h3, h4 { font-family: var(--font-display); font-weight: 600; letter-spacing: -0.02em; line-height: 1.15; margin: 0; color: var(--text); }
  .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--brand); padding: 6px 14px; background: var(--brand-tint); border: 1px solid var(--brand-border); border-radius: 100px; margin-bottom: 20px; }
  .eyebrow::before { content: ''; width: 6px; height: 6px; background: var(--brand); border-radius: 50%; box-shadow: 0 0 0 4px rgba(232, 35, 31, 0.15); }

  /* BUTTONS */
  .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 100px; font-size: 14px; font-weight: 600; border: 1px solid transparent; transition: all 0.15s; cursor: pointer; text-decoration: none; white-space: nowrap; font-family: inherit; }
  .btn-lg { padding: 15px 28px; font-size: 15px; }
  .btn-primary { background: var(--brand); color: white; border-color: var(--brand); box-shadow: 0 4px 14px rgba(232, 35, 31, 0.25); }
  .btn-primary:hover { background: var(--brand-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(232, 35, 31, 0.35); }
  .btn-secondary { background: var(--surface); color: var(--text); border-color: var(--border-strong); }
  .btn-secondary:hover { background: var(--surface-2); border-color: var(--text-4); }
  .btn-ghost { background: transparent; color: var(--text-2); }
  .btn-ghost:hover { background: var(--surface-2); color: var(--text); }
  .btn-white { background: white; color: var(--brand); }
  .btn-white:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }

  /* NAV */
  .nav { position: sticky; top: 0; z-index: 50; background: rgba(250, 250, 249, 0.85); backdrop-filter: saturate(180%) blur(12px); -webkit-backdrop-filter: saturate(180%) blur(12px); border-bottom: 1px solid var(--border); }
  .nav-inner { display: flex; align-items: center; justify-content: space-between; height: 68px; }
  .nav-brand { display: flex; align-items: center; gap: 10px; font-family: var(--font-display); font-weight: 700; font-size: 18px; letter-spacing: -0.02em; color: var(--text); }
  .nav-brand img { width: 30px; height: 30px; border-radius: 6px; }
  .nav-links { display: flex; align-items: center; gap: 6px; }
  .nav-link { padding: 8px 14px; font-size: 14px; font-weight: 500; color: var(--text-2); border-radius: 8px; transition: all 0.15s; }
  .nav-link:hover { color: var(--text); background: var(--surface-2); }
  .nav-actions { display: flex; align-items: center; gap: 8px; }
  @media (max-width: 880px) {
    .nav-links { display: none; }
  }

  /* HERO */
  .hero { padding: 80px 0 60px; position: relative; overflow: hidden; }
  .hero::before { content: ''; position: absolute; top: -200px; left: 50%; transform: translateX(-50%); width: 800px; height: 800px; background: radial-gradient(circle, rgba(232, 35, 31, 0.06) 0%, transparent 60%); pointer-events: none; }
  .hero-inner { text-align: center; position: relative; }
  .hero h1 {
    font-size: clamp(36px, 6vw, 64px);
    margin-bottom: 22px;
    max-width: 880px;
    margin-left: auto;
    margin-right: auto;
  }
  .hero h1 .accent { color: var(--brand); }
  .hero-sub {
    font-size: clamp(16px, 1.4vw, 19px);
    color: var(--text-2);
    max-width: 620px;
    margin: 0 auto 32px;
    line-height: 1.55;
  }
  .hero-ctas { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 18px; }
  .hero-trust { font-size: 13px; color: var(--text-3); }
  .hero-trust b { color: var(--text); font-weight: 600; }

  /* APP MOCKUP */
  .app-mockup-wrap {
    margin-top: 60px;
    position: relative;
    perspective: 1500px;
  }
  .app-mockup {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: 0 30px 80px rgba(28, 25, 23, 0.18), 0 8px 20px rgba(28, 25, 23, 0.06);
    overflow: hidden;
    max-width: 1080px;
    margin: 0 auto;
    transform: rotateX(2deg);
    transform-origin: top center;
  }
  .mock-chrome {
    background: var(--surface-2);
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .mock-dot { width: 11px; height: 11px; border-radius: 50%; background: var(--surface-3); }
  .mock-dot.red { background: #EF4444; }
  .mock-dot.yellow { background: #F59E0B; }
  .mock-dot.green { background: #10B981; }
  .mock-url { margin-left: 14px; padding: 4px 12px; background: var(--surface); border-radius: 6px; font-size: 11px; color: var(--text-3); font-family: var(--font-mono); border: 1px solid var(--border); }
  .mock-body { display: grid; grid-template-columns: 200px 1fr; height: 540px; }
  .mock-sidebar { background: white; border-right: 1px solid var(--border); padding: 14px; display: flex; flex-direction: column; gap: 4px; }
  .mock-side-brand { display: flex; align-items: center; gap: 8px; padding: 6px 10px; margin-bottom: 12px; font-family: var(--font-display); font-weight: 700; font-size: 14px; }
  .mock-side-brand .ico { width: 22px; height: 22px; background: var(--brand); border-radius: 5px; }
  .mock-side-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-4); font-weight: 600; padding: 8px 10px 4px; }
  .mock-side-item { padding: 7px 10px; font-size: 12px; color: var(--text-2); border-radius: 6px; display: flex; align-items: center; gap: 8px; }
  .mock-side-item.active { background: var(--brand-tint); color: var(--brand); font-weight: 600; }
  .mock-side-dot { width: 14px; height: 14px; background: currentColor; opacity: 0.4; border-radius: 3px; flex-shrink: 0; }
  .mock-side-item.active .mock-side-dot { opacity: 1; }
  .mock-main { padding: 22px 26px; overflow: hidden; background: var(--bg); }
  .mock-h1 { font-family: var(--font-display); font-size: 22px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 18px; }
  .mock-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
  .mock-kpi { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; }
  .mock-kpi-label { font-size: 9px; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; margin-bottom: 4px; }
  .mock-kpi-value { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
  .mock-kpi-meta { font-size: 9px; color: var(--success); margin-top: 2px; font-weight: 600; }
  .mock-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 16px 18px; }
  .mock-card-title { font-family: var(--font-display); font-weight: 600; font-size: 13px; margin-bottom: 14px; }
  .mock-chart { display: flex; align-items: flex-end; gap: 6px; height: 100px; }
  .mock-bar { flex: 1; background: var(--brand-tint-2); border-radius: 3px 3px 0 0; position: relative; }
  .mock-bar.tall { background: var(--brand); }
  @media (max-width: 760px) {
    .mock-body { grid-template-columns: 1fr; height: auto; }
    .mock-sidebar { display: none; }
    .mock-kpi-grid { grid-template-columns: repeat(2, 1fr); }
  }

  /* TRUST STRIP */
  .trust-strip {
    background: var(--text);
    color: white;
    padding: 40px 0;
    text-align: center;
  }
  .trust-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
  .trust-stat-num { font-family: var(--font-display); font-weight: 700; font-size: clamp(28px, 4vw, 42px); letter-spacing: -0.02em; color: white; line-height: 1; }
  .trust-stat-num .accent { color: #FCD34D; }
  .trust-stat-label { font-size: 13px; color: rgba(255,255,255,0.65); margin-top: 8px; }
  @media (max-width: 720px) {
    .trust-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
  }

  /* SECTION HEADER */
  .section { padding: 90px 0; }
  .section-alt { background: white; }
  .section-header { text-align: center; max-width: 720px; margin: 0 auto 56px; }
  .section-header h2 { font-size: clamp(28px, 4vw, 42px); margin-bottom: 14px; }
  .section-header p { color: var(--text-2); font-size: 17px; line-height: 1.55; }

  /* FEATURES */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
  }
  @media (max-width: 900px) { .features-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 600px) { .features-grid { grid-template-columns: 1fr; } }
  .feature-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
  }
  .feature-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--border-strong);
  }
  .feature-icon {
    width: 44px; height: 44px;
    background: var(--brand-tint);
    color: var(--brand);
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
  }
  .feature-icon svg { width: 22px; height: 22px; }
  .feature-title { font-family: var(--font-display); font-weight: 600; font-size: 19px; letter-spacing: -0.01em; margin-bottom: 8px; }
  .feature-desc { color: var(--text-2); font-size: 14.5px; line-height: 1.6; }

  /* PRICING */
  .pricing-wrap { display: grid; grid-template-columns: 1fr 1.4fr; gap: 30px; align-items: center; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 880px) { .pricing-wrap { grid-template-columns: 1fr; } }
  .pricing-lead h2 { font-size: clamp(28px, 4vw, 42px); margin-bottom: 16px; }
  .pricing-lead p { font-size: 17px; color: var(--text-2); line-height: 1.6; margin-bottom: 20px; }
  .pricing-lead-points { list-style: none; padding: 0; margin: 0; }
  .pricing-lead-points li { padding: 7px 0; font-size: 14px; color: var(--text-2); display: flex; align-items: center; gap: 10px; }
  .pricing-lead-points li::before {
    content: '';
    width: 16px; height: 16px;
    background: var(--success-bg);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='3'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .pricing-card {
    background: var(--surface);
    border: 2px solid var(--brand);
    border-radius: 20px;
    padding: 36px 40px;
    box-shadow: var(--shadow-brand);
    position: relative;
  }
  .pricing-badge {
    position: absolute;
    top: -14px;
    right: 32px;
    background: var(--brand);
    color: white;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 6px 14px;
    border-radius: 100px;
  }
  .pricing-title { font-family: var(--font-display); font-weight: 700; font-size: 24px; letter-spacing: -0.015em; margin-bottom: 8px; }
  .pricing-desc { color: var(--text-3); font-size: 14px; margin-bottom: 24px; }
  .pricing-price-row { display: flex; align-items: baseline; gap: 10px; margin-bottom: 6px; }
  .pricing-price {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 56px;
    letter-spacing: -0.03em;
    line-height: 1;
    color: var(--text);
  }
  .pricing-price .euro { font-size: 36px; }
  .pricing-period { color: var(--text-3); font-size: 15px; font-weight: 500; }
  .pricing-vat { font-size: 12px; color: var(--text-3); margin-bottom: 24px; }
  .pricing-features { list-style: none; padding: 0; margin: 0 0 26px; }
  .pricing-features li { padding: 8px 0; font-size: 14.5px; color: var(--text-2); display: flex; align-items: flex-start; gap: 10px; line-height: 1.5; }
  .pricing-features li svg { width: 18px; height: 18px; color: var(--success); flex-shrink: 0; margin-top: 1px; }
  .pricing-fineprint { text-align: center; font-size: 12px; color: var(--text-3); margin-top: 14px; }

  /* TESTIMONIALS */
  .testimonials-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
  @media (max-width: 900px) { .testimonials-grid { grid-template-columns: 1fr; } }
  .testimonial {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 28px;
    position: relative;
  }
  .testimonial-quote-mark {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 50px;
    color: var(--brand-tint-2);
    line-height: 0.8;
    margin-bottom: 8px;
  }
  .testimonial-text { font-size: 15px; color: var(--text-2); line-height: 1.6; margin-bottom: 22px; }
  .testimonial-author { display: flex; align-items: center; gap: 12px; }
  .testimonial-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); color: white; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 14px; flex-shrink: 0; }
  .testimonial-author-name { font-weight: 600; font-size: 14px; }
  .testimonial-author-role { font-size: 12px; color: var(--text-3); }

  /* FAQ */
  .faq-list { max-width: 760px; margin: 0 auto; }
  .faq-item { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 10px; overflow: hidden; transition: all 0.15s; }
  .faq-item[open] { border-color: var(--brand-border); box-shadow: var(--shadow-sm); }
  .faq-item summary {
    padding: 18px 22px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    list-style: none;
    user-select: none;
  }
  .faq-item summary::-webkit-details-marker { display: none; }
  .faq-chevron { transition: transform 0.2s; color: var(--text-3); }
  .faq-item[open] .faq-chevron { transform: rotate(180deg); color: var(--brand); }
  .faq-content { padding: 0 22px 20px; color: var(--text-2); font-size: 14.5px; line-height: 1.65; }

  /* FINAL CTA */
  .cta-final {
    background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .cta-final::before {
    content: '';
    position: absolute;
    top: -100px; right: -100px;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }
  .cta-final::after {
    content: '';
    position: absolute;
    bottom: -150px; left: -100px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }
  .cta-inner { position: relative; z-index: 1; }
  .cta-final h2 { color: white; font-size: clamp(30px, 4.5vw, 46px); margin-bottom: 16px; }
  .cta-final p { font-size: 18px; opacity: 0.9; max-width: 540px; margin: 0 auto 28px; }

  /* FOOTER */
  footer { background: #0F0E0D; color: rgba(255,255,255,0.7); padding: 60px 0 30px; }
  .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
  @media (max-width: 800px) { .footer-grid { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 480px) { .footer-grid { grid-template-columns: 1fr; } }
  .footer-brand-block { max-width: 320px; }
  .footer-brand { display: flex; align-items: center; gap: 10px; font-family: var(--font-display); font-weight: 700; font-size: 18px; color: white; margin-bottom: 14px; }
  .footer-brand img { width: 28px; height: 28px; border-radius: 6px; }
  .footer-desc { font-size: 13.5px; line-height: 1.6; color: rgba(255,255,255,0.55); }
  .footer-col-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; color: white; margin-bottom: 14px; }
  .footer-links { list-style: none; padding: 0; margin: 0; }
  .footer-links li { padding: 4px 0; }
  .footer-links a { font-size: 13.5px; color: rgba(255,255,255,0.55); transition: color 0.15s; }
  .footer-links a:hover { color: white; }
  .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 28px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 12.5px; color: rgba(255,255,255,0.4); }
  .footer-bottom-links { display: flex; gap: 18px; }
  .footer-bottom-links a:hover { color: rgba(255,255,255,0.8); }
</style>
</head>
<body>

<!-- NAVIGATION -->
<header class="nav">
  <div class="container nav-inner">
    <a href="/" class="nav-brand">
      <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23E8231F'/%3E%3Ctext x='16' y='22' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='700' font-size='18'%3EE%3C/text%3E%3C/svg%3E" alt="">
      EasyInvoice
    </a>
    <nav class="nav-links">
      <a href="#functies" class="nav-link">Functies</a>
      <a href="#prijzen" class="nav-link">Prijzen</a>
      <a href="#reviews" class="nav-link">Reviews</a>
      <a href="#faq" class="nav-link">Veelgestelde vragen</a>
    </nav>
    <div class="nav-actions">
      <a href="{{ route('login') }}" class="btn btn-ghost">Inloggen</a>
      <a href="{{ route('register') }}" class="btn btn-primary">Start gratis →</a>
    </div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="container hero-inner">
    <div class="eyebrow">Voor Nederlandse ondernemers</div>
    <h1>Facturatie <span class="accent">zonder gedoe.</span></h1>
    <p class="hero-sub">
      Stuur facturen, beheer je klanten en houd je BTW automatisch bij. Speciaal voor ZZP'ers en MKB — voor <b style="color:var(--text);font-weight:600;">maar €2,50 per maand</b>.
    </p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
        Start 14 dagen gratis
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="#functies" class="btn btn-secondary btn-lg">
        Bekijk de demo
      </a>
    </div>
    <div class="hero-trust">
      Geen creditcard nodig · 14 dagen gratis · Daarna <b>€2,50/maand</b>
    </div>

    <!-- APP MOCKUP -->
    <div class="app-mockup-wrap">
      <div class="app-mockup">
        <div class="mock-chrome">
          <div class="mock-dot red"></div>
          <div class="mock-dot yellow"></div>
          <div class="mock-dot green"></div>
          <div class="mock-url">app.easyinvoice.nl/dashboard</div>
        </div>
        <div class="mock-body">
          <div class="mock-sidebar">
            <div class="mock-side-brand"><div class="ico"></div>EasyInvoice</div>
            <div class="mock-side-label">Overzicht</div>
            <div class="mock-side-item active"><div class="mock-side-dot"></div>Dashboard</div>
            <div class="mock-side-label">Verkoop</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Facturen</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Klanten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Producten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Incasso</div>
            <div class="mock-side-label">Rapporten</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Klantomzet</div>
            <div class="mock-side-label">Instellingen</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Bedrijf</div>
            <div class="mock-side-item"><div class="mock-side-dot"></div>Huisstijl</div>
          </div>
          <div class="mock-main">
            <div class="mock-h1">Welkom terug, Jan</div>
            <div class="mock-kpi-grid">
              <div class="mock-kpi">
                <div class="mock-kpi-label">Openstaand</div>
                <div class="mock-kpi-value">€ 7.842</div>
                <div class="mock-kpi-meta" style="color:var(--warning);">12 facturen</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">Achterstallig</div>
                <div class="mock-kpi-value" style="color:var(--brand);">€ 1.573</div>
                <div class="mock-kpi-meta" style="color:var(--brand);">2 facturen</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">Deze maand</div>
                <div class="mock-kpi-value">€ 4.230</div>
                <div class="mock-kpi-meta">+18% vs vorige maand</div>
              </div>
              <div class="mock-kpi">
                <div class="mock-kpi-label">BTW Q2</div>
                <div class="mock-kpi-value">€ 892</div>
                <div class="mock-kpi-meta" style="color:var(--info);">Over 14 dagen</div>
              </div>
            </div>
            <div class="mock-card">
              <div class="mock-card-title">Omzet laatste 12 maanden</div>
              <div class="mock-chart">
                <div class="mock-bar" style="height:30%"></div>
                <div class="mock-bar" style="height:40%"></div>
                <div class="mock-bar" style="height:55%"></div>
                <div class="mock-bar" style="height:50%"></div>
                <div class="mock-bar" style="height:65%"></div>
                <div class="mock-bar" style="height:70%"></div>
                <div class="mock-bar" style="height:60%"></div>
                <div class="mock-bar" style="height:80%"></div>
                <div class="mock-bar" style="height:75%"></div>
                <div class="mock-bar" style="height:85%"></div>
                <div class="mock-bar" style="height:78%"></div>
                <div class="mock-bar tall" style="height:95%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST STRIP -->
<section class="trust-strip">
  <div class="container trust-grid">
    <div>
      <div class="trust-stat-num"><span class="accent">2.500+</span></div>
      <div class="trust-stat-label">Actieve gebruikers</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">€48M</span></div>
      <div class="trust-stat-label">Aan facturen verstuurd</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">4,9</span>/5</div>
      <div class="trust-stat-label">Sterren van klanten</div>
    </div>
    <div>
      <div class="trust-stat-num"><span class="accent">99,9%</span></div>
      <div class="trust-stat-label">Uptime SLA</div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section section-alt" id="functies">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Functies</div>
      <h2>Alles wat je nodig hebt — niet meer.</h2>
      <p>Geen overbodige features waar je nooit aan toekomt. Wel alles om vandaag nog professioneel je administratie te doen.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
        </div>
        <div class="feature-title">Facturen versturen</div>
        <div class="feature-desc">In drie klikken een factuur opgesteld, automatisch genummerd en met je eigen huisstijl naar de klant verstuurd.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="feature-title">BTW automatisch</div>
        <div class="feature-desc">21%, 9% en 0% per regel. Per kwartaal een aangifte-overzicht klaar — gewoon overnemen op MijnBelastingdienst.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="feature-title">Klantenbeheer</div>
        <div class="feature-desc">Volledige klantadministratie inclusief KVK-, BTW- en IBAN-gegevens. Onderscheid tussen zakelijk en particulier.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
        </div>
        <div class="feature-title">Creditnota's</div>
        <div class="feature-desc">Volledig of gedeeltelijk crediteren met eigen nummerreeks — voldoet aan alle Nederlandse boekhoudregels.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
        </div>
        <div class="feature-title">Incasso bij Armaere</div>
        <div class="feature-desc">Eén klik om een achterstallige factuur over te dragen aan onze vaste deurwaarder. Incassokosten voor de schuldenaar.</div>
      </div>

      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="feature-title">AI-assistent EASY</div>
        <div class="feature-desc">Vraag EASY in normale taal wat er openstaat, hoe je BTW ervoor staat en welke klanten extra aandacht nodig hebben.</div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="section" id="prijzen">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Eerlijke prijs</div>
      <h2>Eén prijs. Alles inbegrepen.</h2>
      <p>Geen verborgen kosten, geen limieten op facturen of klanten. Opzeggen kan elke maand.</p>
    </div>

    <div class="pricing-wrap">
      <div class="pricing-lead">
        <h2>Waarom zou je meer betalen?</h2>
        <p>Andere boekhoudpakketten vragen €8 tot €25 per maand voor functies die jij waarschijnlijk niet gebruikt. EasyInvoice geeft je alleen wat je echt nodig hebt — voor een fractie van de prijs.</p>
        <ul class="pricing-lead-points">
          <li>Onbeperkt facturen versturen</li>
          <li>Onbeperkt klanten en producten</li>
          <li>Alle functies vanaf dag één</li>
          <li>Persoonlijke ondersteuning</li>
          <li>Maandelijks opzegbaar</li>
        </ul>
      </div>

      <div class="pricing-card">
        <div class="pricing-badge">Meest gekozen</div>
        <div class="pricing-title">EasyInvoice</div>
        <div class="pricing-desc">Alles wat een Nederlandse ondernemer nodig heeft</div>
        <div class="pricing-price-row">
          <div class="pricing-price"><span class="euro">€</span>2,50</div>
          <div class="pricing-period">/ maand</div>
        </div>
        <div class="pricing-vat">Excl. 21% BTW · €3,03 incl. BTW</div>
        <ul class="pricing-features">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt facturen en creditnota's</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Onbeperkt klanten en producten</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Eigen huisstijl op factuur en PDF</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>BTW-rapport per kwartaal</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Automatische herinneringen en aanmaningen</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Incasso via Armaere Gerechtsdeurwaarders</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI-assistent EASY</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Tweestapsverificatie en beveiliging</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Persoonlijke support per e-mail</li>
        </ul>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Start 14 dagen gratis</a>
        <div class="pricing-fineprint">Geen creditcard nodig · Opzeggen wanneer je wil</div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-alt" id="reviews">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow" style="margin-bottom:16px;">Reviews</div>
      <h2>Wat klanten zeggen.</h2>
      <p>Meer dan 2.500 Nederlandse ondernemers gebruiken EasyInvoice dagelijks.</p>
    </div>

    <div class="testimonials-grid">
      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">Ik betaalde €18 per maand bij Moneybird voor functies die ik niet gebruikte. EasyInvoice doet precies wat ik nodig heb, en is zeven keer goedkoper. Heerlijk simpel.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">SV</div>
          <div>
            <div class="testimonial-author-name">Sander Vermeer</div>
            <div class="testimonial-author-role">ZZP webdeveloper</div>
          </div>
        </div>
      </div>

      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">De incassofunctie heeft ons al meerdere keren gered. Eén klik en Armaere neemt het over — geen e-mailtjes meer naar wanbetalers. Een uitkomst voor een klein bedrijf.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">MK</div>
          <div>
            <div class="testimonial-author-name">Marit de Koning</div>
            <div class="testimonial-author-role">Eigenaar Bloemstudio Lina</div>
          </div>
        </div>
      </div>

      <div class="testimonial">
        <div class="testimonial-quote-mark">"</div>
        <div class="testimonial-text">EASY weet altijd hoe het ervoor staat met mijn administratie. "Hoeveel staat er open?" — boem, antwoord. BTW-deadline? EASY waarschuwt. Bijna alsof ik een boekhouder heb.</div>
        <div class="testimonial-author">
          <div class="testimonial-avatar">TP</div>
          <div>
            <div class="testimonial-author-name">Tom Pieters</div>
            <div class="testimonial-author-role">Trainer en coach</div>
          </div>
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
      <h2>Vragen die je vast hebt.</h2>
      <p>Staat je vraag er niet bij? <a href="mailto:hallo@easyinvoice.nl" style="color:var(--brand);font-weight:500;">Mail ons direct.</a></p>
    </div>

    <div class="faq-list">
      <details class="faq-item">
        <summary>
          Hoe lang duurt het om EasyInvoice op te zetten?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Vijf minuten. Maak een account, vul je bedrijfsgegevens (KVK, BTW, IBAN) in, en je kunt direct je eerste factuur versturen. Geen ingewikkelde inrichting nodig.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Kan ik op elk moment opzeggen?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja, EasyInvoice is maandelijks opzegbaar. Geen contracten, geen jaarverplichtingen. Opzeggen kan vanuit je account-instellingen. Je facturen blijven gewoon downloadbaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat als ik geen BTW-nummer heb (KOR-regeling)?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Geen probleem. EasyInvoice ondersteunt de Kleine Ondernemersregeling (KOR) volledig — je kunt facturen zonder BTW versturen, met automatische vermelding van de KOR-regeling op je factuur.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Hoe veilig is mijn data?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Je data staat op servers in Amsterdam, dagelijks geback-upt. We zijn AVG-compliant en bieden tweestapsverificatie. Alle verkeer is versleuteld via TLS 1.3. Je houdt de eigendomsrechten op al je data en kunt het op elk moment exporteren.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Werkt EasyInvoice samen met mijn boekhouder?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Ja. Je kunt je boekhouder toegang geven tot je administratie, en BTW-overzichten, factuurregels en omzetrapporten exporteren naar Excel of CSV. Een directe koppeling met Twinfield, e-Boekhouden en Exact volgt later dit jaar.
        </div>
      </details>

      <details class="faq-item">
        <summary>
          Wat zit er ná de gratis proefperiode in het abonnement?
          <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="faq-content">
          Alles. Voor €2,50 per maand (excl. BTW) krijg je het volledige product — onbeperkt facturen, klanten, producten, incasso, AI-assistent, alle functies. We geloven niet in betaalmuren voor basisfuncties.
        </div>
      </details>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="cta-final">
  <div class="container cta-inner">
    <h2>Klaar om te beginnen?</h2>
    <p>Begin vandaag nog en stuur binnen vijf minuten je eerste professionele factuur.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">
      Start 14 dagen gratis
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <div style="margin-top:16px;font-size:13px;opacity:0.8;">Geen creditcard nodig · Daarna €2,50/maand</div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand-block">
        <div class="footer-brand">
          <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23E8231F'/%3E%3Ctext x='16' y='22' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='700' font-size='18'%3EE%3C/text%3E%3C/svg%3E" alt="">
          EasyInvoice
        </div>
        <div class="footer-desc">
          Eenvoudige facturatie voor Nederlandse ondernemers. Vanaf €2,50 per maand. Gemaakt in Amsterdam.
        </div>
      </div>

      <div>
        <div class="footer-col-title">Product</div>
        <ul class="footer-links">
          <li><a href="#functies">Functies</a></li>
          <li><a href="#prijzen">Prijzen</a></li>
          <li><a href="#reviews">Reviews</a></li>
          <li><a href="#">Wat is nieuw</a></li>
          <li><a href="#">Roadmap</a></li>
        </ul>
      </div>

      <div>
        <div class="footer-col-title">Bedrijf</div>
        <ul class="footer-links">
          <li><a href="#">Over ons</a></li>
          <li><a href="#">Contact</a></li>
          <li><a href="#">Pers</a></li>
          <li><a href="#">Werken bij</a></li>
        </ul>
      </div>

      <div>
        <div class="footer-col-title">Hulp</div>
        <ul class="footer-links">
          <li><a href="#">Helpcentrum</a></li>
          <li><a href="#faq">Veelgestelde vragen</a></li>
          <li><a href="mailto:hallo@easyinvoice.nl">E-mail support</a></li>
          <li><a href="#">Status</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <div>© 2026 EasyInvoice B.V. · KvK 87654321 · BTW NL862345678B01</div>
      <div class="footer-bottom-links">
        <a href="#">Algemene voorwaarden</a>
        <a href="#">Privacybeleid</a>
        <a href="#">Cookies</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
