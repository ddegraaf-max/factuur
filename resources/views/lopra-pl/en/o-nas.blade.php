@extends('layouts.marketing')

{{-- About us — Lopra Polska, English edition: Lopra as a service of Creditline B.V. (Bussum, more than 25 years in receivables management) with sprzedamfakture.pl as invoice-purchase partner. No invented figures or people. --}}

@section('title', 'About us — ' . brand('name') . ', a Creditline B.V. service')
@section('description', 'Who we are: ' . brand('name') . ' is a service of Creditline B.V. from Bussum (the Netherlands), a company with more than 25 years of experience in receivables management and debt collection, developed in Poland together with sprzedamfakture.pl, which buys unpaid invoices. One tool from your first invoice to the one that is not paid.')

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">About us</div>
    <h1>From your first invoice to getting paid</h1>
    <p class="lead">{{ brand('name') }} grew out of a simple observation: the biggest problem for a small business is not issuing the invoice, but the fact that it does not always get paid. So we built a tool that keeps an eye on both.</p>
  </div>
</section>

<section class="section" style="padding-top:48px;">
  <div class="container">
    <div class="prose">
      <h2>A service of Creditline B.V.</h2>
      <p>{{ brand('name') }} is a service of Creditline B.V. from Bussum in the Netherlands — a company that has specialised in receivables management and debt collection for more than 25 years. We know from daily practice, not from stories, what happens to a business when clients pay late or not at all: strained cash flow, hours spent chasing payments and the nagging doubt whether fighting for your own money is even worth it.</p>
      <p>That experience is built into the product. Reminders, a payment demand with statutory interest and compensation, and the option to sell an unpaid invoice — not as a paid add-on, but as a fixed part of every subscription.</p>

      <h2>sprzedamfakture.pl</h2>
      <p>In Poland, {{ brand('name') }} works together with <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener">sprzedamfakture.pl</a> — Creditline B.V.'s service in Poland that buys unpaid B2B invoices. When reminders and a wezwanie do zapłaty (payment demand) get no result, you do not have to wait or go to court yourself: submit the invoice for purchase with one click, straight from {{ brand('name') }}, with all the data already in place. sprzedamfakture.pl sends a purchase offer within one working day; once you accept, the money is paid out and the buyer takes over the risk and any further recovery.</p>
      <p>Every purchase offer states the price before you decide, with no fees and no upfront costs. The decision is always yours.</p>

      <h2>Why one tool?</h2>
      <p>A small business usually needs four things at once: invoicing software, a logo and colours, a simple website, and someone to help when a client does not pay. That usually means four subscriptions, four logins and data retyped from one place to another.</p>
      <p>In {{ brand('name') }} everything lives in one place and works together: the KSeF-ready invoice carries the logo from your visual identity, the business card and website look the same, an enquiry from the website turns into a quote, and an unpaid invoice — without retyping a thing — into a payment demand or a purchase offer. One subscription, one price, zero accounting jargon.</p>

      <h2>What we promise</h2>
      <ul>
        <li><strong>Simplicity.</strong> We keep only what genuinely helps you run your business. No hundred buttons.</li>
        <li><strong>A fair price.</strong> One monthly subscription, no limits on invoices or clients, no hidden fees. Cancel whenever you like.</li>
        <li><strong>Your data is yours.</strong> Servers in the European Union, a full export of your company at any time, GDPR compliance.</li>
        <li><strong>Support in English and Polish.</strong> We reply within one working day — also during your trial.</li>
      </ul>
      <p>Questions? <a href="{{ route('pl.kontakt') }}">Write to us</a> or browse the <a href="{{ route('pl.faq') }}">frequently asked questions</a>.</p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top:64px;padding-bottom:64px;">
  <div class="container">
    <div class="section-header" style="margin-bottom:40px;"><h2>What we stand for</h2></div>
    <div class="card-grid cols-2" style="max-width:900px;margin:0 auto;">
      <div class="info-card"><h3>An invoice is only the start</h3><p>What matters is that it gets paid. That is why chasing due dates, the payment demand and selling the invoice are part of the product, not a separate service.</p></div>
      <div class="info-card"><h3>Built for small businesses</h3><p>For sole traders, sp. z o.o. companies and teams of a few people — not for accounting departments.</p></div>
      <div class="info-card"><h3>Experience from practice</h3><p>More than 25 years of Creditline B.V. receivables management, distilled into a simple tool.</p></div>
      <div class="info-card"><h3>Privacy first</h3><p>Data in the European Union, an encrypted connection, two-factor authentication and a full export at any time.</p></div>
    </div>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Ready to get started?</h2>
    <p>Try {{ brand('name') }} free for 14 days — invoices, brand, website and a collection toolkit in one place.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-lg">Try 14 days free</a>
  </div>
</section>
@endsection
