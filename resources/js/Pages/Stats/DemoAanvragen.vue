<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ requests: Array, brand: String, validDays: Number });

const statusLabel = { pending: 'Wacht op jou', approved: 'Goedgekeurd', denied: 'Afgewezen' };
const statusClass = { pending: 'pill-sent', approved: 'pill-paid', denied: 'pill-draft' };
const pending = computed(() => props.requests.filter(r => r.status === 'pending').length);

const inviteForm = useForm({ email: '', name: '', locale: 'nl' });
const invite = () => inviteForm.post(route('owner.demo.invite'), { preserveScroll: true, onSuccess: () => inviteForm.reset() });
const approve = (r) => router.post(route('owner.demo.approve', r.id), {}, { preserveScroll: true });
const deny = (r) => {
  if (confirm(`${r.email} afwijzen? De aanvrager krijgt geen bericht.`)) router.post(route('owner.demo.deny', r.id), {}, { preserveScroll: true });
};
const remove = (r) => {
  if (confirm(`Aanvraag van ${r.email} verwijderen?`)) router.delete(route('owner.demo.destroy', r.id), { preserveScroll: true });
};
const copied = ref(null);
const copy = async (r) => {
  try {
    await navigator.clipboard.writeText(r.link);
    copied.value = r.id;
    setTimeout(() => { copied.value = null; }, 2000);
  } catch (e) {
    prompt('Kopieer de link:', r.link);
  }
};
</script>

<template>
  <Head title="Demo-aanvragen" />
  <AppLayout>
    <template #breadcrumb>Eigenaar / <span class="breadcrumb-current">Demo-aanvragen</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Demo-aanvragen</h1>
        <p class="page-subtitle">De demo van {{ brand }} start alleen met een goedgekeurde link. {{ pending }} {{ pending === 1 ? 'aanvraag wacht' : 'aanvragen wachten' }} op je oordeel. Een goedgekeurde link werkt {{ validDays }} dagen en mag vaker worden gebruikt; opnieuw goedkeuren verlengt hem en mailt hem opnieuw.</p>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header">
        <div>
          <div class="card-title">Iemand direct uitnodigen</div>
          <div class="card-subtitle">Zonder aanvraag: de link gaat meteen per mail. Handig voor jezelf (open de link in een privévenster) of voor een klant aan de telefoon.</div>
        </div>
      </div>
      <div class="card-body">
        <form class="invite" @submit.prevent="invite">
          <div class="form-group">
            <label>E-mailadres</label>
            <input type="email" v-model="inviteForm.email" required maxlength="190">
            <div v-if="inviteForm.errors.email" class="field-error">{{ inviteForm.errors.email }}</div>
          </div>
          <div class="form-group">
            <label>Naam <span class="label-hint">(optioneel)</span></label>
            <input type="text" v-model="inviteForm.name" maxlength="120">
          </div>
          <div class="form-group">
            <label>Taal van de mail</label>
            <select v-model="inviteForm.locale"><option value="nl">Nederlands</option><option value="en">Engels</option><option value="pl">Pools</option></select>
          </div>
          <button class="btn btn-primary" :disabled="inviteForm.processing">Uitnodigen</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body-flush">
        <table v-if="requests.length" class="data-table">
          <thead><tr><th>Aangevraagd</th><th>Wie</th><th>Bericht</th><th>Status</th><th class="right">Gestart</th><th></th></tr></thead>
          <tbody>
            <tr v-for="r in requests" :key="r.id">
              <td class="nowrap">{{ r.created_label }}<div class="muted-sm">{{ (r.locale || '').toUpperCase() }}</div></td>
              <td><b>{{ r.name || r.email }}</b><div v-if="r.name" class="muted-sm">{{ r.email }}</div><div v-if="r.company" class="muted-sm">{{ r.company }}</div></td>
              <td class="msg">{{ r.message || '—' }}</td>
              <td>
                <span class="pill" :class="statusClass[r.status] || 'pill-draft'">{{ statusLabel[r.status] || r.status }}</span>
                <div v-if="r.status === 'approved'" class="muted-sm">{{ r.usable ? 'link werkt t/m ' + r.expires_label : 'link verlopen' }}</div>
              </td>
              <td class="right num">{{ r.starts }}<div v-if="r.last_started_label" class="muted-sm">{{ r.last_started_label }}</div></td>
              <td class="right actions">
                <template v-if="r.status === 'approved' && r.usable">
                  <button class="btn btn-secondary btn-sm" @click="copy(r)">{{ copied === r.id ? 'Gekopieerd' : 'Link kopiëren' }}</button>
                  <button class="btn btn-ghost btn-sm" @click="approve(r)">Opnieuw mailen</button>
                </template>
                <button v-else class="btn btn-primary btn-sm" @click="approve(r)">{{ r.status === 'approved' ? 'Verlengen en mailen' : 'Goedkeuren' }}</button>
                <button v-if="r.status !== 'denied'" class="btn btn-ghost btn-sm" @click="deny(r)">Afwijzen</button>
                <button class="btn btn-ghost btn-sm" style="color:var(--brand-dark);" @click="remove(r)">Verwijder</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="card-body muted">Nog geen aanvragen. Zodra iemand op de demopagina zijn e-mailadres achterlaat, krijg je een mail en staat de aanvraag hier.</div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.invite { display: grid; grid-template-columns: 2fr 1.5fr 1fr auto; gap: 12px; align-items: end; }
.invite .form-group { margin: 0; }
.msg { max-width: 320px; white-space: pre-wrap; font-size: 13px; color: var(--text-2); }
.nowrap { white-space: nowrap; }
.actions { white-space: nowrap; }
.actions .btn { margin-left: 4px; }
.muted { color: var(--text-3); }
.muted-sm { font-size: 12px; color: var(--text-3); }
@media (max-width: 900px) { .invite { grid-template-columns: 1fr; } }
</style>
