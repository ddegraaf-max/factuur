<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ logs: Object, filters: Object, users: Array, types: Array, actions: Object });

const f = ref({ q: props.filters.q || '', user: props.filters.user || '', type: props.filters.type || '', action: props.filters.action || '', from: props.filters.from || '', to: props.filters.to || '' });
let timer = null;
watch(f, () => {
  clearTimeout(timer);
  timer = setTimeout(() => router.get(route('settings.activity'), Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)), { preserveState: true, replace: true }), 300);
}, { deep: true });
const reset = () => { f.value = { q: '', user: '', type: '', action: '', from: '', to: '' }; };
const open = ref(null);
const exportUrl = () => route('settings.activity.export', Object.fromEntries(Object.entries(f.value).filter(([, v]) => v)));
const actionClass = { created: 'pill-sent', updated: 'pill-draft', deleted: 'pill-overdue', sent: 'pill-paid', reminded: 'pill-partial', accepted: 'pill-paid', rejected: 'pill-overdue', paid: 'pill-paid', login: 'pill-draft', logout: 'pill-draft', purged: 'pill-overdue', exported: 'pill-draft' };
</script>

<template>
  <Head title="Logboek" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Logboek</span></template>
    <template #topbar-actions>
      <a :href="exportUrl()" class="btn btn-secondary btn-sm">Exporteer CSV</a>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Logboek</h1>
        <p class="page-subtitle">Wie deed wat, wanneer — elke wijziging, verzending en aanmelding in deze administratie. Onuitwisbaar; handig bij vragen van je accountant of bij een geschil.</p>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-body lb-filters">
        <input type="search" v-model="f.q" placeholder="Zoek in omschrijving…" class="lb-q">
        <select v-model="f.user"><option value="">Iedereen</option><option v-for="u in users" :key="u" :value="u">{{ u }}</option></select>
        <select v-model="f.type"><option value="">Alle onderwerpen</option><option v-for="t in types" :key="t" :value="t">{{ t }}</option></select>
        <select v-model="f.action"><option value="">Alle acties</option><option v-for="(label, key) in actions" :key="key" :value="key">{{ label }}</option></select>
        <input type="date" v-model="f.from"><input type="date" v-model="f.to">
        <button type="button" class="btn btn-secondary btn-sm" @click="reset">Wis</button>
      </div>
    </div>

    <div class="card">
      <div class="card-body-flush" v-if="logs.data.length">
        <table class="data-table">
          <thead><tr><th>Wanneer</th><th>Wie</th><th>Actie</th><th>Wat</th><th></th></tr></thead>
          <tbody>
            <template v-for="l in logs.data" :key="l.id">
              <tr>
                <td class="num lb-when">{{ l.when }}</td>
                <td>{{ l.user }}</td>
                <td><span class="pill" :class="actionClass[l.action] || 'pill-draft'">{{ l.action_label }}</span></td>
                <td class="lb-desc">{{ l.description }}</td>
                <td class="right"><button v-if="l.changes" type="button" class="link-btn" @click="open = open === l.id ? null : l.id">{{ open === l.id ? 'verberg' : 'details' }}</button></td>
              </tr>
              <tr v-if="open === l.id && l.changes" class="lb-changes-row">
                <td colspan="5">
                  <table class="lb-changes">
                    <tr v-for="(c, key) in l.changes" :key="key"><td class="lb-key">{{ key }}</td><td class="lb-old">{{ c.van ?? '—' }}</td><td class="lb-arrow">→</td><td>{{ c.naar ?? '—' }}</td></tr>
                  </table>
                  <div v-if="l.ip" class="muted-sm" style="margin-top:6px;">IP {{ l.ip }}</div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div v-else class="card-empty">Nog niets gelogd — vanaf nu verschijnt hier elke wijziging.</div>
      <div v-if="logs.links && logs.links.length > 3" class="lb-pager">
        <template v-for="link in logs.links" :key="link.label">
          <Link v-if="link.url" :href="link.url" :class="['btn btn-secondary btn-sm', { active: link.active }]" v-html="link.label" preserve-scroll />
          <span v-else class="btn btn-secondary btn-sm disabled" v-html="link.label"></span>
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.lb-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.lb-filters input, .lb-filters select { font-size: 13px; padding: 7px 10px; }
.lb-q { flex: 1; min-width: 200px; }
.lb-when { white-space: nowrap; font-size: 12.5px; color: var(--text-3); }
.lb-desc { max-width: 560px; white-space: normal; }
.link-btn { background: none; border: none; padding: 0; font-size: 12px; color: var(--brand); text-decoration: underline; cursor: pointer; }
.lb-changes-row td { background: var(--surface-2); }
.lb-changes { font-size: 12.5px; border-collapse: collapse; }
.lb-changes td { padding: 2px 10px 2px 0; vertical-align: top; }
.lb-key { color: var(--text-3); font-family: ui-monospace, Menlo, Consolas, monospace; }
.lb-old { color: var(--text-4); text-decoration: line-through; }
.lb-arrow { color: var(--text-4); }
.muted-sm { font-size: 12px; color: var(--text-4); }
.lb-pager { display: flex; gap: 6px; flex-wrap: wrap; padding: 14px 18px; border-top: 1px solid var(--border); }
.lb-pager .active { background: var(--brand); color: #fff; border-color: var(--brand); }
.lb-pager .disabled { opacity: .45; pointer-events: none; }
</style>
