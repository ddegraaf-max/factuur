<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const groups = [
  {
    title: 'Factureren',
    items: [
      ['Professionele facturen', 'Stel facturen op met je eigen logo, kleuren en huisstijl. Meerdere sjablonen beschikbaar.'],
      ['BTW per regel', 'Automatische btw-berekening per factuurregel — 21%, 9% of 0%. Ook btw-verlegd.'],
      ['Eigen nummerreeks', 'Een doorlopende factuurnummerreeks per jaar, conform de Belastingdienst.'],
      ['Creditfacturen', 'Maak in één klik een creditnota op een bestaande factuur.'],
      ['PDF & e-mail', 'Verstuur facturen direct als PDF per e-mail naar je klant.'],
      ['Bijlagen', 'Voeg werkbonnen, urenstaten of bonnen toe aan je factuur.'],
    ],
  },
  {
    title: 'Betaald krijgen',
    items: [
      ['Automatische herinneringen', 'Stel herinneringen in die automatisch worden verstuurd op het juiste moment.'],
      ['Incasso in fases', 'Gefaseerd traject van herinnering tot aanmaning en incasso.'],
      ['Betalingen registreren', 'Leg deelbetalingen en volledige betalingen vast en zie de status live.'],
      ['Openstaand & achterstallig', 'Altijd inzicht in wat er nog binnen moet komen.'],
    ],
  },
  {
    title: 'Inzicht & beheer',
    items: [
      ['Live dashboard', 'Omzet, openstaand en achterstallig in één overzicht.'],
      ['Klantomzet', 'Zie wie je beste klanten zijn en waar je omzet vandaan komt.'],
      ['Klanten & producten', 'Beheer je klanten en een productcatalogus voor snel factureren.'],
      ['EASY-agent', 'Een slimme assistent die meedenkt terwijl je werkt.'],
    ],
  },
  {
    title: 'Veiligheid',
    items: [
      ['Tweestapsverificatie', 'Beveilig je account met 2FA via een authenticator-app.'],
      ['Data in de EU', 'Je gegevens worden veilig opgeslagen binnen de Europese Unie.'],
      ['Back-ups', 'Automatische back-ups zodat je nooit iets kwijtraakt.'],
    ],
  },
];
</script>

<template>
  <Head title="Functies" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:48px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Functies</span>
        <h1 class="m-h1">Alles om als ondernemer<br>professioneel te factureren</h1>
        <p class="m-lead">Van de eerste factuur tot de laatste betaalde euro. EasyInvoice dekt het hele proces — zonder onnodige toeters en bellen.</p>
        <div class="m-hero-actions">
          <Link :href="route('register')" class="m-btn m-btn-primary m-btn-lg">Gratis starten</Link>
          <Link :href="route('prijzen')" class="m-btn m-btn-secondary m-btn-lg">Bekijk prijzen</Link>
        </div>
      </div>
    </section>

    <section class="m-section">
      <div class="m-wrap" style="display:grid;gap:56px;">
        <div v-for="g in groups" :key="g.title">
          <h2 class="m-h2" style="margin-bottom:24px;">{{ g.title }}</h2>
          <div class="m-grid m-grid-2">
            <div v-for="item in g.items" :key="item[0]" class="m-card">
              <div class="m-check" style="margin:0;">
                <span class="tick">✓</span>
                <div>
                  <h3 class="m-h3" style="font-size:17px;margin-bottom:4px;">{{ item[0] }}</h3>
                  <p class="m-muted" style="font-size:14px;">{{ item[1] }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <div class="m-cta-band">
          <h2 class="m-h2">Probeer het zelf</h2>
          <p>Maak een account en verstuur binnen twee minuten je eerste factuur.</p>
          <div class="m-hero-actions">
            <Link :href="route('register')" class="m-btn m-btn-white m-btn-lg">Gratis starten</Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
