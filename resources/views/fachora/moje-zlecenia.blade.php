@extends('layouts.marketing')

{{-- 1.99: "Moje zlecenia" — inloggen zonder wachtwoord (e-mail + code per mail), daarna de eigen aanvragen.
     Eén Blade voor pl (standaard) en en (?lang=en) via __(). Zie CustomerAccessController. --}}

@section('robots', 'noindex')
@section('title', __('Mijn opdrachten') . ' — fachora.pl')
@section('description', __('Je opdrachten bij Fachora, zonder wachtwoord: e-mailadres, code per mail, klaar.'))

@push('styles')
<style>
  .my-wrap { max-width: 640px; margin: 0 auto; }
  .my-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 26px 28px; margin-top: 20px; }
  .my-field { margin-bottom: 14px; }
  .my-field label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; }
  .my-field input { width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 10px; padding: 11px 12px; font: inherit; font-size: 16px; background: var(--surface); color: inherit; }
  .my-field input.my-code { font-size: 26px; letter-spacing: .3em; text-align: center; font-weight: 700; }
  .my-err { color: var(--brand-darker); font-size: 13.5px; margin-top: 5px; }
  .my-ok { background: var(--success-bg); border: 1px solid #6EE7B7; border-radius: 12px; padding: 12px 16px; font-size: 14.5px; margin-bottom: 14px; }
  .my-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
  .my-list { list-style: none; margin: 0; padding: 0; }
  .my-job { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 14px 0; border-top: 1px solid var(--border); }
  .my-job:first-child { border-top: none; padding-top: 0; }
  .my-job-title { font-weight: 600; }
  .my-job-meta { font-size: 13.5px; color: var(--text-3); margin-top: 3px; }
  .my-status { display: inline-block; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: var(--brand-tint); color: var(--brand-darker); margin-left: 6px; }
  .my-foot { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-top: 18px; font-size: 13.5px; color: var(--text-3); }
  .my-foot form { margin: 0; }
  .my-link { background: none; border: 0; padding: 0; color: var(--brand); font: inherit; cursor: pointer; text-decoration: underline; }
  @media (max-width: 560px) { .my-job { flex-direction: column; align-items: flex-start; } }
</style>
@endpush

@section('content')
<section class="section" style="padding-top:40px;">
  <div class="container">
    <div class="my-wrap">
      <h1 style="font-size:30px;line-height:1.2;margin:0;">{{ __('Mijn opdrachten') }}</h1>
      <p style="color:var(--text-3);margin:8px 0 0;">{{ __('Je opdrachten bij Fachora, zonder wachtwoord') }}</p>

      @if($email)
        <div class="my-card">
          @if($jobs->isEmpty())
            <p style="margin:0 0 14px;">{{ __('Je hebt nog geen aanvragen met dit adres.') }}</p>
            <a href="{{ route('fachora.intake') }}" class="btn btn-primary">{{ __('Nieuwe aanvraag') }}</a>
          @else
            <ul class="my-list">
              @foreach($jobs as $job)
                <li class="my-job">
                  <div>
                    <div class="my-job-title">{{ $job->title }}<span class="my-status">{{ $job->isPending() ? __('Nog te bevestigen') : $job->statusLabel() }}</span></div>
                    <div class="my-job-meta">{{ $job->reference }} · {{ $job->categoryName() }} · {{ $job->city }} · {{ $job->created_at?->translatedFormat('j F Y') }}</div>
                  </div>
                  <a href="{{ $job->isPending() ? $job->confirmUrl() : $job->url() }}" class="btn btn-secondary">{{ __('Openen') }}</a>
                </li>
              @endforeach
            </ul>
          @endif
          <div class="my-foot">
            <span>{{ __('Ingelogd als :email', ['email' => $email]) }}</span>
            <form method="POST" action="{{ route('fachora.my.logout') }}">@csrf<button type="submit" class="my-link">{{ __('Uitloggen') }}</button></form>
          </div>
        </div>
      @elseif($pending)
        <div class="my-card">
          @if(session('my_sent'))<div class="my-ok">{{ __('Als we aanvragen met dit adres hebben, is de code onderweg. Kijk ook in je spam.') }}</div>@endif
          <form method="POST" action="{{ route('fachora.my.verify') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $pending }}">
            <div class="my-field">
              <label for="my-code">{{ __('Code uit de mail') }} <span style="font-weight:400;color:var(--text-3);">({{ $pending }})</span></label>
              <input id="my-code" class="my-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
              @error('code')<div class="my-err">{{ $message }}</div>@enderror
            </div>
            <div class="my-actions">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Inloggen') }}</button>
              <a href="{{ route('fachora.my.logout') }}" onclick="event.preventDefault();document.getElementById('my-reset').submit();" class="my-link">{{ __('Ander e-mailadres') }}</a>
            </div>
          </form>
          <form id="my-reset" method="POST" action="{{ route('fachora.my.logout') }}">@csrf</form>
        </div>
      @else
        <div class="my-card">
          <p style="margin:0 0 16px;">{{ __('Vul je e-mailadres in; je krijgt een code per mail en ziet daarna al je aanvragen met hun links.') }}</p>
          <form method="POST" action="{{ route('fachora.my.code') }}">
            @csrf
            <div class="my-field">
              <label for="my-email">{{ __('E-mailadres') }}</label>
              <input id="my-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
              @error('email')<div class="my-err">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-lg">{{ __('Code sturen') }}</button>
          </form>
        </div>
      @endif
    </div>
  </div>
</section>
@endsection
