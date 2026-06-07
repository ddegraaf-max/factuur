@extends('layouts.marketing')

@section('title', 'Werken bij — EasyInvoice')
@section('description', 'Bouw mee aan EasyInvoice. Bekijk onze openstaande vacatures.')

@push('styles')
<style>
  .job { display: flex; align-items: center; justify-content: space-between; gap: 20px; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px 26px; margin-bottom: 14px; transition: transform .15s, box-shadow .15s, border-color .15s; }
  .job:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--border-strong); }
  .job h3 { font-size: 18px; margin-bottom: 8px; }
  .job p { color: var(--text-2); font-size: 14px; margin: 0; }
  .job-tags { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
  .job-arrow { font-size: 24px; color: var(--brand); flex-shrink: 0; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Werken bij</div>
    <h1>Bouw mee aan het MKB van morgen</h1>
    <p class="lead">We zijn een klein team met grote ambities. Help je mee om factureren voor heel ondernemend Nederland makkelijk te maken?</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <h2 style="font-size:24px;margin-bottom:22px;">Waarom EasyInvoice</h2>
    <div class="card-grid">
      <div class="info-card"><h3>Flexibel werken</h3><p>Werk vanuit huis of ons kantoor in Amsterdam.</p></div>
      <div class="info-card"><h3>Klein, hecht team</h3><p>Korte lijnen, veel impact, weinig vergaderingen.</p></div>
      <div class="info-card"><h3>Echte impact</h3><p>Je werk helpt ondernemers direct vooruit.</p></div>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:56px;padding-bottom:56px;">
  <div class="container" style="max-width:840px;">
    <h2 style="font-size:24px;margin-bottom:22px;">Open posities</h2>

    <a href="mailto:jobs@easyinvoice.nl?subject=Sollicitatie Full-stack developer" class="job">
      <div>
        <h3>Full-stack developer (Laravel / Vue)</h3>
        <p>Bouw mee aan het product dat duizenden ondernemers dagelijks gebruiken.</p>
        <div class="job-tags"><span class="value-pill">Fulltime</span><span class="value-pill">Amsterdam / Remote</span></div>
      </div>
      <span class="job-arrow">→</span>
    </a>

    <a href="mailto:jobs@easyinvoice.nl?subject=Sollicitatie Customer Success" class="job">
      <div>
        <h3>Customer Success Specialist</h3>
        <p>Help ondernemers het maximale uit EasyInvoice te halen.</p>
        <div class="job-tags"><span class="value-pill">Fulltime</span><span class="value-pill">Amsterdam</span></div>
      </div>
      <span class="job-arrow">→</span>
    </a>

    <a href="mailto:jobs@easyinvoice.nl?subject=Sollicitatie Product Designer" class="job">
      <div>
        <h3>Product Designer</h3>
        <p>Ontwerp eenvoudige, mooie ervaringen voor het MKB.</p>
        <div class="job-tags"><span class="value-pill">Parttime</span><span class="value-pill">Remote</span></div>
      </div>
      <span class="job-arrow">→</span>
    </a>

    <p style="margin-top:24px;color:var(--text-2);">
      Geen passende vacature? Stuur een open sollicitatie naar
      <a href="mailto:jobs@easyinvoice.nl" style="color:var(--brand);font-weight:600;">jobs@easyinvoice.nl</a>.
    </p>
  </div>
</section>
@endsection
