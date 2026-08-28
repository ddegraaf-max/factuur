<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ companies: Array });

const accessLabel = { exempt: 'Vrijgesteld', active: 'Abonnement', trial: 'Proefperiode', expired: 'Verlopen', cancelled: 'Opgezegd' };
const accessClass = { exempt: 'pill-paid', active: 'pill-paid', trial: 'pill-sent', expired: 'pill-overdue', cancelled: 'pill-draft' };
const expired = computed(() => props.companies.filter(c => c.deletable && !c.is_demo && c.access === 'expired').length);

const remove = (c) => {
  const typed = prompt(`Je verwijdert "${c.name}" met ${c.invoices} facturen, ${c.quotes} offertes, ${c.customers} klanten en ${c.users} gebruiker(s). Dit is onomkeerbaar.\n\nTyp ter bevestiging de naam van de administratie:`);
  if (typed === null) return;
  router.delete(route('owner.companies.destroy', c.id), { data: { confirm: typed }, preserveScroll: true });
};
</script>

<template>
  <Head title="Administraties" />
  <AppLayout>
    <template #breadcrumb>Eigenaar / <span class="breadcrumb-current">Administraties</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">Administraties</h1>
        <p class="page-subtitle">{{ companies.length }} administraties · {{ expired }} verlopen proefaccount{{ expired === 1 ? '' : 's' }} die je kunt opruimen. Verwijderen is definitief: documenten, klanten, instellingen en gebruikers gaan mee.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-body-flush">
        <table class="data-table">
          <thead><tr><th>#</th><th>Administratie</th><th>Eigenaar</th><th>Status</th><th class="right">Facturen</th><th class="right">Offertes</th><th class="right">Klanten</th><th>Aangemaakt</th><th>Laatst actief</th><th></th></tr></thead>
          <tbody>
            <tr v-for="c in companies" :key="c.id">
              <td class="num">{{ c.id }}</td>
              <td><b>{{ c.name }}</b><span v-if="c.is_demo" class="pill pill-draft" style="margin-left:8px;">demo</span></td>
              <td><span v-if="c.owner_email">{{ c.owner_email }}</span><span v-else class="muted">— geen gebruiker</span><div v-if="c.users > 1" class="muted-sm">+{{ c.users - 1 }} teamlid/-leden</div></td>
              <td><span class="pill" :class="accessClass[c.access] || 'pill-draft'">{{ accessLabel[c.access] || c.access }}</span><div v-if="c.trial_ends_label && c.access !== 'exempt'" class="muted-sm">proef t/m {{ c.trial_ends_label }}</div></td>
              <td class="right num">{{ c.invoices }}</td>
              <td class="right num">{{ c.quotes }}</td>
              <td class="right num">{{ c.customers }}</td>
              <td class="muted-sm">{{ c.created_label }}</td>
              <td class="muted-sm">{{ c.last_seen_label || '—' }}</td>
              <td class="right"><button v-if="c.deletable" type="button" class="btn btn-secondary btn-sm btn-danger-ghost" @click="remove(c)">Verwijderen</button><span v-else class="muted-sm">eigen</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.muted { color: var(--text-4); }
.muted-sm { font-size: 12px; color: var(--text-4); }
.btn-danger-ghost { color: #B91C1C; border-color: #FECACA; }
.btn-danger-ghost:hover { background: #FEF2F2; }
</style>
