<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const releases = [
  {
    date: '15 mei 2026', version: 'v2.4', tag: 'Nieuw',
    title: 'EASY-agent: je slimme assistent',
    items: [
      'Stel vragen over je administratie en krijg direct antwoord.',
      'Suggesties terwijl je een factuur opstelt.',
      'Snelkoppelingen naar veelgebruikte acties.',
    ],
  },
  {
    date: '2 april 2026', version: 'v2.3', tag: 'Verbetering',
    title: 'Incassotraject in fases',
    items: [
      'Gefaseerd traject: herinnering → aanmaning → incasso.',
      'Per fase de status en datum bijhouden.',
      'Overzichtspagina met alle lopende trajecten.',
    ],
  },
  {
    date: '10 maart 2026', version: 'v2.2', tag: 'Nieuw',
    title: 'Creditfacturen & deelbetalingen',
    items: [
      'Maak in één klik een creditnota op een bestaande factuur.',
      'Registreer deelbetalingen met automatische statusupdate.',
    ],
  },
  {
    date: '5 februari 2026', version: 'v2.1', tag: 'Verbetering',
    title: 'Nieuwe factuursjablonen',
    items: [
      'Modern sjabloon met meer ruimte voor je huisstijl.',
      'Eigen accentkleur instelbaar.',
    ],
  },
];
</script>

<template>
  <Head title="Wat is nieuw" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Wat is nieuw</span>
        <h1 class="m-h1">Elke maand beter</h1>
        <p class="m-lead">We verbeteren EasyInvoice continu op basis van wat ondernemers nodig hebben. Hier zie je de laatste updates.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap" style="max-width:760px;">
        <div class="timeline">
          <article v-for="r in releases" :key="r.version" class="tl-item">
            <div class="tl-dot"></div>
            <div class="tl-meta">
              <span class="m-pill" :class="r.tag === 'Nieuw' ? 'm-pill-brand' : 'm-pill-green'">{{ r.tag }}</span>
              <span class="m-muted">{{ r.date }} · {{ r.version }}</span>
            </div>
            <h3 class="m-h3" style="margin:8px 0 10px;">{{ r.title }}</h3>
            <ul class="tl-list">
              <li v-for="i in r.items" :key="i">{{ i }}</li>
            </ul>
          </article>
        </div>
        <p class="m-muted" style="margin-top:32px;">
          Benieuwd wat er nog komt? Bekijk de <Link :href="route('roadmap')" style="color:var(--brand);font-weight:600;">roadmap →</Link>
        </p>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.timeline { position: relative; padding-left: 28px; }
.timeline::before { content: ''; position: absolute; left: 7px; top: 6px; bottom: 6px; width: 2px; background: var(--border); }
.tl-item { position: relative; padding-bottom: 36px; }
.tl-item:last-child { padding-bottom: 0; }
.tl-dot { position: absolute; left: -28px; top: 4px; width: 16px; height: 16px; border-radius: 100px; background: var(--brand); border: 3px solid var(--bg); }
.tl-meta { display: flex; align-items: center; gap: 12px; font-size: 13px; }
.tl-list { margin: 0; padding-left: 18px; color: var(--ink-3); display: grid; gap: 6px; }
</style>
