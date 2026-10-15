<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Fachora — listy</title>
<style>
    @page { margin: 22mm 20mm 20mm 20mm; }
    body { font-family: {!! \App\Support\DocumentLocale::font('sans') !!}; font-size: 11pt; color: #0F172A; line-height: 1.5; }
    .letter { page-break-after: always; }
    .letter:last-child { page-break-after: auto; }
    .head { border-bottom: 3px solid #0B2545; padding-bottom: 8px; margin-bottom: 26px; }
    .wordmark { font-size: 22pt; font-weight: 700; letter-spacing: 0.04em; color: #0B2545; }
    .wordmark span { color: #F4781F; }
    .tag { font-size: 9pt; color: #64748B; }
    .addr { margin: 0 0 26px; font-size: 11pt; line-height: 1.45; }
    .date { text-align: right; color: #64748B; font-size: 10pt; margin-bottom: 18px; }
    h1 { font-size: 15pt; margin: 0 0 12px; color: #0B2545; }
    p { margin: 0 0 10px; }
    .box { border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px 14px; margin: 16px 0; background: #F8FAFC; }
    .box table { width: 100%; border-collapse: collapse; }
    .box td { vertical-align: middle; padding: 0; }
    .code { font-family: DejaVu Sans Mono, monospace; font-size: 20pt; font-weight: 700; letter-spacing: 0.08em; color: #0B2545; }
    .url { font-family: DejaVu Sans Mono, monospace; font-size: 10.5pt; color: #0B2545; }
    .small { font-size: 9pt; color: #64748B; }
    ul { margin: 6px 0 12px 18px; padding: 0; }
    li { margin-bottom: 4px; }
    .foot { margin-top: 30px; border-top: 1px solid #E2E8F0; padding-top: 8px; font-size: 8.5pt; color: #94A3B8; }
</style>
</head>
<body>
@foreach($letters as $l)
    @php($p = $l['provider'])
    <div class="letter">
        <div class="head">
            <div class="wordmark">FACH<span>ORA</span></div>
            <div class="tag">Sprawdzone firmy. Bezpieczne usługi. · {{ $domain }}</div>
        </div>

        <div class="addr">
            <strong>{{ $p->legal_name ?: $p->display_name }}</strong><br>
            @if($p->address_line){{ $p->address_line }}<br>@endif
            {{ $p->postal_code }} {{ $p->city }}
        </div>
        <div class="date">{{ $date }}</div>

        <h1>Nowi klienci w Twojej okolicy. Płacisz tylko za zrealizowane zlecenia.</h1>

        <p>Szanowni Państwo,</p>
        <p>Fachora to platforma, na której klienci — także obcokrajowcy mieszkający w Polsce — szukają sprawdzonego fachowca
            w kategorii{{ count($l['categories']) === 1 ? '' : 'ch' }} <strong>{{ implode(', ', $l['categories']) ?: 'usług' }}</strong>.
            Firma {{ $p->display_name }} znajduje się już w naszej bazie na podstawie danych z publicznych rejestrów,
            ale profil nie jest jeszcze aktywny. Nie wysyłamy e-maili ani SMS-ów bez Państwa zgody, dlatego piszemy listownie.</p>

        <p>Co daje aktywny profil ze znakiem <strong>Fachora Verified</strong>:</p>
        <ul>
            <li>zlecenia z Państwa okolicy wprost na e-mail, z opisem, zdjęciami i terminem,</li>
            <li>cena ustalona z góry i zabezpieczona płatność Fachora Protect — klient rezerwuje kwotę przed pracą,</li>
            <li>0 zł abonamentu, 0 zł za zapytanie; prowizja tylko od wykonanego zlecenia,</li>
            <li>bezpłatny program do faktur (KSeF), ofert i terminarza.</li>
        </ul>

        <div class="box">
            <table>
                <tr>
                    <td style="width: 70%;">
                        <div class="small">Przejmij profil swojej firmy na stronie</div>
                        <div class="url">{{ $domain }}/przejmij</div>
                        <div class="small" style="margin-top: 8px;">i wpisz kod z tego listu:</div>
                        <div class="code">{{ $l['code'] }}</div>
                        <div class="small">Kod działa tylko dla Państwa firmy i nie zobowiązuje do niczego.</div>
                    </td>
                    <td style="width: 30%; text-align: right;">
                        @if($l['qr'])<img src="{{ $l['qr'] }}" alt="QR" style="width: 32mm; height: 32mm;">@endif
                    </td>
                </tr>
            </table>
        </div>

        <p>Po przejęciu profilu uzupełniają Państwo obszar działania, języki i stawki, a my sprawdzamy dane w rejestrach.
            Dopiero wtedy profil otrzymuje znak Fachora Verified i zaczyna otrzymywać zlecenia.</p>
        <p>Z poważaniem,<br>Zespół Fachora</p>

        <div class="foot">
            Fachora jest usługą Creditline B.V., Torenlaan 5B, 1402 AT Bussum, Holandia · {{ $domain }} · Dane firmy pochodzą z publicznych rejestrów (CEIDG/KRS).
            Jeśli nie życzą sobie Państwo dalszej korespondencji, prosimy o wiadomość na kontakt@{{ $domain }} — usuniemy wpis.
        </div>
    </div>
@endforeach
</body>
</html>
