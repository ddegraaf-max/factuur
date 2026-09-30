<script setup>
/*
 * Het rekeningschema.
 *
 * De lijst is een boom: hoofdrubriek › rubriek › rekening. Die indeling staat
 * er niet voor de sier — hij is de indeling van de balans en de
 * winst-en-verliesrekening. Een platte lijst van tweehonderd nummers zegt een
 * ondernemer niets; onder "Vorderingen" staan wordt het meteen duidelijk.
 *
 * Bijkiezen gaat uit de officiële RGS-lijst en niet met een eigen nummer: dan
 * blijft de auditfile bruikbaar voor de accountant.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  accounts: Array,
  available: Array,
  journals: Array,
});

const zoek = ref('');
const alleenGebruikt = ref(false);
const toevoegenOpen = ref(false);
const zoekNieuw = ref('');

const zichtbaar = computed(() => {
  const q = zoek.value.trim().toLowerCase();
  let rijen = props.accounts;

  if (alleenGebruikt.value) {
    rijen = rijen.filter(a => a.used > 0 || !a.postable);
  }

  if (!q) return rijen;

  // Zoeken op nummer, naam of RGS-code. De rubrieken erboven blijven staan,
  // anders zweeft een treffer los in het niets.
  const treffers = new Set();
  rijen.forEach(a => {
    if ((a.number || '').includes(q)
      || (a.name || '').toLowerCase().includes(q)
      || (a.rgs_code || '').toLowerCase().includes(q)) {
      treffers.add(a.id);
    }
  });

  return rijen.filter(a => treffers.has(a.id) || (!a.postable && heeftTrefferOnder(a, treffers, rijen)));
});

// Een rubriek blijft in beeld als er onder hem een treffer staat. Rubrieken
// staan altijd vóór hun rekeningen in de lijst, dus we kijken vooruit tot de
// volgende rubriek van hetzelfde of een hoger niveau.
function heeftTrefferOnder(rubriek, treffers, rijen) {
  const start = rijen.indexOf(rubriek);
  for (let i = start + 1; i < rijen.length; i++) {
    if (rijen[i].level <= rubriek.level) break;
    if (treffers.has(rijen[i].id)) return true;
  }
  return false;
}

const kandidaten = computed(() => {
  const q = zoekNieuw.value.trim().toLowerCase();
  if (q.length < 2) return [];
  return props.available.filter(a =>
    (a.number || '').includes(q) || (a.name || '').toLowerCase().includes(q)
  ).slice(0, 40);
});

const toevoegen = useForm({ rgs_code: '' });
const voegToe = (code) => {
  toevoegen.rgs_code = code;
  toevoegen.post(route('ledger.accounts.store'), {
    preserveScroll: true,
    onSuccess: () => { zoekNieuw.value = ''; toevoegenOpen.value = false; },
  });
};

const bewerken = ref(null);
const naam = useForm({ name: '' });
const startBewerken = (a) => { bewerken.value = a.id; naam.name = a.name; };
const bewaar = (a) => {
  naam.patch(route('ledger.accounts.update', a.id), {
    preserveScroll: true,
    onSuccess: () => { bewerken.value = null; },
  });
};

const zetActief = (a, actief) => {
  useForm({ active: actief }).patch(route('ledger.accounts.update', a.id), { preserveScroll: true });
};

const herbouw = useForm({});
</script>

<template>
  <Head :title="$t('Rekeningschema')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Grootboek') }} / <span class="breadcrumb-current">{{ $t('Rekeningschema') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Rekeningschema') }}</h1>
        <p class="page-subtitle">
          {{ $t('De officiële rekeningen van het Referentie Grootboekschema (RGS 3.3). Uw accountant kent deze nummers.') }}
        </p>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary btn-sm" @click="toevoegenOpen = !toevoegenOpen">
          {{ $t('Rekening toevoegen') }}
        </button>
        <Link :href="route('ledger.entries')" class="btn btn-secondary btn-sm">{{ $t('Journaal') }}</Link>
        <Link :href="route('ledger.trial')" class="btn btn-primary btn-sm">{{ $t('Proefbalans') }}</Link>
      </div>
    </div>

    <div v-if="toevoegenOpen" class="card" style="margin-bottom:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Rekening bijkiezen uit RGS') }}</div></div>
      <div class="card-body">
        <p class="hint">
          {{ $t('Zoek op naam of nummer. U krijgt de officiële code en het officiële nummer mee, zodat de auditfile blijft kloppen.') }}
        </p>
        <input type="search" v-model="zoekNieuw" :placeholder="$t('Bijvoorbeeld: telefoon, verzekering, 4206')">

        <div v-if="zoekNieuw.trim().length >= 2 && !kandidaten.length" class="hint" style="margin-top:12px;">
          {{ $t('Niets gevonden. Alle rekeningen die hierop lijken staan al in uw schema.') }}
        </div>

        <table v-if="kandidaten.length" class="data-table" style="margin-top:12px;">
          <thead>
            <tr>
              <th>{{ $t('Nummer') }}</th>
              <th>{{ $t('Naam') }}</th>
              <th>{{ $t('Soort') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="k in kandidaten" :key="k.rgs_code">
              <td class="num">{{ k.number }}</td>
              <td class="cell-primary">{{ k.name }}</td>
              <td>{{ k.statement === 'balans' ? $t('Balans') : $t('Resultaat') }}</td>
              <td class="right">
                <button class="btn btn-secondary btn-sm" :disabled="toevoegen.processing" @click="voegToe(k.rgs_code)">
                  {{ $t('Toevoegen') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="filter-bar">
      <input type="search" class="filter-search" v-model="zoek" :placeholder="$t('Zoek op nummer, naam of RGS-code')">
      <button class="filter-chip" :class="{ active: alleenGebruikt }" @click="alleenGebruikt = !alleenGebruikt">
        {{ $t('Alleen gebruikte rekeningen') }}
      </button>
    </div>

    <div class="card">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:120px;">{{ $t('Nummer') }}</th>
            <th>{{ $t('Naam') }}</th>
            <th style="width:110px;">{{ $t('RGS') }}</th>
            <th style="width:60px;" class="right">{{ $t('D/C') }}</th>
            <th style="width:90px;" class="right">{{ $t('Boekingen') }}</th>
            <th style="width:180px;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in zichtbaar" :key="a.id"
              :class="{ 'is-rubriek': !a.postable, 'is-uit': !a.active }">
            <td class="num" :style="{ paddingLeft: (8 + (a.level - 2) * 14) + 'px' }">{{ a.number }}</td>
            <td class="cell-primary">
              <span v-if="bewerken !== a.id">{{ a.name }}</span>
              <span v-else class="naam-edit">
                <input type="text" v-model="naam.name" @keyup.enter="bewaar(a)">
                <button class="btn btn-primary btn-sm" @click="bewaar(a)">{{ $t('Bewaren') }}</button>
                <button class="btn btn-secondary btn-sm" @click="bewerken = null">{{ $t('Annuleren') }}</button>
              </span>
            </td>
            <td class="rgs">{{ a.rgs_code }}</td>
            <td class="right">{{ a.postable ? a.side : '' }}</td>
            <td class="right num">{{ a.postable ? a.used : '' }}</td>
            <td class="right acties">
              <template v-if="a.postable">
                <Link :href="route('ledger.card', a.id)" class="btn btn-secondary btn-sm">{{ $t('Kaart') }}</Link>
                <button v-if="bewerken !== a.id" class="btn-plain" @click="startBewerken(a)">{{ $t('Naam') }}</button>
                <button v-if="a.active && !a.is_system && a.used === 0" class="btn-plain" @click="zetActief(a, false)">
                  {{ $t('Uitzetten') }}
                </button>
                <button v-if="!a.active" class="btn-plain" @click="zetActief(a, true)">{{ $t('Aanzetten') }}</button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!zichtbaar.length" class="card-empty">
        {{ $t('Geen rekeningen gevonden.') }}
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Dagboeken') }}</div></div>
      <div class="card-body">
        <p class="hint">
          {{ $t('Elk boekstuknummer begint met de code van zijn dagboek. Zo ziet u aan VRK 2026-0001 meteen dat het een verkoop is.') }}
        </p>
        <table class="data-table">
          <tbody>
            <tr v-for="j in journals" :key="j.id">
              <td class="num" style="width:80px;">{{ j.code }}</td>
              <td class="cell-primary">{{ j.name }}</td>
              <td>{{ j.kind }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header"><div class="card-title">{{ $t('Opnieuw boeken') }}</div></div>
      <div class="card-body">
        <p class="hint">
          {{ $t('Loopt het grootboek achter op de facturen — bijvoorbeeld na een import — dan boekt dit alles na wat nog niet in het grootboek staat. Wat er al in staat blijft ongemoeid.') }}
        </p>
        <button class="btn btn-secondary btn-sm" :disabled="herbouw.processing"
                @click="herbouw.post(route('ledger.rebuild'), { preserveScroll: true })">
          {{ herbouw.processing ? $t('Bezig…') : $t('Facturen naboeken') }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.hint { font-size: 13px; color: var(--text-3); margin: 0 0 12px; line-height: 1.55; }
.rgs { font-family: var(--font-mono); font-size: 11.5px; color: var(--text-3); }

/* Een rubriek is geen rekening: hij draagt de indeling. Grijs en vet, zodat het
   oog de boom ziet zonder dat er lijnen bij hoeven. */
.is-rubriek { background: var(--surface-2); cursor: default; }
.is-rubriek td { font-weight: 600; }
.is-uit td { opacity: 0.45; }

.naam-edit { display: flex; gap: 6px; align-items: center; }
.naam-edit input { max-width: 280px; }

.acties { white-space: nowrap; }
.btn-plain {
  background: none; border: 0; padding: 2px 6px; margin-left: 2px;
  font-size: 12px; color: var(--text-3); cursor: pointer; text-decoration: underline;
}
.btn-plain:hover { color: var(--brand); }
</style>
