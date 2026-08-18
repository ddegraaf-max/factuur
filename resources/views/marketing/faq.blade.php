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
        <div class="faq-content">€ 12,10 per maand (incl. 21% BTW), met alle functies inbegrepen. Geen extra of verborgen kosten.</div>
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
        <div class="faq-content">Ja, per factuurregel kies je 21%, 9% of 0%. Per kwartaal staat je aangifte klaar: rubriek 1a, 1b en 1e, plus de voorbelasting (5b) uit je ingeboekte inkoopfacturen en het saldo dat je moet betalen — inclusief deadline-waarschuwing en PDF-download.</div>
      </details>
      <details class="faq-item">
        <summary>Kan ik ook mijn inkoopfacturen bijhouden? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja. Boek binnengekomen facturen van leveranciers in — handmatig of met een foto van de bon (op je telefoon opent direct de camera). Je ziet wat er bij leveranciers openstaat en de BTW telt automatisch mee als voorbelasting in je aangifte.</div>
      </details>
      <details class="faq-item">
        <summary>Wat als ik onder de KOR-regeling val? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">EasyInvoice ondersteunt de Kleine Ondernemersregeling volledig — je verstuurt facturen zonder BTW, met automatische vermelding van de KOR-regeling.</div>
      </details>
      <details class="faq-item">
        <summary>Voldoen de facturen aan de eisen van de Belastingdienst? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, inclusief doorlopende nummering per jaar en alle verplichte gegevens. Ook creditnota's voldoen aan de Nederlandse boekhoudregels.</div>
      </details>

      <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-4);margin:32px 0 14px;">Samenwerken &amp; klantenportaal</h3>
      <details class="faq-item">
        <summary>Kunnen collega's ook in EasyInvoice werken? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, en extra gebruikers kosten niets. Nodig collega's uit via Instellingen → Team en kies per persoon een rol: beheerder (alles), medewerker (het dagelijkse werk, zonder instellingen en rapporten) of boekhouder (alles inzien, niets wijzigen).</div>
      </details>
      <details class="faq-item">
        <summary>Kan mijn boekhouder meekijken? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja, gratis. De boekhouder-rol mag je hele administratie inzien en juist wél de rapporten en exports gebruiken (klantomzet, BTW-overzicht, CSV van verkoop en inkoop), maar kan niets aanmaken of wijzigen.</div>
      </details>
      <details class="faq-item">
        <summary>Ziet mijn klant de factuur ook online? <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
        <div class="faq-content">Ja. In elke factuurmail staat een knop "Bekijk factuur online" met een beveiligde link. Voor de zekerheid bevestigt je klant eerst zijn e-mailadres met een eenmalige code. Jij ziet daarna in het inzagelog precies óf en wanneer de factuur is bekeken en gedownload.</div>
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
