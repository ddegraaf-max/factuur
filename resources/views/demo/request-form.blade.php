{{--
  Demo op aanvraag (1.86.0), gedeeld door alle merken en talen (teksten via __()).
  Drie standen: de persoonlijke link ($token) toont de startknop, een verzonden
  aanvraag (session demo_requested) de bevestiging, anders het aanvraagformulier.
--}}
@push('styles')
<style>
  .demo-start .btn-xl { height: 52px; padding: 0 30px; font-size: 16px; font-weight: 600; }
  .demo-form { max-width: 560px; margin: 0 auto; text-align: left; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 22px 24px 20px; }
  .demo-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
  @media (max-width: 600px) { .demo-form-grid { grid-template-columns: 1fr; } }
  .demo-form .btn-xl { width: 100%; }
  .demo-form .req { color: var(--brand); }
  .demo-form .opt { color: var(--text-3); font-weight: 400; }
  .demo-form textarea { min-height: 76px; }
  .demo-ok { max-width: 560px; margin: 0 auto; background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; font-size: 14.5px; line-height: 1.6; color: var(--text-2); }
  .demo-ok b { color: var(--text); }
</style>
@endpush

<div class="demo-start">
  @if (session('error'))
    <div style="max-width:560px;margin:0 auto 18px;background:var(--brand-tint);border:1px solid var(--brand-border);color:var(--brand-darker);border-radius:10px;padding:12px 16px;font-size:14px;">
      {{ session('error') }}
    </div>
  @endif

  @if (! empty($token))
    <form method="POST" action="{{ route('demo.start', $token) }}">
      @csrf
      <button type="submit" class="btn btn-primary btn-xl">{{ __('Start de demo') }} →</button>
    </form>
    <div class="demo-note">{{ __('Je toegang is goedgekeurd · elke start is een verse voorbeeldomgeving · geen creditcard') }}</div>
  @elseif (session('demo_requested'))
    <div class="demo-ok">
      <b>{{ __('Bedankt, je aanvraag is binnen.') }}</b><br>
      {{ __('Je krijgt een e-mail met je persoonlijke link zodra de demo voor je is klaargezet — meestal dezelfde dag.') }}
    </div>
  @else
    <form method="POST" action="{{ route('demo.request') }}" class="demo-form">
      @csrf
      <div class="demo-form-grid">
        <div class="m-field">
          <label for="demo-name">{{ __('Naam') }}</label>
          <input id="demo-name" type="text" name="name" value="{{ old('name') }}" maxlength="120" autocomplete="name">
          @error('name')<div class="m-err">{{ $message }}</div>@enderror
        </div>
        <div class="m-field">
          <label for="demo-email">{{ __('E-mailadres') }} <span class="req">*</span></label>
          <input id="demo-email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
          @error('email')<div class="m-err">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="m-field">
        <label for="demo-company">{{ __('Bedrijf') }} <span class="opt">{{ __('(optioneel)') }}</span></label>
        <input id="demo-company" type="text" name="company" value="{{ old('company') }}" maxlength="160" autocomplete="organization">
        @error('company')<div class="m-err">{{ $message }}</div>@enderror
      </div>
      <div class="m-field">
        <label for="demo-message">{{ __('Waar wil je naar kijken?') }} <span class="opt">{{ __('(optioneel)') }}</span></label>
        <textarea id="demo-message" name="message" rows="3" maxlength="600">{{ old('message') }}</textarea>
        @error('message')<div class="m-err">{{ $message }}</div>@enderror
      </div>
      @if(config('services.turnstile.sitekey'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-language="{{ app()->getLocale() }}" style="margin-bottom:14px;"></div>
        @error('cf-turnstile-response')<div class="m-err" style="margin-bottom:12px;">{{ $message }}</div>@enderror
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      @endif
      <button type="submit" class="btn btn-primary btn-xl">{{ __('Vraag toegang tot de demo') }} →</button>
    </form>
    <div class="demo-note">{{ __('Persoonlijke toegang: je krijgt een e-mail met je link zodra we je aanvraag hebben goedgekeurd · geen creditcard') }}</div>
  @endif
</div>
