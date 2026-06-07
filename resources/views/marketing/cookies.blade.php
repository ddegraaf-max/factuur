@extends('layouts.marketing')

@section('title', 'Cookiebeleid — EasyInvoice')
@section('description', 'Welke cookies EasyInvoice gebruikt en waarom.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Juridisch</div>
    <h1>Cookiebeleid</h1>
    <p class="lead">Laatst bijgewerkt: 1 januari 2026</p>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="prose">
      <p>EasyInvoice gebruikt cookies om de website goed te laten werken en het gebruik te kunnen analyseren. We gebruiken geen advertentie- of tracking-cookies van derden.</p>

      <h2>Welke cookies we gebruiken</h2>
      <h3>Functionele cookies</h3>
      <p>Deze zijn noodzakelijk om de dienst te laten werken, bijvoorbeeld om je ingelogd te houden en de beveiliging (CSRF) te waarborgen. Deze cookies kun je niet uitschakelen.</p>

      <h3>Analytische cookies</h3>
      <p>Deze helpen ons begrijpen hoe de website wordt gebruikt, zodat we hem kunnen verbeteren. We verzamelen deze gegevens geanonimiseerd.</p>

      <h2>Cookies beheren</h2>
      <p>Je kunt cookies op elk moment beheren of verwijderen via de instellingen van je browser. Houd er rekening mee dat het uitschakelen van functionele cookies de werking van de dienst kan beperken.</p>

      <h2>Meer informatie</h2>
      <p>Lees ook ons <a href="{{ route('privacy') }}">privacybeleid</a> voor hoe we met je persoonsgegevens omgaan. Vragen? Mail <a href="mailto:privacy@easyinvoice.nl">privacy@easyinvoice.nl</a>.</p>

      <p style="font-size:13px;color:var(--text-3);margin-top:32px;">Dit is een voorbeeldtekst en vormt geen juridisch advies.</p>
    </div>
  </div>
</section>
@endsection
