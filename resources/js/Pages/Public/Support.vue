<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const tips = [
  'Beschrijf zo concreet mogelijk wat er gebeurt.',
  'Vermeld om welke factuur of klant het gaat.',
  'Voeg eventueel een schermafbeelding toe.',
];
</script>

<template>
  <Head title="E-mailondersteuning" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap" style="text-align:center;">
        <span class="m-eyebrow">E-mailondersteuning</span>
        <h1 class="m-h1">Persoonlijke hulp<br>per e-mail</h1>
        <p class="m-lead" style="margin:16px auto 0;max-width:540px;">Echte mensen, geen chatbot. Stuur ons je vraag en we reageren binnen één werkdag.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap" style="max-width:640px;">
        <div class="m-card m-center" style="padding:44px;">
          <div style="font-size:40px;margin-bottom:12px;">✉️</div>
          <h2 class="m-h2" style="font-size:24px;margin-bottom:6px;">Mail ons rechtstreeks</h2>
          <a href="mailto:support@easyinvoice.nl" style="font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--brand);">support@easyinvoice.nl</a>
          <div style="margin-top:20px;">
            <a href="mailto:support@easyinvoice.nl" class="m-btn m-btn-primary m-btn-lg">Stuur een e-mail</a>
          </div>
          <p class="m-muted" style="margin-top:16px;font-size:14px;">Gemiddelde reactietijd: <strong>binnen 1 werkdag</strong></p>
        </div>

        <div class="m-card" style="margin-top:20px;">
          <h3 class="m-h3" style="margin-bottom:12px;">Zo helpen we je het snelst</h3>
          <div class="m-checks">
            <div v-for="t in tips" :key="t" class="m-check"><span class="tick">✓</span><span>{{ t }}</span></div>
          </div>
        </div>

        <p class="m-center m-muted" style="margin-top:24px;">
          Liever eerst zelf kijken? Bekijk het
          <Link :href="route('helpcentrum')" style="color:var(--brand);font-weight:600;">helpcentrum</Link> of de
          <Link :href="route('faq')" style="color:var(--brand);font-weight:600;">veelgestelde vragen</Link>.
        </p>
      </div>
    </section>
  </PublicLayout>
</template>
