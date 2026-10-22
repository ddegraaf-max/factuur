<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * De openbare schil van Fachora: nachtblauwe kop met het woordmerk en de
 * taalkeuze, de inhoud in één smalle kolom (telefoon eerst), een lichte voet.
 * Flash en foutmeldingen uit de sessie staan hier, zodat de pagina's zelf
 * alleen hun eigen formulierfouten hoeven te tonen.
 *
 * Gedeelde stijlen (.fa-*) staan hieronder zonder `scoped`: elke Fachora-pagina
 * importeert deze schil, dus de klassen zijn overal beschikbaar.
 */
const props = defineProps({
  title: { type: String, default: '' },
  narrow: { type: Boolean, default: false },
  noindex: { type: Boolean, default: false },
});

const page = usePage();
const brand = computed(() => page.props.brand || {});
const market = computed(() => page.props.market || {});
const locale = computed(() => page.props.locale || market.value.locale || 'pl');
const locales = computed(() => market.value.locales || []);
const flash = computed(() => page.props.flash?.flash || null);
const error = computed(() => page.props.flash?.error || null);
const year = new Date().getFullYear();
</script>

<template>
  <Head :title="title">
    <meta v-if="noindex" name="robots" content="noindex, nofollow">
  </Head>
  <div class="fa-shell">
    <header class="fa-top">
      <div class="fa-top-inner">
        <a href="/" class="fa-logo" :title="brand.tagline || brand.name">
          <img v-if="brand.wordmark_dark || brand.wordmark" :src="brand.wordmark_dark || brand.wordmark" :alt="brand.name || 'Fachora'">
          <span v-else>{{ brand.name || 'Fachora' }}</span>
        </a>
        <nav v-if="locales.length > 1" class="fa-lang" :aria-label="$t('Taal')">
          <a v-for="loc in locales" :key="loc" :href="route('lang', loc)" :class="{ 'is-active': loc === locale }" :hreflang="loc" rel="nofollow">{{ loc.toUpperCase() }}</a>
        </nav>
      </div>
    </header>

    <main class="fa-main" :class="{ 'fa-main--narrow': narrow }">
      <div v-if="flash" class="fa-flash" role="status">{{ flash }}</div>
      <div v-if="error" class="fa-error" role="alert">{{ error }}</div>
      <slot />
    </main>

    <footer class="fa-foot">
      <div class="fa-foot-inner">
        <div class="fa-foot-links">
          <a :href="route('fachora.how')">{{ $t('Hoe het werkt') }}</a>
          <a :href="route('pl.regulamin')">{{ $t('Regulamin') }}</a>
          <a :href="route('pl.prywatnosc')">{{ $t('Privacy') }}</a>
          <a v-if="brand.email" :href="'mailto:' + brand.email">{{ brand.email }}</a>
        </div>
        <div class="fa-foot-note">{{ brand.tagline }} · {{ $t('Fachora is een dienst van Creditline B.V.') }} · {{ year }}</div>
      </div>
    </footer>
  </div>
</template>

<style>
/* ===== Fachora, openbare pagina's. Prefix .fa- zodat niets botst met de app. ===== */
.fa-shell { min-height: 100vh; display: flex; flex-direction: column; background: #F6F8FB; color: #0F172A; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; line-height: 1.55; -webkit-font-smoothing: antialiased; }
.fa-shell *, .fa-shell *::before, .fa-shell *::after { box-sizing: border-box; }
.fa-shell a { color: #0B2545; }
.fa-shell h1, .fa-shell h2, .fa-shell h3 { font-family: 'Bricolage Grotesque', 'DM Sans', system-ui, sans-serif; font-weight: 700; letter-spacing: -0.02em; line-height: 1.15; margin: 0; color: #0B2545; }
.fa-shell p { margin: 0 0 12px; }
.fa-shell p:last-child { margin-bottom: 0; }

/* Kop */
.fa-top { background: #0B2545; color: #fff; }
.fa-top-inner { max-width: 760px; margin: 0 auto; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.fa-logo { display: inline-flex; align-items: center; color: #fff; text-decoration: none; font-family: 'Bricolage Grotesque', system-ui, sans-serif; font-weight: 800; font-size: 20px; }
.fa-logo img { height: 32px; width: auto; display: block; }
.fa-lang { display: inline-flex; gap: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 100px; padding: 3px; }
.fa-lang a { color: rgba(255, 255, 255, 0.75); text-decoration: none; font-size: 12px; font-weight: 700; letter-spacing: 0.05em; padding: 5px 10px; border-radius: 100px; min-width: 40px; text-align: center; }
.fa-lang a.is-active { background: #fff; color: #0B2545; }

/* Inhoud */
.fa-main { flex: 1; width: 100%; max-width: 760px; margin: 0 auto; padding: 20px 16px 48px; }
.fa-main--narrow { max-width: 600px; }
.fa-card { background: #fff; border: 1px solid #DCE3EC; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
@media (min-width: 640px) { .fa-card { padding: 24px 28px; } }
.fa-card--tint { background: #EAF2FB; border-color: #B9D3EC; }
.fa-card--warn { background: #FFF1E6; border-color: #F9C9A4; }
.fa-h1 { font-size: 26px; margin-bottom: 8px; }
.fa-h2 { font-size: 19px; margin-bottom: 10px; }
.fa-h3 { font-size: 16px; margin-bottom: 6px; }
.fa-lead { color: #334155; font-size: 16px; margin-bottom: 16px; }
.fa-muted { color: #64748B; }
.fa-small { font-size: 13.5px; }
.fa-eyebrow { display: inline-block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748B; margin-bottom: 6px; }
.fa-ref { font-family: 'JetBrains Mono', ui-monospace, Menlo, monospace; font-size: 13px; color: #334155; background: #EEF2F7; border-radius: 6px; padding: 2px 8px; }
.fa-pre { white-space: pre-wrap; word-break: break-word; }

/* Meldingen */
.fa-flash, .fa-error, .fa-note, .fa-warn { border-radius: 10px; padding: 12px 14px; font-size: 14.5px; line-height: 1.5; margin-bottom: 14px; }
.fa-flash { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
.fa-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
.fa-note { background: #EAF2FB; border: 1px solid #B9D3EC; color: #0B2545; }
.fa-warn { background: #FFF7ED; border: 1px solid #FED7AA; color: #9A3412; }

/* Knoppen: oranje alleen voor de belangrijkste actie. */
.fa-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 12px 22px; border-radius: 12px; border: 1px solid #F4781F; background: #F4781F; color: #fff; font-family: inherit; font-size: 16px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background 0.15s, transform 0.15s; width: 100%; }
.fa-btn:hover { background: #D9640F; border-color: #D9640F; }
.fa-btn:disabled { opacity: 0.6; cursor: default; }
.fa-btn--secondary { background: #fff; color: #0B2545; border-color: #C5CFDC; }
.fa-btn--secondary:hover { background: #EEF2F7; border-color: #C5CFDC; }
.fa-btn--navy { background: #0B2545; border-color: #0B2545; color: #fff; }
.fa-btn--navy:hover { background: #143A66; border-color: #143A66; }
.fa-btn--danger { background: #fff; color: #B91C1C; border-color: #FECACA; }
.fa-btn--danger:hover { background: #FEF2F2; border-color: #FECACA; }
.fa-btn--sm { min-height: 40px; padding: 8px 16px; font-size: 14.5px; border-radius: 10px; }
@media (min-width: 640px) { .fa-btn { width: auto; } .fa-btn--block { width: 100%; } }
.fa-link { background: none; border: 0; padding: 0; color: #0B2545; text-decoration: underline; cursor: pointer; font-family: inherit; font-size: 14.5px; }
.fa-link--muted { color: #64748B; }
.fa-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 16px; }
@media (min-width: 640px) { .fa-actions { flex-direction: row; align-items: center; flex-wrap: wrap; gap: 14px; } }

/* Formulieren */
.fa-field { margin-bottom: 16px; }
.fa-field > label, .fa-label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #0F172A; }
.fa-hint { font-weight: 400; color: #64748B; font-size: 13px; }
.fa-field input[type="text"], .fa-field input[type="email"], .fa-field input[type="tel"], .fa-field input[type="date"], .fa-field input[type="datetime-local"], .fa-field input[type="number"], .fa-field input[type="password"], .fa-field input[type="url"], .fa-field textarea, .fa-field select,
.fa-input { width: 100%; min-height: 48px; border: 1px solid #C5CFDC; border-radius: 10px; padding: 11px 14px; font-size: 16px; font-family: inherit; color: #0F172A; background: #fff; }
.fa-field textarea { min-height: 120px; resize: vertical; line-height: 1.5; }
.fa-field input:focus, .fa-field textarea:focus, .fa-field select:focus, .fa-input:focus { outline: 2px solid #8FC4F2; outline-offset: 1px; border-color: #8FC4F2; }
.fa-field input[aria-invalid="true"], .fa-field textarea[aria-invalid="true"] { border-color: #F87171; }
.fa-err { color: #B91C1C; font-size: 13px; margin-top: 5px; }
.fa-row { display: grid; grid-template-columns: 1fr; gap: 0 14px; }
@media (min-width: 560px) { .fa-row { grid-template-columns: 1fr 1fr; } }
.fa-money { display: flex; align-items: center; border: 1px solid #C5CFDC; border-radius: 10px; background: #fff; }
.fa-money input { border: 0 !important; min-height: 46px !important; flex: 1; }
.fa-money span { padding: 0 14px; color: #64748B; font-weight: 600; }
.fa-check { display: flex; align-items: flex-start; gap: 10px; font-size: 14.5px; line-height: 1.45; cursor: pointer; }
.fa-check input { width: 20px; height: 20px; margin: 2px 0 0; flex: 0 0 20px; accent-color: #0B2545; }
.hp { position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0; }

/* Keuzekaarten (radio's die als knop ogen) */
.fa-options { display: grid; grid-template-columns: 1fr; gap: 8px; }
@media (min-width: 560px) { .fa-options { grid-template-columns: 1fr 1fr; } }
.fa-option { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #C5CFDC; border-radius: 12px; background: #fff; cursor: pointer; min-height: 52px; font-size: 15.5px; }
.fa-option input { accent-color: #0B2545; width: 18px; height: 18px; flex: 0 0 18px; }
.fa-option.is-on { border-color: #0B2545; background: #EAF2FB; box-shadow: inset 0 0 0 1px #0B2545; }
.fa-option .flag { font-size: 20px; line-height: 1; }

/* Tegels (categorieën) */
.fa-tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (min-width: 640px) { .fa-tiles { grid-template-columns: repeat(3, 1fr); } }
.fa-tile { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; padding: 14px; border: 1px solid #C5CFDC; border-radius: 12px; background: #fff; cursor: pointer; text-align: left; font-family: inherit; color: #0F172A; min-height: 96px; transition: border-color 0.15s, background 0.15s; }
.fa-tile:hover { border-color: #0B2545; }
.fa-tile.is-on { border-color: #0B2545; background: #EAF2FB; box-shadow: inset 0 0 0 1px #0B2545; }
.fa-tile .ic { color: #0B2545; }
.fa-tile.is-on .ic { color: #F4781F; }
.fa-tile strong { font-size: 15px; }
.fa-tile small { color: #64748B; font-size: 12.5px; line-height: 1.35; }
.ic { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.ic svg { display: block; }

/* Chips, sterren, sleutel-waarde */
.fa-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.fa-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 100px; background: #EEF2F7; color: #334155; font-size: 12.5px; font-weight: 600; white-space: nowrap; }
.fa-chip--ok { background: #ECFDF5; color: #065F46; }
.fa-chip--warn { background: #FFF7ED; color: #9A3412; }
.fa-chip--navy { background: #0B2545; color: #fff; }
.fa-chip--blue { background: #EAF2FB; color: #0B2545; }
.fa-chip--muted { background: #F1F5F9; color: #64748B; }
.fa-stars { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #334155; }
.fa-stars .st { color: #CBD5E1; letter-spacing: 1px; font-size: 16px; line-height: 1; }
.fa-stars .st.on { color: #F4781F; }
.fa-kv { display: grid; grid-template-columns: 1fr; gap: 6px 16px; font-size: 14.5px; }
@media (min-width: 560px) { .fa-kv { grid-template-columns: 160px 1fr; } }
.fa-kv dt { color: #64748B; margin: 0; }
.fa-kv dd { margin: 0 0 4px; }
@media (min-width: 560px) { .fa-kv dd { margin-bottom: 0; } }
.fa-amount { font-family: 'Bricolage Grotesque', system-ui, sans-serif; font-size: 26px; font-weight: 800; color: #0B2545; letter-spacing: -0.02em; }
.fa-sep { border: 0; border-top: 1px solid #DCE3EC; margin: 16px 0; }

/* Foto's */
.fa-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
@media (min-width: 560px) { .fa-gallery { grid-template-columns: repeat(4, 1fr); } }
.fa-gallery a, .fa-gallery .ph { display: block; position: relative; aspect-ratio: 1; border-radius: 10px; overflow: hidden; background: #EEF2F7; border: 1px solid #DCE3EC; }
.fa-gallery img { width: 100%; height: 100%; object-fit: cover; display: block; }
.fa-gallery .rm { position: absolute; top: 4px; right: 4px; width: 28px; height: 28px; border-radius: 50%; border: 0; background: rgba(11, 37, 69, 0.85); color: #fff; font-size: 16px; line-height: 1; cursor: pointer; }
.fa-file { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border: 1px solid #DCE3EC; border-radius: 10px; background: #fff; text-decoration: none; font-size: 14px; margin-top: 6px; }

/* Lijsten met vinkjes */
.fa-ticks { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; font-size: 14.5px; }
.fa-ticks li { display: flex; gap: 10px; align-items: flex-start; }
.fa-ticks li::before { content: '✓'; flex: 0 0 22px; width: 22px; height: 22px; border-radius: 50%; background: #EAF2FB; color: #0B2545; font-weight: 800; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; margin-top: 1px; }

/* Tijdlijn */
.fa-timeline { list-style: none; margin: 0; padding: 0; border-left: 2px solid #DCE3EC; margin-left: 6px; }
.fa-timeline li { position: relative; padding: 0 0 12px 18px; font-size: 14px; }
.fa-timeline li::before { content: ''; position: absolute; left: -7px; top: 6px; width: 12px; height: 12px; border-radius: 50%; background: #fff; border: 2px solid #8FC4F2; }
.fa-timeline li:last-child { padding-bottom: 0; }
.fa-timeline time { display: block; color: #64748B; font-size: 12.5px; }

/* Voet */
.fa-foot { background: #fff; border-top: 1px solid #DCE3EC; }
.fa-foot-inner { max-width: 760px; margin: 0 auto; padding: 20px 16px 28px; font-size: 13px; color: #64748B; }
.fa-foot-links { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-bottom: 8px; }
.fa-foot-links a { color: #334155; text-decoration: none; font-weight: 600; }
.fa-foot-links a:hover { text-decoration: underline; }
.fa-foot-note { line-height: 1.5; }
</style>
