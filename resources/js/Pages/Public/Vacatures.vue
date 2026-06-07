<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const jobs = [
  { title: 'Full-stack developer (Laravel / Vue)', type: 'Fulltime', loc: 'Amsterdam / Remote', text: 'Bouw mee aan het product dat duizenden ondernemers dagelijks gebruiken.' },
  { title: 'Customer Success Specialist', type: 'Fulltime', loc: 'Amsterdam', text: 'Help ondernemers het maximale uit EasyInvoice te halen.' },
  { title: 'Product Designer', type: 'Parttime', loc: 'Remote', text: 'Ontwerp eenvoudige, mooie ervaringen voor het MKB.' },
  { title: 'Growth Marketeer', type: 'Fulltime', loc: 'Amsterdam / Remote', text: 'Breng EasyInvoice bij meer Nederlandse ondernemers.' },
];

const perks = [
  ['Flexibel werken', 'Werk vanuit huis of ons kantoor in Amsterdam.'],
  ['Klein, hecht team', 'Korte lijnen, veel impact, weinig vergaderingen.'],
  ['Echte impact', 'Je werk helpt ondernemers direct vooruit.'],
  ['Goede voorwaarden', 'Marktconform salaris en ruimte om te groeien.'],
];
</script>

<template>
  <Head title="Vacatures" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Vacatures</span>
        <h1 class="m-h1">Bouw mee aan het<br>MKB van morgen</h1>
        <p class="m-lead">We zijn een klein team met grote ambities. Help je mee om factureren voor heel ondernemend Nederland makkelijk te maken?</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap">
        <h2 class="m-h2" style="margin-bottom:20px;">Waarom EasyInvoice</h2>
        <div class="m-grid m-grid-4">
          <div v-for="p in perks" :key="p[0]" class="m-card">
            <h3 class="m-h3" style="font-size:16px;margin-bottom:6px;">{{ p[0] }}</h3>
            <p class="m-muted" style="font-size:14px;">{{ p[1] }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap" style="max-width:820px;">
        <h2 class="m-h2" style="margin-bottom:20px;">Open posities</h2>
        <div style="display:grid;gap:14px;">
          <a v-for="j in jobs" :key="j.title" href="mailto:jobs@easyinvoice.nl" class="m-card m-card-hover job">
            <div>
              <h3 class="m-h3" style="font-size:18px;margin-bottom:8px;">{{ j.title }}</h3>
              <p class="m-muted" style="font-size:14px;">{{ j.text }}</p>
              <div class="job-tags">
                <span class="m-pill">{{ j.type }}</span>
                <span class="m-pill">{{ j.loc }}</span>
              </div>
            </div>
            <span class="job-arrow">→</span>
          </a>
        </div>
        <p class="m-muted" style="margin-top:24px;">
          Geen passende vacature? Stuur een open sollicitatie naar
          <a href="mailto:jobs@easyinvoice.nl" style="color:var(--brand);font-weight:600;">jobs@easyinvoice.nl</a>.
        </p>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.job { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.job-tags { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
.job-arrow { font-size: 24px; color: var(--brand); flex-shrink: 0; }
</style>
