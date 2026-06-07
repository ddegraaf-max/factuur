<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const values = [
  ['Eenvoud voorop', 'Facturatie hoeft niet ingewikkeld te zijn. We laten alles weg wat niet helpt.'],
  ['Eerlijke prijs', 'Eén lage prijs voor iedereen. Geen verborgen kosten, geen verkooppraatjes.'],
  ['Voor het MKB', 'Gebouwd voor zzp\'ers en kleine ondernemers, niet voor accountants.'],
  ['Privacy first', 'Jouw data is van jou en blijft veilig binnen de EU.'],
];

const team = [
  ['Daan de Graaf', 'Oprichter & product'],
  ['Eva Mulder', 'Engineering'],
  ['Joris Visser', 'Support & klantgeluk'],
];
</script>

<template>
  <Head title="Over ons" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Over ons</span>
        <h1 class="m-h1">Facturatie, terug<br>naar de basis</h1>
        <p class="m-lead">EasyInvoice is ontstaan uit frustratie: bestaande boekhoudsoftware is duur, log en overweldigend voor een gewone ondernemer. Dat kon beter.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap" style="max-width:760px;">
        <h2 class="m-h2" style="margin-bottom:16px;">Onze missie</h2>
        <p class="m-lead" style="margin-bottom:16px;">We willen dat elke Nederlandse ondernemer met een gerust hart kan factureren en sneller betaald krijgt — zonder een boekhouddiploma of een duur abonnement.</p>
        <p class="m-muted">Daarom bouwen we de eenvoudigste facturatietool van Nederland. Eén heldere prijs, alle functies die je écht nodig hebt, en ondersteuning van echte mensen.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <h2 class="m-h2 m-center" style="margin-bottom:32px;">Waar we voor staan</h2>
        <div class="m-grid m-grid-2">
          <div v-for="v in values" :key="v[0]" class="m-card">
            <h3 class="m-h3" style="margin-bottom:6px;">{{ v[0] }}</h3>
            <p class="m-muted">{{ v[1] }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <h2 class="m-h2 m-center" style="margin-bottom:32px;">Het team</h2>
        <div class="m-grid m-grid-3">
          <div v-for="t in team" :key="t[0]" class="m-card m-center">
            <div class="team-avatar">{{ t[0].split(' ').map(w => w[0]).join('').slice(0,2) }}</div>
            <h3 class="m-h3" style="font-size:17px;">{{ t[0] }}</h3>
            <p class="m-muted" style="font-size:14px;">{{ t[1] }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <div class="m-cta-band">
          <h2 class="m-h2">Werk met ons mee</h2>
          <p>We zijn altijd op zoek naar mensen die het MKB vooruit willen helpen.</p>
          <div class="m-hero-actions">
            <Link :href="route('vacatures')" class="m-btn m-btn-white m-btn-lg">Bekijk vacatures</Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.team-avatar {
  width: 64px; height: 64px; border-radius: 100px; margin: 0 auto 14px;
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
  color: #fff; font-weight: 700; font-size: 20px; font-family: var(--font-display);
  display: flex; align-items: center; justify-content: center;
}
</style>
