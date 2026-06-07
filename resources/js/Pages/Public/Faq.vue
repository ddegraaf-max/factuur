<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const open = ref(0);
const toggle = (i) => { open.value = open.value === i ? -1 : i; };

const sections = [
  {
    title: 'Algemeen',
    items: [
      ['Wat is EasyInvoice?', 'EasyInvoice is een eenvoudige Nederlandse facturatietool voor zzp\'ers en het MKB. Je maakt facturen, verstuurt herinneringen en houdt je betalingen bij — alles op één plek.'],
      ['Voor wie is het bedoeld?', 'Voor ondernemers die snel en professioneel willen factureren zonder ingewikkelde boekhoudsoftware: zzp\'ers, freelancers en kleine bedrijven.'],
      ['Heb ik boekhoudkennis nodig?', 'Nee. EasyInvoice is gemaakt om zonder voorkennis te gebruiken. Btw wordt automatisch berekend en alles is in begrijpelijk Nederlands.'],
    ],
  },
  {
    title: 'Prijs & abonnement',
    items: [
      ['Wat kost EasyInvoice?', '€2,50 per maand (excl. btw), met alle functies inbegrepen. Geen extra of verborgen kosten.'],
      ['Kan ik maandelijks opzeggen?', 'Ja, je kunt op elk moment opzeggen. Er is geen jaarcontract.'],
      ['Is er een gratis proefperiode?', 'Je kunt gratis een account aanmaken en de tool bekijken voordat je betaalt.'],
    ],
  },
  {
    title: 'Facturen & btw',
    items: [
      ['Wordt btw automatisch berekend?', 'Ja, per factuurregel kies je 21%, 9% of 0%. Het totaal en de btw worden automatisch berekend.'],
      ['Kan ik mijn eigen logo gebruiken?', 'Zeker. Upload je logo en kies je accentkleur bij de huisstijlinstellingen.'],
      ['Voldoen de facturen aan de eisen van de Belastingdienst?', 'Ja, inclusief doorlopende nummering per jaar en alle verplichte gegevens.'],
      ['Kan ik creditfacturen maken?', 'Ja, je maakt met één klik een creditnota op een bestaande factuur.'],
    ],
  },
  {
    title: 'Betalingen & incasso',
    items: [
      ['Kan ik automatische herinneringen versturen?', 'Ja, je stelt in wanneer herinneringen automatisch worden verstuurd.'],
      ['Hoe werkt het incassotraject?', 'In fases: van een vriendelijke herinnering naar een aanmaning en uiteindelijk incasso, netjes vastgelegd.'],
    ],
  },
  {
    title: 'Account & veiligheid',
    items: [
      ['Is mijn data veilig?', 'Ja. Je gegevens worden versleuteld opgeslagen binnen de EU en we maken automatisch back-ups.'],
      ['Kan ik tweestapsverificatie inschakelen?', 'Ja, je beveiligt je account met 2FA via een authenticator-app.'],
    ],
  },
];

let idx = 0;
const indexed = sections.map(s => ({ ...s, items: s.items.map(it => ({ q: it[0], a: it[1], i: idx++ })) }));
</script>

<template>
  <Head title="Veelgestelde vragen" />
  <PublicLayout>
    <section class="m-hero" style="padding-bottom:40px;">
      <div class="m-wrap" style="text-align:center;">
        <span class="m-eyebrow">Veelgestelde vragen</span>
        <h1 class="m-h1">Vragen & antwoorden</h1>
        <p class="m-lead" style="margin:16px auto 0;max-width:520px;">De meest gestelde vragen op een rij. Staat je vraag er niet bij? We helpen je graag.</p>
      </div>
    </section>

    <section class="m-section" style="padding-top:24px;">
      <div class="m-wrap" style="max-width:760px;">
        <div v-for="sec in indexed" :key="sec.title" style="margin-bottom:36px;">
          <h2 class="m-h3" style="font-size:15px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-4);margin-bottom:12px;">{{ sec.title }}</h2>
          <div class="faq-list">
            <div v-for="item in sec.items" :key="item.i" class="faq-item" :class="{ open: open === item.i }">
              <button class="faq-q" @click="toggle(item.i)">
                <span>{{ item.q }}</span>
                <span class="faq-chev">{{ open === item.i ? '−' : '+' }}</span>
              </button>
              <div v-show="open === item.i" class="faq-a">{{ item.a }}</div>
            </div>
          </div>
        </div>

        <div class="m-card m-center" style="padding:32px;">
          <h3 class="m-h3" style="margin-bottom:8px;">Nog een vraag?</h3>
          <p class="m-muted" style="margin-bottom:18px;">Ons team beantwoordt je vraag binnen één werkdag.</p>
          <Link :href="route('contact')" class="m-btn m-btn-primary">Neem contact op</Link>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>

<style scoped>
.faq-list { display: grid; gap: 10px; }
.faq-item { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; transition: border-color .15s; }
.faq-item.open { border-color: var(--border-strong); }
.faq-q {
  width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 16px;
  padding: 16px 20px; background: none; border: none; cursor: pointer; text-align: left;
  font-family: inherit; font-size: 16px; font-weight: 600; color: var(--ink);
}
.faq-chev { font-size: 22px; color: var(--brand); line-height: 1; flex-shrink: 0; }
.faq-a { padding: 0 20px 18px; color: var(--ink-3); }
</style>
