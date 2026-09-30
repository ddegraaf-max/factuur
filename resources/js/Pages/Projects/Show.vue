<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { eur, num } from '@/format.js';
import { t } from '@/i18n';

const props = defineProps({
  project: Object,
  figures: Object,
  budget_lines: Array,
  quotes: Array,
  invoices: Array,
  purchases: Array,
  hours: Array,
  trips: Array,
  rounds: Array,
  candidates: Object,
  kinds: Array,
});

const kindLabel = { labour: 'Arbeid (uren)', material: 'Materiaal', subcontract: 'Onderaanneming', other: 'Overig' };
const pct = (n) => (n === null || n === undefined ? '—' : num(n, 1) + '%');
const fmtHours = (minutes) => num(minutes / 60, 2) + ' ' + t('uur');
const isOpen = computed(() => props.project.status === 'open');

/* ---------- Status ---------- */
const setStatus = (status) => {
  const ask = status === 'closed' ? t('Project sluiten? Koppelen en de calculatie aanpassen kan daarna niet meer; heropenen wel.') : t('Project heropenen?');
  if (confirm(ask)) router.patch(route('projects.status', props.project.id), { status }, { preserveScroll: true });
};
const destroy = () => {
  if (confirm(t('Project verwijderen? De offertes, facturen en andere documenten blijven bestaan; alleen de koppeling verdwijnt.'))) {
    router.delete(route('projects.destroy', props.project.id));
  }
};

/* ---------- Calculatie ---------- */
const editingBudget = ref(false);
const budgetForm = useForm({ lines: [] });
const openBudget = () => {
  budgetForm.clearErrors();
  budgetForm.lines = props.budget_lines.length
    ? props.budget_lines.map(l => ({ kind: l.kind, description: l.description, amount: l.amount }))
    : [{ kind: 'material', description: '', amount: '' }, { kind: 'labour', description: '', amount: '' }, { kind: 'subcontract', description: '', amount: '' }];
  editingBudget.value = true;
};
const addLine = () => budgetForm.lines.push({ kind: 'other', description: '', amount: '' });
const removeLine = (i) => budgetForm.lines.splice(i, 1);
const budgetSum = computed(() => budgetForm.lines.reduce((s, l) => s + (Number(String(l.amount).replace(',', '.')) || 0), 0));
const saveBudget = () => budgetForm
  .transform((data) => ({ lines: data.lines.map(l => ({ ...l, amount: Number(String(l.amount).replace(',', '.')) || 0 })) }))
  .put(route('projects.budget', props.project.id), { preserveScroll: true, onSuccess: () => { editingBudget.value = false; } });

const rows = computed(() => props.kinds.map(kind => ({
  kind,
  label: kindLabel[kind],
  budget: props.figures.budget[kind] || 0,
  actual: props.figures.actual[kind] || 0,
})));

/* ---------- Koppelen ---------- */
const linking = ref(null); // 'quote' | 'invoice' | 'purchase' | 'hours' | 'trip' | 'round'
const linkForm = useForm({ type: '', ids: [] });
const candidateKey = { quote: 'quotes', invoice: 'invoices', purchase: 'purchases', hours: 'hours', trip: 'trips', round: 'rounds' };
const linkTitle = { quote: 'Offertes koppelen', invoice: 'Facturen koppelen', purchase: 'Inkoopfacturen koppelen', hours: 'Uren koppelen', trip: 'Ritten koppelen', round: 'Uitvragen koppelen' };
const openLink = (type) => { linkForm.clearErrors(); linkForm.type = type; linkForm.ids = []; linking.value = type; };
const toggleId = (id) => {
  const i = linkForm.ids.indexOf(id);
  if (i >= 0) linkForm.ids.splice(i, 1); else linkForm.ids.push(id);
};
const link = () => linkForm.post(route('projects.link', props.project.id), { preserveScroll: true, onSuccess: () => { linking.value = null; } });
const unlink = (type, id) => {
  if (confirm(t('Losmaken van dit project? Het document zelf blijft bestaan.'))) {
    router.post(route('projects.unlink', props.project.id), { type, id }, { preserveScroll: true });
  }
};
const candidateList = computed(() => (linking.value ? props.candidates[candidateKey[linking.value]] || [] : []));

const statusPill = { draft: 'pill-draft', sent: 'pill-sent', accepted: 'pill-paid', paid: 'pill-paid', partial: 'pill-partial', overdue: 'pill-overdue', open: 'pill-sent', awarded: 'pill-paid', closed: 'pill-cancelled', rejected: 'pill-cancelled', expired: 'pill-cancelled', cancelled: 'pill-cancelled', incasso: 'pill-overdue' };
</script>

<template>
  <Head :title="project.name" />
  <AppLayout>
    <template #breadcrumb>
      <div class="breadcrumb">
        {{ $t('Verkoop') }} / <Link :href="route('projects.index')" style="color:var(--text-3);">{{ $t('Projecten') }}</Link> /
        <span class="breadcrumb-current">{{ project.number }}</span>
      </div>
    </template>
    <template #topbar-actions>
      <Link :href="route('projects.edit', project.id)" class="btn btn-secondary btn-sm">{{ $t('Bewerken') }}</Link>
    </template>

    <div class="page-header">
      <div>
        <Link :href="route('projects.index')" class="btn btn-ghost btn-sm" style="padding-left:0;margin-bottom:6px;">‹ {{ $t('Terug') }}</Link>
        <h1 class="page-title">{{ project.name }} <span :class="['pill', isOpen ? 'pill-sent' : 'pill-draft']" style="vertical-align:middle;margin-left:8px;">{{ isOpen ? $t('Open') : $t('Gesloten') }}</span></h1>
        <p class="page-subtitle">
          {{ project.number }}
          <template v-if="project.customer_name"> · <Link :href="route('customers.show', project.customer_id)">{{ project.customer_name }}</Link></template>
          <template v-if="project.location"> · {{ project.location }}</template>
          <template v-if="project.starts_on_label || project.ends_on_label"> · {{ [project.starts_on_label, project.ends_on_label].filter(Boolean).join(' – ') }}</template>
        </p>
      </div>
      <div class="page-actions">
        <template v-if="isOpen">
          <Link :href="route('quotes.create', { project: project.id, customer_id: project.customer_id || undefined })" class="btn btn-secondary btn-sm">{{ $t('Nieuwe offerte') }}</Link>
          <Link :href="route('invoices.create', { project: project.id, customer_id: project.customer_id || undefined })" class="btn btn-secondary btn-sm">{{ $t('Nieuwe factuur') }}</Link>
          <Link :href="route('purchases.create', { project: project.id })" class="btn btn-secondary btn-sm">{{ $t('Inkoopfactuur') }}</Link>
          <button class="btn btn-ghost btn-sm" @click="setStatus('closed')">{{ $t('Sluiten') }}</button>
        </template>
        <button v-else class="btn btn-secondary btn-sm" @click="setStatus('open')">{{ $t('Heropenen') }}</button>
        <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="destroy">{{ $t('Verwijderen') }}</button>
      </div>
    </div>

    <p v-if="project.description" class="pr-desc">{{ project.description }}</p>

    <!-- De cijfers -->
    <div class="kpis">
      <div class="kpi">
        <div class="lbl">{{ $t('Afgesproken') }}</div>
        <div class="val">{{ figures.agreed ? eur(figures.agreed) : '—' }}</div>
        <div class="meta">
          <template v-if="project.agreed_price !== null">{{ $t('handmatig ingevuld') }}</template>
          <template v-else-if="figures.counts.quotes">{{ $t('uit :n offertes', { n: figures.counts.quotes }) }}</template>
          <template v-else>{{ $t('koppel een offerte of vul een prijs in') }}</template>
        </div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Gefactureerd') }}</div>
        <div class="val">{{ eur(figures.invoiced) }}</div>
        <div class="meta">{{ $t('ontvangen :received', { received: eur(figures.received) }) }}<span v-if="figures.outstanding > 0" class="warn"> · {{ $t('open :open', { open: eur(figures.outstanding) }) }}</span></div>
      </div>
      <div class="kpi">
        <div class="lbl">{{ $t('Kosten') }}</div>
        <div class="val">{{ eur(figures.costs) }}</div>
        <div class="meta">{{ $t('inkoop :purchases · uren :hours · ritten :trips', { purchases: eur(figures.actual.material + figures.actual.subcontract + figures.actual.other - figures.trips_amount), hours: eur(figures.hours_amount), trips: eur(figures.trips_amount) }) }}</div>
      </div>
      <div class="kpi" :class="{ alert: figures.result < 0 }">
        <div class="lbl">{{ $t('Resultaat') }}</div>
        <div class="val" :class="{ good: figures.result > 0, bad: figures.result < 0 }">{{ eur(figures.result) }}<span class="pct">{{ pct(figures.margin) }}</span></div>
        <div class="meta">
          {{ $t('verwacht :expected (:pct) bij afgesproken prijs en nog te ontvangen inkoop', { expected: eur(figures.expected), pct: pct(figures.expected_margin) }) }}
        </div>
      </div>
    </div>

    <div class="pr-grid">
      <div class="pr-main">
        <!-- Calculatie -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">{{ $t('Calculatie') }}</div>
              <div class="card-subtitle">{{ $t('Begroot tegenover werkelijk, per kostensoort, exclusief btw.') }}</div>
            </div>
            <button v-if="isOpen && !editingBudget" class="btn btn-secondary btn-sm" @click="openBudget">{{ budget_lines.length ? $t('Calculatie aanpassen') : $t('Calculatie invullen') }}</button>
          </div>

          <div v-if="editingBudget" class="card-body">
            <table class="data-table budget-edit">
              <thead><tr><th style="width:170px;">{{ $t('Kostensoort') }}</th><th>{{ $t('Omschrijving') }}</th><th class="right" style="width:150px;">{{ $t('Bedrag excl. btw') }}</th><th style="width:40px;"></th></tr></thead>
              <tbody>
                <tr v-for="(l, i) in budgetForm.lines" :key="i">
                  <td><select v-model="l.kind"><option v-for="k in kinds" :key="k" :value="k">{{ $t(kindLabel[k]) }}</option></select></td>
                  <td><input type="text" v-model="l.description" maxlength="200" :placeholder="$t('Bijv. stenen en mortel')"></td>
                  <td><input type="text" v-model="l.amount" inputmode="decimal" class="right" placeholder="0,00"></td>
                  <td class="right"><button type="button" class="btn btn-ghost btn-sm" @click="removeLine(i)">✕</button></td>
                </tr>
              </tbody>
              <tfoot><tr><td colspan="2"><button type="button" class="link-btn" @click="addLine">+ {{ $t('Regel toevoegen') }}</button></td><td class="right num"><b>{{ eur(budgetSum) }}</b></td><td></td></tr></tfoot>
            </table>
            <div v-if="budgetForm.errors.lines" class="field-error">{{ budgetForm.errors.lines }}</div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
              <button class="btn btn-secondary btn-sm" @click="editingBudget = false">{{ $t('Annuleren') }}</button>
              <button class="btn btn-primary btn-sm" :disabled="budgetForm.processing" @click="saveBudget">{{ $t('Calculatie opslaan') }}</button>
            </div>
          </div>

          <table v-else class="data-table">
            <thead><tr><th>{{ $t('Kostensoort') }}</th><th class="right">{{ $t('Begroot') }}</th><th class="right">{{ $t('Werkelijk') }}</th><th class="right">{{ $t('Verschil') }}</th></tr></thead>
            <tbody>
              <tr v-for="r in rows" :key="r.kind">
                <td>
                  {{ $t(r.label) }}
                  <div v-if="r.kind === 'labour' && figures.hours_minutes" class="sub">{{ fmtHours(figures.hours_minutes) }}</div>
                  <div v-if="r.kind === 'subcontract' && figures.awarded > 0" class="sub">{{ $t('gegund :awarded, waarvan :open nog niet gefactureerd', { awarded: eur(figures.awarded), open: eur(figures.awarded_open) }) }}</div>
                </td>
                <td class="right num">{{ r.budget ? eur(r.budget) : '—' }}</td>
                <td class="right num">{{ eur(r.actual) }}</td>
                <td class="right num" :class="{ good: r.budget && r.budget - r.actual > 0, bad: r.budget && r.budget - r.actual < 0 }">{{ r.budget ? eur(r.budget - r.actual) : '—' }}</td>
              </tr>
              <tr class="total">
                <td>{{ $t('Totaal kosten') }}</td>
                <td class="right num">{{ figures.budget_total ? eur(figures.budget_total) : '—' }}</td>
                <td class="right num">{{ eur(figures.costs) }}</td>
                <td class="right num" :class="{ good: figures.budget_total && figures.budget_total - figures.costs > 0, bad: figures.budget_total && figures.budget_total - figures.costs < 0 }">{{ figures.budget_total ? eur(figures.budget_total - figures.costs) : '—' }}</td>
              </tr>
              <tr v-if="figures.agreed" class="total">
                <td>{{ $t('Resultaat op afgesproken prijs') }}</td>
                <td class="right num">{{ figures.budget_total ? eur(figures.agreed - figures.budget_total) : '—' }}</td>
                <td class="right num">{{ eur(figures.agreed - figures.costs) }}</td>
                <td class="right num sub">{{ $t('verwacht :amount', { amount: eur(figures.expected) }) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="!editingBudget && budget_lines.length" class="card-body budget-lines">
            <div v-for="l in budget_lines" :key="l.id" class="budget-line"><span class="k">{{ $t(kindLabel[l.kind]) }}</span><span>{{ l.description }}</span><span class="num">{{ eur(l.amount) }}</span></div>
          </div>
        </div>

        <!-- Verkoop -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div class="card-title">{{ $t('Offertes') }} <span class="count">{{ quotes.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('quote')">{{ $t('Koppelen') }}</button>
          </div>
          <table v-if="quotes.length" class="data-table">
            <tbody>
              <tr v-for="q in quotes" :key="q.id">
                <td class="cell-primary"><Link :href="route('quotes.show', q.id)">{{ q.number }}</Link><div class="sub">{{ q.date_label }}</div></td>
                <td><span :class="['pill', statusPill[q.status] || 'pill-draft']">{{ q.status_label }}</span></td>
                <td class="right num">{{ eur(q.subtotal) }} <span class="sub">{{ $t('excl.') }}</span></td>
                <td class="right actions"><button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('quote', q.id)">✕</button></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen offerte gekoppeld. Een geaccepteerde offerte is de afgesproken prijs.') }}</div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div class="card-title">{{ $t('Facturen') }} <span class="count">{{ invoices.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('invoice')">{{ $t('Koppelen') }}</button>
          </div>
          <table v-if="invoices.length" class="data-table">
            <tbody>
              <tr v-for="i in invoices" :key="i.id">
                <td class="cell-primary"><Link :href="route('invoices.show', i.id)">{{ i.number }}</Link><span v-if="i.is_credit" class="sub"> · {{ $t('creditnota') }}</span><div class="sub">{{ i.date_label }}</div></td>
                <td><span :class="['pill', statusPill[i.status] || 'pill-draft']">{{ $t(i.status) }}</span></td>
                <td class="right num">{{ eur(i.subtotal) }} <span class="sub">{{ $t('excl.') }}</span><div v-if="i.remaining > 0 && !i.is_credit" class="sub">{{ $t('open :amount', { amount: eur(i.remaining) }) }}</div></td>
                <td class="right actions"><button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('invoice', i.id)">✕</button></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen factuur gekoppeld.') }}</div>
        </div>

        <!-- Kosten -->
        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div class="card-title">{{ $t('Inkoopfacturen') }} <span class="count">{{ purchases.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('purchase')">{{ $t('Koppelen') }}</button>
          </div>
          <table v-if="purchases.length" class="data-table">
            <tbody>
              <tr v-for="p in purchases" :key="p.id">
                <td class="cell-primary"><Link :href="route('purchases.show', p.id)">{{ p.supplier }}</Link><div class="sub">{{ p.date_label }}<template v-if="p.reference"> · {{ p.reference }}</template><template v-if="p.category"> · {{ p.category }}</template></div></td>
                <td><span :class="['pill', p.status === 'paid' ? 'pill-paid' : 'pill-sent']">{{ p.status === 'paid' ? $t('Betaald') : $t('Open') }}</span></td>
                <td class="right num">{{ eur(p.subtotal) }} <span class="sub">{{ $t('excl.') }}</span></td>
                <td class="right actions"><button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('purchase', p.id)">✕</button></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen inkoopfactuur gekoppeld. Kies bij een inkoopfactuur het project, of koppel er hier een.') }}</div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div class="card-title">{{ $t('Uitvragen bij onderaannemers') }} <span class="count">{{ rounds.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('round')">{{ $t('Koppelen') }}</button>
          </div>
          <table v-if="rounds.length" class="data-table">
            <tbody>
              <tr v-for="r in rounds" :key="r.id">
                <td class="cell-primary"><Link :href="route('tenders.show', r.id)">{{ r.title }}</Link><div v-if="r.awarded_to" class="sub">{{ $t('gegund aan :name', { name: r.awarded_to }) }}</div></td>
                <td><span :class="['pill', statusPill[r.status] || 'pill-draft']">{{ $t(r.status) }}</span></td>
                <td class="right num">{{ r.price !== null ? eur(r.price) : '—' }}</td>
                <td class="right actions"><button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('round', r.id)">✕</button></td>
              </tr>
            </tbody>
          </table>
          <div v-else class="card-body sub">{{ $t('Nog geen uitvraag gekoppeld. Een uitvraag vanuit een offerte van dit project hoort er vanzelf bij.') }}</div>
        </div>
      </div>

      <div class="pr-side">
        <div class="card">
          <div class="card-header">
            <div class="card-title">{{ $t('Uren') }} <span class="count">{{ hours.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('hours')">{{ $t('Koppelen') }}</button>
          </div>
          <div class="card-body">
            <div class="side-total"><b>{{ fmtHours(figures.hours_minutes) }}</b> · {{ eur(figures.hours_amount) }}</div>
            <div v-for="h in hours.slice(0, 15)" :key="h.id" class="side-row">
              <span class="d">{{ h.date_label }}</span>
              <span class="t">{{ h.description }}<span v-if="h.invoiced" class="sub"> · {{ $t('gefactureerd') }}</span></span>
              <span class="num">{{ fmtHours(h.minutes) }}</span>
              <button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('hours', h.id)">✕</button>
            </div>
            <div v-if="hours.length > 15" class="sub">{{ $t('en :n meer', { n: hours.length - 15 }) }}</div>
            <div v-if="!hours.length" class="sub">{{ $t('Kies bij het schrijven van uren dit project, of koppel ze hier.') }}</div>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header">
            <div class="card-title">{{ $t('Ritten') }} <span class="count">{{ trips.length }}</span></div>
            <button v-if="isOpen" class="btn btn-secondary btn-sm" @click="openLink('trip')">{{ $t('Koppelen') }}</button>
          </div>
          <div class="card-body">
            <div class="side-total"><b>{{ num(figures.trips_km, 1) }} km</b> · {{ eur(figures.trips_amount) }}</div>
            <div v-for="tr in trips.slice(0, 10)" :key="tr.id" class="side-row">
              <span class="d">{{ tr.date_label }}</span>
              <span class="t">{{ tr.route }}</span>
              <span class="num">{{ num(tr.kilometers, 1) }} km</span>
              <button v-if="isOpen" class="btn btn-ghost btn-sm" :title="$t('Losmaken')" @click="unlink('trip', tr.id)">✕</button>
            </div>
            <div v-if="!trips.length" class="sub">{{ $t('Nog geen ritten gekoppeld.') }}</div>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card-header"><div class="card-title">{{ $t('Zo werkt het') }}</div></div>
          <div class="card-body sub" style="line-height:1.6;">
            {{ $t('Kies bij een offerte, factuur, inkoopfactuur of urenregel dit project; dan telt het hier mee. Een factuur uit een offerte en een uitvraag vanuit een offerte horen er vanzelf bij. Bedragen zijn exclusief btw; uren tellen tegen het uurtarief van de regel.') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Koppelen -->
    <div v-if="linking" class="modal-overlay" @click.self="linking = null">
      <div class="modal" style="max-width:640px;">
        <div class="modal-header">
          <div class="modal-title">{{ $t(linkTitle[linking]) }}</div>
          <button class="btn btn-ghost btn-sm" @click="linking = null">✕</button>
        </div>
        <div class="modal-body">
          <p class="sub" style="margin:0 0 12px;line-height:1.6;">{{ $t('Alleen wat nog bij geen project hoort; van deze klant eerst.') }}</p>
          <div v-if="candidateList.length" class="link-list">
            <label v-for="c in candidateList" :key="c.id" class="link-item" :class="{ mine: c.mine }">
              <input type="checkbox" :checked="linkForm.ids.includes(c.id)" @change="toggleId(c.id)">
              <span class="link-name">{{ c.label }}</span>
              <span class="sub">{{ c.sub }}</span>
            </label>
          </div>
          <div v-else class="sub">{{ $t('Niets te koppelen: alles hoort al bij een project, of er is nog niets.') }}</div>
          <div v-if="linkForm.errors.ids" class="field-error">{{ linkForm.errors.ids }}</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-sm" @click="linking = null">{{ $t('Annuleren') }}</button>
          <button class="btn btn-primary btn-sm" :disabled="linkForm.processing || !linkForm.ids.length" @click="link">{{ $t('Koppelen (:n)', { n: linkForm.ids.length }) }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.pr-desc { white-space: pre-wrap; color: var(--text-2); font-size: 13.5px; line-height: 1.6; margin: -8px 0 16px; max-width: 760px; }
.kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px; }
.kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
.kpi.alert { border-color: var(--brand-border); }
.kpi .lbl { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; }
.kpi .val { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin-top: 4px; }
.kpi .val .pct { font-size: 13px; font-weight: 600; color: var(--text-3); margin-left: 8px; }
.kpi .meta { font-size: 12px; color: var(--text-3); margin-top: 4px; line-height: 1.5; }
.kpi .meta .warn { color: var(--warning, #B45309); }
.good { color: var(--success); }
.bad { color: var(--brand); }
.pr-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.count { font-size: 12px; color: var(--text-3); font-weight: 500; margin-left: 4px; }
.actions { white-space: nowrap; }
.data-table tr.total td { font-weight: 700; background: var(--surface-2); }
.budget-edit input, .budget-edit select { width: 100%; height: 34px; padding: 0 8px; }
.budget-edit input.right { text-align: right; }
.budget-lines { border-top: 1px solid var(--border); }
.budget-line { display: grid; grid-template-columns: 130px 1fr auto; gap: 10px; font-size: 13px; padding: 4px 0; }
.budget-line .k { color: var(--text-3); }
.link-btn { background: none; border: 0; padding: 0; cursor: pointer; font: inherit; color: var(--brand); font-weight: 600; }
.side-total { font-size: 14px; margin-bottom: 8px; }
.side-row { display: grid; grid-template-columns: 62px 1fr auto auto; gap: 8px; align-items: center; font-size: 12.5px; padding: 5px 0; border-top: 1px solid var(--border); }
.side-row .d { color: var(--text-3); white-space: nowrap; }
.side-row .t { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.side-row .btn { padding: 2px 6px; }
.link-list { display: flex; flex-direction: column; gap: 6px; max-height: 420px; overflow: auto; }
.link-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 13.5px; }
.link-item input { width: 17px; height: 17px; padding: 0; flex: none; }
.link-item.mine { border-color: var(--brand-border); }
.link-name { font-weight: 600; color: var(--text); }
.link-item .sub { margin-left: auto; white-space: nowrap; }
@media (max-width: 1000px) { .pr-grid { grid-template-columns: 1fr; } }
</style>
