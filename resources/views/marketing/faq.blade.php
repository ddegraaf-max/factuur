@extends('layouts.marketing')

@section('title', 'Veelgestelde vragen — EasyInvoice')
@section('description', 'Antwoorden op de meest gestelde vragen over EasyInvoice: prijzen, facturen, BTW, incasso, beveiliging en meer.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Veelgestelde vragen</div>
    <h1>Vragen &amp; antwoorden</h1>
    <p class="lead">De meest gestelde vragen over EasyInvoice op een rij. Staat je vraag er niet bij? <a href="{{ route('contact') }}" style="color:var(--brand);font-weight:600;">Neem contact op</a>.</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="faq-list">

      <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:8px 0 14px;">Algemeen</h3>
      <details class="faq-item">
        <summary>Wat is EasyInvoice? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">EasyInvoice is eenvoudige facturatiesoftware voor Nederlandse ZZP'ers en het MKB. Je maakt facturen, beheert klanten, houdt je BTW bij en verstuurt herinneringen — alles op één plek.</div>
      </details>
      <details class="faq-item">
        <summary>Voor wie is het bedoeld? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Voor ondernemers die snel en professioneel willen factureren zonder ingewikkelde boekhoudsoftware: freelancers, ZZP'ers en kleine bedrijven.</div>
      </details>
      <details class="faq-item">
        <summary>Heb ik boekhoudkennis nodig? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Nee. EasyInvoice is gemaakt om zonder voorkennis te gebruiken. BTW wordt automatisch berekend en alles is in begrijpelijk Nederlands.</div>
      </details>

      <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:32px 0 14px;">Prijs &amp; abonnement</h3>
      <details class="faq-item">
        <summary>Wat kost EasyInvoice? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">€10 per maand (excl. 21% BTW), met alle functies inbegrepen. Geen extra of verborgen kosten.</div>
      </details>
      <details class="faq-item">
        <summary>Kan ik op elk moment opzeggen? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, EasyInvoice is maandelijks opzegbaar. Geen contracten of jaarverplichtingen. Je facturen blijven altijd downloadbaar.</div>
      </details>
      <details class="faq-item">
        <summary>Is er een gratis proefperiode? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, je probeert EasyInvoice 14 dagen gratis. Geen creditcard nodig om te starten.</div>
      </details>

      <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:32px 0 14px;">Facturen &amp; BTW</h3>
      <details class="faq-item">
        <summary>Wordt BTW automatisch berekend? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, per factuurregel kies je 21%, 9% of 0%. Het totaal en de BTW worden automatisch berekend, met een aangifte-overzicht per kwartaal.</div>
      </details>
      <details class="faq-item">
        <summary>Wat als ik onder de KOR-regeling val? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">EasyInvoice ondersteunt de Kleine Ondernemersregeling volledig — je verstuurt facturen zonder BTW, met automatische vermelding van de KOR-regeling.</div>
      </details>
      <details class="faq-item">
        <summary>Voldoen de facturen aan de eisen van de Belastingdienst? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, inclusief doorlopende nummering per jaar en alle verplichte gegevens. Ook creditnota's voldoen aan de Nederlandse boekhoudregels.</div>
      </details>

      <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:32px 0 14px;">Betalingen, incasso &amp; veiligheid</h3>
      <details class="faq-item">
        <summary>Kan ik automatische herinneringen versturen? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, je stelt in wanneer herinneringen en aanmaningen automatisch worden verstuurd. Bij uitblijvende betaling draag je in één klik over aan incasso.</div>
      </details>
      <details class="faq-item">
        <summary>Hoe veilig is mijn data? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Je data staat versleuteld op servers binnen de EU, dagelijks geback-upt. We zijn AVG-compliant en bieden tweestapsverificatie (2FA).</div>
      </details>
    </div>

    <div style="text-align:center;margin-top:40px;">
      <p style="color:var(--text-2);margin-bottom:16px;">Geen antwoord gevonden?</p>
      <a href="{{ route('contact') }}" class="btn btn-primary">Neem contact op</a>
    </div>
  </div>
</section>
@endsection
