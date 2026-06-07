<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const categories = [
  { icon: '🚀', title: 'Aan de slag', text: 'Account aanmaken, bedrijfsgegevens invullen en je eerste factuur versturen.', articles: ['Een account aanmaken', 'Je bedrijfsgegevens instellen', 'Je eerste factuur maken'] },
  { icon: '🧾', title: 'Facturen', text: 'Facturen opstellen, btw instellen, creditnota\'s en nummering.', articles: ['BTW per regel instellen', 'Een creditfactuur maken', 'Factuurnummering aanpassen'] },
  { icon: '💸', title: 'Betalingen & incasso', text: 'Betalingen registreren, herinneringen en het incassotraject.', articles: ['Een betaling registreren', 'Automatische herinneringen', 'Het incassotraject'] },
  { icon: '👥', title: 'Klanten & producten', text: 'Klantgegevens en je productcatalogus beheren.', articles: ['Een klant toevoegen', 'Producten beheren'] },
  { icon: '🎨', title: 'Huisstijl', text: 'Logo, kleuren en factuursjablonen aanpassen.', articles: ['Je logo uploaden', 'Een sjabloon kiezen'] },
  { icon: '🔒', title: 'Account & beveiliging', text: 'Wachtwoord, tweestapsverificatie en je gegevens.', articles: ['2FA instellen', 'Je wachtwoord wijzigen'] },
];
</script>

<template>
  <Head title="Helpcentrum" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap" style="text-align:center;">
        <span class="m-eyebrow">Helpcentrum</span>
        <h1 class="m-h1">Hoe kunnen we helpen?</h1>
        <p class="m-lead" style="margin:16px auto 0;max-width:560px;">Vind snel antwoord op je vraag, of neem contact op met ons team.</p>
        <div style="max-width:520px;margin:28px auto 0;">
          <div class="help-search">
            <span>🔍</span>
            <input type="text" placeholder="Zoek in het helpcentrum…" />
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap">
        <div class="m-grid m-grid-3">
          <div v-for="c in categories" :key="c.title" class="m-card m-card-hover">
            <div style="font-size:28px;margin-bottom:12px;">{{ c.icon }}</div>
            <h3 class="m-h3" style="margin-bottom:6px;">{{ c.title }}</h3>
            <p class="m-muted" style="font-size:14px;margin-bottom:14px;">{{ c.text }}</p>
            <div style="display:grid;gap:8px;">
              <Link v-for="a in c.articles" :key="a" :href="route('faq')" class="help-link">{{ a }} →</Link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <div class="m-card m-center" style="padding:40px;">
          <h2 class="m-h2" style="font-size:24px;margin-bottom:8px;">Niet gevonden wat je zocht?</h2>
          <p class="m-muted" style="margin-bottom:20px;">Ons team helpt je graag persoonlijk verder.</p>
          <div class="m-hero-actions" style="justify-content:center;">
            <Link :href="route('contact')" class="m-btn m-btn-primary">Neem contact op</Link>
            <Link :href="route('support')" class="m-btn m-btn-secondary">E-mailondersteuning</Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.help-search { display: flex; align-items: center; gap: 10px; background: var(--surface); border: 1px solid var(--border-strong); border-radius: var(--r); padding: 0 16px; box-shadow: var(--shadow-sm); }
.help-search input { flex: 1; border: none; outline: none; background: none; padding: 14px 0; font-size: 15px; font-family: inherit; color: var(--ink); }
.help-link { font-size: 14px; color: var(--ink-2); }
.help-link:hover { color: var(--brand); }
</style>
