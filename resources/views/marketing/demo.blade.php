@verbatim
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>EasyInvoice — Demo-omgeving</title>
<link rel="icon" type="image/png" sizes="32x32" href="/images/easyinvoice-favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/images/easyinvoice-favicon-180.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --brand:#E8231F;--brand-dark:#C71A17;--brand-darker:#9C1411;
    --brand-tint:#FEF2F2;--brand-tint-2:#FEE2E2;--brand-border:#FECACA;
    --text:#1C1917;--text-2:#44403C;--text-3:#78716C;--text-4:#A8A29E;
    --bg:#FAFAF9;--surface:#FFFFFF;--surface-2:#F5F5F4;--surface-3:#E7E5E4;
    --border:#E7E5E4;--border-strong:#D6D3D1;
    --success:#059669;--success-bg:#D1FAE5;--info:#0369A1;--info-bg:#E0F2FE;
    --warning:#D97706;--warning-bg:#FEF3C7;
    --font-display:'Bricolage Grotesque',system-ui,sans-serif;
    --font-body:'DM Sans',system-ui,sans-serif;
    --font-mono:'JetBrains Mono',monospace;
    --shadow-sm:0 1px 2px rgba(28,25,23,.05);
    --shadow-md:0 4px 12px rgba(28,25,23,.07);
    --shadow-lg:0 12px 40px rgba(28,25,23,.10);
  }
  *,*::before,*::after{box-sizing:border-box;}
  body{margin:0;font-family:var(--font-body);color:var(--text);background:var(--bg);-webkit-font-smoothing:antialiased;line-height:1.55;font-size:15px;overflow-x:hidden;}
  a{color:inherit;text-decoration:none;}
  button{font-family:inherit;cursor:pointer;}
  h1,h2,h3,h4{font-family:var(--font-display);font-weight:600;letter-spacing:-.02em;margin:0;color:var(--text);line-height:1.15;}
  .mono{font-family:var(--font-mono);font-variant-numeric:tabular-nums;}

  /* ---- TOPBAR ---- */
  .topbar{position:sticky;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:16px;height:58px;padding:0 18px;background:rgba(250,250,249,.9);backdrop-filter:saturate(180%) blur(10px);border-bottom:1px solid var(--border);}
  .tb-left{display:flex;align-items:center;gap:12px;min-width:0;}
  .tb-brand{display:flex;align-items:center;gap:9px;font-family:var(--font-display);font-weight:700;font-size:17px;letter-spacing:-.02em;}
  .tb-brand img{width:28px;height:28px;border-radius:6px;}
  .tb-brand sup{font-size:.5em;font-weight:600;vertical-align:super;}
  .demo-pill{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--brand);background:var(--brand-tint);border:1px solid var(--brand-border);padding:4px 10px;border-radius:100px;}
  .demo-pill::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--brand);box-shadow:0 0 0 3px rgba(232,35,31,.16);}
  .tb-right{display:flex;align-items:center;gap:10px;}
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:100px;font-size:13.5px;font-weight:600;border:1px solid transparent;transition:all .15s;white-space:nowrap;}
  .btn-primary{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 4px 14px rgba(232,35,31,.25);}
  .btn-primary:hover{background:var(--brand-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(232,35,31,.34);}
  .btn-ghost{background:transparent;color:var(--text-2);}
  .btn-ghost:hover{background:var(--surface-2);color:var(--text);}
  .btn-soft{background:var(--surface);color:var(--text);border-color:var(--border-strong);}
  .btn-soft:hover{background:var(--surface-2);}
  .btn-sm{padding:7px 13px;font-size:12.5px;}
  .btn svg{width:15px;height:15px;flex:none;stroke:currentColor;fill:none;}
  .demo-banner svg{width:18px;height:18px;flex:none;stroke:currentColor;fill:none;}
  .back svg{width:16px;height:16px;flex:none;stroke:currentColor;fill:none;}

  /* ---- SHELL ---- */
  .shell{display:grid;grid-template-columns:236px 1fr;min-height:calc(100vh - 58px);}
  .sidebar{border-right:1px solid var(--border);padding:18px 12px;background:var(--surface);position:sticky;top:58px;height:calc(100vh - 58px);overflow-y:auto;}
  .side-group{margin-bottom:18px;}
  .side-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--text-4);padding:0 10px;margin-bottom:7px;}
  .side-item{display:flex;align-items:center;gap:11px;width:100%;text-align:left;background:none;border:none;padding:9px 10px;border-radius:9px;font-size:13.5px;font-weight:500;color:var(--text-2);transition:all .12s;}
  .side-item svg{width:17px;height:17px;flex-shrink:0;stroke:currentColor;}
  .side-item:hover{background:var(--surface-2);color:var(--text);}
  .side-item.active{background:var(--brand-tint);color:var(--brand);font-weight:600;}
  .side-badge{margin-left:auto;font-size:11px;font-weight:700;background:var(--brand);color:#fff;border-radius:100px;padding:1px 7px;}

  .main{padding:28px 32px 60px;max-width:1100px;}
  .page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px;}
  .page-head h1{font-size:25px;}
  .page-sub{color:var(--text-3);font-size:14px;margin-top:3px;}

  /* ---- DASHBOARD ---- */
  .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
  .kpi{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 17px;box-shadow:var(--shadow-sm);}
  .kpi-label{font-size:12.5px;color:var(--text-3);font-weight:500;margin-bottom:8px;display:flex;align-items:center;gap:7px;}
  .kpi-label .dot{width:8px;height:8px;border-radius:50%;}
  .kpi-value{font-family:var(--font-mono);font-size:23px;font-weight:600;letter-spacing:-.02em;}
  .kpi-meta{font-size:12px;margin-top:5px;font-weight:500;}

  .panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-sm);overflow:hidden;}
  .panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 18px;border-bottom:1px solid var(--border);}
  .panel-head h3{font-size:15px;}
  .panel-pad{padding:18px;}
  .grid-2{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:16px;}

  .chart{display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:8px;}
  .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;}
  .bar{width:100%;max-width:34px;border-radius:6px 6px 0 0;background:var(--brand-tint-2);transition:height .6s cubic-bezier(.2,.7,.2,1);position:relative;}
  .bar.hot{background:var(--brand);}
  .bar-col:hover .bar{background:var(--brand-dark);}
  .bar-lbl{font-size:11px;color:var(--text-4);font-weight:600;}

  /* ---- TABLE ---- */
  .tbl{width:100%;border-collapse:collapse;font-size:13.5px;}
  .tbl th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-4);font-weight:700;padding:11px 18px;border-bottom:1px solid var(--border);}
  .tbl td{padding:13px 18px;border-bottom:1px solid var(--border);color:var(--text-2);}
  .tbl tr:last-child td{border-bottom:none;}
  .tbl tbody tr{transition:background .12s;}
  .tbl tbody tr.click{cursor:pointer;}
  .tbl tbody tr.click:hover{background:var(--surface-2);}
  .t-strong{color:var(--text);font-weight:600;}
  .t-num{font-family:var(--font-mono);text-align:right;font-variant-numeric:tabular-nums;}
  th.t-num{text-align:right;}

  .badge{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:100px;white-space:nowrap;}
  .badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
  .b-paid{color:var(--success);background:var(--success-bg);}
  .b-open{color:var(--info);background:var(--info-bg);}
  .b-late{color:var(--brand-darker);background:var(--brand-tint-2);}
  .b-draft{color:var(--text-3);background:var(--surface-2);}

  /* ---- TOOLBAR ---- */
  .toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
  .search{display:flex;align-items:center;gap:8px;background:var(--surface);border:1px solid var(--border-strong);border-radius:10px;padding:8px 12px;flex:1;min-width:200px;max-width:340px;}
  .search input{border:none;outline:none;background:none;font-family:inherit;font-size:13.5px;width:100%;color:var(--text);}
  .search svg{width:16px;height:16px;stroke:var(--text-4);}
  .chips{display:flex;gap:7px;flex-wrap:wrap;}
  .chip{font-size:12.5px;font-weight:600;padding:7px 13px;border-radius:100px;border:1px solid var(--border-strong);background:var(--surface);color:var(--text-2);}
  .chip.on{background:var(--brand-tint);border-color:var(--brand-border);color:var(--brand);}

  /* ---- INVOICE DETAIL ---- */
  .back{display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:600;color:var(--text-3);margin-bottom:16px;}
  .back:hover{color:var(--brand);}
  .doc{background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-md);overflow:hidden;max-width:740px;}
  .doc-top{display:flex;justify-content:space-between;gap:24px;padding:30px 34px;border-bottom:1px solid var(--border);flex-wrap:wrap;}
  .doc-from .nm{font-family:var(--font-display);font-weight:700;font-size:19px;}
  .doc-from .ln,.doc-to .ln{font-size:13px;color:var(--text-3);}
  .doc-to{text-align:right;}
  .doc-to .lab{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-4);font-weight:700;margin-bottom:5px;}
  .doc-to .nm{font-weight:600;color:var(--text);}
  .doc-meta{display:flex;gap:32px;padding:18px 34px;background:var(--surface-2);flex-wrap:wrap;}
  .doc-meta div .k{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-4);font-weight:700;}
  .doc-meta div .v{font-family:var(--font-mono);font-size:14px;font-weight:500;margin-top:2px;}
  .doc-lines{padding:6px 34px 0;}
  .doc-totals{padding:14px 34px 30px;display:flex;justify-content:flex-end;}
  .totbox{width:280px;}
  .totrow{display:flex;justify-content:space-between;padding:6px 0;font-size:13.5px;color:var(--text-2);}
  .totrow.grand{border-top:2px solid var(--text);margin-top:6px;padding-top:12px;font-weight:700;color:var(--text);font-size:16px;}
  .totrow .mono{font-weight:600;}
  .doc-actions{display:flex;gap:9px;flex-wrap:wrap;margin:18px 0;}

  /* ---- KANBAN (incasso) ---- */
  .kan{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
  .kan-col{background:var(--surface-2);border:1px solid var(--border);border-radius:14px;padding:12px;}
  .kan-col h4{font-size:13px;display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;color:var(--text-2);}
  .kan-col h4 span{font-size:11px;font-weight:700;background:var(--surface-3);color:var(--text-3);border-radius:100px;padding:1px 8px;}
  .kan-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:11px 13px;margin-bottom:9px;box-shadow:var(--shadow-sm);}
  .kan-card .c1{font-weight:600;font-size:13px;}
  .kan-card .c2{font-size:12px;color:var(--text-3);display:flex;justify-content:space-between;margin-top:4px;}

  /* ---- SETTINGS ---- */
  .set-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--border);}
  .set-row:last-child{border-bottom:none;}
  .set-k{font-weight:600;font-size:13.5px;}
  .set-k small{display:block;font-weight:400;color:var(--text-3);font-size:12.5px;margin-top:2px;}
  .set-v{font-family:var(--font-mono);font-size:13.5px;color:var(--text-2);}
  .swatch{display:inline-flex;align-items:center;gap:9px;}
  .swatch i{width:22px;height:22px;border-radius:6px;display:inline-block;border:1px solid var(--border-strong);}

  /* ---- BANNER + TOAST ---- */
  .demo-banner{display:flex;align-items:center;gap:12px;background:linear-gradient(100deg,var(--brand-tint),var(--surface));border:1px solid var(--brand-border);border-radius:14px;padding:13px 16px;margin-bottom:22px;font-size:13.5px;color:var(--brand-darker);}
  .demo-banner b{color:var(--brand-darker);}
  .demo-banner .x{margin-left:auto;background:none;border:none;color:var(--brand);font-size:18px;line-height:1;padding:2px 6px;border-radius:6px;}
  .demo-banner .x:hover{background:var(--brand-tint-2);}
  .toast{position:fixed;bottom:22px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--text);color:#fff;font-size:13.5px;font-weight:500;padding:11px 18px;border-radius:100px;box-shadow:var(--shadow-lg);opacity:0;pointer-events:none;transition:all .25s;z-index:60;}
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

  .hide{display:none!important;}

  /* ---- RESPONSIVE ---- */
  @media (max-width:880px){
    .shell{grid-template-columns:1fr;}
    .sidebar{position:static;height:auto;display:flex;gap:6px;overflow-x:auto;border-right:none;border-bottom:1px solid var(--border);padding:10px;}
    .side-group{margin:0;display:flex;gap:6px;}
    .side-label{display:none;}
    .side-item{white-space:nowrap;padding:8px 12px;}
    .side-badge{display:none;}
    .kpi-grid{grid-template-columns:1fr 1fr;}
    .grid-2{grid-template-columns:1fr;}
    .kan{grid-template-columns:1fr;}
    .main{padding:20px 16px 50px;}
    .tb-brand{font-size:15px;}
    .tb-right .lbl{display:none;}
    .tb-right .btn-ghost{display:none;}
    .topbar{padding:0 12px;}
    .panel{overflow-x:auto;}
    .doc-lines{overflow-x:auto;}
    .tbl{min-width:520px;}
    .sidebar, .main{min-width:0;}
  }
  @media (max-width:520px){.demo-pill{display:none;}}
  @media (max-width:480px){.kpi-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="topbar">
  <div class="tb-left">
    <a href="/" class="tb-brand"><img src="/images/easyinvoice-favicon-180.png" alt="">EasyInvoice<sup>&reg;</sup></a>
    <span class="demo-pill">Demo</span>
  </div>
  <div class="tb-right">
    <a href="/" class="btn btn-ghost">&larr;&nbsp;<span class="lbl">Terug naar site</span></a>
    <a href="/register" class="btn btn-primary">Start <span class="lbl">14 dagen </span>gratis</a>
  </div>
</div>

<div class="shell">
  <aside class="sidebar">
    <div class="side-group">
      <div class="side-label">Overzicht</div>
      <button class="side-item active" data-view="dashboard">{ico_dash}Dashboard</button>
    </div>
    <div class="side-group">
      <div class="side-label">Verkoop</div>
      <button class="side-item" data-view="invoices">{ico_inv}Facturen</button>
      <button class="side-item" data-view="customers">{ico_cust}Klanten</button>
      <button class="side-item" data-view="products">{ico_prod}Producten</button>
      <button class="side-item" data-view="incasso">{ico_inc}Incasso<span class="side-badge">2</span></button>
    </div>
    <div class="side-group">
      <div class="side-label">Rapporten</div>
      <button class="side-item" data-view="stats">{ico_stat}Statistieken</button>
    </div>
    <div class="side-group">
      <div class="side-label">Instellingen</div>
      <button class="side-item" data-view="settings">{ico_set}Bedrijf</button>
    </div>
  </aside>

  <main class="main" id="main"></main>
</div>

<div class="toast" id="toast"></div>

<script>
/* ---------------- DEMO DATA (fictief) ---------------- */
const COMPANY = { name:'Jansen Webdesign', kvk:'81234567', btw:'NL003456789B01', iban:'NL12 RABO 0123 4567 89', addr:'Lindelaan 12, 1211 AB Hilversum' };

const INVOICES = [
  {id:'i43', nr:'2025-0043', klant:'Tandartspraktijk Smile', datum:'06-06-2025', verval:'-', total:0,       status:'draft', lines:[{d:'Nog op te stellen',q:1,p:0}]},
  {id:'i42', nr:'2025-0042', klant:'De Vries Bouw B.V.',     datum:'04-06-2025', verval:'18-06-2025', total:2795.10, status:'open',
     lines:[{d:'Website ontwerp & realisatie',q:1,p:1650},{d:'Onderhoudscontract (3 mnd)',q:3,p:45},{d:'Content & SEO-opzet (uur)',q:7,p:75}]},
  {id:'i41', nr:'2025-0041', klant:'Bakkerij Het Stoepje',   datum:'28-05-2025', verval:'11-06-2025', total:847.00,  status:'paid'},
  {id:'i40', nr:'2025-0040', klant:'Coöperatie Groen',       datum:'21-05-2025', verval:'04-06-2025', total:1210.00, status:'late'},
  {id:'i39', nr:'2025-0039', klant:'Studio Lumen',           datum:'15-05-2025', verval:'29-05-2025', total:3025.00, status:'paid'},
  {id:'i38', nr:'2025-0038', klant:'Janssen Advies',         datum:'09-05-2025', verval:'23-05-2025', total:605.00,  status:'paid'},
  {id:'i37', nr:'2025-0037', klant:'De Vries Bouw B.V.',     datum:'02-05-2025', verval:'16-05-2025', total:1815.00, status:'paid'},
  {id:'i36', nr:'2025-0036', klant:'Coöperatie Groen',       datum:'24-04-2025', verval:'08-05-2025', total:968.00,  status:'paid'},
  {id:'i35', nr:'2025-0035', klant:'Studio Lumen',           datum:'18-04-2025', verval:'02-05-2025', total:2299.00, status:'paid'},
  {id:'i34', nr:'2025-0034', klant:'Bakkerij Het Stoepje',   datum:'11-04-2025', verval:'25-04-2025', total:423.50,  status:'late'},
];

const CUSTOMERS = [
  {nr:'KL10004', naam:'De Vries Bouw B.V.',  contact:'M. de Vries',  email:'info@devriesbouw.nl',   open:2795.10, fac:5},
  {nr:'KL10003', naam:'Studio Lumen',        contact:'S. Bakker',    email:'hallo@studiolumen.nl',  open:0,       fac:4},
  {nr:'KL10005', naam:'Coöperatie Groen',    contact:'T. Hofman',    email:'admin@coopgroen.nl',    open:1210.00, fac:3},
  {nr:'KL10002', naam:'Bakkerij Het Stoepje',contact:'J. Smit',      email:'jan@stoepje.nl',        open:423.50,  fac:6},
  {nr:'KL10006', naam:'Janssen Advies',      contact:'P. Janssen',   email:'p.janssen@jadvies.nl',  open:0,       fac:2},
  {nr:'KL10007', naam:'Tandartspraktijk Smile',contact:'Dr. Visser', email:'balie@tpsmile.nl',     open:0,       fac:1},
];

const PRODUCTS = [
  {nr:'P001', naam:'Website ontwerp & realisatie', prijs:1650.00, btw:21, eenheid:'project'},
  {nr:'P002', naam:'Onderhoudscontract',           prijs:45.00,   btw:21, eenheid:'per maand'},
  {nr:'P003', naam:'Uurtarief development',         prijs:75.00,   btw:21, eenheid:'per uur'},
  {nr:'P004', naam:'Hosting (managed)',            prijs:12.50,   btw:21, eenheid:'per maand'},
  {nr:'P005', naam:'Logo & huisstijl',             prijs:480.00,  btw:21, eenheid:'project'},
  {nr:'P006', naam:'SEO-pakket Start',             prijs:225.00,  btw:21, eenheid:'per maand'},
];

const REVENUE = [
  {m:'Jan',v:4120},{m:'Feb',v:3680},{m:'Mrt',v:5240},{m:'Apr',v:4505},
  {m:'Mei',v:6534},{m:'Jun',v:2541}
];

/* ---------------- HELPERS ---------------- */
const eur = n => new Intl.NumberFormat('nl-NL',{style:'currency',currency:'EUR'}).format(n);
const r2  = n => Math.round(n*100)/100;
const BADGE = {paid:['b-paid','Betaald'],open:['b-open','Openstaand'],late:['b-late','Verlopen'],draft:['b-draft','Concept']};
const badge = s => `<span class="badge ${BADGE[s][0]}">${BADGE[s][1]}</span>`;

function linesFor(inv){
  if(inv.lines) return inv.lines.map(l=>({d:l.d,q:l.q,p:l.p,sum:r2(l.q*l.p)}));
  const sub = r2(inv.total/1.21);
  return [{d:'Dienstverlening volgens opdracht',q:1,p:sub,sum:sub}];
}
function totals(inv){
  const ls=linesFor(inv); const sub=r2(ls.reduce((a,l)=>a+l.sum,0));
  const btw=r2(sub*0.21); return {ls,sub,btw,total:r2(sub+btw)};
}

function toast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('show'),1900);}

/* ---------------- VIEWS ---------------- */
const banner = `<div class="demo-banner">{ico_info}<div>Je bekijkt een <b>interactieve demo</b> met voorbeeldgegevens — niets wordt opgeslagen. Klik gerust rond.</div><button class="x" onclick="this.parentElement.remove()">&times;</button></div>`;

function vDashboard(){
  const max=Math.max(...REVENUE.map(r=>r.v));
  const bars=REVENUE.map((r,i)=>`<div class="bar-col"><div class="bar ${i===REVENUE.length-1?'hot':''}" style="height:${Math.round(r.v/max*100)}%" title="${eur(r.v)}"></div><div class="bar-lbl">${r.m}</div></div>`).join('');
  const open=INVOICES.filter(i=>i.status==='open'||i.status==='late').reduce((a,i)=>a+i.total,0);
  const late=INVOICES.filter(i=>i.status==='late').reduce((a,i)=>a+i.total,0);
  const recent=INVOICES.slice(0,5).map(i=>`<tr class="click" onclick="openInvoice('${i.id}')"><td class="t-strong mono">${i.nr}</td><td>${i.klant}</td><td>${i.datum}</td><td class="t-num">${eur(i.total)}</td><td>${badge(i.status)}</td></tr>`).join('');
  return `${banner}
  <div class="page-head"><div><h1>Welkom terug, Jan 👋</h1><div class="page-sub">Hier is je overzicht voor juni 2025.</div></div><a class="btn btn-primary" onclick="toast('In de echte omgeving open je hier de factuur-editor')">+ Nieuwe factuur</a></div>
  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-label"><span class="dot" style="background:var(--info)"></span>Openstaand</div><div class="kpi-value">${eur(open)}</div><div class="kpi-meta" style="color:var(--info)">3 facturen</div></div>
    <div class="kpi"><div class="kpi-label"><span class="dot" style="background:var(--brand)"></span>Verlopen</div><div class="kpi-value">${eur(late)}</div><div class="kpi-meta" style="color:var(--brand)">2 facturen</div></div>
    <div class="kpi"><div class="kpi-label"><span class="dot" style="background:var(--success)"></span>Omzet deze maand</div><div class="kpi-value">${eur(REVENUE[REVENUE.length-1].v)}</div><div class="kpi-meta" style="color:var(--success)">+12% t.o.v. mei</div></div>
    <div class="kpi"><div class="kpi-label"><span class="dot" style="background:var(--text-3)"></span>Omzet 2025</div><div class="kpi-value">${eur(REVENUE.reduce((a,r)=>a+r.v,0))}</div><div class="kpi-meta" style="color:var(--text-3)">6 maanden</div></div>
  </div>
  <div class="grid-2">
    <div class="panel"><div class="panel-head"><h3>Omzet per maand</h3><span class="page-sub">excl. btw</span></div><div class="panel-pad"><div class="chart">${bars}</div></div></div>
    <div class="panel"><div class="panel-head"><h3>Sneltoegang</h3></div><div class="panel-pad" style="display:flex;flex-direction:column;gap:9px">
      <a class="btn btn-soft" onclick="nav('invoices')">{ico_inv2} Alle facturen</a>
      <a class="btn btn-soft" onclick="nav('customers')">{ico_cust2} Klanten beheren</a>
      <a class="btn btn-soft" onclick="nav('incasso')">{ico_inc2} Incasso opvolgen</a>
      <a class="btn btn-soft" onclick="nav('stats')">{ico_stat2} Bekijk statistieken</a>
    </div></div>
  </div>
  <div class="panel"><div class="panel-head"><h3>Recente facturen</h3><a class="page-sub" style="cursor:pointer" onclick="nav('invoices')">Alles bekijken &rarr;</a></div>
    <table class="tbl"><thead><tr><th>Nummer</th><th>Klant</th><th>Datum</th><th class="t-num">Bedrag</th><th>Status</th></tr></thead><tbody>${recent}</tbody></table>
  </div>`;
}

let invFilter='all';
function vInvoices(){
  const rows=INVOICES.filter(i=>invFilter==='all'||i.status===invFilter).map(i=>`<tr class="click" onclick="openInvoice('${i.id}')"><td class="t-strong mono">${i.nr}</td><td>${i.klant}</td><td>${i.datum}</td><td>${i.verval}</td><td class="t-num">${eur(i.total)}</td><td>${badge(i.status)}</td></tr>`).join('');
  const chip=(k,l)=>`<button class="chip ${invFilter===k?'on':''}" onclick="invFilter='${k}';render('invoices')">${l}</button>`;
  return `<div class="page-head"><div><h1>Facturen</h1><div class="page-sub">${INVOICES.length} facturen</div></div><a class="btn btn-primary" onclick="toast('In de echte omgeving open je hier de factuur-editor')">+ Nieuwe factuur</a></div>
  <div class="toolbar"><div class="search">{ico_search}<input placeholder="Zoek op klant of nummer…" oninput="toast('Zoeken is actief in de echte omgeving')"></div>
    <div class="chips">${chip('all','Alle')}${chip('open','Openstaand')}${chip('late','Verlopen')}${chip('paid','Betaald')}${chip('draft','Concept')}</div></div>
  <div class="panel"><table class="tbl"><thead><tr><th>Nummer</th><th>Klant</th><th>Datum</th><th>Vervaldatum</th><th class="t-num">Bedrag</th><th>Status</th></tr></thead><tbody>${rows||'<tr><td colspan="6" style="text-align:center;color:var(--text-4);padding:30px">Geen facturen in deze filter</td></tr>'}</tbody></table></div>`;
}

function vInvoiceDetail(id){
  const inv=INVOICES.find(i=>i.id===id); const t=totals(inv);
  const lines=t.ls.map(l=>`<tr><td class="t-strong">${l.d}</td><td class="t-num">${l.q}</td><td class="t-num">${eur(l.p)}</td><td class="t-num">${eur(l.sum)}</td></tr>`).join('');
  return `<a class="back" onclick="nav('invoices')">{ico_back} Terug naar facturen</a>
  <div class="page-head"><div><h1>Factuur ${inv.nr}</h1><div class="page-sub">${inv.klant} · ${inv.datum}</div></div><div>${badge(inv.status)}</div></div>
  <div class="doc-actions">
    <a class="btn btn-primary btn-sm" onclick="toast('Verstuurd via e-mail (demo)')">{ico_send} Versturen</a>
    <a class="btn btn-soft btn-sm" onclick="toast('PDF gedownload (demo)')">{ico_pdf} Download PDF</a>
    ${inv.status!=='paid'&&inv.status!=='draft'?'<a class="btn btn-soft btn-sm" onclick="toast(\'Betaling geregistreerd (demo)\')">{ico_check} Betaling registreren</a>':''}
  </div>
  <div class="doc">
    <div class="doc-top">
      <div class="doc-from"><div class="nm">${COMPANY.name}</div><div class="ln">${COMPANY.addr}</div><div class="ln">KvK ${COMPANY.kvk} · ${COMPANY.btw}</div><div class="ln">${COMPANY.iban}</div></div>
      <div class="doc-to"><div class="lab">Factuur aan</div><div class="nm">${inv.klant}</div><div class="ln">T.a.v. de administratie</div></div>
    </div>
    <div class="doc-meta">
      <div><div class="k">Factuurnr.</div><div class="v">${inv.nr}</div></div>
      <div><div class="k">Datum</div><div class="v">${inv.datum}</div></div>
      <div><div class="k">Vervaldatum</div><div class="v">${inv.verval}</div></div>
    </div>
    <div class="doc-lines"><table class="tbl"><thead><tr><th>Omschrijving</th><th class="t-num">Aantal</th><th class="t-num">Stuksprijs</th><th class="t-num">Totaal</th></tr></thead><tbody>${lines}</tbody></table></div>
    <div class="doc-totals"><div class="totbox">
      <div class="totrow"><span>Subtotaal (excl. btw)</span><span class="mono">${eur(t.sub)}</span></div>
      <div class="totrow"><span>Btw 21%</span><span class="mono">${eur(t.btw)}</span></div>
      <div class="totrow grand"><span>Totaal</span><span class="mono">${eur(t.total)}</span></div>
    </div></div>
  </div>`;
}

function vCustomers(){
  const rows=CUSTOMERS.map(c=>`<tr><td class="t-strong mono">${c.nr}</td><td class="t-strong">${c.naam}</td><td>${c.contact}</td><td style="color:var(--text-3)">${c.email}</td><td class="t-num">${c.fac}</td><td class="t-num">${c.open>0?'<span style="color:var(--brand);font-weight:600">'+eur(c.open)+'</span>':'<span style="color:var(--text-4)">—</span>'}</td></tr>`).join('');
  return `<div class="page-head"><div><h1>Klanten</h1><div class="page-sub">${CUSTOMERS.length} klanten</div></div><a class="btn btn-primary" onclick="toast('Nieuwe klant toevoegen (demo)')">+ Nieuwe klant</a></div>
  <div class="panel"><table class="tbl"><thead><tr><th>Nummer</th><th>Naam</th><th>Contact</th><th>E-mail</th><th class="t-num">Facturen</th><th class="t-num">Openstaand</th></tr></thead><tbody>${rows}</tbody></table></div>`;
}

function vProducts(){
  const rows=PRODUCTS.map(p=>`<tr><td class="t-strong mono">${p.nr}</td><td class="t-strong">${p.naam}</td><td style="color:var(--text-3)">${p.eenheid}</td><td class="t-num">${p.btw}%</td><td class="t-num">${eur(p.prijs)}</td></tr>`).join('');
  return `<div class="page-head"><div><h1>Producten & diensten</h1><div class="page-sub">${PRODUCTS.length} items</div></div><a class="btn btn-primary" onclick="toast('Nieuw product toevoegen (demo)')">+ Nieuw product</a></div>
  <div class="panel"><table class="tbl"><thead><tr><th>Code</th><th>Omschrijving</th><th>Eenheid</th><th class="t-num">Btw</th><th class="t-num">Prijs excl.</th></tr></thead><tbody>${rows}</tbody></table></div>`;
}

function vIncasso(){
  const od=INVOICES.filter(i=>i.status==='late');
  const card=(i,fase)=>`<div class="kan-card"><div class="c1">${i.klant}</div><div class="c2"><span class="mono">${i.nr}</span><span class="mono" style="color:var(--brand);font-weight:600">${eur(i.total)}</span></div></div>`;
  return `<div class="page-head"><div><h1>Incasso</h1><div class="page-sub">Volg verlopen facturen automatisch op via herinnering, aanmaning en incasso.</div></div></div>
  <div class="kan">
    <div class="kan-col"><h4>Herinnering <span>${od.length}</span></h4>${od.map(i=>card(i)).join('')}</div>
    <div class="kan-col"><h4>Aanmaning <span>0</span></h4><div style="color:var(--text-4);font-size:12.5px;padding:10px 4px">Sleep een factuur hierheen om een aanmaning te sturen.</div></div>
    <div class="kan-col"><h4>Incassobureau <span>0</span></h4><div style="color:var(--text-4);font-size:12.5px;padding:10px 4px">Nog niets overgedragen.</div></div>
  </div>`;
}

function vStats(){
  const max=Math.max(...REVENUE.map(r=>r.v));
  const bars=REVENUE.map(r=>`<div class="bar-col"><div class="bar hot" style="height:${Math.round(r.v/max*100)}%" title="${eur(r.v)}"></div><div class="bar-lbl">${r.m}</div></div>`).join('');
  const top=[...CUSTOMERS].map(c=>({n:c.naam,v:INVOICES.filter(i=>i.klant===c.naam&&i.status==='paid').reduce((a,i)=>a+i.total,0)})).filter(x=>x.v>0).sort((a,b)=>b.v-a.v).slice(0,5);
  const tmax=Math.max(...top.map(t=>t.v));
  const toprows=top.map(t=>`<div style="margin-bottom:12px"><div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px"><span class="t-strong">${t.n}</span><span class="mono" style="color:var(--text-2)">${eur(t.v)}</span></div><div style="height:8px;background:var(--surface-2);border-radius:100px;overflow:hidden"><div style="height:100%;width:${Math.round(t.v/tmax*100)}%;background:var(--brand);border-radius:100px"></div></div></div>`).join('');
  return `<div class="page-head"><div><h1>Statistieken</h1><div class="page-sub">Inzicht in je omzet en klanten.</div></div></div>
  <div class="grid-2">
    <div class="panel"><div class="panel-head"><h3>Omzet 2025</h3><span class="page-sub">excl. btw</span></div><div class="panel-pad"><div class="chart">${bars}</div></div></div>
    <div class="panel"><div class="panel-head"><h3>Top klanten (betaald)</h3></div><div class="panel-pad">${toprows}</div></div>
  </div>`;
}

function vSettings(){
  return `<div class="page-head"><div><h1>Bedrijfsinstellingen</h1><div class="page-sub">Deze gegevens verschijnen op je facturen.</div></div></div>
  <div class="panel"><div class="panel-pad">
    <div class="set-row"><div class="set-k">Bedrijfsnaam<small>Zoals op je facturen</small></div><div class="set-v">${COMPANY.name}</div></div>
    <div class="set-row"><div class="set-k">KvK-nummer</div><div class="set-v">${COMPANY.kvk}</div></div>
    <div class="set-row"><div class="set-k">Btw-nummer</div><div class="set-v">${COMPANY.btw}</div></div>
    <div class="set-row"><div class="set-k">IBAN</div><div class="set-v">${COMPANY.iban}</div></div>
    <div class="set-row"><div class="set-k">Adres</div><div class="set-v">${COMPANY.addr}</div></div>
    <div class="set-row"><div class="set-k">Huisstijlkleur<small>Accent op facturen & portaal</small></div><div class="set-v swatch"><i style="background:#E8231F"></i> #E8231F</div></div>
    <div class="set-row"><div class="set-k">Factuurnummering</div><div class="set-v">{year}-{0000}</div></div>
    <div class="set-row"><div class="set-k">Betaaltermijn</div><div class="set-v">14 dagen</div></div>
  </div></div>
  <div style="margin-top:16px"><a class="btn btn-primary" onclick="toast('Wijzigen kan in je eigen omgeving')">Instellingen wijzigen</a></div>`;
}

/* ---------------- ROUTER ---------------- */
const VIEWS={dashboard:vDashboard,invoices:vInvoices,customers:vCustomers,products:vProducts,incasso:vIncasso,stats:vStats,settings:vSettings};
let current='dashboard';

function setActive(v){document.querySelectorAll('.side-item').forEach(b=>b.classList.toggle('active',b.dataset.view===v));}
function render(v){current=v;setActive(v);document.getElementById('main').innerHTML=icons(VIEWS[v]());window.scrollTo({top:0,behavior:'smooth'});}
function nav(v){render(v);}
function openInvoice(id){setActive('invoices');document.getElementById('main').innerHTML=icons(vInvoiceDetail(id));window.scrollTo({top:0,behavior:'smooth'});}

document.querySelectorAll('.side-item').forEach(b=>b.addEventListener('click',()=>render(b.dataset.view)));

/* ---------------- ICONS (inline, vervangt {ico_*}) ---------------- */
const ICONS={
  ico_dash:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
  ico_inv:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>',
  ico_cust:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>',
  ico_prod:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
  ico_inc:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  ico_stat:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
  ico_set:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
  ico_search:'<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
  ico_back:'<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
  ico_send:'<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
  ico_pdf:'<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
  ico_check:'<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>',
  ico_info:'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
};
ICONS.ico_inv2=ICONS.ico_inv;ICONS.ico_cust2=ICONS.ico_cust;ICONS.ico_inc2=ICONS.ico_inc;ICONS.ico_stat2=ICONS.ico_stat;
function icons(html){return html.replace(/\{(ico_[a-z0-9]+)\}/g,(m,k)=>{let s=ICONS[k]||'';const open=(s.match(/<svg[^>]*>/)||[''])[0];if(!/\swidth=/.test(open))s=s.replace('<svg','<svg width="17" height="17"');if(!/\sstroke=/.test(open))s=s.replace('<svg','<svg stroke="currentColor"');return s;});}
/* sidebar icons */
document.querySelectorAll('.side-item').forEach(b=>{b.innerHTML=icons(b.innerHTML);});

render('dashboard');
</script>
</body>
</html>
@endverbatim
