<script setup>
/**
 * Rozliczenie VAT (Polen): de kwoty voor JPK_V7M / JPK_V7K per okres.
 * Tegenhanger van Btw/Index.vue (Nederlandse rubrieken); de data komt uit
 * App\Services\VatPlService. Teksten staan direct in het Pools.
 */
import { computed, ref } from 'vue';
import { router, Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur } from '@/format.js';

const brand = usePage().props.brand;

const props = defineProps({
  summary: Object,
  year: Number,
  allYears: Array,
  periodType: String,
  period: Number,
  periods: Array,
  settings: Object,
  eus_url: String,
  microaccount_url: String,
});

/* ---------- Navigatie: jaar, okres-type, okres ---------- */
const go = (over) => {
  const query = { year: props.year, type: props.periodType, period: props.period, ...over };
  Object.keys(query).forEach((k) => { if (query[k] === null || query[k] === undefined) delete query[k]; });
  router.get(route('vat.index'), query, { preserveState: false, preserveScroll: true });
};
const setYear = (y) => go({ year: y, period: null });
const setType = (t) => go({ type: t, period: null });
const setPeriod = (p) => go({ period: Number(p) });

const saveDefault = () => router.patch(route('vat.settings'), {
  vat_period: props.periodType,
  vat_reminder_enabled: !!props.settings.vat_reminder_enabled,
}, { preserveScroll: true, preserveState: false });

/* ---------- Bedragen ---------- */
// eur() is marktbewust: in de Poolse markt "1 234,50 zł". 'whole' = hele złoty zoals in de deklaracja.
const amount = (n) => (n < 0 ? '− ' : '') + eur(Math.abs(n || 0));
const whole = (n) => (n < 0 ? '− ' : '') + new Intl.NumberFormat('pl-PL', { maximumFractionDigits: 0 }).format(Math.abs(Math.round(n || 0))) + ' zł';
const showGrosze = ref(false);
const fmt = (row, key) => (showGrosze.value ? amount(row[key]) : whole(row[key + '_rounded']));
const fmtV = (exact, rounded) => (showGrosze.value ? amount(exact) : whole(rounded));
const dni = (n) => (n === 1 ? '1 dzień' : `${n} dni`);

/* ---------- Tabellen ---------- */
// Velden van het deklaracja-deel van JPK_V7 (2): netto / VAT per stawka.
const fields = { 23: ['P_19', 'P_20'], 8: ['P_17', 'P_18'], 5: ['P_15', 'P_16'], 0: ['P_13', null] };
const salesRows = computed(() => Object.values(props.summary.sales).filter((r) => r.rate !== null || !r.empty));
const salesGross = computed(() => Math.round((props.summary.sales_net + props.summary.output_vat) * 100) / 100);
const salesGrossRounded = computed(() => props.summary.sales_net_rounded + props.summary.output_vat_rounded);

const balanceLabel = computed(() => ({
  to_pay: 'Do zapłaty',
  refund: 'Do zwrotu / do przeniesienia',
  zero: 'Nic do zapłaty',
}[props.summary.balance_kind]));

const chip = computed(() => {
  const s = props.summary;
  if (s.filing.paid) return { label: 'Zapłacono', cls: 'paid' };
  if (s.filing.filed) return { label: 'Złożono', cls: 'filed' };
  if (s.declaration_due) return { label: s.days_left <= 7 ? `Zostało ${dni(s.days_left)}` : 'Do złożenia', cls: 'due' };
  if (s.status === 'current') return { label: 'Bieżący okres', cls: 'current' };
  if (s.status === 'future') return { label: 'Jeszcze się nie zaczął', cls: 'future' };
  if (s.overdue) return { label: 'Termin minął', cls: 'due' };
  return { label: 'Nieoznaczony', cls: 'unmarked' };
});

const periodOption = (p) => p.label + (p.paid ? ' · zapłacono' : p.filed ? ' · złożono' : p.status === 'current' ? ' · bieżący' : '');

/* ---------- Status (deelt VatFiling met de NL-aangifte) ---------- */
const filingRoute = () => route('vat.filing.update', { year: props.year, type: props.periodType, period: props.period });
const toggle = (field, value) => router.patch(filingRoute(), { [field]: value }, { preserveScroll: true, preserveState: true });

const showSales = ref(false);
const showPurchases = ref(false);

/* ---------- Eksport CSV (client-side) ---------- */
const exportCsv = () => {
  const s = props.summary;
  const num = (n) => (Number(n) || 0).toFixed(2).replace('.', ',');
  const rows = [
    ['Rozliczenie VAT', s.period_label],
    ['Formularz', s.form],
    ['Termin', s.due_date_label],
    ['Symbol okresu (przelew)', s.payment_symbol],
    [],
    ['Sprzedaż (VAT należny)', 'Netto', 'VAT', 'Brutto', 'Netto (pełne zł)', 'VAT (pełne zł)', 'Pole netto', 'Pole VAT'],
    ...salesRows.value.map((r) => [r.label, num(r.net), num(r.vat), num(r.gross), r.net_rounded, r.vat_rounded, fields[r.rate]?.[0] || '', fields[r.rate]?.[1] || '']),
    ['Razem sprzedaż', num(s.sales_net), num(s.output_vat), num(salesGross.value), s.sales_net_rounded, s.output_vat_rounded, 'P_37', 'P_38'],
    [],
    ['Zakupy (VAT naliczony)', 'Netto', 'VAT', 'Brutto', 'Netto (pełne zł)', 'VAT (pełne zł)', 'Pole netto', 'Pole VAT'],
    [`${s.purchase_count} faktur zakupu`, num(s.purchases.net), num(s.purchases.vat), num(s.purchases.gross), s.purchases.net_rounded, s.purchases.vat_rounded, 'P_42', 'P_43'],
    [],
    ['Rozliczenie', 'Kwota', '', '', 'Pełne zł', '', 'Pole'],
    ['VAT należny', num(s.output_vat), '', '', s.output_vat_rounded, '', 'P_38'],
    ['VAT naliczony', num(s.input_vat), '', '', s.input_vat_rounded, '', 'P_48'],
    [balanceLabel.value, num(Math.abs(s.balance)), '', '', Math.abs(s.balance_rounded), '', s.balance_kind === 'refund' ? 'P_53 / P_62' : 'P_51'],
    [],
    ['Faktury sprzedaży', 'Klient', 'Kraj', 'Data', 'Netto', 'VAT', 'Brutto'],
    ...s.documents.sales.map((i) => [(i.is_credit ? 'korekta ' : '') + i.number, i.customer_name, i.country, i.date_label, num(i.net), num(i.vat), num(i.gross)]),
    [],
    ['Faktury zakupu', 'Numer', '', 'Data', 'Netto', 'VAT', 'Brutto'],
    ...s.documents.purchases.map((p) => [p.supplier_name, p.reference || '', '', p.date_label, num(p.net), num(p.vat), num(p.gross)]),
  ];
  const esc = (v) => { const t = String(v ?? ''); return /[;"\n]/.test(t) ? '"' + t.replace(/"/g, '""') + '"' : t; };
  // BOM + średnik: zo opent Excel (PL) het bestand direct met de juiste kolommen en komma's.
  const text = String.fromCharCode(0xfeff) + rows.map((r) => r.map(esc).join(';')).join('\r\n');
  const blob = new Blob([text], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `vat-${s.form}-${s.year}-${String(s.period).padStart(2, '0')}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
};
</script>

<template>
  <Head title="Rozliczenie VAT" />
  <AppLayout>
    <template #breadcrumb>Raporty / <span class="breadcrumb-current">Rozliczenie VAT</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Rozliczenie VAT</h1>
        <p class="page-subtitle">Kwoty do {{ summary.form }} za {{ summary.period_label }} — VAT należny ze sprzedaży, naliczony z zakupów, saldo i termin</p>
      </div>
      <div class="btw-header-actions">
        <div class="year-tabs">
          <div v-for="y in allYears" :key="y" class="tab" :class="{ active: year === y }" @click="setYear(y)">{{ y }}</div>
        </div>
        <button class="btn btn-secondary btn-sm" title="Pobierz kwoty i faktury tego okresu jako CSV" @click="exportCsv">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Eksportuj CSV
        </button>
      </div>
    </div>

    <!-- Okres: miesiąc / kwartał + keuzelijst -->
    <div class="period-bar">
      <div class="seg">
        <button type="button" :class="{ active: periodType === 'month' }" @click="setType('month')">Miesięcznie · JPK_V7M</button>
        <button type="button" :class="{ active: periodType === 'quarter' }" @click="setType('quarter')">Kwartalnie · JPK_V7K</button>
      </div>
      <select class="period-select" :value="period" @change="setPeriod($event.target.value)">
        <option v-for="p in periods" :key="p.period" :value="p.period">{{ periodOption(p) }}</option>
      </select>
      <span class="btw-chip" :class="chip.cls">{{ chip.label }}</span>
      <button v-if="periodType !== settings.vat_period" type="button" class="link" @click="saveDefault">Ustaw jako mój domyślny okres rozliczeniowy</button>
    </div>

    <!-- Termijn loopt / verstreken -->
    <div v-if="summary.declaration_due" class="btw-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div class="btw-alert-text">
        <strong>Rozliczenie za {{ summary.period_label }} czeka</strong> — zostało {{ dni(summary.days_left) }}.
        <template v-if="summary.balance_kind === 'to_pay'">Do zapłaty <strong>{{ whole(summary.balance_rounded) }}</strong>; deklaracja i przelew do <strong>{{ summary.due_date_long }}</strong>.</template>
        <template v-else-if="summary.balance_kind === 'refund'">Masz nadwyżkę <strong>{{ whole(-summary.balance_rounded) }}</strong> do zwrotu lub przeniesienia — złóż {{ summary.form }} do <strong>{{ summary.due_date_long }}</strong>.</template>
        <template v-else>Deklaracja zerowa — {{ summary.form }} składasz także bez sprzedaży, do <strong>{{ summary.due_date_long }}</strong>.</template>
      </div>
    </div>
    <div v-else-if="summary.overdue" class="btw-alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <div class="btw-alert-text">
        <strong>Termin za {{ summary.period_label }} minął {{ summary.due_date_label }}.</strong>
        Jeśli deklaracja jest już złożona, oznacz to poniżej. Jeśli nie — złóż ją jak najszybciej; przy spóźnieniu warto dołączyć czynny żal.
      </div>
    </div>

    <!-- Kaarten -->
    <div class="kpi-grid">
      <div class="kpi">
        <div class="lbl">VAT należny · sprzedaż</div>
        <div class="val">{{ amount(summary.output_vat) }}</div>
        <div class="meta">{{ summary.invoice_count }} faktur<span v-if="summary.credit_count"> · {{ summary.credit_count }} korekt</span> · pole P_38</div>
      </div>
      <div class="kpi">
        <div class="lbl">VAT naliczony · zakupy</div>
        <div class="val">{{ amount(summary.input_vat) }}</div>
        <div class="meta">{{ summary.purchase_count }} faktur zakupu · pole P_48</div>
      </div>
      <div class="kpi tint">
        <div class="lbl">{{ balanceLabel }} · {{ summary.period_label }}</div>
        <div class="val brand">{{ whole(Math.abs(summary.balance_rounded)) }}</div>
        <div class="meta">{{ amount(summary.balance) }} · należny − naliczony</div>
      </div>
      <div class="kpi">
        <div class="lbl">Termin</div>
        <div class="val">do {{ summary.due_date_label }}</div>
        <div class="meta">{{ summary.form }} · symbol okresu {{ summary.payment_symbol }}</div>
      </div>
    </div>

    <div v-if="summary.invoice_count === 0 && summary.credit_count === 0 && summary.purchase_count === 0" class="btw-empty-note">
      Brak wystawionych faktur i zaksięgowanych zakupów w tym okresie — kwoty poniżej są zerowe.
      Pamiętaj, że {{ summary.form }} składasz także wtedy (deklaracja zerowa).
    </div>

    <!-- Sprzedaż / zakupy -->
    <div class="btw-grid cols-2">
      <div class="btw-card" :class="{ current: summary.status === 'current', due: summary.declaration_due, done: summary.filing.filed }">
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">Sprzedaż — VAT należny</div>
            <div class="btw-card-months">według stawek, na dzień wystawienia faktury</div>
          </div>
          <label class="cents-toggle"><input type="checkbox" v-model="showGrosze"> z groszami</label>
        </div>
        <table class="btw-table">
          <thead><tr><th>Stawka</th><th class="right">Netto</th><th class="right">VAT</th><th class="right">Brutto</th></tr></thead>
          <tbody>
            <tr v-for="r in salesRows" :key="r.label" :class="{ dim: r.empty }">
              <td>
                <span class="btw-rubriek">{{ r.label }}</span>
                <span v-if="fields[r.rate]" class="field-tag">{{ fields[r.rate][0] }}<template v-if="fields[r.rate][1]"> / {{ fields[r.rate][1] }}</template></span>
                <span v-else class="field-tag">poza JPK — sprawdź</span>
              </td>
              <td class="right num" :class="{ neg: r.net < 0 }">{{ fmt(r, 'net') }}</td>
              <td class="right num" :class="{ neg: r.vat < 0 }">{{ fmt(r, 'vat') }}</td>
              <td class="right num" :class="{ neg: r.gross < 0 }">{{ fmt(r, 'gross') }}</td>
            </tr>
            <tr class="btw-total-row">
              <td>Razem <span class="field-tag">P_37 / P_38</span></td>
              <td class="right num" :class="{ neg: summary.sales_net < 0 }">{{ fmtV(summary.sales_net, summary.sales_net_rounded) }}</td>
              <td class="right num" :class="{ neg: summary.output_vat < 0 }">{{ fmtV(summary.output_vat, summary.output_vat_rounded) }}</td>
              <td class="right num" :class="{ neg: salesGross < 0 }">{{ fmtV(salesGross, salesGrossRounded) }}</td>
            </tr>
          </tbody>
        </table>
        <div class="btw-card-foot">
          <span>{{ summary.invoice_count }} {{ summary.invoice_count === 1 ? 'faktura' : 'faktur' }}<span v-if="summary.credit_count"> · {{ summary.credit_count }} {{ summary.credit_count === 1 ? 'korekta' : 'korekt' }}</span></span>
          <span>{{ showGrosze ? 'kwoty dokładne' : 'pełne złote, jak w deklaracji' }}</span>
        </div>
      </div>

      <div class="btw-card">
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">Zakupy — VAT naliczony</div>
            <div class="btw-card-months">faktury zakupu zaksięgowane w {{ brand.name }}, na dzień wystawienia</div>
          </div>
        </div>
        <table class="btw-table">
          <thead><tr><th>Nabycie</th><th class="right">Netto</th><th class="right">VAT</th><th class="right">Brutto</th></tr></thead>
          <tbody>
            <tr :class="{ dim: summary.purchase_count === 0 }">
              <td>Pozostałe towary i usługi <span class="field-tag">P_42 / P_43</span></td>
              <td class="right num">{{ fmt(summary.purchases, 'net') }}</td>
              <td class="right num vat-in">{{ fmt(summary.purchases, 'vat') }}</td>
              <td class="right num">{{ fmt(summary.purchases, 'gross') }}</td>
            </tr>
            <tr class="btw-total-row">
              <td>Razem do odliczenia <span class="field-tag">P_48</span></td>
              <td></td>
              <td class="right num vat-in">{{ fmtV(summary.input_vat, summary.input_vat_rounded) }}</td>
              <td></td>
            </tr>
          </tbody>
        </table>
        <div class="btw-card-foot">
          <span>{{ summary.purchase_count }} {{ summary.purchase_count === 1 ? 'faktura zakupu' : 'faktur zakupu' }}</span>
          <Link :href="route('purchases.index')" class="lnk">Zaksięguj faktury zakupu →</Link>
        </div>
        <p class="card-note">
          Środki trwałe (P_40 / P_41), WNT, import usług i odwrotne obciążenie {{ brand.name }} nie rozróżnia — uzupełnij je z księgową.
        </p>
      </div>
    </div>

    <!-- Rozliczenie + status | Jak złożyć -->
    <div class="btw-grid cols-2" style="margin-top:16px;">
      <div class="btw-card">
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">Rozliczenie za {{ summary.period_label }}</div>
            <div class="btw-card-months">część deklaracyjna {{ summary.form }}</div>
          </div>
        </div>
        <table class="btw-table">
          <tbody>
            <tr><td>VAT należny <span class="field-tag">P_38</span></td><td class="right num">{{ fmtV(summary.output_vat, summary.output_vat_rounded) }}</td></tr>
            <tr><td>VAT naliczony <span class="field-tag">P_48</span></td><td class="right num vat-in">− {{ fmtV(summary.input_vat, summary.input_vat_rounded) }}</td></tr>
            <tr class="btw-total-row">
              <td>{{ balanceLabel }} <span class="field-tag">{{ summary.balance_kind === 'refund' ? 'P_53 → P_54 / P_62' : 'P_51' }}</span></td>
              <td class="right num" :class="{ neg: summary.balance < 0 }">{{ fmtV(Math.abs(summary.balance), Math.abs(summary.balance_rounded)) }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="summary.balance_kind === 'refund'" class="card-note">
          Nadwyżkę podatku naliczonego możesz przenieść na następny okres (P_62) albo wystąpić o zwrot (P_54, zwykle w 60 dni). Wybór zaznaczasz w deklaracji.
        </p>
        <p v-else-if="summary.balance_kind === 'zero'" class="card-note">Nic do zapłaty — deklarację składasz mimo to.</p>

        <div class="sect-title" style="margin-top:18px;">Status</div>
        <div class="status-row">
          <label class="opt" :class="{ on: summary.filing.filed }">
            <input type="checkbox" :checked="summary.filing.filed" @change="toggle('filed', $event.target.checked)">
            <div>
              <div class="opt-title">{{ summary.form }} złożony</div>
              <div class="opt-sub">{{ summary.filing.filed_at_label ? 'Oznaczono ' + summary.filing.filed_at_label : 'Zaznacz po wysłaniu pliku i otrzymaniu UPO.' }}</div>
            </div>
          </label>
          <label v-if="summary.balance_kind === 'to_pay'" class="opt" :class="{ on: summary.filing.paid }">
            <input type="checkbox" :checked="summary.filing.paid" @change="toggle('paid', $event.target.checked)">
            <div>
              <div class="opt-title">VAT zapłacony</div>
              <div class="opt-sub">{{ summary.filing.paid_at_label ? 'Oznaczono ' + summary.filing.paid_at_label : whole(summary.balance_rounded) + ' przelane na mikrorachunek podatkowy.' }}</div>
            </div>
          </label>
        </div>

        <div class="sect-title" style="margin-top:18px;">Dokumenty</div>
        <div class="fold">
          <button type="button" class="fold-head" @click="showSales = !showSales">
            <span>Faktury sprzedaży i korekty <em>{{ summary.documents.sales.length }}</em></span><span>{{ showSales ? '−' : '+' }}</span>
          </button>
          <table v-if="showSales && summary.documents.sales.length" class="list-table">
            <tr v-for="i in summary.documents.sales" :key="i.id">
              <td><Link :href="route('invoices.show', i.id)" class="lnk">{{ i.number }}</Link><span v-if="i.is_credit" class="cc">korekta</span></td>
              <td class="grow">{{ i.customer_name }}<span v-if="i.country !== 'PL'" class="cc">{{ i.country }}</span></td>
              <td class="muted">{{ i.date_label }}</td>
              <td class="right num">{{ amount(i.net) }}</td>
              <td class="right num">{{ amount(i.vat) }}</td>
              <td class="tags"><span v-for="k in i.rates" :key="k" class="btw-rubriek">{{ k === 'other' ? 'inne' : k + '%' }}</span></td>
            </tr>
          </table>
          <div v-else-if="showSales" class="fold-empty">Brak faktur sprzedaży w tym okresie.</div>
        </div>
        <div class="fold">
          <button type="button" class="fold-head" @click="showPurchases = !showPurchases">
            <span>Faktury zakupu <em>{{ summary.documents.purchases.length }}</em></span><span>{{ showPurchases ? '−' : '+' }}</span>
          </button>
          <table v-if="showPurchases && summary.documents.purchases.length" class="list-table">
            <tr v-for="p in summary.documents.purchases" :key="p.id">
              <td class="grow"><Link :href="route('purchases.show', p.id)" class="lnk">{{ p.supplier_name }}</Link><span v-if="p.reference" class="cc">{{ p.reference }}</span></td>
              <td class="muted">{{ p.date_label }}</td>
              <td class="right num muted">{{ amount(p.gross) }} brutto</td>
              <td class="right num vat-in">{{ amount(p.vat) }}</td>
            </tr>
          </table>
          <div v-else-if="showPurchases" class="fold-empty">Brak faktur zakupu w tym okresie — <Link :href="route('purchases.index')" class="lnk">zaksięguj je</Link>, aby odliczyć VAT naliczony.</div>
        </div>
      </div>

      <div class="btw-card">
        <div class="btw-card-head">
          <div>
            <div class="btw-card-title">Jak złożyć {{ summary.form }}</div>
            <div class="btw-card-months">krok po kroku · termin {{ summary.due_date_long }}</div>
          </div>
        </div>
        <ol class="steps">
          <li>
            <b>Sprawdź kwoty</b> po lewej — {{ brand.name }} liczy je z faktur sprzedaży i zaksięgowanych zakupów.
            Uzupełnij z księgową to, czego {{ brand.name }} nie zna: WNT, import usług, odwrotne obciążenie, środki trwałe, korekty z poprzednich okresów.
          </li>
          <li>
            <b>Przygotuj plik {{ summary.form }}</b> w <a :href="eus_url" target="_blank" rel="noopener">e-Urzędzie Skarbowym ↗</a> (JPK_VAT z deklaracją),
            w bezpłatnej aplikacji e-mikrofirma albo w programie księgowym — wpisując kwoty do pól P_… podanych przy każdej pozycji.
            <span class="rub-note">Eksport pliku JPK-XML bezpośrednio z {{ brand.name }} jest w przygotowaniu.</span>
          </li>
          <li :class="{ done: summary.filing.filed }">
            <b>Podpisz i wyślij do {{ summary.due_date_label }}</b> — Profilem Zaufanym, podpisem kwalifikowanym lub danymi autoryzującymi. Zachowaj UPO.
            <span v-if="periodType === 'quarter'" class="rub-note">Przy rozliczeniu kwartalnym część ewidencyjną JPK_V7K wysyłasz co miesiąc (do 25. dnia następnego miesiąca), a część deklaracyjną raz — po zakończeniu kwartału.</span>
          </li>
          <li v-if="summary.balance_kind === 'to_pay'" :class="{ done: summary.filing.paid }">
            <b>Zapłać {{ whole(summary.balance_rounded) }}</b> do {{ summary.due_date_label }} przelewem podatkowym na swój mikrorachunek — dane poniżej.
          </li>
          <li>Oznacz okres jako złożony<template v-if="summary.balance_kind === 'to_pay'"> i zapłacony</template> — wtedy {{ brand.name }} przestanie przypominać.</li>
        </ol>

        <div class="sect-title">Przelew podatkowy</div>
        <div class="pay-box">
          <div class="pay-row"><span class="pay-k">Kwota</span><span class="pay-v">{{ summary.balance_kind === 'to_pay' ? whole(summary.balance_rounded) : '—' }}</span></div>
          <div class="pay-row"><span class="pay-k">Rachunek</span><span class="pay-v">Twój mikrorachunek podatkowy</span><a :href="microaccount_url" target="_blank" rel="noopener" class="pay-src">sprawdź w generatorze (NIP) ↗</a></div>
          <div class="pay-row"><span class="pay-k">Typ identyfikatora</span><span class="pay-v">NIP</span></div>
          <div class="pay-row"><span class="pay-k">Symbol formularza</span><span class="pay-v mono">{{ periodType === 'quarter' ? 'VAT-7K' : 'VAT-7' }}</span><span class="pay-src">w części banków: {{ summary.form }}</span></div>
          <div class="pay-row"><span class="pay-k">Okres</span><span class="pay-v mono">{{ summary.payment_symbol }}</span><span class="pay-src">{{ summary.period_label }}</span></div>
          <div class="pay-note">
            Użyj w banku formularza „przelew podatkowy”, nie zwykłego przelewu — bez symbolu i okresu urząd nie przypisze wpłaty do deklaracji.
            Gdy 25. dzień wypada w weekend lub święto, termin przesuwa się na następny dzień roboczy.
          </div>
        </div>
      </div>
    </div>

    <p class="btw-disclaimer">
      {{ brand.name }} przygotowuje kwoty na podstawie wystawionych faktur i korekt (na dzień wystawienia) oraz
      <Link :href="route('purchases.index')" style="color:var(--brand);font-weight:500;">zaksięgowanych faktur zakupu</Link> — VAT naliczony jest więc tak kompletny, jak Twoje księgowanie.
      Plik {{ summary.form }} składasz Ty lub Twoja księgowa; {{ brand.name }} nie wysyła deklaracji do urzędu.
      Sprzedaż zwolniona (stawka zw, pole P_10) nie jest rozróżniana od stawki 0% — jeśli ją masz, przenieś te kwoty ręcznie.
      Zaokrąglenie do pełnych złotych jak w deklaracji: końcówki poniżej 50 gr pomija się, 50 gr i więcej podwyższa. Kwoty zawsze skonsultuj z księgową.
    </p>
  </AppLayout>
</template>

<style scoped>
.btw-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.year-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); padding: 4px; border-radius: 10px; }
.tab { padding: 8px 16px; font-size: 13px; font-weight: 500; color: var(--text-3); border-radius: 7px; cursor: pointer; }
.tab:hover { color: var(--text); }
.tab.active { background: var(--text); color: white; }

.period-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.seg { display: inline-flex; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 4px; gap: 4px; }
.seg button { border: none; background: none; font: inherit; font-size: 13px; font-weight: 500; color: var(--text-3); padding: 7px 14px; border-radius: 7px; cursor: pointer; }
.seg button:hover { color: var(--text); }
.seg button.active { background: var(--text); color: white; }
.period-select { min-width: 220px; font-size: 13.5px; padding: 8px 10px; }
.link { background: none; border: none; padding: 0; font: inherit; font-size: 12.5px; color: var(--brand); text-decoration: underline; cursor: pointer; }

.btw-alert {
  display: flex; align-items: center; gap: 12px;
  background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning);
  border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;
}
.btw-alert > svg { width: 19px; height: 19px; flex: none; }
.btw-alert-text { flex: 1; min-width: 0; }
.btw-alert strong { font-weight: 700; }

.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.kpi.tint { background: var(--brand-tint); border-color: var(--brand-border); }
.kpi .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.kpi .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.kpi .val.brand { color: var(--brand-darker); }
.kpi .meta { font-size: 11px; color: var(--text-3); margin-top: 4px; }

.btw-empty-note { background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 13px; color: var(--text-3); line-height: 1.6; }

.btw-grid { display: grid; gap: 16px; }
.btw-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.btw-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 20px 22px; display: flex; flex-direction: column; }
.btw-card.current { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.btw-card.due { border-color: var(--warning-border); }
.btw-card.done { border-color: var(--success-border); }

.btw-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.btw-card-title { font-family: var(--font-display); font-weight: 700; font-size: 17px; letter-spacing: -0.01em; }
.btw-card-months { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
.cents-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-3); cursor: pointer; white-space: nowrap; }

.btw-chip { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 100px; border: 1px solid var(--border-strong); background: var(--surface-2); color: var(--text-2); white-space: nowrap; flex: none; }
.btw-chip.current { background: var(--info-bg); border-color: var(--info-border); color: var(--info); }
.btw-chip.filed, .btw-chip.paid { background: var(--success-bg); border-color: var(--success-border); color: var(--success); }
.btw-chip.due { background: var(--warning-bg); border-color: var(--warning-border); color: var(--warning); }
.btw-chip.unmarked, .btw-chip.future { color: var(--text-4); }

.btw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.btw-table th { text-align: left; padding: 7px 8px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); border-bottom: 1px solid var(--border); background: var(--surface-2); }
.btw-table th:first-child { border-radius: 6px 0 0 6px; }
.btw-table th:last-child { border-radius: 0 6px 6px 0; }
.btw-table td { padding: 8px 8px; border-bottom: 1px solid var(--border); }
.btw-table .right { text-align: right; }
.btw-table .num { font-family: var(--font-mono); }
.btw-table tr.dim td { color: var(--text-4); }
.btw-rubriek { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; padding: 1px 5px; margin-right: 7px; background: var(--surface-3); border-radius: 5px; font-size: 10.5px; font-weight: 700; color: var(--text-2); }
.field-tag { font-family: var(--font-mono); font-size: 10.5px; color: var(--text-4); margin-left: 4px; white-space: nowrap; }
.vat-in { color: var(--success); font-weight: 600; }
.btw-total-row td { font-weight: 700; border-bottom: none; padding-top: 11px; }
.neg { color: var(--brand); }
.muted { color: var(--text-4); }

.btw-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-3); }
.card-note { margin: 12px 0 0; font-size: 12px; color: var(--text-4); line-height: 1.6; }
.btw-disclaimer { margin-top: 18px; font-size: 12px; color: var(--text-4); line-height: 1.6; max-width: 760px; }

.sect-title { font-family: var(--font-display); font-weight: 600; font-size: 15px; margin-bottom: 10px; }
.steps { margin: 0 0 20px; padding: 0 0 0 22px; font-size: 13px; color: var(--text-2); line-height: 1.7; }
.steps li { margin-bottom: 8px; }
.steps li.done { color: var(--text-4); text-decoration: line-through; }
.steps a { color: var(--brand); font-weight: 600; }
.rub-note { display: block; font-size: 11.5px; color: var(--text-4); font-weight: 400; }

.fold { border: 1px solid var(--border); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
.fold-head { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface-2); border: none; font: inherit; font-size: 13px; font-weight: 600; color: var(--text-2); cursor: pointer; }
.fold-head em { font-style: normal; font-weight: 500; color: var(--text-4); margin-left: 6px; }
.fold-empty { padding: 12px 14px; font-size: 12.5px; color: var(--text-4); }
.list-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.list-table td { padding: 7px 12px; border-top: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
.list-table td.grow { white-space: normal; width: 100%; }
.list-table .right { text-align: right; }
.list-table .num { font-family: var(--font-mono); }
.list-table .tags .btw-rubriek { margin-right: 3px; }
.lnk { color: var(--brand); font-weight: 600; }
.cc { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; color: var(--text-4); background: var(--surface-3); border-radius: 4px; padding: 1px 5px; }

.pay-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; }
.pay-row { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
.pay-row:last-of-type { border-bottom: none; }
.pay-k { width: 150px; flex: none; font-size: 12.5px; color: var(--text-3); }
.pay-v { font-size: 13.5px; font-weight: 600; }
.pay-v.mono { font-family: var(--font-mono); letter-spacing: 0.02em; }
.pay-src { font-size: 11.5px; color: var(--text-4); }
.pay-note { font-size: 12px; color: var(--text-3); line-height: 1.6; margin: 10px 0 2px; }

.status-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.opt { display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; cursor: pointer; transition: border-color .15s, background .15s; }
.opt:hover { background: var(--surface-2); }
.opt.on { border-color: var(--success); background: var(--success-bg); }
.opt input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--success); flex: none; }
.opt-title { font-weight: 600; font-size: 13.5px; }
.opt-sub { font-size: 12px; color: var(--text-3); margin-top: 2px; line-height: 1.5; }

@media (max-width: 900px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .btw-grid.cols-2 { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 640px) { .status-row { grid-template-columns: minmax(0, 1fr); } .pay-k { width: 100%; } }
</style>
