<script setup>
// Linkerpaneel van de inlog- en registratiepagina onder het merk Lopra:
// korte belofte, drie kernpunten en een voorbeeld-visitekaartje in de
// huisstijl van een (fictieve) starter. EasyInvoice houdt zijn eigen tekst.
import { usePage } from '@inertiajs/vue3';

defineProps({
  mode: { type: String, default: 'login' }, // 'login' | 'register'
});

// Fictieve starter op het voorbeeldkaartje, passend bij de markt (nl/pl).
const sample = usePage().props.market?.key === 'pl'
  ? { name: 'Anna Kowalska', role: 'Projektantka wnętrz · Studio Wnętrz Kowalska', phone: '+48 600 123 456', email: 'anna@studiokowalska.pl' }
  : { name: 'Sanne de Wit', role: 'Interieurstylist · De Wit Interieur', phone: '06 - 12 34 56 78', email: 'sanne@dewitinterieur.nl' };
</script>

<template>
  <div class="lh">
    <h1 class="auth-h1">
      <template v-if="mode === 'register'">{{ $t('Begin vandaag.') }}<br>{{ $t('Professioneel vanaf de eerste factuur.') }}</template>
      <template v-else>{{ $t('Welkom terug.') }}</template>
    </h1>
    <p class="lh-sub">
      <template v-if="mode === 'register'">{{ $t('14 dagen gratis, geen creditcard. Account, huisstijl, visitekaartje, website en je eerste factuur — allemaal vandaag nog.') }}</template>
      <template v-else>{{ $t('Log in en ga verder waar je gebleven was: je facturen, offertes, huisstijl en website staan klaar.') }}</template>
    </p>

    <ul class="lh-points">
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/></svg></i>
        <div><b>{{ $t('Factureren zoals het hoort') }}</b><span>{{ $t('Offertes met digitale handtekening, iDEAL-link op elke factuur, btw-overzicht klaar voor de aangifte.') }}</span></div>
      </li>
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></i>
        <div><b>{{ $t('Huisstijl in een kwartier') }}</b><span>{{ $t('Drie AI-voorstellen met kleuren, lettertype, slogan en logo — of upload je eigen logo.') }}</span></div>
      </li>
      <li>
        <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></i>
        <div><b>{{ $t('Visitekaartje en website in één klik online') }}</b><span>{{ $t('Met QR-code en contactformulier; aanvragen komen als leads in je administratie.') }}</span></div>
      </li>
    </ul>

    <div class="lh-card" aria-hidden="true">
      <div class="lh-card-top">
        <div class="lh-av">{{ sample.name.charAt(0) }}</div>
        <div>
          <div class="lh-nm">{{ sample.name }}</div>
          <div class="lh-rl">{{ sample.role }}</div>
        </div>
        <div class="lh-qr"><i></i><i></i><i class="o"></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i><i class="o"></i><i></i><i></i></div>
      </div>
      <div class="lh-lines">
        <span>{{ sample.phone }}</span>
        <span>{{ sample.email }}</span>
        <span class="site">{{ $t('Bekijk mijn website →') }}</span>
      </div>
      <div class="lh-cap">{{ $t('Voorbeeld: visitekaartje in de eigen huisstijl van een starter') }}</div>
    </div>

    <ul class="auth-links">
      <template v-if="mode === 'register'">
        <li><a :href="route('demo')">{{ $t('Eerst de demo bekijken') }}</a></li>
        <li><a :href="route('faq')">{{ $t('Veelgestelde vragen') }}</a></li>
        <li><a :href="route('login')">{{ $t('Al een account? Inloggen') }}</a></li>
      </template>
      <template v-else>
        <li><a :href="route('register')">{{ $t('Gratis account aanmaken') }}</a></li>
        <li><a :href="route('demo')">{{ $t('Demo bekijken') }}</a></li>
        <li><a :href="route('helpcentrum')">{{ $t('Helpcentrum') }}</a></li>
        <li><a :href="route('portal.login')">{{ $t('Klantenportaal') }}</a></li>
      </template>
    </ul>
  </div>
</template>

<style scoped>
.lh { position: relative; z-index: 1; max-width: 460px; }
.lh .auth-h1 { margin-bottom: 12px; }
.lh-sub { font-size: 15.5px; line-height: 1.6; color: rgba(255,255,255,0.9); margin: 0 0 26px; }
.lh-points { list-style: none; margin: 0 0 28px; padding: 0; display: grid; gap: 14px; }
.lh-points li { display: flex; gap: 12px; align-items: flex-start; }
.lh-points i { flex-shrink: 0; width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14); display: flex; align-items: center; justify-content: center; color: #E0A55C; }
.lh-points svg { width: 17px; height: 17px; }
.lh-points b { display: block; font-size: 14.5px; color: #fff; margin-bottom: 2px; }
.lh-points span { font-size: 13px; line-height: 1.5; color: rgba(255,255,255,0.72); }

.lh-card { position: relative; background: #2E4A3F; border-radius: 16px; padding: 18px 18px 14px; color: #fff; box-shadow: 0 20px 50px rgba(0,0,0,0.28); max-width: 380px; margin-bottom: 24px; overflow: hidden; }
.lh-card::before { content: ''; position: absolute; width: 220px; height: 220px; border-radius: 50%; right: -90px; top: -110px; background: radial-gradient(circle, rgba(217,160,102,0.35) 0%, rgba(217,160,102,0) 65%); }
.lh-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; position: relative; }
.lh-av { width: 40px; height: 40px; border-radius: 11px; background: #D9A066; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 18px; }
.lh-nm { font-family: var(--font-display); font-weight: 600; font-size: 16px; }
.lh-rl { font-size: 11.5px; opacity: 0.7; }
.lh-qr { margin-left: auto; width: 40px; height: 40px; background: #fff; border-radius: 7px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; padding: 5px; }
.lh-qr i { background: #2E4A3F; border-radius: 1px; }
.lh-qr i.o { background: transparent; }
.lh-lines { display: grid; gap: 6px; position: relative; }
.lh-lines span { font-size: 12px; padding: 8px 12px; border-radius: 8px; background: rgba(255,255,255,0.09); }
.lh-lines .site { background: #D9A066; font-weight: 600; color: #fff; }
.lh-cap { margin-top: 12px; font-size: 10.5px; color: rgba(255,255,255,0.5); }

@media (max-width: 880px) {
  .lh-card, .lh-points { display: none; }
  .lh-sub { margin-bottom: 14px; }
}
</style>
