<script setup>
import { reactive, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  numbering: Object,
});

const state = reactive({ numbering: JSON.parse(JSON.stringify(props.numbering)) });

const rows = [
  { key: 'invoices', label: 'Facturen' },
  { key: 'customers', label: 'Klanten' },
  { key: 'products',  label: 'Producten' },
];

const next = (key) => {
  const cfg = state.numbering[key];
  const seq = cfg.start + (cfg.current || 0);
  const padded = String(seq).padStart(String(cfg.start).length, '0');
  return `${cfg.prefix || ''}${padded}`;
};

const submit = () => router.patch(route('settings.numbering.update'), { numbering: state.numbering }, { preserveScroll: true });
</script>

<template>
  <Head title="Nummering" />
  <AppLayout>
    <template #breadcrumb>Instellingen / <span class="breadcrumb-current">Nummering</span></template>
    <template #topbar-actions>
      <button class="btn btn-primary btn-sm" @click="submit">Opslaan</button>
    </template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Nummering</h1>
        <p class="page-subtitle">Voorvoegsel en startnummer per onderdeel</p>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="numbering-hint">
          Je kunt automatisch het jaartal of de maand in het voorvoegsel gebruiken. Gebruik hiervoor:
          <code>{jaar}</code> of <code>{maand}</code>.
        </div>
        <table class="numbering-table">
          <thead>
            <tr>
              <th></th>
              <th>Voorvoegsel</th>
              <th>Beginnen op</th>
              <th>Volgend nummer</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.key">
              <td><b>{{ row.label }}</b></td>
              <td><input type="text" v-model="state.numbering[row.key].prefix" maxlength="10" class="mono" /></td>
              <td><input type="number" v-model.number="state.numbering[row.key].start" min="1" /></td>
              <td><span class="next-pill">{{ next(row.key) }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.numbering-hint { padding: 10px 14px; background: var(--info-bg); border-left: 3px solid var(--info); border-radius: 4px; font-size: 13px; margin-bottom: 16px; }
.numbering-hint code { font-family: var(--font-mono); background: var(--surface); padding: 1px 5px; border-radius: 3px; }
.numbering-table { width: 100%; }
.numbering-table th { text-align: left; padding: 10px; font-size: 11px; text-transform: uppercase; color: var(--text-3); }
.numbering-table td { padding: 8px 10px; }
.numbering-table input { height: 36px; }
.next-pill { display: inline-block; padding: 6px 12px; background: var(--surface-2); border: 1px dashed var(--border); border-radius: 6px; font-family: var(--font-mono); font-weight: 600; }
</style>
