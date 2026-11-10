<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ merken: Array, geschiedenis: Array, team_ingesteld: Boolean });

const bezig = ref(null);
const statusLabel = { ok: 'Gelukt', fout: 'Fout', overgeslagen: 'Overgeslagen', bezig: 'Bezig' };
const statusClass = { ok: 'pill-paid', fout: 'pill-overdue', overgeslagen: 'pill-draft', bezig: 'pill-sent' };
const euro = (v) => v == null ? '—' : new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(v);

const ophalen = (code = null) => {
  bezig.value = code || 'alles';
  router.post(route('owner.merken.ophalen'), code ? { merk: code } : {}, {
    preserveScroll: true,
    onFinish: () => { bezig.value = null; },
  });
};
</script>

<template>
  <Head title="Merken" />
  <AppLayout>
    <template #breadcrumb>Eigenaar / <span class="breadcrumb-current">Merken</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Merken van het VvE-pakket</h1>
        <p class="page-subtitle">Bijgekochte bundels en brieven per post worden elke 1e van de maand opgehaald en per klant als <b>concept</b> klaargezet. Er gaat niets vanzelf de deur uit: controleer en verstuur de concepten zelf, in de administratie van het merk.</p>
      </div>
      <button type="button" class="btn btn-primary" :disabled="!!bezig" @click="ophalen()">{{ bezig === 'alles' ? 'Bezig…' : 'Alles nu ophalen' }}</button>
    </div>

    <div v-if="!team_ingesteld" class="card notice">
      Er is nog geen administratie ingesteld die voor de merken factureert. Zet <code>MERKEN_TEAM_ID</code> op het id van de administratie van NapoleonSoftware B.V. (zie Eigenaar → Administraties).
    </div>

    <div class="card">
      <div class="card-body-flush">
        <table class="data-table">
          <thead><tr><th>Merk</th><th>Koppeling</th><th>Laatste ophaling</th><th>Klaargezet (concept)</th><th>Niet teruggemeld</th><th></th></tr></thead>
          <tbody>
            <tr v-for="m in merken" :key="m.code">
              <td><b>{{ m.naam }}</b><div class="muted-sm">{{ m.url }}</div><div class="muted-sm">{{ m.team || 'geen administratie' }}</div></td>
              <td>
                <span v-if="!m.ontbreekt.length" class="pill pill-paid">Ingesteld</span>
                <template v-else><span class="pill pill-draft">Niet ingesteld</span><div class="muted-sm">ontbreekt: {{ m.ontbreekt.join(', ') }}</div></template>
              </td>
              <td>
                <template v-if="m.laatste">
                  <span class="pill" :class="statusClass[m.laatste.status] || 'pill-draft'">{{ statusLabel[m.laatste.status] || m.laatste.status }}</span>
                  <span class="muted-sm"> {{ m.laatste.tijd }}</span>
                  <div class="muted-sm melding" :class="{ fout: m.laatste.status === 'fout' }">{{ m.laatste.melding }}</div>
                </template>
                <span v-else class="muted">nog nooit</span>
              </td>
              <td>
                <div v-for="c in m.concepten" :key="c.id" class="muted-sm"><a :href="c.url">{{ c.klant || c.kenmerk }}</a> · {{ c.periode }} · {{ c.regels }} regel(s) · {{ euro(c.totaal) }}</div>
                <span v-if="!m.concepten.length" class="muted">—</span>
              </td>
              <td>
                <span v-if="m.niet_afgemeld" class="pill pill-overdue">{{ m.niet_afgemeld }}</span><span v-else class="muted">0</span>
                <div v-if="m.afmeld_fout" class="muted-sm fout">{{ m.afmeld_fout }}</div>
              </td>
              <td class="right"><button type="button" class="btn btn-secondary btn-sm" :disabled="!!bezig || m.ontbreekt.length > 0" @click="ophalen(m.code)">{{ bezig === m.code ? 'Bezig…' : 'Nu ophalen' }}</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <p class="muted-sm">De concepten staan in de administratie van het merk; wissel daarheen (Administraties) om ze te openen en te versturen.</p>

    <h2 class="section-title">Laatste ophalingen</h2>
    <div class="card">
      <div class="card-body-flush">
        <table class="data-table">
          <thead><tr><th>Tijd</th><th>Merk</th><th>Status</th><th class="right">Klanten</th><th class="right">Regels</th><th class="right">Nieuwe concepten</th><th>Melding</th><th>Door</th></tr></thead>
          <tbody>
            <tr v-for="o in geschiedenis" :key="o.id">
              <td class="muted-sm">{{ o.tijd }}</td>
              <td>{{ o.merk }}</td>
              <td><span class="pill" :class="statusClass[o.status] || 'pill-draft'">{{ statusLabel[o.status] || o.status }}</span></td>
              <td class="right num">{{ o.klanten }}</td>
              <td class="right num">{{ o.regels }}</td>
              <td class="right num">{{ o.facturen }}</td>
              <td class="muted-sm melding">{{ o.melding }}</td>
              <td class="muted-sm">{{ o.door }}</td>
            </tr>
            <tr v-if="!geschiedenis.length"><td colspan="8" class="muted">Nog niets opgehaald.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.muted { color: var(--text-4); }
.muted-sm { font-size: 12px; color: var(--text-4); }
.melding { max-width: 420px; white-space: normal; }
.fout { color: #B91C1C; }
.notice { padding: 14px 18px; margin-bottom: 16px; border-color: #FDE68A; background: #FFFBEB; }
.section-title { font-size: 16px; font-weight: 600; margin: 24px 0 10px; }
</style>
