<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const included = [
  'Onbeperkt facturen versturen',
  'Onbeperkt klanten en producten',
  'BTW automatisch (21/9/0%)',
  'Eigen huisstijl & logo',
  'Automatische herinneringen',
  'Incassotraject in fases',
  'Creditfacturen',
  'Live dashboard & rapportages',
  'Tweestapsverificatie (2FA)',
  'Data veilig in de EU',
  'EASY-agent assistent',
  'E-mailondersteuning',
];

const faqs = [
  ['Zijn er extra kosten?', 'Nee. €2,50 per maand is alles-in. Geen transactiekosten, geen verborgen toeslagen.'],
  ['Kan ik maandelijks opzeggen?', 'Ja, je kunt op elk moment opzeggen. Geen jaarcontract, geen gedoe.'],
  ['Heb ik een creditcard nodig?', 'Niet om te starten. Je kunt gratis een account aanmaken en alles bekijken.'],
  ['Is btw inbegrepen in de prijs?', 'De genoemde prijs is exclusief btw.'],
];
</script>

<template>
  <Head title="Prijzen" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap" style="text-align:center;">
        <span class="m-eyebrow">Prijzen</span>
        <h1 class="m-h1">Eén eerlijke prijs.<br>Alles inbegrepen.</h1>
        <p class="m-lead" style="margin:18px auto 0;max-width:560px;">Geen ingewikkelde pakketten of upsells. Alle functies, voor iedereen, voor één vaste prijs.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap" style="max-width:560px;">
        <div class="price-card">
          <span class="m-pill m-pill-brand">Meest gekozen</span>
          <div class="price-name">EasyInvoice Compleet</div>
          <div class="price-amount">
            <span class="price-num">€2,50</span>
            <span class="price-per">/ maand <span class="m-muted">excl. btw</span></span>
          </div>
          <p class="m-muted" style="margin-bottom:24px;">Alles wat je nodig hebt om professioneel te factureren — zonder limieten.</p>
          <Link :href="route('register')" class="m-btn m-btn-primary m-btn-lg m-btn-block">Gratis starten</Link>
          <div class="m-checks" style="margin-top:28px;">
            <div v-for="i in included" :key="i" class="m-check">
              <span class="tick">✓</span><span>{{ i }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap" style="max-width:760px;">
        <h2 class="m-h2 m-center" style="margin-bottom:32px;">Veelgestelde vragen over prijzen</h2>
        <div style="display:grid;gap:14px;">
          <div v-for="f in faqs" :key="f[0]" class="m-card">
            <h3 class="m-h3" style="font-size:17px;margin-bottom:6px;">{{ f[0] }}</h3>
            <p class="m-muted">{{ f[1] }}</p>
          </div>
        </div>
        <p class="m-center m-muted" style="margin-top:28px;">
          Nog een vraag? <Link :href="route('contact')" style="color:var(--brand);font-weight:600;">Neem contact op →</Link>
        </p>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.price-card {
  background: var(--surface); border: 2px solid var(--brand-border);
  border-radius: var(--r-xl); padding: 36px; box-shadow: var(--shadow-lg); text-align: left;
}
.price-name { font-family: var(--font-display); font-weight: 700; font-size: 22px; margin: 16px 0 6px; }
.price-amount { display: flex; align-items: baseline; gap: 8px; margin-bottom: 14px; }
.price-num { font-family: var(--font-display); font-weight: 800; font-size: 52px; letter-spacing: -0.03em; }
.price-per { color: var(--ink-3); font-size: 16px; }
.m-btn-block { width: 100%; }
</style>
