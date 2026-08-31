<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { t } from '@/i18n';
import { eur, fmtDate } from '@/format';

// "Facturen verkopen" (Poolse markt): er is geen incassopartner — herinneringen
// en het wezwanie do zapłaty verstuurt de ondernemer zelf; een onbetaalde
// factuur verkoopt hij aan de factuurkoper (sprzedamfakture.pl, wykup wierzytelności).
const props = defineProps({
  partner: Object,     // { name, email, website }
  offered: Array,      // te koop aangeboden facturen
  candidates: Array,   // vervallen, onbetaalde facturen die je kunt aanbieden
  stats: Object,
});

const formatDate = (s) => s ? fmtDate(s, { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
const website = (props.partner?.website || '').replace(/^https?:\/\//, '');

const sell = (invoice) => {
  const msg = t('Factuur :number te koop aanbieden aan :partner? Zij nemen daarna contact met je op over de voorwaarden.', {
    number: invoice.number,
    partner: props.partner?.name || '',
  });
  if (!confirm(msg)) return;
  router.post(route('windykacja.wykup', invoice.id), {}, { preserveScroll: true });
};

// Skup starych wyroków: ook een oud vonnis (tytuł wykonawczy) kan te koop worden aangeboden.
const wyrokOpen = ref(false);
const wyrokForm = useForm({
  sygnatura: '', sad: '', data_wyroku: '', kwota: '', dluznik: '', dluznik_nip: '',
  forma: '', egzekucja: '', egzekucja_rok: '', uwagi: '',
});
const submitWyrok = () => {
  wyrokForm.post(route('wykup.wyrok'), {
    preserveScroll: true,
    onSuccess: () => { wyrokForm.reset(); wyrokOpen.value = false; },
  });
};
</script>

<template>
  <Head :title="$t('Facturen verkopen')" />
  <AppLayout>
    <template #breadcrumb>{{ $t('Verkoop') }} / <span class="breadcrumb-current">{{ $t('Facturen verkopen') }}</span></template>

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('Facturen verkopen') }}</h1>
        <p class="page-subtitle">{{ $t('Verkoop een onbetaalde factuur aan :partner en heb je geld snel op je rekening — zonder incassotraject.', { partner: partner.name }) }}</p>
      </div>
    </div>

    <div class="partner-card">
      <div class="partner-mark">SF</div>
      <div>
        <div class="eyebrow">{{ $t('Factuurkoper') }}</div>
        <div class="name">{{ partner.name }}</div>
        <div class="sub">{{ $t('Koopt onbetaalde B2B-facturen · offerte binnen één werkdag') }}</div>
      </div>
      <div class="contacts">
        <div v-if="partner.email">✉ <a :href="'mailto:' + partner.email">{{ partner.email }}</a></div>
        <div v-if="partner.website">🌐 <a :href="partner.website" target="_blank" rel="noopener">{{ website }}</a></div>
        <div class="hint">{{ $t('Vragen over een aanbod of de voorwaarden? Mail :partner rechtstreeks.', { partner: partner.name }) }}</div>
      </div>
    </div>

    <div class="steps">
      <div class="step"><span class="nr">1</span><div><b>{{ $t('Bied de factuur aan') }}</b><p>{{ $t('Eén klik: het dossier (factuur, vervaldatum, rente en vergoeding) gaat per e-mail naar :partner.', { partner: partner.name }) }}</p></div></div>
      <div class="step"><span class="nr">2</span><div><b>{{ $t('Offerte binnen één werkdag') }}</b><p>{{ $t(':partner doet een koopvoorstel — jij beslist.', { partner: partner.name }) }}</p></div></div>
      <div class="step"><span class="nr">3</span><div><b>{{ $t('Cessie en uitbetaling') }}</b><p>{{ $t('Na akkoord teken je de cessie en staat het geld snel op je rekening; het risico gaat over naar de koper.') }}</p></div></div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="lbl">{{ $t('Aangeboden') }}</div>
        <div class="val">{{ stats.offered_count }} <span class="val-sub">· {{ eur(stats.offered_total) }}</span></div>
      </div>
      <div class="stat-card">
        <div class="lbl">{{ $t('Verkoopbaar nu') }}</div>
        <div class="val">{{ stats.candidates_count }} <span class="val-sub">· {{ eur(stats.candidates_total) }}</span></div>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Verkoopbare facturen') }}</div>
          <div class="card-subtitle">{{ $t('Vervallen, onbetaalde facturen die je met één klik kunt aanbieden.') }}</div>
        </div>
      </div>
      <div v-if="candidates.length === 0" class="empty">{{ $t('Geen vervallen facturen — alles is op tijd betaald.') }}</div>
      <table v-else class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Factuur') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Vervaldatum') }}</th>
            <th>{{ $t('Dagen te laat') }}</th>
            <th class="right">{{ $t('Openstaand') }}</th>
            <th class="right">{{ $t('Acties') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in candidates" :key="c.id">
            <td class="mono cell-primary"><Link :href="route('invoices.show', c.id)">{{ c.number }}</Link></td>
            <td :data-label="$t('Klant')">{{ c.customer_name }}</td>
            <td :data-label="$t('Vervaldatum')">{{ formatDate(c.due_date) }}</td>
            <td :data-label="$t('Dagen te laat')">{{ $t(':n dagen', { n: c.days_overdue }) }}</td>
            <td class="right num" :data-label="$t('Openstaand')">{{ eur(c.remaining) }}</td>
            <td class="right actions" :data-label="$t('Acties')">
              <a :href="route('windykacja.wezwanie', c.id)" target="_blank" class="link-btn">{{ $t('Formele aanmaning (PDF)') }}</a>
              <button type="button" class="btn btn-primary btn-sm" @click="sell(c)">{{ $t('Factuur verkopen') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Aangeboden facturen') }}</div>
          <div class="card-subtitle">{{ $t('in behandeling bij :partner', { partner: partner.name }) }}</div>
        </div>
      </div>
      <div v-if="offered.length === 0" class="empty">{{ $t('Nog geen factuur aangeboden.') }}</div>
      <table v-else class="data-table">
        <thead>
          <tr>
            <th>{{ $t('Factuur') }}</th>
            <th>{{ $t('Klant') }}</th>
            <th>{{ $t('Aangeboden op') }}</th>
            <th>{{ $t('Dagen te laat') }}</th>
            <th class="right">{{ $t('Openstaand') }}</th>
            <th class="right">{{ $t('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in offered" :key="c.id">
            <td class="mono cell-primary"><Link :href="route('invoices.show', c.id)">{{ c.number }}</Link></td>
            <td :data-label="$t('Klant')">{{ c.customer_name }}</td>
            <td :data-label="$t('Aangeboden op')">{{ formatDate(c.sale_requested_at) }}</td>
            <td :data-label="$t('Dagen te laat')">{{ $t(':n dagen', { n: c.days_overdue }) }}</td>
            <td class="right num" :data-label="$t('Openstaand')">{{ eur(c.remaining) }}</td>
            <td class="right" :data-label="$t('Status')"><span class="pill">{{ $t('Wacht op offerte') }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card" style="margin-top:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">{{ $t('Oude vonnissen en betalingsbevelen') }}</div>
          <div class="card-subtitle">{{ $t('Ook een oud vonnis of nakaz zapłaty (executoriale titel) is geld waard — vaak 10–40% van de nominale waarde, na beoordeling per dossier.') }}</div>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" @click="wyrokOpen = !wyrokOpen">{{ wyrokOpen ? $t('Formulier verbergen') : $t('Vonnis aanbieden') }}</button>
      </div>
      <form v-if="wyrokOpen" class="wyrok-form" @submit.prevent="submitWyrok">
        <div class="wyrok-grid">
          <label>
            <span>{{ $t('Zaaknummer (sygnatura akt)') }} *</span>
            <input type="text" v-model="wyrokForm.sygnatura" maxlength="60" required>
            <em v-if="wyrokForm.errors.sygnatura" class="field-error">{{ wyrokForm.errors.sygnatura }}</em>
          </label>
          <label>
            <span>{{ $t('Rechtbank') }}</span>
            <input type="text" v-model="wyrokForm.sad" maxlength="120">
          </label>
          <label>
            <span>{{ $t('Datum vonnis') }}</span>
            <input type="date" v-model="wyrokForm.data_wyroku">
            <em v-if="wyrokForm.errors.data_wyroku" class="field-error">{{ wyrokForm.errors.data_wyroku }}</em>
          </label>
          <label>
            <span>{{ $t('Nominale waarde (zł)') }} *</span>
            <input type="text" v-model="wyrokForm.kwota" maxlength="40" required inputmode="decimal">
            <em v-if="wyrokForm.errors.kwota" class="field-error">{{ wyrokForm.errors.kwota }}</em>
          </label>
          <label>
            <span>{{ $t('Schuldenaar') }} *</span>
            <input type="text" v-model="wyrokForm.dluznik" maxlength="160" required>
            <em v-if="wyrokForm.errors.dluznik" class="field-error">{{ wyrokForm.errors.dluznik }}</em>
          </label>
          <label>
            <span>{{ $t('NIP van de schuldenaar') }}</span>
            <input type="text" v-model="wyrokForm.dluznik_nip" maxlength="20">
          </label>
          <label>
            <span>{{ $t('Rechtsvorm van de schuldenaar') }}</span>
            <select v-model="wyrokForm.forma">
              <option value=""></option>
              <option value="sp_zoo">Sp. z o.o.</option>
              <option value="sa">S.A.</option>
              <option value="jdg">{{ $t('Eenmanszaak (JDG)') }}</option>
              <option value="inna">{{ $t('Anders / onbekend') }}</option>
            </select>
          </label>
          <label>
            <span>{{ $t('Eerdere executie') }}</span>
            <select v-model="wyrokForm.egzekucja">
              <option value=""></option>
              <option value="none">{{ $t('Nog nooit uitgevoerd') }}</option>
              <option value="bezskutecznosc">{{ $t('Gestaakt — bezskuteczność (geen verhaal)') }}</option>
              <option value="inna">{{ $t('Gestaakt — andere reden') }}</option>
              <option value="nie_wiem">{{ $t('Weet ik niet') }}</option>
            </select>
          </label>
          <label>
            <span>{{ $t('Jaar laatste executie') }}</span>
            <input type="number" v-model="wyrokForm.egzekucja_rok" min="1990" max="2100">
            <em v-if="wyrokForm.errors.egzekucja_rok" class="field-error">{{ wyrokForm.errors.egzekucja_rok }}</em>
          </label>
          <label class="wyrok-wide">
            <span>{{ $t('Toelichting') }}</span>
            <textarea v-model="wyrokForm.uwagi" rows="3" maxlength="2000"></textarea>
          </label>
        </div>
        <p class="wyrok-hint">{{ $t('Waarom de vorige executie is gestaakt, bepaalt de waarde: na bezskuteczność begint de verjaring van zes jaar opnieuw; na staking wegens stilzitten van de schuldeiser vervalt de stuiting.') }}</p>
        <button type="submit" class="btn btn-primary" :disabled="wyrokForm.processing">{{ wyrokForm.processing ? $t('Bezig…') : $t('Vonnis te koop aanbieden') }}</button>
      </form>
    </div>
  </AppLayout>
</template>

<style scoped>
.partner-card { background: linear-gradient(135deg, #1C4E7A 0%, #132F49 100%); color: white; border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; display: grid; grid-template-columns: auto 1fr auto; gap: 20px; align-items: center; }
.partner-mark { width: 56px; height: 56px; background: #E0A55C; color: #132F49; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; font-size: 20px; letter-spacing: 0.02em; }
.eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #E0A55C; font-weight: 700; margin-bottom: 4px; }
.name { font-family: var(--font-display); font-weight: 700; font-size: 22px; }
.sub { font-size: 13px; color: rgba(255,255,255,0.78); }
.contacts { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: rgba(255,255,255,0.85); max-width: 320px; }
.contacts a { color: white; font-weight: 600; }
.contacts .hint { font-size: 12px; opacity: .7; margin-top: 4px; }
.steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
.step { display: flex; gap: 12px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; }
.step .nr { flex: none; width: 28px; height: 28px; border-radius: 50%; background: var(--brand); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
.step b { display: block; font-size: 14px; margin-bottom: 2px; }
.step p { margin: 0; font-size: 12.5px; color: var(--text-2); line-height: 1.5; }
.stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px; }
.stat-card .lbl { font-size: 12px; color: var(--text-3); margin-bottom: 6px; }
.stat-card .val { font-family: var(--font-display); font-weight: 600; font-size: 22px; }
.stat-card .val-sub { font-size: 14px; color: var(--text-2); font-weight: 500; }
.empty { padding: 40px 20px; text-align: center; color: var(--text-3); }
.pill { color: #92400E; background: #FEF3C7; border: 1px solid #FCD34D; padding: 3px 9px; border-radius: 100px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.actions { white-space: nowrap; }
.actions .link-btn { margin-right: 12px; }
.link-btn { background: none; border: none; padding: 0; font: inherit; font-size: 12px; color: var(--brand); text-decoration: underline; cursor: pointer; }
.wyrok-form { padding: 4px 20px 20px; }
.wyrok-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 16px; }
.wyrok-grid label { display: flex; flex-direction: column; gap: 5px; font-size: 12.5px; font-weight: 600; color: var(--text-2); }
.wyrok-grid input, .wyrok-grid select, .wyrok-grid textarea { height: 38px; padding: 0 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--surface); font: inherit; font-weight: 400; color: var(--text); }
.wyrok-grid textarea { height: auto; padding: 8px 10px; resize: vertical; }
.wyrok-grid input:focus, .wyrok-grid select:focus, .wyrok-grid textarea:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-tint); }
.wyrok-wide { grid-column: 1 / -1; }
.wyrok-hint { font-size: 12px; color: var(--text-3); line-height: 1.55; margin: 12px 0 14px; }
.field-error { font-style: normal; font-weight: 400; font-size: 12px; color: #B91C1C; }

@media (max-width: 760px) {
  .partner-card { grid-template-columns: minmax(0, 1fr); gap: 14px; padding: 20px; }
  .contacts { overflow-wrap: anywhere; max-width: none; }
  .steps, .stat-grid { grid-template-columns: minmax(0, 1fr); gap: 10px; }
  .actions { white-space: normal; }
}
</style>
