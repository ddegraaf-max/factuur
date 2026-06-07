<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const facts = [
  ['Opgericht', '2024'],
  ['Hoofdkantoor', 'Amsterdam, NL'],
  ['Klanten', 'Honderden MKB\'ers'],
  ['Prijs', '€2,50 / maand'],
];

const news = [
  ['15 mei 2026', 'EasyInvoice lanceert EASY-agent, een slimme assistent voor ondernemers.'],
  ['2 april 2026', 'Nieuw gefaseerd incassotraject helpt ondernemers sneller betaald te krijgen.'],
  ['10 januari 2026', 'EasyInvoice bereikt mijlpaal van honderden actieve ondernemers.'],
];
</script>

<template>
  <Head title="Pers" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap m-hero-inner">
        <span class="m-eyebrow">Pers</span>
        <h1 class="m-h1">Perskit & nieuws</h1>
        <p class="m-lead">Schrijf je over EasyInvoice? Hier vind je de feiten, ons logo en de laatste aankondigingen. Voor persvragen mail je <a href="mailto:pers@easyinvoice.nl" style="color:var(--brand);font-weight:600;">pers@easyinvoice.nl</a>.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap">
        <h2 class="m-h2" style="margin-bottom:24px;">In het kort</h2>
        <div class="m-grid m-grid-4">
          <div v-for="f in facts" :key="f[0]" class="m-card m-center">
            <div style="font-family:var(--font-display);font-weight:800;font-size:26px;color:var(--brand);">{{ f[1] }}</div>
            <div class="m-muted" style="font-size:14px;margin-top:4px;">{{ f[0] }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <h2 class="m-h2" style="margin-bottom:24px;">Logo & merk</h2>
        <div class="m-grid m-grid-2">
          <div class="m-card" style="display:flex;align-items:center;justify-content:center;padding:48px;background:var(--surface);">
            <div style="display:inline-flex;align-items:center;gap:12px;font-weight:700;">
              <span style="width:48px;height:48px;border-radius:12px;background:var(--brand);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 32 32" width="28" height="28" fill="none"><circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/><path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/></svg>
              </span>
              <span style="font-family:var(--font-display);font-size:24px;">EasyInvoice</span>
            </div>
          </div>
          <div class="m-card" style="display:flex;align-items:center;justify-content:center;padding:48px;background:var(--ink);">
            <div style="display:inline-flex;align-items:center;gap:12px;font-weight:700;color:#fff;">
              <span style="width:48px;height:48px;border-radius:12px;background:var(--brand);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 32 32" width="28" height="28" fill="none"><circle cx="16" cy="14" r="11" stroke="currentColor" stroke-width="2.5"/><path d="M11 10h10v3h-7v2h6v3h-6v2h7v3H11z" fill="currentColor"/></svg>
              </span>
              <span style="font-family:var(--font-display);font-size:24px;">EasyInvoice</span>
            </div>
          </div>
        </div>
        <p class="m-muted" style="margin-top:14px;font-size:14px;">Gebruik het logo niet vervormd of in andere kleuren. De merkkleur is <strong style="color:var(--brand);">#E8231F</strong>.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap" style="max-width:760px;">
        <h2 class="m-h2" style="margin-bottom:20px;">Persberichten</h2>
        <div style="display:grid;gap:12px;">
          <div v-for="n in news" :key="n[0]" class="m-card" style="display:flex;gap:16px;align-items:baseline;">
            <span class="m-muted" style="font-size:13px;white-space:nowrap;min-width:110px;">{{ n[0] }}</span>
            <span>{{ n[1] }}</span>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
