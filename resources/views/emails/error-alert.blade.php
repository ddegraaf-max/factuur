<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Fout in EasyInvoice</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; padding: 32px 16px; }
        .container { max-width: 640px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: #B91C1C; padding: 22px 32px; color: #fff; }
        .header h1 { margin: 0; font-size: 17px; font-weight: 700; }
        .body { padding: 24px 32px 30px; }
        .msg { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 14px; color: #7F1D1D; white-space: pre-wrap; word-break: break-word; }
        table.meta { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 13px; }
        table.meta td { padding: 5px 0; border-bottom: 1px solid #F5F5F4; vertical-align: top; }
        table.meta td:first-child { color: #78716C; width: 120px; }
        pre { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11.5px; line-height: 1.5; background: #1C1917; color: #E7E5E4; border-radius: 8px; padding: 14px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
        .foot { font-size: 12px; color: #A8A29E; margin-top: 16px; }
    </style>
</head>
<body>
<div class="wrapper"><div class="container">
    <div class="header"><h1>⚠️ Onverwachte fout in EasyInvoice</h1></div>
    <div class="body">
        <div class="msg">{{ get_class($exception) }}
{{ $exception->getMessage() ?: '(geen melding)' }}</div>
        <table class="meta">
            <tr><td>Bestand</td><td>{{ str_replace(base_path() . '/', '', $exception->getFile()) }}:{{ $exception->getLine() }}</td></tr>
            @if(!empty($context['url']))<tr><td>Verzoek</td><td>{{ $context['method'] ?? '' }} {{ $context['url'] }}</td></tr>@endif
            @if(!empty($context['console']))<tr><td>Commando</td><td>{{ $context['console'] }}</td></tr>@endif
            @if(!empty($context['user']))<tr><td>Gebruiker</td><td>{{ $context['user'] }}@if(!empty($context['company'])) · {{ $context['company'] }}@endif</td></tr>@endif
            @if(!empty($context['ip']))<tr><td>IP</td><td>{{ $context['ip'] }}</td></tr>@endif
            <tr><td>Tijd</td><td>{{ $context['time'] ?? now()->format('d-m-Y H:i:s') }} · {{ $context['version'] ?? '' }}</td></tr>
        </table>
        <pre>{{ implode("\n", $trace) }}</pre>
        <div class="foot">Dezelfde fout wordt maximaal één keer per uur gemeld. Volledige details staan in het Railway-log.</div>
    </div>
</div></div>
</body>
</html>
