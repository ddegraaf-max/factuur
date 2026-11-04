@extends('layouts.marketing')

{{-- 1.98: alleen-lezen opdrachtpagina voor een registerbedrijf. De klant zet de link zelf in zijn bericht
     (Job.vue messageFor); Fachora stuurt het bedrijf niets. Eén view voor pl (standaard) en en (?lang=en) via __().
     Niet indexeren: de pagina bestaat alleen voor het aangeschreven bedrijf. --}}

@section('robots', 'noindex, nofollow')
@section('title', __('Opdracht van een klant: :title', ['title' => $view['title_pl'] ?: $view['title']]) . ' — fachora.pl')
@section('description', __('Een klant koos uw bedrijf uit het CEIDG-register en schreef u persoonlijk. Hier ziet u de klus en antwoordt u met één klik.'))

@push('styles')
<style>
  .sh-wrap { max-width: 720px; margin: 0 auto; }
  .sh-eyebrow { font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--brand); margin-bottom: 8px; }
  .sh-for { color: var(--text-3); font-size: 15px; margin: 6px 0 22px; }
  .sh-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 24px 26px; margin-top: 18px; }
  .sh-facts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 22px; margin: 0; }
  .sh-facts div { font-size: 14.5px; }
  .sh-facts dt { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--text-3); margin-bottom: 2px; }
  .sh-facts dd { margin: 0; font-weight: 600; }
  @media (max-width: 560px) { .sh-facts { grid-template-columns: 1fr; } }
  .sh-desc { white-space: pre-wrap; line-height: 1.65; font-size: 16px; }
  .sh-photos { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
  .sh-photos img { width: 150px; height: 110px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border); }
  .sh-why { background: var(--success-bg); border: 1px solid #6EE7B7; }
  .sh-why h2 { font-size: 18px; margin: 0 0 10px; }
  .sh-why p { margin: 0 0 10px; font-size: 15px; line-height: 1.6; }
  .sh-why p:last-child { margin-bottom: 0; }
  .sh-done { background: var(--success-bg); border: 1px solid #6EE7B7; border-radius: 14px; padding: 18px 22px; font-weight: 600; margin-top: 18px; }
  .sh-off { background: var(--bg-2, #F5F6F8); border: 1px solid var(--border); border-radius: 14px; padding: 18px 22px; margin-top: 18px; }
  .sh-form h2 { font-size: 20px; margin: 0 0 6px; }
  .sh-field { margin-bottom: 14px; }
  .sh-field label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; }
  .sh-field input, .sh-field textarea { width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font: inherit; font-size: 15px; background: var(--surface); color: inherit; }
  .sh-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 560px) { .sh-row { grid-template-columns: 1fr; } }
  .sh-err { color: var(--brand-darker); font-size: 13.5px; margin-top: 5px; }
  .sh-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 6px; }
  .sh-note { font-size: 13px; color: var(--text-3); margin-top: 12px; line-height: 1.55; }
  .sh-foot { margin-top: 26px; font-size: 13.5px; color: var(--text-3); line-height: 1.7; }
  .sh-foot a { color: var(--brand); }
</style>
@endpush

@section('content')
<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="sh-wrap">
      <div class="sh-eyebrow">{{ __('Opdracht van een klant') }} · fachora.pl</div>
      <h1 style="font-size:30px;line-height:1.2;margin:0;">{{ $view['title_pl'] ?: $view['title'] }}</h1>
      <p class="sh-for">{{ __('Voor: :name', ['name' => $provider->display_name]) }}</p>

      <div class="sh-card sh-why">
        <h2>{{ __('Waarom kreeg u dit bericht?') }}</h2>
        <p>{{ __('De klant (:name) zocht een vakman in de buurt van :city, koos uw bedrijf uit de bedrijven in deze branche in het CEIDG-register en schreef u persoonlijk, vanuit zijn eigen mailbox.', ['name' => $firstName, 'city' => $job->city]) }}</p>
        <p>{{ __('Fachora.pl heeft dit bericht niet verstuurd. Wij sturen geen e-mails of sms-berichten aan bedrijven uit het register; uw adres komt uit uw openbare CEIDG-inschrijving en staat op geen enkele mailinglijst.') }}</p>
        <p>{{ __('Als u antwoordt, praat u rechtstreeks met de klant. Er is geen vergoeding, registratie of overeenkomst met fachora.pl.') }}</p>
      </div>

      <div class="sh-card">
        <dl class="sh-facts">
          <div><dt>{{ __('Plaats') }}</dt><dd>{{ trim($job->postal_code . ' ' . $job->city) }}</dd></div>
          <div><dt>{{ __('Categorie') }}</dt><dd>{{ $view['category_name'] }}</dd></div>
          <div><dt>{{ __('Termijn') }}</dt><dd>{{ app()->getLocale() === 'en' ? $view['urgency_label'] : $view['urgency_label_pl'] }}</dd></div>
          <div><dt>{{ __('Talen van de klant') }}</dt><dd>{{ $view['language_label'] }}</dd></div>
          <div><dt>{{ __('Kenmerk') }}</dt><dd>{{ $job->reference }}</dd></div>
          <div><dt>{{ __('Aangevraagd op') }}</dt><dd>{{ $job->created_at?->translatedFormat('j F Y') }}</dd></div>
        </dl>
        <hr style="border:0;border-top:1px solid var(--border);margin:18px 0;">
        <h2 style="font-size:17px;margin:0 0 8px;">{{ __('Omschrijving van de klus') }}</h2>
        <div class="sh-desc">{{ $view['description_pl'] ?: $view['description'] }}</div>
        @if($photos)
          <h2 style="font-size:17px;margin:18px 0 0;">{{ __("Foto's") }}</h2>
          <div class="sh-photos">
            @foreach($photos as $photo)
              <a href="{{ $photo['url'] }}" target="_blank" rel="noopener"><img src="{{ $photo['url'] }}" alt="{{ $photo['filename'] }}" loading="lazy"></a>
            @endforeach
          </div>
        @endif
      </div>

      @if(! $active)
        <div class="sh-off">
          <strong>{{ __('Deze opdracht is niet meer actueel.') }}</strong><br>
          <span style="font-size:14.5px;">{{ __('De klant heeft al een bedrijf gekozen of zijn lijst aangepast. U hoeft niets te doen.') }}</span>
        </div>
      @else
        @if(session('share_done') === 'interested' || ($answered === 'interested' && ! session('share_done')))
          <div class="sh-done">{{ __('Bedankt! De klant heeft uw antwoord ontvangen en neemt contact met u op.') }}</div>
        @elseif(session('share_done') === 'declined' || $answered === 'declined')
          <div class="sh-done">{{ __('Genoteerd: u heeft geen interesse. De klant ziet dat op zijn opdrachtpagina. Bedankt voor uw antwoord.') }}</div>
        @endif

        <div class="sh-card sh-form">
          <h2>{{ $answered ? __('Toch van gedachten veranderd? U kunt uw antwoord hieronder aanpassen.') : __('Heeft u interesse?') }}</h2>
          @error('kind')<div class="sh-err">{{ $message }}</div>@enderror
          <form method="POST" action="{{ route('fachora.share.respond', [request()->route('share'), request()->route('firm')]) }}" style="margin-top:12px;">
            @csrf
            <div class="sh-row">
              <div class="sh-field">
                <label for="sh-name">{{ __('Contactpersoon') }}</label>
                <input id="sh-name" type="text" name="name" value="{{ old('name', $response['name'] ?? '') }}" autocomplete="name">
                @error('name')<div class="sh-err">{{ $message }}</div>@enderror
              </div>
              <div class="sh-field">
                <label for="sh-phone">{{ __('Telefoon') }}</label>
                <input id="sh-phone" type="tel" name="phone" value="{{ old('phone', $response['phone'] ?? $provider->phone) }}" autocomplete="tel">
                @error('phone')<div class="sh-err">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="sh-field">
              <label for="sh-email">{{ __('E-mail') }}</label>
              <input id="sh-email" type="email" name="email" value="{{ old('email', $response['email'] ?? $provider->email) }}" autocomplete="email">
              @error('email')<div class="sh-err">{{ $message }}</div>@enderror
            </div>
            <div class="sh-field">
              <label for="sh-message">{{ __('Bericht aan de klant (optioneel)') }}</label>
              <textarea id="sh-message" name="message" rows="4" placeholder="{{ __('Bijvoorbeeld: ik kan volgende week langskomen om te kijken; richtprijs vanaf … zł.') }}">{{ old('message', $response['message'] ?? '') }}</textarea>
              @error('message')<div class="sh-err">{{ $message }}</div>@enderror
            </div>
            {{-- Eén formulier, twee knoppen: de knop bepaalt kind (interested/declined). --}}
            <div class="sh-actions">
              <button type="submit" name="kind" value="interested" class="btn btn-primary btn-lg">{{ __('Ja, neem contact met mij op') }}</button>
              <button type="submit" name="kind" value="declined" class="btn btn-secondary">{{ __('Nee, geen interesse') }}</button>
            </div>
          </form>
          <p class="sh-note">{{ __('Uw antwoord gaat alleen naar deze klant. Fachora.pl stuurt u verder geen berichten.') }}</p>
        </div>
      @endif

      <div class="sh-foot">
        @if($optoutUrl)
          {{ __('Wilt u zulke aanvragen in de toekomst niet ontvangen?') }} <a href="{{ $optoutUrl }}">{{ __('Verwijder mijn bedrijf van fachora.pl') }}</a><br>
        @endif
        {{ __('Wilt u opdrachten rechtstreeks op fachora.pl ontvangen?') }} <a href="{{ $provider->publicUrl() }}">{{ __('Neem gratis uw profiel over') }}</a><br>
        <a href="{{ url('/polityka-prywatnosci') }}">{{ __('Privacybeleid') }}</a> · czesc@fachora.pl
      </div>
    </div>
  </div>
</section>
@endsection
