@extends('layouts.marketing')

{{-- We buy old judgments — Lopra Polska, English edition (same URL /skup-wyrokow as the Polish original). Lead page for sprzedamfakture.pl, which also buys claims confirmed by an enforceable title (tytuł wykonawczy). The form POSTs to route('pl.skup-wyrokow.send'); the success box is shown in English whenever session('flash') is set. --}}

@section('title', 'We buy old Polish judgments (tytuł wykonawczy) — ' . brand('name'))
@section('description', 'Holding a final Polish judgment or payment order that was never paid? sprzedamfakture.pl, the invoice-purchase partner of ' . brand('name') . ', also buys claims confirmed by an enforceable title (tytuł wykonawczy) — typically 10–40% of nominal value after a per-file assessment. Single titles and small portfolios welcome; also for Dutch and German creditors, with correspondence in English.')

@push('styles')
<style>
  .wy-facts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 860px) { .wy-facts { grid-template-columns: 1fr; } }
  .wy-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
  .wy-card h3 { font-size: 16px; margin: 0 0 8px; }
  .wy-card p { font-size: 14px; color: var(--text-2); line-height: 1.6; margin: 0; }
  .wy-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; max-width: 1000px; margin: 0 auto; }
  @media (max-width: 760px) { .wy-grid { grid-template-columns: 1fr; } }
  .wy-form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 28px; }
  .wy-hint { font-size: 12.5px; color: var(--text-3); margin-top: 4px; line-height: 1.5; }
  .wy-steps { counter-reset: step; margin: 0; padding: 0; list-style: none; }
  .wy-steps li { counter-increment: step; display: flex; gap: 12px; padding: 10px 0; border-top: 1px solid var(--border); font-size: 14px; color: var(--text-2); line-height: 1.55; }
  .wy-steps li:first-child { border-top: none; }
  .wy-steps li::before { content: counter(step); width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; background: var(--brand-tint); color: var(--brand); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }
  .wy-partner { display: flex; align-items: center; gap: 12px; }
  .wy-partner .lg { width: 38px; height: 38px; border-radius: 10px; background: #ec3013; color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <div class="eyebrow">Sell a judgment</div>
    <h1>That old judgment may still be worth money</h1>
    <p class="lead">A final Polish judgment or payment order — a tytuł wykonawczy (enforceable title) — does not lose its value the day the komornik (bailiff) gives up. sprzedamfakture.pl, the invoice-purchase partner of {{ brand('name') }}, also buys claims confirmed by an enforceable title: typically 10–40% of nominal value, after a per-file assessment. Single titles and small portfolios are just as welcome as larger ones.</p>
    <div style="margin-top:20px;">
      <a href="#formularz" class="btn btn-primary btn-lg">Describe your judgment — offer within a few working days</a>
    </div>
  </div>
</section>

<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="wy-facts">
      <div class="wy-card">
        <h3>The title keeps its force</h3>
        <p>When enforcement is discontinued as fruitless — bezskuteczność (fruitless enforcement) — the title returns to you and remains valid. You can hand it to a komornik again when the debtor's situation improves: a new job, an inheritance, assets.</p>
      </div>
      <div class="wy-card">
        <h3>Six years — and the clock can restart</h3>
        <p>A claim confirmed by an enforceable title becomes time-barred after six years (art. 125 of the Civil Code). A discontinuation for lack of assets restarts those six years from the date of the bailiff's decision.</p>
      </div>
      <div class="wy-card">
        <h3>Typically 10–40% of nominal</h3>
        <p>The price depends on the quality of the title and the debtor profile. Every file is assessed individually; you receive an offer within a few working days, with no fees and no obligation to accept.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="prose">
      <h2>Why an old judgment still has value</h2>
      <p>A claim confirmed by a final judgment or payment order is subject to a <strong>six-year limitation period</strong> (art. 125 of the Civil Code). The interest that has accrued expires earlier — after <strong>three years</strong> — but the principal keeps its enforceable status far longer than most creditors assume.</p>
      <p>Filing an enforcement request <strong>interrupts</strong> the limitation period. When the komornik discontinues the enforcement as fruitless (bezskuteczność), the six years <strong>start again</strong> from that decision, and the title comes back to you intact. In other words: an old judgment is an option on the debtor's future. Nothing recoverable today does not mean nothing recoverable in three years.</p>
      <p><strong>One caveat matters.</strong> If a previous enforcement was discontinued because of <em>creditor inactivity</em>, the law treats that request as never filed — the interruption lapses retroactively, and the limitation clock is measured as if the enforcement had not happened. That is why the reason for the previous discontinuation has to be established for each file. Since <strong>21 August 2019</strong> the komornik is obliged to check limitation and will refuse a time-barred title, so the distinction is not academic: it decides whether a title can still be enforced at all.</p>

      <h2>When there is more behind an sp. z o.o.</h2>
      <p>A judgment against a limited company that turned out to be empty is not always the end of the road. After fruitless enforcement against an sp. z o.o., the members of the management board can be held <strong>personally liable</strong> for the company's debts (art. 299 of the Commercial Companies Code). And where assets were siphoned off to frustrate creditors, they can be clawed back through the actio pauliana (art. 527 of the Civil Code). None of this is automatic — it is litigation — but it is part of what a professional buyer weighs when pricing a file.</p>

      <h2>Dutch or German creditor with a Polish judgment?</h2>
      <p>You litigated in Poland years ago, obtained a judgment, the enforcement came back empty and the claim was written off? That title may still have value. We assess it like any other file and make an offer — you send the documents as scans, and correspondence is in English. No Polish counsel or renewed proceedings on your side; if the offer is accepted, the assignment paperwork is prepared for you.</p>
    </div>
  </div>
</section>

<section class="section" id="formularz">
  <div class="container wy-grid">
    <div>
      @if (session('flash'))
        <div class="alert-success">Thank you — your file has been sent. sprzedamfakture.pl will assess the title and the debtor and you will receive a response, typically within a few working days.</div>
      @endif
      <form class="wy-form-card" method="POST" action="{{ route('pl.skup-wyrokow.send') }}">
        @csrf
        <h2 style="font-size:19px;margin-bottom:6px;">Describe your judgment</h2>
        <p style="font-size:14px;color:var(--text-2);margin:0 0 18px;line-height:1.6;">The more we know about the title and the previous enforcement, the faster and more precise the offer. Nothing here commits you to anything.</p>

        <div class="m-row-2">
          <div class="m-field">
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="email">E-mail address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="phone">Phone <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
            @error('phone')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="firm">Company <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="firm" type="text" name="firm" value="{{ old('firm') }}" autocomplete="organization">
            @error('firm')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="m-row-2">
          <div class="m-field">
            <label for="sygnatura">Case number (sygnatura akt)</label>
            <input id="sygnatura" type="text" name="sygnatura" value="{{ old('sygnatura') }}" required placeholder="e.g. VI GNc 1234/20">
            @error('sygnatura')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="sad">Court <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="sad" type="text" name="sad" value="{{ old('sad') }}" placeholder="e.g. Sąd Rejonowy dla m.st. Warszawy">
            @error('sad')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="data_wyroku">Date of the judgment <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="data_wyroku" type="date" name="data_wyroku" value="{{ old('data_wyroku') }}">
            @error('data_wyroku')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="kwota">Nominal amount (zł)</label>
            <input id="kwota" type="text" name="kwota" inputmode="decimal" value="{{ old('kwota') }}" required placeholder="e.g. 48 200,00">
            <div class="wy-hint">The principal from the title, without accrued interest.</div>
            @error('kwota')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="m-row-2">
          <div class="m-field">
            <label for="dluznik">Debtor</label>
            <input id="dluznik" type="text" name="dluznik" value="{{ old('dluznik') }}" required placeholder="Company name of the debtor">
            @error('dluznik')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="dluznik_nip">Debtor's NIP <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="dluznik_nip" type="text" name="dluznik_nip" value="{{ old('dluznik_nip') }}">
            @error('dluznik_nip')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-field">
          <label for="forma">Debtor's legal form</label>
          <select id="forma" name="forma">
            <option value="" @selected(old('forma') === null || old('forma') === '')>— select —</option>
            <option value="sp_zoo" @selected(old('forma') === 'sp_zoo')>Sp. z o.o.</option>
            <option value="sa" @selected(old('forma') === 'sa')>S.A.</option>
            <option value="jdg" @selected(old('forma') === 'jdg')>Sole trader (JDG)</option>
            <option value="inna" @selected(old('forma') === 'inna')>Other / unknown</option>
          </select>
          @error('forma')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-row-2">
          <div class="m-field">
            <label for="egzekucja">Previous enforcement</label>
            <select id="egzekucja" name="egzekucja">
              <option value="" @selected(old('egzekucja') === null || old('egzekucja') === '')>— select —</option>
              <option value="none" @selected(old('egzekucja') === 'none')>Never enforced</option>
              <option value="bezskutecznosc" @selected(old('egzekucja') === 'bezskutecznosc')>Discontinued — no assets (bezskuteczność)</option>
              <option value="inna" @selected(old('egzekucja') === 'inna')>Discontinued — other reason</option>
              <option value="nie_wiem" @selected(old('egzekucja') === 'nie_wiem')>I don't know</option>
            </select>
            <div class="wy-hint">Why the previous enforcement was discontinued determines the value: after bezskuteczność the six-year limitation restarts; after discontinuation for creditor inactivity the interruption lapses.</div>
            @error('egzekucja')<div class="m-err">{{ $message }}</div>@enderror
          </div>
          <div class="m-field">
            <label for="egzekucja_rok">Year enforcement ended <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
            <input id="egzekucja_rok" type="number" name="egzekucja_rok" value="{{ old('egzekucja_rok') }}" min="1990" max="2100" step="1" placeholder="e.g. 2022">
            @error('egzekucja_rok')<div class="m-err">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="m-field">
          <label for="uwagi">Anything else we should know? <span style="color:var(--text-4);font-weight:400;">(optional)</span></label>
          <textarea id="uwagi" name="uwagi" rows="4" placeholder="e.g. partial payments, the debtor still trades, several titles against the same debtor…">{{ old('uwagi') }}</textarea>
          @error('uwagi')<div class="m-err">{{ $message }}</div>@enderror
        </div>

        @if(config('services.turnstile.sitekey'))
          <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="en" style="margin-bottom:14px;"></div>
          @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
          <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
        <button type="submit" class="btn btn-primary btn-lg">Request an offer</button>
        <div style="font-size:12.5px;color:var(--text-3);margin-top:12px;">By sending this form you agree to your details being passed to sprzedamfakture.pl so the title can be assessed and an offer made — see the <a href="{{ route('pl.prywatnosc') }}" style="color:var(--brand);">privacy policy</a>.</div>
      </form>
    </div>

    <aside>
      <div class="wy-card">
        <h3 style="font-size:17px;margin-bottom:12px;">What happens next</h3>
        <ol class="wy-steps">
          <li>sprzedamfakture.pl assesses the title, the limitation position and the debtor — including the reason any previous enforcement was discontinued.</li>
          <li>You receive an offer within a few working days: typically 10–40% of nominal value, depending on the file. No fees, no obligation.</li>
          <li>If you accept, the claim is assigned, the amount is paid out and any further recovery is the buyer's concern.</li>
        </ol>
      </div>

      <div class="wy-card" style="margin-top:16px;">
        <div class="wy-partner" style="margin-bottom:10px;">
          <div class="lg">C</div>
          <div><div style="font-weight:700;">sprzedamfakture.pl</div><div style="font-size:13px;color:var(--text-3);">Invoice-purchase partner of {{ brand('name') }}</div></div>
        </div>
        <p style="color:var(--text-2);font-size:14px;margin:0 0 12px;line-height:1.6;">Besides unpaid B2B invoices, sprzedamfakture.pl buys claims confirmed by an enforceable title — single judgments and small portfolios included. The big players buy bank portfolios; five old B2B judgments are exactly the kind of file we look at.</p>
        <a href="https://sprzedamfakture.pl" target="_blank" rel="noopener" class="btn btn-secondary">sprzedamfakture.pl →</a>
      </div>

      <div class="wy-card" style="margin-top:16px;">
        <h3 style="font-size:16px;margin-bottom:8px;">Documents that help</h3>
        <p>The judgment or payment order with the enforceability clause (klauzula wykonalności), the bailiff's discontinuation decision (postanowienie o umorzeniu) and any correspondence. Scans are fine — you can send them after the first contact.</p>
      </div>
    </aside>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>An unpaid invoice that hasn't been to court yet?</h2>
    <p>Work out what you are actually owed — statutory interest and the 40/70/100 EUR compensation — and generate a payment demand in one click. Or let {{ brand('name') }} chase, demand and, if need be, sell the invoice for you.</p>
    <div class="hero-ctas">
      <a href="{{ route('pl.kalkulator') }}" class="btn btn-white btn-lg">Calculate interest and compensation →</a>
      <a href="{{ route('register') }}" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border-color:rgba(255,255,255,0.3);">Try {{ brand('name') }} free for 14 days</a>
    </div>
  </div>
</section>
@endsection
