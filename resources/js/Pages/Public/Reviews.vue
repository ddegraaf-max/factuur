<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const reviews = [
  { name: 'Sanne de Vries', role: "ZZP'er · ontwerpstudio", text: 'Eindelijk facturatie die gewoon werkt. Binnen een middag had ik mijn hele administratie omgezet. De automatische herinneringen schelen me echt uren per maand.' },
  { name: 'Mark Jansen', role: 'Eigenaar · klusbedrijf', text: 'Ik ben geen administratieheld, maar dit snap zelfs ik. Factuur maken, versturen, klaar. En ik zie precies wie er nog moet betalen.' },
  { name: 'Fatima El Amrani', role: 'Consultant', text: 'De prijs is bijna te mooi om waar te zijn. Voor €2,50 per maand heb ik alles wat ik nodig heb. Geen dure boekhoudsoftware meer.' },
  { name: 'Peter Bakker', role: 'Webdeveloper', text: 'Het incassotraject in fases is goud waard. Vroeger durfde ik klanten nooit te pushen, nu gaat het netjes en automatisch.' },
  { name: 'Linda Smit', role: 'Fotograaf', text: 'Strakke facturen met mijn eigen logo, en mijn klanten kunnen meteen betalen. Ziet er super professioneel uit.' },
  { name: 'Tom Hendriks', role: 'Hovenier', text: 'Btw werd altijd een gedoe rond de aangifte. Nu staat alles netjes per regel en klopt het gewoon. Aanrader.' },
];
</script>

<template>
  <Head title="Reviews" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap" style="text-align:center;">
        <span class="m-eyebrow">Reviews</span>
        <h1 class="m-h1">Geliefd door<br>Nederlandse ondernemers</h1>
        <div style="font-size:24px;color:#F59E0B;letter-spacing:3px;margin-top:18px;">★★★★★</div>
        <p class="m-lead" style="margin:10px auto 0;max-width:520px;">Gemiddeld <strong>4,8 / 5</strong> op basis van honderden ondernemers die sneller betaald krijgen.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap">
        <div class="m-grid m-grid-3">
          <div v-for="r in reviews" :key="r.name" class="m-card">
            <div style="color:#F59E0B;letter-spacing:1px;margin-bottom:12px;">★★★★★</div>
            <p style="color:var(--ink-2);">"{{ r.text }}"</p>
            <div style="display:flex;align-items:center;gap:12px;margin-top:18px;">
              <div class="avatar-bubble">{{ r.name.split(' ').map(w => w[0]).join('').slice(0,2) }}</div>
              <div>
                <div style="font-weight:600;font-size:14px;">{{ r.name }}</div>
                <div class="m-muted" style="font-size:13px;">{{ r.role }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="m-section" style="padding-top:0;">
      <div class="m-wrap">
        <div class="m-cta-band">
          <h2 class="m-h2">Word ook fan van factureren</h2>
          <p>Sluit je aan bij ondernemers die hun administratie eindelijk op orde hebben.</p>
          <div class="m-hero-actions">
            <Link :href="route('register')" class="m-btn m-btn-white m-btn-lg">Gratis starten</Link>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.avatar-bubble {
  width: 40px; height: 40px; border-radius: 100px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
  color: #fff; font-weight: 600; font-size: 14px;
  display: inline-flex; align-items: center; justify-content: center;
}
</style>
