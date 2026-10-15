<script setup>
/**
 * Eigenaar: alle opdrachten op Fachora, met de cijfers (afgerond, omzet,
 * fee, deze maand) en een filter per fase: open, in uitvoering, geschil,
 * gesloten.
 */
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { icon } from '@/fachora-icons.js';
import { money } from '@/format.js';

const props = defineProps({
  jobs: Object,
  status: { type: String, default: 'open' },
  counts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const jobPill = {
  pending: 'pill-draft', open: 'pill-sent', quoted: 'pill-partial', no_match: 'pill-overdue', agreed: 'pill-sent', scheduled: 'pill-sent',
  in_progress: 'pill-partial', done: 'pill-partial', accepted: 'pill-paid', disputed: 'pill-overdue', cancelled: 'pill-cancelled', expired: 'pill-cancelled',
};
const groups = ['open', 'active', 'disputed', 'closed', 'all'];
const groupLabel = { open: 'Open', active: 'In uitvoering', disputed: 'Geschil', closed: 'Gesloten', all: 'Alle' };
const setStatus = (s) => router.get(route('fachora.admin.jobs'), { status: s }, { preserveState: true, replace: true });
const rows = computed(() => props.jobs.data || []);
const open = (j) => router.visit(j.url);
</script>

<template>
  <Head :title="$t('Fachora: opdrachten')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Eigenaar') }} / <span class="breadcrumb-current">{{ $t('Fachora: opdrachten') }}</span></template>
    <template #topbar-actions>
      <Link :href="route('fachora.admin.providers')" class="btn btn-secondary btn-sm">{{ $t('Fachora: bedrijven') }}</Link>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Opdrachten op Fachora') }}</h1>
        <p class="page-subtitle">{{ $t('Van aanvraag tot uitbetaling. Een opdracht zonder reacties verdient een belrondje; een geschil een beslissing binnen 48 uur.') }}</p>
      </div>
      <div class="page-actions">
        <Link :href="route('fachora.admin.providers')" class="btn btn-secondary btn-sm">{{ $t('Fachora: bedrijven') }}</Link>
      </div>
    </div>

    <div v-if="flash.error" class="fx-alert fx-alert--err">{{ flash.error }}</div>

    <!-- Cijfers -->
    <div class="fx-tiles">
      <div class="fx-tile"><div class="lbl">{{ $t('Afgerond') }}</div><div class="val">{{ stats.accepted ?? 0 }}</div><div class="sub">{{ $t('deze maand: :n', { n: stats.month_accepted ?? 0 }) }}</div></div>
      <div class="fx-tile"><div class="lbl">{{ $t('Omzet via Fachora') }}</div><div class="val">{{ money(stats.revenue || 0) }}</div><div class="sub">{{ $t('afgesproken bedragen, afgeronde opdrachten') }}</div></div>
      <div class="fx-tile fx-tile--accent"><div class="lbl">{{ $t('Platformfee') }}</div><div class="val">{{ money(stats.fees || 0) }}</div><div class="sub">{{ $t('deze maand: :amount', { amount: money(stats.month_fees || 0) }) }}</div></div>
      <div class="fx-tile"><div class="lbl">{{ $t('Verified-bedrijven') }}</div><div class="val">{{ stats.verified ?? 0 }}</div><div class="sub"><Link :href="route('fachora.admin.providers', { status: 'pending' })">{{ $t(':n wachten op beoordeling', { n: stats.pending ?? 0 }) }}</Link></div></div>
    </div>

    <div class="filters" style="margin-bottom:14px;">
      <button v-for="g in groups" :key="g" :class="['filter-chip', { active: status === g }]" @click="setStatus(g)">
        {{ $t(groupLabel[g]) }} <span class="count">{{ counts[g] ?? 0 }}</span>
      </button>
    </div>

    <div class="card">
      <div v-if="rows.length" class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th>{{ $t('Kenmerk') }}</th>
              <th>{{ $t('Opdracht') }}</th>
              <th>{{ $t('Status') }}</th>
              <th>{{ $t('Bedrijf') }}</th>
              <th class="right">{{ $t('Reacties') }}</th>
              <th class="right">{{ $t('Bedrag') }}</th>
              <th>{{ $t('Betaling') }}</th>
              <th>{{ $t('Aangevraagd') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="j in rows" :key="j.id" class="row-link" @click="open(j)">
              <td :data-label="$t('Kenmerk')" class="num"><Link :href="j.url" @click.stop>{{ j.reference }}</Link></td>
              <td class="cell-primary">{{ j.title }}<div class="sub">{{ j.category_name }}<template v-if="j.city"> · {{ j.city }}</template> · {{ (j.language || '').toUpperCase() }}</div></td>
              <td :data-label="$t('Status')"><span :class="['pill', jobPill[j.status]]">{{ j.status_label }}</span></td>
              <td :data-label="$t('Bedrijf')">{{ j.provider || '—' }}</td>
              <td :data-label="$t('Reacties')" class="right num">{{ j.responded }} / {{ j.offers }}</td>
              <td :data-label="$t('Bedrag')" class="right num">{{ j.amount !== null ? money(j.amount) : '—' }}</td>
              <td :data-label="$t('Betaling')">
                <span v-if="j.protect && j.paid" class="fx-ok" :title="$t('Bedrag gereserveerd via Fachora Protect')"><span v-html="icon('shield', 13)"></span> {{ $t('gereserveerd') }}</span>
                <span v-else-if="j.protect" class="sub">{{ $t('Protect, nog niet betaald') }}</span>
                <span v-else class="sub">{{ $t('rechtstreeks') }}</span>
              </td>
              <td :data-label="$t('Aangevraagd')" class="sub">{{ j.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="card-body sub">{{ $t('Geen opdrachten in deze selectie.') }}</div>
    </div>

    <div v-if="jobs.last_page > 1" class="pagination">
      <span class="sub">{{ $t(':from–:to van :total', { from: jobs.from, to: jobs.to, total: jobs.total }) }}</span>
      <Link v-for="link in jobs.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['page-link', { active: link.active, disabled: !link.url }]" preserve-state />
    </div>
  </AppLayout>
</template>

<style scoped>
.sub { font-size: 12px; color: var(--text-3); font-weight: 400; }
.filters { display: flex; flex-wrap: wrap; gap: 6px; }
.num { font-family: var(--font-mono); }
.row-link { cursor: pointer; }
.fx-alert { padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 14px; }
.fx-alert--err { background: var(--brand-tint-2); color: var(--brand-darker); border: 1px solid var(--brand-border); }
.fx-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
.fx-tile { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 14px 16px; }
.fx-tile--accent { border-color: var(--accent, #F4781F); }
.fx-tile .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; margin-bottom: 4px; }
.fx-tile .val { font-family: var(--font-display); font-size: 22px; font-weight: 600; color: var(--text); }
.fx-ok { color: var(--success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px; }
.pagination { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; margin-top: 14px; }
.pagination .sub { margin-right: 8px; }
.page-link { padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12.5px; color: var(--text-2); background: var(--surface); }
.page-link.active { background: var(--text); color: #fff; border-color: var(--text); }
.page-link.disabled { opacity: 0.4; pointer-events: none; }
</style>
