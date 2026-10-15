<script setup>
/**
 * Linkerpaneel van de inlog- en registratiepagina onder het merk Fachora:
 * het panel van een aangesloten bedrijf, drie kernpunten en de twee wegen
 * naar een account (profiel overnemen of bedrijf aanmelden). Geen
 * proefperiode, geen demo, geen klantenportaal: dat is de factuurkant.
 */
defineProps({
  mode: { type: String, default: 'login' }, // 'login' | 'register'
});
</script>

<template>
  <div class="fh">
    <h1 class="auth-h1">
      <template v-if="mode === 'register'">{{ $t('Word een Fachora Verified bedrijf') }}</template>
      <template v-else>{{ $t('Het bedrijfspanel van Fachora') }}</template>
    </h1>
    <p class="fh-sub">
      <template v-if="mode === 'register'">{{ $t('Het snelst: neem het profiel van je bedrijf over (met de code uit de brief of op NIP) of meld je bedrijf aan. Dan sta je meteen in de verificatie.') }}</template>
      <template v-else>{{ $t('Log in en zie je opdrachten, je profiel, de verificatie en je facturen op één plek.') }}</template>
    </p>

    <ul class="fh-points">
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg></i>
        <div><b>{{ $t('Opdrachten uit je buurt per e-mail') }}</b><span>{{ $t("Met omschrijving, foto's en gewenste datum; jij bepaalt de prijs, het adres krijg je na de keuze van de klant.") }}</span></div>
      </li>
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg></i>
        <div><b>{{ $t('Betaling via Fachora Protect') }}</b><span>{{ $t('De klant reserveert het bedrag vooraf; na goedkeuring wordt het uitbetaald. Geen abonnement, fee alleen over uitgevoerd werk.') }}</span></div>
      </li>
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4M9 12h6M9 16h6"/></svg></i>
        <div><b>{{ $t('Facturen klaar voor KSeF') }}</b><span>{{ $t('Een gratis factuurprogramma hoort bij je profiel: facturen, offertes en klanten.') }}</span></div>
      </li>
    </ul>

    <div class="fh-card" aria-hidden="true">
      <div class="fh-badge"><span class="fh-check">✓</span>Fachora Verified</div>
      <div class="fh-nm">Kowalski Instalacje</div>
      <div class="fh-tags"><span>Hydraulik</span><span>Ogrzewanie</span><span>🇵🇱 🇬🇧</span></div>
      <div class="fh-job"><span>{{ $t('Nieuwe opdracht') }}</span><b>{{ $t('Lekkage onder de gootsteen') }} · 480 zł</b></div>
      <div class="fh-cap">{{ $t('Voorbeeld van een profiel en een aanbieding') }}</div>
    </div>

    <ul class="auth-links">
      <li><a :href="route('fachora.claim')">{{ $t('Profiel overnemen') }}</a></li>
      <li><a :href="route('fachora.apply')">{{ $t('Bedrijf aanmelden') }}</a></li>
      <template v-if="mode === 'register'">
        <li><a :href="route('login')">{{ $t('Al een account? Inloggen') }}</a></li>
      </template>
      <template v-else>
        <li><a :href="route('fachora.for-companies')">{{ $t('Voor bedrijven') }}</a></li>
        <li><a :href="route('pl.kontakt')">{{ $t('Contact') }}</a></li>
      </template>
    </ul>
  </div>
</template>

<style scoped>
.fh { position: relative; z-index: 1; max-width: 460px; }
.fh .auth-h1 { margin-bottom: 12px; }
.fh-sub { font-size: 15.5px; line-height: 1.6; color: rgba(255,255,255,0.9); margin: 0 0 26px; }
.fh-points { list-style: none; margin: 0 0 28px; padding: 0; display: grid; gap: 14px; }
.fh-points li { display: flex; gap: 12px; align-items: flex-start; }
.fh-points i { flex-shrink: 0; width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14); display: flex; align-items: center; justify-content: center; color: #FFA45C; }
.fh-points svg { width: 17px; height: 17px; }
.fh-points b { display: block; font-size: 14.5px; color: #fff; margin-bottom: 2px; }
.fh-points span { font-size: 13px; line-height: 1.5; color: rgba(255,255,255,0.72); }

.fh-card { position: relative; background: #fff; color: #0F172A; border-radius: 16px; padding: 16px 18px 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.28); max-width: 380px; margin-bottom: 24px; overflow: hidden; }
.fh-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px 4px 6px; border-radius: 100px; background: #0B2545; color: #fff; font-size: 11px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; }
.fh-check { width: 16px; height: 16px; border-radius: 50%; background: #F4781F; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
.fh-nm { font-family: var(--font-display); font-weight: 700; font-size: 17px; margin: 10px 0 6px; }
.fh-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
.fh-tags span { font-size: 11.5px; padding: 3px 8px; border-radius: 100px; background: #EAF2FB; color: #0B2545; font-weight: 600; }
.fh-job { display: grid; gap: 2px; padding: 10px 12px; border-radius: 10px; background: #FFF1E6; border: 1px solid #FFD5B0; }
.fh-job span { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #D9640F; }
.fh-job b { font-size: 13px; }
.fh-cap { margin-top: 10px; font-size: 10.5px; color: #94A3B8; }

@media (max-width: 880px) {
  .fh-card, .fh-points { display: none; }
  .fh-sub { margin-bottom: 14px; }
}
</style>
