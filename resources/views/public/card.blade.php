@extends('layouts.public-brand', ['madeWith' => 'kaart'])
@section('title', ($card->contact_name ? $card->contact_name . ' · ' : '') . $company->publicName())
@section('description', $card->tagline ?: 'Contactgegevens van ' . $company->publicName())
@section('styles')
  .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 16px; background: linear-gradient(160deg, var(--brand) 0%, var(--accent) 100%); }
  .card { width: 100%; max-width: 440px; background: var(--surface); border-radius: 22px; box-shadow: 0 24px 60px rgba(0,0,0,.25); overflow: hidden; }
  .head { padding: 34px 28px 22px; text-align: center; border-bottom: 1px solid var(--border); }
  .logo { max-height: 72px; max-width: 200px; object-fit: contain; margin: 0 auto 14px; display: block; }
  .mono { width: 72px; height: 72px; border-radius: 18px; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 28px; margin: 0 auto 14px; }
  .name { font-size: 22px; font-weight: 800; letter-spacing: -.01em; }
  .role { color: var(--text-2); margin-top: 2px; }
  .org { color: var(--brand); font-weight: 700; margin-top: 8px; }
  .tag { color: var(--text-2); margin-top: 10px; font-size: 15px; }
  .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 20px 22px 8px; }
  .actions .btn-brand { grid-column: 1 / -1; }
  .details { padding: 12px 24px 26px; font-size: 14px; color: var(--text-2); }
  .details div { padding: 8px 0; border-top: 1px solid var(--border); display: flex; justify-content: space-between; gap: 12px; }
  .details div:first-child { border-top: 0; }
  .details b { color: var(--text); font-weight: 600; text-align: right; }
@endsection
@section('content')
<div class="wrap">
  <div class="card">
    <div class="head">
      @if($company->logo_data)
        <img class="logo" src="{{ $company->logo_data }}" alt="{{ $company->publicName() }}">
      @else
        <div class="mono">{{ mb_strtoupper(mb_substr($company->publicName(), 0, 1)) }}</div>
      @endif
      @if($card->contact_name)
        <div class="name">{{ $card->contact_name }}</div>
        @if($card->job_title)<div class="role">{{ $card->job_title }}</div>@endif
        <div class="org">{{ $company->publicName() }}</div>
      @else
        <div class="name">{{ $company->publicName() }}</div>
        @if($card->job_title)<div class="role">{{ $card->job_title }}</div>@endif
      @endif
      @if($card->tagline)<div class="tag">{{ $card->tagline }}</div>@endif
    </div>
    <div class="actions">
      <a class="btn btn-brand" href="{{ route('card.vcard', $company->public_slug) }}">Opslaan in contacten</a>
      @if($phone_url)<a class="btn" href="{{ $phone_url }}">Bellen</a>@endif
      @if($company->email)<a class="btn" href="mailto:{{ $company->email }}">E-mailen</a>@endif
      @if($whatsapp_url)<a class="btn" href="{{ $whatsapp_url }}" rel="noopener">WhatsApp</a>@endif
      @if($site_url)<a class="btn" href="{{ $site_url }}">Website</a>
      @elseif($website_url)<a class="btn" href="{{ $website_url }}" rel="noopener">Website</a>@endif
      @if($card->linkedin_url)<a class="btn" href="{{ $card->linkedin_url }}" rel="noopener">LinkedIn</a>@endif
    </div>
    <div class="details">
      @if($card->show_address && $company->full_address)<div><span>Adres</span><b>{{ $company->full_address }}</b></div>@endif
      @if($card->show_kvk && $company->kvk_number)<div><span>KvK</span><b>{{ $company->kvk_number }}</b></div>@endif
      @if($card->show_vat && $company->vat_number)<div><span>Btw-nummer</span><b>{{ $company->vat_number }}</b></div>@endif
    </div>
  </div>
</div>
@endsection
