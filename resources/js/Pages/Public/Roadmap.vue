<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const columns = [
  {
    title: 'In onderzoek', key: 'idea',
    items: [
      ['Koppeling met de bank', 'Betalingen automatisch matchen via een PSD2-koppeling.'],
      ['Offertes', 'Offertes maken en met één klik omzetten naar een factuur.'],
      ['Meerdere gebruikers', 'Collega\'s toegang geven met eigen rechten.'],
    ],
  },
  {
    title: 'In ontwikkeling', key: 'doing',
    items: [
      ['UBL / e-facturatie', 'Facturen versturen in UBL-formaat (Peppol).'],
      ['Terugkerende facturen', 'Automatisch periodiek factureren voor abonnementen.'],
      ['Exporteren naar boekhouder', 'Eenvoudige export voor je accountant.'],
    ],
  },
  {
    title: 'Onlangs geleverd', key: 'done',
    items: [
      ['EASY-agent', 'Slimme assistent die met je meedenkt.'],
      ['Incasso in fases', 'Gefaseerd incassotraject.'],
      ['Creditfacturen', 'Creditnota\'s en deelbetalingen.'],
    ],
  },
];
</script>

<template>
  <Head title="Roadmap" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Roadmap</span>
        <h1 class="m-h1">Waar we aan werken</h1>
        <p class="m-lead">EasyInvoice wordt gebouwd samen met onze gebruikers. Dit staat op de planning — heb je een wens? Laat het ons weten.</p>
        <div class="m-hero-actions">
          <Link :href="route('contact')" class="m-btn m-btn-primary m-btn-lg">Deel je idee</Link>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap">
        <div class="m-grid m-grid-3">
          <div v-for="col in columns" :key="col.key" class="road-col">
            <div class="road-head" :class="`road-${col.key}`">
              <span class="road-dot"></span> {{ col.title }}
            </div>
            <div style="display:grid;gap:12px;">
              <div v-for="item in col.items" :key="item[0]" class="m-card" style="padding:18px;">
                <h3 class="m-h3" style="font-size:16px;margin-bottom:4px;">{{ item[0] }}</h3>
                <p class="m-muted" style="font-size:14px;">{{ item[1] }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.road-head { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; margin-bottom: 16px; }
.road-dot { width: 10px; height: 10px; border-radius: 100px; background: currentColor; }
.road-idea { color: var(--ink-4); }
.road-doing { color: var(--brand); }
.road-done { color: var(--success); }
</style>
