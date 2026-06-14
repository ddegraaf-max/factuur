@extends('layouts.marketing')

@section('title', 'Cookiebeleid — EasyInvoice')
@section('description', 'Welke cookies EasyInvoice gebruikt en waarom.')

@section('content')
<style>
  .legal{padding:60px 0 80px;}
  .legal .container{max-width:760px;}
  .legal h1{font-size:clamp(30px,5vw,42px);margin-bottom:10px;}
  .legal .meta{color:var(--text-3);font-size:14px;margin-bottom:26px;}
  .legal .entity{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px;margin-bottom:34px;box-shadow:var(--shadow-sm);font-size:14.5px;line-height:1.75;color:var(--text-2);}
  .legal .entity strong{color:var(--text);}
  .legal h2{font-size:20px;margin:34px 0 10px;}
  .legal h3{font-size:16px;margin:22px 0 6px;}
  .legal p{color:var(--text-2);margin:0 0 14px;line-height:1.75;}
  .legal ul{color:var(--text-2);margin:0 0 16px;padding-left:20px;line-height:1.75;}
  .legal li{margin-bottom:7px;}
  .legal a{color:var(--brand);font-weight:500;}
  .legal a:hover{text-decoration:underline;}
  .legal .disclaimer{margin-top:38px;padding:14px 16px;background:var(--surface-2);border-radius:10px;font-size:13px;color:var(--text-3);}
</style>

<section class="legal">
  <div class="container">
    <div class="eyebrow">Juridisch</div>
    <h1>Cookiebeleid</h1>
    <div class="meta">Laatst bijgewerkt: 14 juni 2026</div>

    <div class="entity">
      <strong>EasyInvoice®</strong> is een dienst van<br>
      <strong>Creditline B.V.</strong><br>
      Torenlaan 5B · 1402 AT Bussum · Nederland<br>
      KvK 59683198 · BTW NL853603108B01<br>
      E-mail: <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>
    </div>

    <p>EasyInvoice gebruikt cookies om de website goed te laten werken en het gebruik te kunnen analyseren. We gebruiken geen advertentie- of tracking-cookies van derden.</p>

    <h2>Welke cookies we gebruiken</h2>

    <h3>Functionele cookies</h3>
    <p>Deze zijn noodzakelijk om de dienst te laten werken, bijvoorbeeld om je ingelogd te houden en de beveiliging (CSRF) te waarborgen. Deze cookies kun je niet uitschakelen.</p>

    <h3>Analytische cookies</h3>
    <p>Deze helpen ons begrijpen hoe de website wordt gebruikt, zodat we hem kunnen verbeteren. We verzamelen deze gegevens geanonimiseerd.</p>

    <h2>Cookies beheren</h2>
    <p>Je kunt cookies op elk moment beheren of verwijderen via de instellingen van je browser. Houd er rekening mee dat het uitschakelen van functionele cookies de werking van de dienst kan beperken.</p>

    <h2>Meer informatie</h2>
    <p>Lees ook ons <a href="{{ route('privacy') }}">privacybeleid</a> voor hoe we met je persoonsgegevens omgaan. Vragen? Mail <a href="mailto:hallo@easyinvoice.nl">hallo@easyinvoice.nl</a>.</p>

    <div class="disclaimer">Dit is een voorbeeldtekst en vormt geen juridisch advies.</div>
  </div>
</section>
@endsection
