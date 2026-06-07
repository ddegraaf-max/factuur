<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const features = [
  { icon: 'invoice', title: 'Facturen in seconden', text: 'Professionele facturen met je eigen huisstijl. BTW automatisch berekend per regel (21/9/0%).' },
  { icon: 'bell', title: 'Automatische herinneringen', text: 'Nooit meer achter je geld aan. Stuur herinneringen op het juiste moment, volledig automatisch.' },
  { icon: 'gavel', title: 'Incasso in fases', text: 'Van vriendelijke herinnering tot aanmaning en incasso — gefaseerd en netjes vastgelegd.' },
  { icon: 'chart', title: 'Live inzicht', text: 'Zie in één oogopslag wat openstaat, wat achterstallig is en wie je beste klanten zijn.' },
  { icon: 'hash', title: 'Eigen nummerreeks', text: 'Eigen factuurnummerreeks per jaar, volledig conform de eisen van de Belastingdienst.' },
  { icon: 'shield', title: 'Veilig met 2FA', text: 'Tweestapsverificatie en versleutelde opslag. Jouw administratie blijft van jou.' },
];

const stats = [
  { value: '€2,50', label: 'per maand, alles inbegrepen' },
  { value: '2 min.', label: 'van aanmelden tot eerste factuur' },
  { value: '99,9%', label: 'uptime, gehost in de EU' },
];

const steps = [
  { n: '1', title: 'Maak een account', text: 'Aanmelden duurt geen minuut. Geen creditcard nodig om te starten.' },
  { n: '2', title: 'Vul je gegevens in', text: 'Bedrijfsnaam, logo en btw-gegevens — daarna ben je klaar om te factureren.' },
  { n: '3', title: 'Verstuur je factuur', text: 'Stel op, verstuur per e-mail en volg de betaling live in je dashboard.' },
];
</script>

<template>
  <Head title="Nederlandse facturatie zonder gedoe" />
  <PublicLayout>
    <!-- HERO -->
    <section class="m-hero">
      <div class="m-wrap m-hero-inner">
        <span class="m-pill m-pill-brand">Nieuw · EASY-agent helpt je live mee 🤖</span>
        <h1 class="m-h1" style="margin-top:18px;">Facturatie voor het MKB,<br>zonder gedoe.</h1>
        <p class="m-lead">Facturen, btw, herinneringen en incasso — alles op één plek. Gemaakt voor Nederlandse ondernemers, voor maar <strong>€2,50 per maand</strong>.</p>
        <div class="m-hero-actions">
          <Link :href="route('register')" class="m-btn m-btn-primary m-btn-lg">Gratis starten</Link>
          <Link :href="route('functies')" class="m-btn m-btn-secondary m-btn-lg">Bekijk functies</Link>
        </div>
        <div class="m-hero-note">✓ Geen creditcard nodig &nbsp;·&nbsp; ✓ Maandelijks opzegbaar &nbsp;·&nbsp; ✓ Data in de EU</div>
      </div>
    </section>

    <!-- STATS -->
    <section class="m-section-tight">
      <div class="m-wrap">
        <div class="m-grid m-grid-3 stat-grid">
          <div v-for="s in stats" :key="s.label" class="stat">
            <div class="stat-value">{{ s.value }}</div>
            <div class="stat-label">{{ s.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="m-section">
      <div class="m-wrap">
        <div class="m-section-head">
          <span class="m-eyebrow">Alles wat je nodig hebt</span>
          <h2 class="m-h2">Eén tool voor je hele facturatie</h2>
          <p class="m-lead">Geen losse Excel-bestanden of dure boekhoudpakketten meer. EasyInvoice doet precies wat een ondernemer nodig heeft.</p>
        </div>
        <div class="m-grid m-grid-3">
          <div v-for="f in features" :key="f.title" class="m-card m-card-hover">
            <span class="m-ficon">
              <svg v-if="f.icon === 'invoice'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg>
              <svg v-else-if="f.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <svg v-else-if="f.icon === 'gavel'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 12.5-8 8a2.119 2.119 0 1 1-3-3l8-8"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
              <svg v-else-if="f.icon === 'chart'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
              <svg v-else-if="f.icon === 'hash'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <h3 class="m-h3">{{ f.title }}</h3>
            <p>{{ f.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="m-section" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
      <div class="m-wrap">
        <div class="m-section-head">
          <span class="m-eyebrow">Zo werkt het</span>
          <h2 class="m-h2">In drie stappen aan de slag</h2>
        </div>
        <div class="m-grid m-grid-3">
          <div v-for="s in steps" :key="s.n" class="step">
            <div class="step-n">{{ s.n }}</div>
            <h3 class="m-h3">{{ s.title }}</h3>
            <p class="m-muted">{{ s.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIAL TEASER -->
    <section class="m-section">
      <div class="m-wrap">
        <div class="m-card" style="max-width:760px;margin:0 auto;text-align:center;padding:44px;">
          <div style="font-size:22px;color:#F59E0B;letter-spacing:2px;">★★★★★</div>
          <p style="font-family:var(--font-display);font-size:24px;line-height:1.4;font-weight:600;margin:18px 0;letter-spacing:-0.01em;">
            "Eindelijk facturatie die gewoon werkt. Binnen een middag had ik mijn hele administratie omgezet."
          </p>
          <div class="m-muted">Sanne de Vries · ZZP'er, ontwerpstudio</div>
          <Link :href="route('reviews')" class="m-btn m-btn-ghost" style="margin-top:18px;">Lees meer reviews →</Link>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <div class="m-cta-band">
          <h2 class="m-h2">Klaar om sneller betaald te krijgen?</h2>
          <p>Start vandaag nog gratis. Geen verplichtingen, maandelijks opzegbaar.</p>
          <div class="m-hero-actions">
            <Link :href="route('register')" class="m-btn m-btn-white m-btn-lg">Gratis starten</Link>
            <Link :href="route('prijzen')" class="m-btn m-btn-lg" style="background:rgba(255,255,255,0.12);color:#fff;">Bekijk prijzen</Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.stat-grid { text-align: center; }
.stat-value { font-family: var(--font-display); font-weight: 800; font-size: 40px; letter-spacing: -0.02em; color: var(--brand); }
.stat-label { color: var(--ink-3); margin-top: 4px; }
.step { text-align: center; }
.step-n {
  width: 48px; height: 48px; border-radius: 100px; margin: 0 auto 16px;
  background: var(--brand); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 20px;
  display: flex; align-items: center; justify-content: center;
}
</style>
