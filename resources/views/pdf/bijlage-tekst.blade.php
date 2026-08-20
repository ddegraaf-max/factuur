<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<style>
  /* DejaVu Sans: ingebouwd in DomPDF en met volledige unicode (€, accenten). */
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #1c1917; margin: 42pt 48pt; line-height: 1.6; }
  h1 { font-size: 15pt; margin: 0 0 4pt; letter-spacing: -0.01em; }
  .meta { color: #78716c; font-size: 8pt; margin-bottom: 18pt; border-bottom: 1px solid #e7e5e4; padding-bottom: 10pt; }
  h2 { font-size: 12pt; margin: 16pt 0 6pt; }
  h3 { font-size: 10.5pt; margin: 12pt 0 4pt; }
  p { margin: 6pt 0; }
  ul, ol { margin: 6pt 0; padding-left: 16pt; }
  li { margin: 2pt 0; }
  strong { font-weight: bold; }
  blockquote { margin: 8pt 0; padding: 6pt 10pt; border-left: 3px solid #e7e5e4; color: #57534e; }
  table { border-collapse: collapse; width: 100%; margin: 8pt 0; }
  th, td { border: 1px solid #d6d3d1; padding: 4pt 6pt; font-size: 8.5pt; text-align: left; }
  th { background: #f5f5f4; }
  code { font-family: "DejaVu Sans Mono", monospace; font-size: 8.5pt; background: #f5f5f4; padding: 1pt 3pt; }
  hr { border: none; border-top: 1px solid #e7e5e4; margin: 12pt 0; }
</style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <div class="meta">{{ $company->name }} · bijlage bij {{ $documentLabel }}</div>
  {!! $html !!}
</body>
</html>
